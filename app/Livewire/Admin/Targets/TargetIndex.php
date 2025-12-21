<?php

namespace App\Livewire\Admin\Targets;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Target;
use Livewire\WithFileUploads;
use App\Imports\TargetsImport;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\SimpleExcel\SimpleExcelReader;


class TargetIndex extends Component
{
    use WithPagination;
    use WithFileUploads;

    protected $paginationTheme = 'bootstrap';
    protected $listeners = ['deleteTarget' => 'delete'];

    /* Import excel */
    public $importFile;
    
    /* Modal */
    public $showModal = false;
    public $targetId = null;

    /* Form */
    public $email;
    public $name;

    /* Filters */
    public $searchEmail = '';
    public $searchStatus = '';

    protected $queryString = [
        'searchEmail',
        'searchStatus',
    ];



    protected function rules()
    {
        return [
            'email' => 'required|email|unique:targets,email,' . $this->targetId,
            'name'  => 'nullable|string|max:255',
        ];
    }

    public function render()
    {
        $targets = Target::query()
            ->when($this->searchEmail !== '', fn ($q) =>
                $q->where('email', 'like', '%' . $this->searchEmail . '%')
            )
            ->when($this->searchStatus !== '', fn ($q) =>
                $q->where('status', $this->searchStatus)
            )
            ->orderByDesc('id')
            ->paginate(10);

        return view('livewire.admin.targets.target-index', compact('targets'));
    }

    /* Reset pagination on filter */
    public function updatingSearchEmail() { $this->resetPage(); }
    public function updatingSearchStatus() { $this->resetPage(); }

    /* CRUD */
    public function edit($id)
    {
        $this->resetValidation();

        $t = Target::findOrFail($id);
        $this->targetId = $t->id;
        $this->email = $t->email;
        $this->name  = $t->name;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        Target::updateOrCreate(
            ['id' => $this->targetId],
            [
                'email' => $this->email,
                'name'  => $this->name,
            ]
        );

        $this->dispatch('notify', type: 'success', message: 'Target saved successfully');

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete($id)
    {
        Target::findOrFail($id)->delete();

        $this->dispatch('notify', type: 'success', message: 'Target deleted');
    }

    protected function resetForm()
    {
        $this->reset(['targetId', 'email', 'name']);
        $this->resetValidation();
    }

    protected function importRules()
    {
        return [
            'importFile' => 'required|file|mimes:csv,xlsx|max:20480',

        ];
    }

    public function import()
    {
        $this->validate($this->importRules());

        try {
            $batchId = 'batch_' . now()->format('Ymd_His') . '_' . Str::random(6);
            $path = $this->importFile->getRealPath();

            $total = 0;
            $valid = 0;
            $inserted = 0;

            SimpleExcelReader::create($path)
                ->trimHeaderRow()
                ->getRows()
                ->chunk(1000)
                ->each(function ($rows) use (&$total, &$valid, &$inserted, $batchId) {

                    $buffer = [];

                    // Normalize & validate emails in chunk
                    $emails = collect($rows)
                        ->pluck('email')
                        ->map(fn ($e) => strtolower(trim($e ?? '')))
                        ->filter(fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL))
                        ->values();

                    // Load existing emails once per chunk
                    $existing = DB::table('targets')
                        ->whereIn('email', $emails)
                        ->pluck('email')
                        ->toArray();

                    $existing = array_flip($existing);

                    foreach ($rows as $row) {
                        $total++;

                        $email = strtolower(trim($row['email'] ?? ''));

                        // ❌ invalid email
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            continue;
                        }

                        $valid++;

                        // ❌ duplicate (DB or same file)
                        if (isset($existing[$email])) {
                            continue;
                        }

                        $buffer[] = [
                            'email'        => $email,
                            'name'         => $row['name'] ?? null,
                            'metadata'     => null,
                            'import_batch' => $batchId,
                            'status'       => 'unsent',
                            'created_at'   => now(),
                            'updated_at'   => now(),
                        ];

                        $existing[$email] = true;
                    }

                    if (!empty($buffer)) {
                        DB::table('targets')->insert($buffer);
                        $inserted += count($buffer); // ✅ CORRECT
                    }
                });

            $skipped = $total - $inserted;

            $this->dispatch('swal', [
                'type'  => 'success',
                'title' => 'Import Completed',
                'html' => "
                    <table style='width:100%; border-collapse:collapse; text-align:left;'>
                        <tr>
                            <th style='padding:6px 8px;'>Metric</th>
                            <th style='padding:6px 8px;'>Count</th>
                        </tr>
                        <tr>
                            <td style='padding:6px 8px;'>Total Valid Rows</td>
                            <td style='padding:6px 8px;'><b>{$total}</b></td>
                        </tr>
                        <tr>
                            <td style='padding:6px 8px;'>Inserted</td>
                            <td style='padding:6px 8px; color:#2fb344;'><b>{$inserted}</b></td>
                        </tr>
                        <tr>
                            <td style='padding:6px 8px;'>Skipped (Invalid / Duplicates)</td>
                            <td style='padding:6px 8px; color:#d63939;'><b>{$skipped}</b></td>
                        </tr>
                    </table>
                "

            ]);

        } catch (\Throwable $e) {

            $this->reset('importFile');

            $this->dispatch('swal', [
                'type'  => 'error',
                'title' => 'Import Failed',
                'html'  => e($e->getMessage()),
            ]);
        }
    }






}
