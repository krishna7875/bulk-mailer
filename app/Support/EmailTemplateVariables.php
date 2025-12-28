<?php

namespace App\Support;

class EmailTemplateVariables
{
    public static function allowed(): array
    {
        return [
            // Target
            'target_email',
            'target_name',
            'target_status',

            // Shooter
            'shooter_email',
            'shooter_name',
            'shooter_daily_quota',

            // Mapping
            'assigned_date',
            'sent_date',
            'mapping_status',

            // System
            'today',
            'now',
            'app_name',
        ];
    }

    /**
     * Extract variables used in text
     */
    public static function extract(string $text): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Find invalid variables
     */
    public static function invalid(array $used): array
    {
        return array_diff($used, self::allowed());
    }
}
