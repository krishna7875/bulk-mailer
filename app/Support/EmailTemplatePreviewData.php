<?php

namespace App\Support;

class EmailTemplatePreviewData
{
    public static function data(): array
    {
        return [
            '{{target_email}}' => 'john@example.com',
            '{{target_name}}' => 'John Doe',
            '{{target_status}}' => 'unsent',

            '{{shooter_email}}' => 'sender@gmail.com',
            '{{shooter_name}}' => 'Sender Name',
            '{{shooter_daily_quota}}' => '200',

            '{{assigned_date}}' => now()->toDateString(),
            '{{sent_date}}' => '',
            '{{mapping_status}}' => 'assigned',

            '{{today}}' => now()->toDateString(),
            '{{now}}' => now()->toDateTimeString(),
            '{{app_name}}' => config('app.name'),
        ];
    }
}
