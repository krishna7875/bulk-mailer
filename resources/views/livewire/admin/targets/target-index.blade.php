<div>

    {{-- Loader only for save/delete --}}
    <div wire:loading wire:target="save,delete"
         class="position-fixed top-50 start-50 translate-middle"
         style="z-index:2000">
        <div class="spinner-border text-primary"></div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="page-title mb-0">Targets</h2>

            <div class="mb-2">
        <form wire:submit.prevent="import" class="d-flex gap-2 align-items-center">
            <input type="file"
                class="form-control form-control-sm"
                wire:model="importFile"
                accept=".csv,.xlsx">

            <button class="btn btn-sm btn-success"
                    wire:loading.attr="disabled"
                    wire:target="import">
                Import
            </button>
        </form>

        @error('importFile')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    
        <button class="btn btn-primary btn-sm" wire:click="$set('showModal', true)">
            Add Target
        </button>
    </div>



    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter card-table mb-0">
                <thead>
                <tr>
                    <th class="text-center">SrNo</th>
                    <th class="text-center">Email</th>
                    <th class="text-center">Name</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Actions</th>
                </tr>

                <tr>
                    <th></th>

                    <th>
                        <input type="text"
                               class="form-control form-control-sm"
                               placeholder="Search email"
                               wire:model.live.debounce.500ms="searchEmail">
                    </th>

                    <th></th>

                    <th>
                        <select class="form-select form-select-sm"
                                wire:model.live="searchStatus">
                            <option value="">All</option>
                            <option value="unsent">Unsent</option>
                            <option value="queued">Queued</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="suppressed">Suppressed</option>
                        </select>
                    </th>

                    <th></th>
                </tr>
                </thead>

                <tbody>
                @foreach($targets as $target)
                    <tr>
                        <td class="text-center">
                            {{ $loop->iteration + ($targets->firstItem() ?? 0) - 1 }}
                        </td>

                        <td class="text-center text-truncate" title="{{ $target->email }}">
                            {{ $target->email }}
                        </td>

                        <td class="text-center">
                            {{ $target->name ?? '-' }}
                        </td>

                        <td class="text-center">
                            <span class="badge bg-{{ match($target->status) {
                                'sent' => 'green',
                                'queued' => 'blue',
                                'failed' => 'red',
                                'suppressed' => 'gray',
                                default => 'yellow'
                            } }}">
                                {{ ucfirst($target->status) }}
                            </span>
                        </td>

                        <td class="text-center">
                            <button class="btn btn-sm btn-icon btn-outline-primary me-2"
                                    wire:click="edit({{ $target->id }})">
                                <i class="ti ti-edit"></i>
                            </button>

                            <button class="btn btn-sm btn-icon btn-outline-danger"
                                    onclick="confirmDelete('deleteTarget', {{ $target->id }})" 
                                    title="Delete">
                                <i class="ti ti-trash"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $targets->links() }}
        </div>
    </div>

    {{-- MODAL --}}
    @if($showModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1">
            <div class="modal-dialog modal-md">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $targetId ? 'Edit Target' : 'Add Target' }}
                        </h5>
                        <button class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control"
                                   wire:model.lazy="email">
                            @error('email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control"
                                   wire:model.lazy="name">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary"
                                wire:click="$set('showModal', false)">
                            Cancel
                        </button>

                        <button class="btn btn-primary"
                                wire:click="save"
                                wire:loading.attr="disabled">
                            Save
                        </button>
                    </div>

                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif
</div>
