<?php
namespace App\Livewire\Admin\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shooter;
use App\Models\ShooterTargetMapping;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;


class MappingReportIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // 👇 MUST be public & initialized
    public string $date = '';
    public string $shooterId = '';

    public function mount()
    {
        $this->date = now()->toDateString();
        $this->shooterId = '';
    }

    // 👇 REQUIRED so pagination resets
    public function updatingDate()
    {
        $this->resetPage();
    }

    public function updatingShooterId()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ShooterTargetMapping::query()
            ->select(
                'shooter_id',
                DB::raw('COUNT(*) as total_assigned'),
                DB::raw("SUM(status = 'sent') as total_sent"),
                DB::raw("SUM(status = 'failed') as total_failed")
            )
            ->where('assigned_date', $this->date);

        // 👇 IMPORTANT: handle empty string properly
        if (!empty($this->shooterId)) {
            $query->where('shooter_id', $this->shooterId);
        }

        $rows = $query
            ->groupBy('shooter_id')
            ->paginate(10);

        return view('livewire.admin.reports.mapping-report-index', [
            'rows' => $rows,
            'shooters' => Shooter::orderBy('email')->get(),
        ]);
    }

    public function exportCsv(): StreamedResponse
    {
        $date = $this->date;
        $shooterId = $this->shooterId;

        return response()->streamDownload(function () use ($date, $shooterId) {

            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Shooter Email',
                'Assigned Date',
                'Total Assigned',
                'Total Sent',
                'Total Failed',
            ]);

            $query = \App\Models\ShooterTargetMapping::query()
                ->select(
                    'shooters.email as shooter_email',
                    'shooter_target_mappings.assigned_date',
                    \DB::raw('COUNT(*) as total_assigned'),
                    \DB::raw("SUM(shooter_target_mappings.status = 'sent') as total_sent"),
                    \DB::raw("SUM(shooter_target_mappings.status = 'failed') as total_failed")
                )
                ->join(
                    'shooters',
                    'shooters.id',
                    '=',
                    'shooter_target_mappings.shooter_id'
                )
                ->where('shooter_target_mappings.assigned_date', $date)
                ->groupBy(
                    'shooter_target_mappings.shooter_id',
                    'shooter_target_mappings.assigned_date',
                    'shooters.email'
                );

                if (!empty($shooterId)) {
                    $query->where('shooter_target_mappings.shooter_id', $shooterId);
                }

            $query->orderBy('shooters.email')
                ->chunk(500, function ($rows) use ($handle) {
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row->shooter_email,
                            $row->assigned_date,
                            $row->total_assigned,
                            $row->total_sent,
                            $row->total_failed,
                        ]);
                    }
                });

            fclose($handle);

        }, "mapping-report-{$date}.csv");
    }

}
