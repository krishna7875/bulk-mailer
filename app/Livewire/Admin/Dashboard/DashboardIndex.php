<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;
use App\Services\Stats\EmailStatsService;

class DashboardIndex extends Component
{
    public string $date;

    public function mount()
    {
        $this->date = now()->toDateString();
    }

    public function render()
    {
        $stats = (new EmailStatsService($this->date))->all();

        return view('livewire.admin.dashboard.dashboard-index', [
            'stats' => $stats,
        ]);
    }
}

