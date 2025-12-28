<?php

namespace App\Services\Stats;

use App\Models\Shooter;
use App\Models\Target;
use App\Models\ShooterTargetMapping;
use Carbon\Carbon;

class EmailStatsService
{
    protected string $date;

    public function __construct(string $date)
    {
        $this->date = Carbon::parse($date)->toDateString();
    }

    /**
     * Shooter metrics
     */
    public function shooterStats(): array
    {
        return [
            'total_shooters' => Shooter::count(),
            'gmail_connected_shooters' => Shooter::whereNotNull('gmail_connected_at')->count(),
        ];
    }

    /**
     * Target metrics
     */
    public function targetStats(): array
    {
        return [
            'total_targets' => Target::count(),
            'available_targets' => $this->availableTargetsCount(),
        ];
    }

    /**
     * Mapping metrics for selected date
     */
    public function mappingStats(): array
    {
        return [
            'assigned' => ShooterTargetMapping::where('assigned_date', $this->date)->count(),

            'sent' => ShooterTargetMapping::where('assigned_date', $this->date)
                ->where('status', 'sent')
                ->count(),

            'failed' => ShooterTargetMapping::where('assigned_date', $this->date)
                ->where('status', 'failed')
                ->count(),
        ];
    }

    /**
     * Available targets for a specific date
     */
    protected function availableTargetsCount(): int
    {
        return Target::where('status', 'unsent')
            ->whereDoesntHave('mappings', function ($q) {
                $q->where('assigned_date', $this->date);
            })
            ->count();
    }

    /**
     * All stats combined (for dashboard)
     */
    public function all(): array
    {
        return array_merge(
            $this->shooterStats(),
            $this->targetStats(),
            $this->mappingStats()
        );
    }
}
