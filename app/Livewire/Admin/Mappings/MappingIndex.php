<?php

namespace App\Livewire\Admin\Mappings;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Shooter;
use App\Models\Target;
use Carbon\Carbon;

class MappingIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ===== Mapping generation inputs =====
    public array $selectedShooters = [];
    public string $assignedDate;

    // ===== Filters for list =====
    public $filterShooter = '';
    public $filterStatus = '';
    public $filterDate = '';
    

    protected $listeners = ['deleteMapping'];

    public function updating($name, $value)
    {
        if (str_starts_with($name, 'filter')) {
            $this->resetPage();
        }
    }
    public function updated($property)
    {
        if ($property === 'assignedDate') {
            $this->dispatch('$refresh');
        }
    }
    public function updatedAssignedDate()
    {
        $this->reset('selectedShooters');
        $this->resetPage();
    }

    public function mount()
    {
        $this->assignedDate = now()->toDateString();
        $this->filterDate  = now()->toDateString();
    }

    /**
     * Generate mappings respecting daily quota
     */
    public function generate()
    {
        $this->validate([
            'selectedShooters' => 'required|array|min:1',
            'assignedDate'     => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $date = Carbon::parse($this->assignedDate)->toDateString();
            $totalAssigned = 0;

            foreach ($this->selectedShooters as $shooterId) {

                $shooter = Shooter::where('id', $shooterId)
                    ->where('status', 'active')
                    ->first();

                if (!$shooter) {
                    continue;
                }

                // Already assigned today
                $alreadyAssigned = DB::table('shooter_target_mappings')
                    ->where('shooter_id', $shooter->id)
                    ->where('assigned_date', $date)
                    ->count();

                $remainingQuota = max(0, $shooter->daily_quota - $alreadyAssigned);

                if ($remainingQuota === 0) {
                    continue;
                }

                // Eligible targets
                $targets = Target::query()
                    ->where('status', 'unsent')
                    ->whereNotIn('id', function ($q) use ($date) {
                        $q->select('target_id')
                          ->from('shooter_target_mappings')
                          ->where('assigned_date', $date);
                    })
                    ->limit($remainingQuota)
                    ->pluck('id');

                if ($targets->isEmpty()) {
                    break;
                }

                $now = now();

                $rows = $targets->map(fn ($targetId) => [
                    'shooter_id'    => $shooter->id,
                    'target_id'     => $targetId,
                    'assigned_date' => $date,
                    'status'        => 'assigned',
                    'assigned_at'   => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ])->toArray();

                DB::table('shooter_target_mappings')->insert($rows);

                $totalAssigned += count($rows);
            }

            DB::commit();

            if ($totalAssigned === 0) {
                $this->dispatch('swal', [
                    'type'  => 'warning',
                    'title' => 'No Targets Available',
                    'html'  => 'There are no eligible targets left for mapping.',
                ]);
                return;
            }

            $this->dispatch('swal', [
                'type'  => 'success',
                'title' => 'Mapping Generated',
                'html'  => "
                    <table style='width:100%'>
                        <tr><td><b>Date</b></td><td>{$date}</td></tr>
                        <tr><td><b>Total Assigned</b></td><td>{$totalAssigned}</td></tr>
                    </table>
                "
            ]);

            $this->reset('selectedShooters');
            $this->resetPage();

        } catch (\Throwable $e) {
            DB::rollBack();

            $this->dispatch('swal', [
                'type'  => 'error',
                'title' => 'Mapping Failed',
                'html'  => e($e->getMessage()),
            ]);
        }
    }

    /**
     * Delete mapping
     */
   
    public function deleteMapping($payload)
    {
        $id = $payload['id'];

        DB::table('shooter_target_mappings')->where('id', $id)->delete();

        $this->dispatch('notify', type: 'success', message: 'Mapping deleted');
        $this->resetPage();
    }

    public function render()
    {

        $availableTargetsCount = Target::where('status', 'unsent')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                ->from('shooter_target_mappings as m')
                ->whereColumn('m.target_id', 'targets.id')
                ->whereDate('m.assigned_date', $this->assignedDate);
            })
            ->count();



        $mappings = DB::table('shooter_target_mappings as m')
            ->join('shooters as s', 's.id', '=', 'm.shooter_id')
            ->join('targets as t', 't.id', '=', 'm.target_id')

            ->when($this->filterShooter !== '', fn ($q) =>
                $q->where('m.shooter_id', $this->filterShooter)
            )

            ->when($this->filterStatus !== '', fn ($q) =>
                $q->where('m.status', $this->filterStatus)
            )

            ->when($this->filterDate !== '', function ($q) {
                $q->whereDate('m.assigned_date', $this->filterDate);
            })

            ->select(
                'm.id',
                's.email as shooter_email',
                't.email as target_email',
                'm.status',
                'm.assigned_date'
            )
            ->orderByDesc('m.id')
            ->paginate(10);

            $shooters = Shooter::where('status', 'active')
                ->get()
                ->map(function ($shooter) {

                    $mappedCount = DB::table('shooter_target_mappings')
                        ->where('shooter_id', $shooter->id)
                        ->whereDate('assigned_date', $this->assignedDate)
                        ->count();

                    $shooter->mapped_today = $mappedCount;
                    $shooter->remaining_quota = max(
                        0,
                        $shooter->daily_quota - $mappedCount
                    );

                    return $shooter;
                });



    return view('livewire.admin.mappings.mapping-index', [
        'shooters'               => $shooters,
        'mappings'               => $mappings,
        'availableTargetsCount'  => $availableTargetsCount,
    ]);

    }
}
