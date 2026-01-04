<div>

    {{-- Full page loader (only for save & delete) --}}
    <div wire:loading wire:target="save,delete"
        class="position-fixed top-50 start-50 translate-middle"
        style="z-index: 2000;">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem;"></div>
    </div>


    <div class="d-flex justify-content-between align-items-center mb-1">
        <h2 class="page-title mb-0">Shooters</h2>
        <button class="btn btn-primary btn-sm" wire:click="$set('showModal', true)">
            Add Shooter
        </button>
    </div>

    <div class="card mt-1">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table mb-0" style="table-layout: fixed;">
                <thead>
                    <tr>
                        <th class="text-center">SrNo</th>
                        <th style="width: 20%;" class="text-center">Name</th>
                        <th style="width: 30%;" class="text-center">Email</th>
                        <th style="width: 10%;" class="text-center">Quota</th>
                        <th style="width: 15%;" class="text-center">Status</th>
                        <th style="width: 15%;" class="text-center">Actions</th>
                    </tr>
                    <tr>
                        <th style="width: 10%;"></th>

                        <th style="width: 20%;">
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

                        <th style="width: 10%;"></th>

                        <th style="width: 15%;">
                           <select class="form-select form-select-sm py-0 px-1"
                                style="font-size: 0.75rem; height: 28px;"
                                wire:model.live="searchStatus">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </th>

                        <th style="width: 15%;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($shooters as $shooter)
                        <tr>
                            <td class="text-center">
                                {{ $loop->iteration + ($shooters->firstItem() ?? 0) - 1 }}
                            </td>
                            <td class="text-center">{{ $shooter->name }}</td>
                            <td class="text-center text-truncate" title="{{ $shooter->email }}">{{ $shooter->email }}</td>
                            <td class="text-center">{{ $shooter->daily_quota }}</td>
                            <td class="text-center">
                                <span style="font-size:10px;" class="p-2 badge bg-{{ 
                                    $shooter->status === 'active' ? 'green' :
                                    ($shooter->status === 'paused' ? 'yellow' : 'red')
                                }}">
                                    {{ ucfirst($shooter->status) }}
                                </span>
                            </td>


                            <td class="text-center">
                                <div class="d-flex align-items-center justify-content-center gap-2">

                                    {{-- Edit --}}
                                    <button class="p-1 btn btn-sm btn-icon btn-outline-primary"
                                            wire:click="edit({{ $shooter->id }})"
                                            title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </button>

                                    {{-- Delete --}}
                                    <button class="p-1 btn btn-sm btn-icon btn-outline-danger"
                                            onclick="confirmDelete('deleteShooter', {{ $shooter->id }})"
                                            title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>

                                    {{-- Gmail Integration --}}

                                    @php $gmailStatus = $shooter->gmail_status; @endphp

                                    @if($gmailStatus === 'connected')
                                        {{-- CONNECTED (non-actionable) --}}
                                        <span
                                            class="p-1 btn btn-sm btn-icon bg-success text-white border-0"
                                            title="Gmail Connected"
                                            style="cursor: default;"
                                        >
                                            <i class="ti ti-mail"></i>
                                        </span>

                                    @elseif($gmailStatus === 'expired')
                                        {{-- EXPIRED --}}
                                        <a
                                            href="{{ route('shooters.gmail.connect', $shooter) }}"
                                            class="p-1 btn btn-sm btn-icon btn-outline-warning"
                                            title="Gmail access expired – reconnect required"
                                        >
                                            <i class="ti ti-mail"></i>
                                        </a>

                                    @else
                                        {{-- NOT CONNECTED --}}
                                        <a
                                            href="{{ route('shooters.gmail.connect', $shooter) }}"
                                            class="p-1 btn btn-sm btn-icon btn-outline-primary"
                                            title="Connect Gmail"
                                        >
                                            <i class="ti ti-mail"></i>
                                        </a>
                                    @endif

                                </div>

                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    
        <div class="card-footer py-1">
            {{ $shooters->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>

    </div>

    {{-- MODAL --}}
    @if($showModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $shooterId ? 'Edit Shooter' : 'Add Shooter' }}
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" wire:model.lazy="name" class="form-control">
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" wire:model.lazy="email" class="form-control">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Daily Quota</label>
                            <input type="number" wire:model.lazy="daily_quota" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select wire:model.lazy="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="paused">Paused</option>
                                <option value="disabled">Disabled</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea wire:model.lazy="description" class="form-control"></textarea>
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

@if(session()->has('gmail_connected'))
    <script>
        Livewire.dispatch('refreshShooters');
    </script>
@endif
