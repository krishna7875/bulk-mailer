<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';
    
    protected $queryString = [
        'searchName',
        'searchEmail',
        'searchRole',
    ];

    protected $listeners = [
        'deleteUser' => 'delete',
        'refreshUsers' => '$refresh',
    ];

    public $showModal = false;
    public $userId = null;

    public $name;
    public $email;
    public $role = 'admin'; // Default role
    public $password;

    public $searchName = '';
    public $searchEmail = '';
    public $searchRole = '';

    protected function rules()
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->userId)],
            'role' => 'required|in:super_admin,admin',
        ];

        // Password is required for new users, optional for updates
        if (!$this->userId) {
            $rules['password'] = 'required|string|min:8';
        } else {
             $rules['password'] = 'nullable|string|min:8';
        }

        return $rules;
    }

    public function render()
    {
        $users = User::query()
            ->when($this->searchName !== '', function ($query) {
                $query->where('name', 'like', '%' . $this->searchName . '%');
            })
            ->when($this->searchEmail !== '', function ($query) {
                $query->where('email', 'like', '%' . $this->searchEmail . '%');
            })
            ->when($this->searchRole !== '', function ($query) {
                $query->where('role', $this->searchRole);
            })
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('livewire.admin.users.user-index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        $this->ensureSuperAdmin();
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureSuperAdmin();
        $this->resetValidation();
        $user = User::findOrFail($id);

        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = null; // Don't send back password hash

        $this->showModal = true;
    }

    public function save()
    {
        $this->ensureSuperAdmin();
        $this->validate();
        
        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        User::updateOrCreate(
            ['id' => $this->userId],
            $data
        );
            
        $this->dispatch('notify', type: 'success', message: 'User saved successfully.');
        $this->showModal = false; 
        $this->resetForm();
    }

    public function delete($id)
    {
        $this->ensureSuperAdmin();

        $user = User::findOrFail($id);

        // Security Checks
        if ($user->id === Auth::id()) {
             $this->dispatch('notify', type: 'error', message: 'You cannot delete yourself.');
             return;
        }

        if ($user->role === 'super_admin') {
            $superAdminCount = User::where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                $this->dispatch('notify', type: 'error', message: 'Cannot delete the last Super Admin.');
                return;
            }
        }

        $user->delete();

        $this->dispatch('notify', type: 'success', message: 'User deleted successfully.');
    }

    public function updatedShowModal($value)
    {
        if ($value === true) {
            // Only allow opening modal if super_admin (double check, though UI hides it)
            $this->ensureSuperAdmin();
        } else {
             $this->resetForm();
             $this->resetValidation();
        }
    }

    private function resetForm()
    {
        $this->reset([
            'userId',
            'name',
            'email',
            'role',
            'password',
        ]);
        $this->resetValidation();
    }
    
    private function ensureSuperAdmin()
    {
        if (Auth::user()->role !== 'super_admin') {
            abort(403, 'Unauthorized action.');
        }
    }

    public function updatingSearchName() { $this->resetPage(); }
    public function updatingSearchEmail() { $this->resetPage(); }
    public function updatingSearchRole() { $this->resetPage(); }
}
