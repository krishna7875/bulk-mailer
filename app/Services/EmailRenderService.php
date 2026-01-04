<?php

namespace App\Services;

use App\Models\ShooterTargetMapping;
use App\Support\EmailTemplateVariables;
use Illuminate\Support\Facades\Storage;
use Log;


class EmailRenderService
{
    public function render(ShooterTargetMapping $mapping): array
    {
        $template = $mapping->emailTemplate;

        if (!$template) {
            throw new \Exception('No email template assigned.');
        }

        $data = $this->buildData($mapping);

        return [
            'to'      => $mapping->target->email,
            'subject' => $this->resolve($template->subject, $data),
            'body'    => $this->resolve($template->body, $data),
            'attachment' => $this->attachment($template),
        ];
    }

    protected function buildData(ShooterTargetMapping $mapping): array
    {
        return [
            'target_email'        => $mapping->target->email,
            'target_name'         => $mapping->target->name ?? '',
            'target_status'       => $mapping->target->status,

            'shooter_email'       => $mapping->shooter->email,
            'shooter_name'        => $mapping->shooter->name,
            'shooter_daily_quota' => $mapping->shooter->daily_quota,

            'assigned_date'       => $mapping->assigned_date,
            'sent_date'           => now()->toDateString(),
            'mapping_status'      => $mapping->status,

            'today'               => now()->toDateString(),
            'now'                 => now()->toDateTimeString(),
            'app_name'            => config('app.name'),
        ];
    }

    protected function resolve(string $content, array $data): string
    {
        return preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            fn ($m) => $data[$m[1]] ?? '',
            $content
        );
    }

    protected function attachment($template)
    {
        Log::info('attachment path : '.$template->attachment_path);

        if (!$template->attachment_path) {
            return null;
        }

        $disk = Storage::disk('private');

        if (!$disk->exists($template->attachment_path)) {

            Log::warning('Email attachment missing, sending without attachment', [
                'template_id' => $template->id,
                'path'        => $template->attachment_path,
            ]);

            return null;
        }

        return [
            'path' => $disk->path($template->attachment_path),
            'name' => $template->attachment_name,
            'mime' => $template->attachment_mime,
        ];
    }

}
