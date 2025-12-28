<?php

namespace App\Livewire\Admin\Shooters;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Shooter;

class ShooterIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    protected $queryString = [
        'searchName',
        'searchEmail',
        'searchStatus',
    ];

    protected $listeners = [
        'deleteShooter' => 'delete',
        'refreshShooters' => '$refresh',
    ];

    public $showModal = false;
    public $shooterId = null;

    public $name;
    public $email;
    public $description;
    public $daily_quota = 200;
    public $status = 'paused';

    public $searchName = '';
    public $searchEmail = '';
    public $searchStatus = '';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:shooters,email,' . $this->shooterId,
            'daily_quota' => 'required|integer|min:1',
            'status' => 'required|in:active,paused,disabled',
            'description' => 'nullable|string',
        ];
    }

    public function render()
    {

        logger([
            'name' => $this->searchName,
            'email' => $this->searchEmail,
            'status' => $this->searchStatus,
        ]);

        $shooters = Shooter::query()
            ->when($this->searchName !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->searchName . '%');
            })
            ->when($this->searchEmail !== '', function ($query) {
                $query->where('email', 'like', '%' . $this->searchEmail . '%');
            })
            ->when($this->searchStatus !== '', function ($query) {
                $query->where('status', $this->searchStatus);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.shooters.shooter-index', [
            'shooters' => $shooters,
        ]);
    }


    public function edit($id)
    {
        $this->resetValidation();
        $shooter = Shooter::findOrFail($id);

        $this->shooterId = $shooter->id;
        $this->name = $shooter->name;
        $this->email = $shooter->email;
        $this->description = $shooter->description;
        $this->daily_quota = $shooter->daily_quota;
        $this->status = $shooter->status;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();
        
        Shooter::updateOrCreate(
            ['id' => $this->shooterId],
            [
                'name' => $this->name,
                'email' => $this->email,
                'description' => $this->description,
                'daily_quota' => $this->daily_quota,
                'status' => $this->status,
                ]
            );
            
        $this->dispatch('notify', type: 'success', message: 'Shooter saved successfully.');
        $this->showModal = false; 
        $this->resetForm();
    }

    public function delete($id)
    {
        Shooter::findOrFail($id)->delete();

        $this->dispatch('notify', type: 'success', message: 'Shooter deleted successfully.');
    }

    public function updatedShowModal($value)
    {
        if ($value === true) {
            $this->resetForm();
            $this->resetValidation();
        }
    }
    private function resetForm()
    {
        $this->reset([
            'shooterId',
            'name',
            'email',
            'description',
            'daily_quota',
            'status',
        ]);
        $this->resetValidation();
    }

    public function updatingSearchName()
    {
        $this->resetPage();
    }

    public function updatingSearchEmail()
    {
        $this->resetPage();
    }

    public function updatingSearchStatus()
    {
        $this->resetPage();
    }

}
