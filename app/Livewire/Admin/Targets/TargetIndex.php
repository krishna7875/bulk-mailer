<?php

namespace App\Livewire\Admin\Targets;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Target;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\TargetsImport;
use Illuminate\Support\Str;

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
            'importFile' => 'required|file|mimes:xlsx,csv',
        ];
    }
    public function import()
    {
        $this->validate($this->importRules());

        $batchId = 'batch_' . now()->format('Ymd_His') . '_' . Str::random(6);

        Excel::import(
            new TargetsImport($batchId),
            $this->importFile
        );

        $this->reset('importFile');

        $this->dispatch(
            'notify',
            type: 'success',
            message: 'Targets imported successfully'
        );
    }


}
