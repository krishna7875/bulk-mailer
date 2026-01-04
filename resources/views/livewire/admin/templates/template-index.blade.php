<div>

    {{-- Full page loader (save only) --}}
    <div wire:loading
        wire:target="save"
        class="position-fixed top-50 start-50 translate-middle"
        style="z-index:2000">
        <div class="spinner-border text-primary" style="width:3rem;height:3rem"></div>
    </div>

    <div class="d-flex justify-content-between mb-2">
        <h2 class="page-title">Email Templates</h2>
        <button class="btn btn-sm btn-primary" wire:click="create">
            Add Template
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Status</th>
                        <th>Attachment</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($templates as $template)
                        <tr>
                            <td>{{ $templates->firstItem() + $loop->index }}</td>
                            <td>{{ $template->name }}</td>
                            <td>
                                <span class="badge bg-{{ $template->status === 'active' ? 'green' : 'gray' }}">
                                    {{ ucfirst($template->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $template->attachment_name ?? '—' }}
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-icon btn-outline-primary"
                                        wire:click="edit({{ $template->id }})">
                                    <i class="ti ti-edit"></i>
                                </button>

                                <button class="btn btn-sm btn-icon btn-outline-danger"
                                        onclick="confirmDelete('deleteTemplate', {{ $template->id }})">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="card-footer py-1">
            {{ $templates->links() }}
        </div>
    </div>

    {{-- MODAL --}}
    @if($showModal)
        <div class="modal modal-blur fade show d-block">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">
                            {{ $templateId ? 'Edit Template' : 'Add Template' }}
                        </h5>
                        <button class="btn-close" wire:click="$set('showModal', false)"></button>
                    </div>

                    <div class="modal-body">

                        <input class="form-control mb-1"
                            placeholder="Template name"
                            wire:model.defer="name">
                        @error('name')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        <div class="m-2">
                            <div class="d-flex flex-wrap gap-1 text-sm">
                                <h6> Mapped Variables : </h6>
                                @foreach($allowedVariables as $var)
                                    @php
                                        $placeholder = '{{ ' . $var . ' }}';
                                    @endphp

                                    <code
                                        class="px-2 py-1 border rounded bg-light"
                                        style="cursor:pointer"
                                        title="Click to copy"
                                        onclick="navigator.clipboard.writeText(@js($placeholder))"
                                    >
                                        {{ $placeholder }}
                                    </code>

                                @endforeach
                            </div>
                        </div>

                        <input class="form-control mb-1"
                            placeholder="Subject"
                            wire:model.defer="subject">

                        @error('subject')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror


                        <textarea class="form-control mb-1"
                                rows="5"
                                placeholder="Body"
                                wire:model.defer="body"></textarea>
                        @error('body')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        <select class="form-select mb-2" wire:model.defer="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>

                        @if($existingAttachment)
                            <div class="mb-2">
                                <span class="badge">
                                    Attached:
                                    {{ $existingAttachment['name'] }}
                                    ({{ number_format($existingAttachment['size'] / 1024, 1) }} KB)
                                </span>

                                <button class="p-1 btn btn-sm btn-icon btn-outline-danger"
                                        wire:click="removeExistingAttachment"
                                        title="Remove Attachment">
                                    <i class="ti ti-trash"></i>
                                </button>
                               
                            </div>
                        @endif
                        <input type="file"
                            class="form-control"
                            wire:model="attachment">
                        @error('attachment')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                    </div>

                    
                    <div class="modal-footer">
                        <button class="btn btn-outline-secondary" wire:click="preview">
                            Preview
                        </button>
                        <button class="btn btn-secondary" wire:click="$set('showModal', false)">Cancel</button>

                        <button class="btn btn-primary"
                                wire:click="save"
                                wire:loading.attr="disabled"
                                wire:target="save">
                            <span wire:loading.remove wire:target="save">Save</span>
                            <span wire:loading wire:target="save">Saving...</span>
                        </button>

                    </div>

                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

    @if($showPreview)
        <div class="modal modal-blur fade show d-block">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Template Preview</h5>
                        <button class="btn-close" wire:click="$set('showPreview', false)"></button>
                    </div>

                    <div class="modal-body">
                        <strong>Subject:</strong>
                        <div class="border p-2 mb-3">
                            {{ $previewSubject }}
                        </div>

                    <strong>Body:</strong>
                    <textarea class="form-control mt-1" rows="10" readonly>
                    {{ $previewBody }}
                    </textarea>

                    </div>

                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    @endif

</div>
