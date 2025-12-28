<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shooter;
use App\Models\ShooterTargetMapping;
use App\Models\Target;
use App\Services\Gmail\GmailClient;
use Illuminate\Support\Facades\Log;
use Exception;

class SendMappedEmails extends Command
{
    protected $signature = 'emails:send {--date=}';
    protected $description = 'Send mapped emails for shooters based on daily quota';

    public function handle(): int
    {
        $date = $this->option('date')
            ? now()->parse($this->option('date'))->toDateString()
            : now()->toDateString();

        Log::info('Email sending started', ['date' => $date]);
        
        $shooters = Shooter::whereNotNull('gmail_connected_at')->get();
        Log::info('Email sending started', ['shooters' => $shooters]);
        
        if($shooters->isEmpty()){
            Log::info('There is no active gmail connected shooter is available.');
        }else{
            foreach ($shooters as $shooter) {
                $this->processShooter($shooter, $date);
            }
        }

        Log::info('Email sending completed', ['date' => $date]);

        return Command::SUCCESS;
    }

    protected function processShooter(Shooter $shooter, string $date): void
    {
        Log::info('SendMappedEmails::processShooter() start');
        
        $dailyQuota = $shooter->daily_quota;
        Log::info('dailyQuota = '.$dailyQuota);
        
        $alreadySent = ShooterTargetMapping::where('shooter_id', $shooter->id)
        ->whereDate('sent_at', $date)
        ->count();
        Log::info('alreadySent = '.$alreadySent);
        
        $remainingQuota = max(0, $dailyQuota - $alreadySent);
        Log::info('remainingQuota = '.$remainingQuota);
        
        if ($remainingQuota <= 0) {
            Log::info('Shooter quota exhausted', [
                'shooter_id' => $shooter->id,
            ]);
            return;
        }
        
        $mappings = ShooterTargetMapping::where('shooter_id', $shooter->id)
        ->where('assigned_date', $date)
        ->where('status', 'assigned')
        ->limit($remainingQuota)
        ->get();

        Log::info('mappings = '.json_encode($mappings));

        if ($mappings->isEmpty()) {
            return;
        }

        Log::info('Processing shooter mappings', [
            'shooter_id' => $shooter->id,
            'count' => $mappings->count(),
        ]);

        $gmail = (new GmailClient())->forShooter($shooter);

        foreach ($mappings as $mapping) {
            $this->sendSingle($gmail, $mapping);
        }

        Log::info('SendMappedEmails::processShooter() end');

    }

    protected function sendSingle(GmailClient $gmail, ShooterTargetMapping $mapping): void
    {
        
        try {
            $mapping->update([
                'status' => 'assigned', // stays assigned until success
                'attempted_at' => now(),
            ]);

            $target = Target::findOrFail($mapping->target_id);

            $gmail->send(
                $target->email,
                'Hello from Bulk Mailer',
                '<p>This is a test email.</p>'
            );

            $mapping->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            $target->update([
                'status' => 'sent',
            ]);

        } catch (Exception $e) {
            Log::error('Email send failed', [
                'mapping_id' => $mapping->id,
                'error' => $e->getMessage(),
            ]);

            $mapping->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Target::where('id', $mapping->target_id)->update([
                'status' => 'failed',
            ]);
        }
    }

}