<?php

namespace App\Livewire\Admin\Mappings;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Shooter;
use App\Models\ShooterTargetMapping;
use App\Models\Target;
use Carbon\Carbon;
use App\Models\EmailTemplate;

use Log;

class MappingIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ===== Mapping generation inputs =====
    public array $selectedShooters = [];
    public string $assignedDate;

    // ===== Filters for list =====
    public ?int $filterShooter = null;
    public ?string $filterStatus = null;
    public ?string $filterDate = null;

    public $emailTemplateId;
    public $templates = [];

    protected $listeners = ['deleteMapping'];

    protected function rules()
    {
        return [
            'shooterId'        => 'required|exists:shooters,id',
            'emailTemplateId'  => 'required|exists:email_templates,id',
            'assignedDate'     => 'required|date',
        ];
    }

    public function updating($property)
    {
        Log::info('MappingIndex::updating()');
        if (str_starts_with($property, 'filter')) {
            $this->resetPage();
        }
    }
    public function updated($property)
    {
        Log::info('MappingIndex::updated()');
        if ($property === 'assignedDate') {
            $this->dispatch('$refresh');
        }
    }
    public function updatedAssignedDate()
    {
        Log::info('MappingIndex::updatedAssignedDate()');
        $this->reset('selectedShooters');
        $this->resetPage();
    }
    
    public function mount()
    {
        Log::info('MappingIndex::mount()');
        $this->assignedDate = now()->toDateString();
        $this->filterDate  = now()->toDateString();

        $this->templates = EmailTemplate::query()
        ->where('status', 'active')
        ->orderBy('name')
        ->get(['id', 'name']);

    }
    
    /**
     * Generate mappings respecting daily quota
    */
    public function generate()
    {
        Log::info('MappingIndex::generate()');

        $this->validate([
            'selectedShooters'  => 'required|array|min:1',
            'assignedDate'      => 'required|date',
            'emailTemplateId'   => 'required|exists:email_templates,id',
        ]);

        DB::beginTransaction();

        try {
            if (!$this->emailTemplateId) {
                $this->dispatch('swal', [
                    'type'  => 'warning',
                    'title' => 'Template Required',
                    'html'  => 'Please select an email template before generating mappings.',
                ]);
                return;
            }

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
                    'email_template_id' => $this->emailTemplateId,
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
   
    // public function deleteMapping($payload)
    // {
    //     $id = $payload['id'];

    //     DB::table('shooter_target_mappings')->where('id', $id)->delete();

    //     $this->dispatch('notify', type: 'success', message: 'Mapping deleted');
    //     $this->resetPage();
    // }

    public function deleteMapping($id)
    {
        Log::info("MappingIndex::deleteMapping() : id = ".$id);

        // DB::table('shooter_target_mappings')->where('id', $id)->delete();
        ShooterTargetMapping::findOrFail($id)->delete();


        $this->dispatch('notify', type: 'success', message: 'Mapping deleted');

        $this->resetPage();
    }


    protected function availableTargetsCount(): int
    {
        return \App\Models\Target::where('status', 'unsent')
            ->whereDoesntHave('mappings', function ($q) {
                $q->where('assigned_date', $this->assignedDate);
            })
            ->count();
    }


    public function render()
    {

        logger()->info('Mapping filters', [
            'shooter' => $this->filterShooter,
            'status'  => $this->filterStatus,
            'date'    => $this->filterDate,
        ]);

        $availableTargetsCount = $this->availableTargetsCount();
        // $availableTargetsCount = Target::where('status', 'unsent')
        //     ->whereNotExists(function ($q) {
        //         $q->select(DB::raw(1))
        //         ->from('shooter_target_mappings as m')
        //         ->whereColumn('m.target_id', 'targets.id')
        //         ->whereDate('m.assigned_date', $this->assignedDate);
        //     })
        //     ->count();



        // $mappings = DB::table('shooter_target_mappings as m')
        //     ->join('shooters as s', 's.id', '=', 'm.shooter_id')
        //     ->join('targets as t', 't.id', '=', 'm.target_id')

        //     ->when(!is_null($this->filterShooter), function ($q) {
        //         $q->where('m.shooter_id', $this->filterShooter);
        //     })

        //     ->when(!is_null($this->filterStatus), function ($q) {
        //         $q->where('m.status', $this->filterStatus);
        //     })

        //     ->when(!is_null($this->filterDate), function ($q) {
        //         $q->whereDate('m.assigned_date', $this->filterDate);
        //     })

        //     ->select(
        //         'm.id',
        //         's.email as shooter_email',
        //         't.email as target_email',
        //         'm.status',
        //         'm.assigned_date'
        //     )
        //     ->orderByDesc('m.id')
        //     ->paginate(10);

        $mappings = DB::table('shooter_target_mappings as m')
            ->join('shooters as s', 's.id', '=', 'm.shooter_id')
            ->join('targets as t', 't.id', '=', 'm.target_id')
            ->leftJoin('email_templates as et', 'et.id', '=', 'm.email_template_id') 

            ->when(!is_null($this->filterShooter), function ($q) {
                $q->where('m.shooter_id', $this->filterShooter);
            })

            ->when(!is_null($this->filterStatus), function ($q) {
                $q->where('m.status', $this->filterStatus);
            })

            ->when(!is_null($this->filterDate), function ($q) {
                $q->whereDate('m.assigned_date', $this->filterDate);
            })

            ->select(
                'm.id',
                's.email as shooter_email',
                't.email as target_email',
                'et.name as template_name',
                'm.status',
                'm.assigned_date'
            )
            ->orderByDesc('m.id')
            ->paginate(10);


            $shooters = Shooter::where('status', 'active')->whereNotNull('gmail_connected_at')
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
