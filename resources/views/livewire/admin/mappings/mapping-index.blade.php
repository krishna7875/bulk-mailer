<div>
    <h2 class="page-title mb-2">Shooter–Target Mapping</h2>

    {{-- ================= Generate Mapping ================= --}}
    <div class="card mb-3">
        <div class="card-body">

            <div class="row g-2 mb-2">
                <div class="col-md-3">
                    <label class="form-label">Assigned Date</label>
                    <input type="date"
                        class="form-control form-control-sm"
                        wire:model.live.debounce.300ms="assignedDate">
                </div>
            </div>
            @if($availableTargetsCount > 0)
                <div class="text-muted mb-2">
                    Available targets: <b>{{ $availableTargetsCount }}</b>
                </div>
            @else
                <div class="text-danger mb-2">
                    No available targets left for this date
                </div>
            @endif

            <label class="form-label">Select Shooters</label>

            <div class="border rounded p-2"
                style="max-height:220px; overflow-y:auto;">
                @foreach($shooters as $shooter)
                    <div class="form-check">
                        <input class="form-check-input"
                            type="checkbox"
                            wire:model="selectedShooters"
                            value="{{ $shooter->id }}"
                            @disabled($shooter->remaining_quota === 0)>

                        <label class="form-check-label">
                            {{ $shooter->email }}
                            <span class="text-muted">
                                ({{ $shooter->mapped_today }}/{{ $shooter->daily_quota }})
                            </span>
                        </label>
                    </div>
                @endforeach
            </div>

            <button class="btn btn-primary btn-sm mt-2"
                    wire:click="generate"
                    wire:loading.attr="disabled">
                Generate Mapping
            </button>
        </div>
    </div>

    {{-- ================= Filters ================= --}}
    <div class="card mb-2">
        <div class="card-body p-2">
            <div class="row g-2">
                <div class="col-md-3">
                    <select wire:model="filterShooter" class="form-select form-select-sm">
                        <option value="">All Shooters</option>
                        @foreach($shooters as $shooter)
                            <option value="{{ $shooter->id }}">
                                {{ $shooter->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <select wire:model="filterStatus"  class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="assigned">Assigned</option>
                        <option value="sent">Sent</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>

                <div class="col-md-3">
                   <input  wire:model="filterDate" type="date" class="form-control form-control-sm">
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Mapping Table ================= --}}
    <div wire:key="mapping-table">
        <div class="card">
            <div class="table-responsive"> 
                <table class="table table-sm table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="text-center">#</th>
                            <th>Shooter</th>
                            <th>Target</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($mappings as $index => $row)
                            <tr>
                                <td class="text-center">
                                    {{ $mappings->firstItem() + $index }}
                                </td>
                                <td>{{ $row->shooter_email }}</td>
                                <td>{{ $row->target_email }}</td>
                                <td>{{ ucfirst($row->status) }}</td>
                                <td>{{ $row->assigned_date }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-icon btn-outline-danger"
                                            onclick="confirmDelete('deleteMapping', {{ $row->id }})">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">
                                    No mappings found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

          <div class="card-footer d-flex justify-content-between align-items-center">
    <div class="text-muted">
        Showing {{ $mappings->firstItem() }} to {{ $mappings->lastItem() }}
        of {{ $mappings->total() }}
    </div>

    <div class="btn-group btn-group-sm">
        <button class="btn btn-outline-secondary"
                wire:click="previousPage"
                @disabled($mappings->onFirstPage())>
            ‹
        </button>

        <button class="btn btn-outline-secondary"
                wire:click="nextPage"
                @disabled(!$mappings->hasMorePages())>
            ›
        </button>
    </div>
</div>


        </div>
    </div>
</div>
