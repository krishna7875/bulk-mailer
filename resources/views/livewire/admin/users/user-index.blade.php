<div>

    {{-- Full page loader (only for save & delete) --}}
    <div wire:loading wire:target="save,delete"
        class="position-fixed top-50 start-50 translate-middle"
        style="z-index: 2000;">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div>
    </div>


    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="page-title mb-0">Users</h2>
        @if(auth()->user()->role === 'super_admin')
            <button class="btn btn-primary btn-sm" wire:click="create">
                Add User
            </button>
        @endif
    </div>

    <div class="card mt-1">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table mb-0" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 5%;">SrNo</th>
                        <th style="width: 25%;" class="text-center">Name</th>
                        <th style="width: 30%;" class="text-center">Email</th>
                        <th style="width: 15%;" class="text-center">Role</th>
                        <th style="width: 10%;" class="text-center">Created</th>
                        @if(auth()->user()->role === 'super_admin')
                            <th style="width: 15%;" class="text-center">Actions</th>
                        @endif
                    </tr>
                    <tr>
                        <th style="width: 5%;"></th>

                        <th style="width: 25%;">
                            <input type="text"
                                class="form-control form-control-sm py-0 px-1"
                                style="font-size: 0.75rem; height: 28px;"
                                placeholder="Search name"
                                wire:model.live.debounce.500ms="searchName">
                        </th>

                        <th style="width: 30%;">
                           <input type="text"
                                class="form-control form-control-sm py-0 px-1"
                                style="font-size: 0.75rem; height: 28px;"
                                placeholder="Search email"
                                wire:model.live.debounce.500ms="searchEmail">
                        </th>

                        <th style="width: 15%;">
                           <select class="form-select form-select-sm py-0 px-1"
                                style="font-size: 0.75rem; height: 28px;"
                                wire:model.live="searchRole">
                                <option value="">All</option>
                                <option value="super_admin">Super Admin</option>
                                <option value="admin">Admin</option>
                            </select>
                        </th>
                         <th style="width: 10%;"></th>

                        @if(auth()->user()->role === 'super_admin')
                            <th style="width: 15%;"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($users->firstItem() ?? 0) - 1 }}
                            </td>
                            <td class="text-center">{{ $user->name }}</td>
                            <td class="text-center text-truncate" title="{{ $user->email }}">{{ $user->email }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $user->role === 'super_admin' ? 'purple' : 'blue' }}">
                                    {{ str_replace('_', ' ', ucfirst($user->role)) }}
                                </span>
                            </td>
                            <td class="text-center" style="font-size: 0.75rem;">
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                            @if(auth()->user()->role === 'super_admin')
                                <td class="text-center">
                                    <div class="d-flex align-items-center justify-content-center gap-2">

                                        {{-- Edit --}}
                                        <button class="p-1 btn btn-sm btn-icon btn-outline-primary"
                                                wire:click="edit({{ $user->id }})"
                                                title="Edit">
                                            <i class="ti ti-edit"></i>
                                        </button>

                                        {{-- Delete --}}
                                        @if(auth()->id() !== $user->id)
                                            <button class="p-1 btn btn-sm btn-icon btn-outline-danger"
                                                    onclick="confirmDelete('deleteUser', {{ $user->id }})"
                                                    title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @else
                                             <button class="p-1 btn btn-sm btn-icon btn-outline-secondary"
                                                    disabled
                                                    title="Cannot delete self">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        @endif

                                    </div>
                                </td>
                            @endif

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    
        <div class="card-footer py-1">
            {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>

    </div>

    {{-- MODAL --}}
    @if($showModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $userId ? 'Edit User' : 'Add User' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label required">Name</label>
                            <input type="text" wire:model.lazy="name" class="form-control">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label required">Email</label>
                            <input type="email" wire:model.lazy="email" class="form-control">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                         <div class="mb-3">
                            <label class="form-label required">Role</label>
                            <select wire:model.lazy="role" class="form-select">
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                             @error('role') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label {{ !$userId ? 'required' : '' }}">Password</label>
                            <input type="password" wire:model.lazy="password" class="form-control" placeholder="{{ $userId ? 'Leave blank to keep current password' : '' }}">
                             @error('password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" wire:click="$set('showModal', false)">
                            Cancel
                        </button>
                       <button class="btn btn-primary"
                            wire:click="save"
                            wire:loading.attr="disabled">

                        <span wire:loading.remove wire:target="save">Save</span>
                        <span wire:loading wire:target="save">Saving...</span>

                    </button>

                    </div>

                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
