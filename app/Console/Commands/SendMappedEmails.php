<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ShooterTargetMapping;
use App\Services\EmailRenderService;
use App\Services\GmailSendService;
use Carbon\Carbon;

class SendMappedEmails extends Command
{
    protected $signature = 'emails:send-mapped';
    protected $description = 'Send assigned shooter-target mapped emails';

    public function handle(
        EmailRenderService $renderer,
        GmailSendService $gmail
    ) {
        Log::info('SendMappedEmails started');

        $today = now()->toDateString();

        /**
         * Step 1: Fetch eligible mappings
         */
        $mappings = ShooterTargetMapping::query()
            ->with(['shooter', 'target', 'emailTemplate'])
            ->where('status', 'assigned')
            ->whereDate('assigned_date', $today)
            ->whereNotNull('email_template_id')
            ->orderBy('id')
            ->limit(50) // HARD SAFETY LIMIT
            ->get();

        if ($mappings->isEmpty()) {
            Log::info('No eligible mappings found');
            return Command::SUCCESS;
        }

        foreach ($mappings as $mapping) {
            DB::beginTransaction();

            try {
                $shooter = $mapping->shooter;

                /**
                 * Step 2: Gmail connection check
                 */
                if (
                    !$shooter->gmail_connected_at ||
                    !$shooter->gmail_refresh_token
                ) {
                    $this->failMapping(
                        $mapping,
                        'Gmail not connected | token expired or revoked. Reconnect required.'
                    );
                    DB::commit();
                    continue;
                }

                /**
                 * Step 3: Daily quota enforcement
                 */
                $sentToday = ShooterTargetMapping::query()
                    ->where('shooter_id', $shooter->id)
                    ->whereDate('sent_at', $today)
                    ->count();

                if ($sentToday >= $shooter->daily_quota) {
                    Log::info('Shooter quota reached', [
                        'shooter_id' => $shooter->id
                    ]);
                    DB::commit();
                    continue;
                }

                /**
                 * Step 4: Render email
                 */
                $payload = $renderer->render($mapping);

                /**
                 * Step 5: Send email
                 */
                $gmail->send(
                    $shooter,
                    $payload['to'],
                    $payload['subject'],
                    $payload['body'],
                    $payload['attachment']
                );

                /**
                 * Step 6: Update statuses
                 */
                $mapping->update([
                    'status'       => 'sent',
                    'sent_at'      => now(),
                    'attempted_at' => now(),
                ]);

                $mapping->target->update([
                    'status' => 'sent',
                ]);

                DB::commit();

            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('Email send failed', [
                    'mapping_id' => $mapping->id,
                    'error'      => $e->getMessage(),
                ]);

                $this->failMapping(
                    $mapping,
                    $e->getMessage()
                );

                // 🔴 HARD STOP if token related
                if (
                    str_contains($e->getMessage(), 'refresh') ||
                    str_contains($e->getMessage(), 'token')
                ) {
                    Log::error('Stopping send process due to Gmail auth failure', [
                        'shooter_id' => $mapping->shooter_id,
                        'error'      => $e->getMessage(),
                    ]);

                    return; // EXIT command safely
                }

                continue;
            }
        }

        Log::info('SendMappedEmails finished');

        return Command::SUCCESS;
    }

    protected function failMapping(
        ShooterTargetMapping $mapping,
        string $reason
    ): void {
        $mapping->update([
            'status'        => 'failed',
            'error_message' => $reason,
            'attempted_at'  => now(),
        ]);
    }
}
