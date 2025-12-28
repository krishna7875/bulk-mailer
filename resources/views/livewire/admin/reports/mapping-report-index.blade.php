<div>
    {{-- Header + Filters --}}
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="page-title mb-0">Mapping Report</h2>

        <div class="d-flex gap-2">
            <input type="date"
                class="form-control form-control-sm"
                wire:model.live="date">

            <select class="form-select form-select-sm"
                    wire:model.live="shooterId">
                <option value="">All Shooters</option>
                @foreach($shooters as $shooter)
                    <option value="{{ $shooter->id }}">
                        {{ $shooter->email }}
                    </option>
                @endforeach
            </select>

            <button class="btn btn-sm btn-outline-primary"
                    wire:click="exportCsv">
                <i class="ti ti-download"></i>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-vcenter mb-0">
                <thead>
                    <tr>
                        <th class="text-center">#</th>
                        <th>Shooter</th>
                        <th class="text-center">Assigned</th>
                        <th class="text-center">Sent</th>
                        <th class="text-center">Failed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $index => $row)
                        <tr>
                            <td class="text-center">
                                {{ $rows->firstItem() + $loop->index }}
                            </td>

                            <td>
                                {{ optional($shooters->find($row->shooter_id))->email }}
                            </td>

                            <td class="text-center">{{ $row->total_assigned }}</td>
                            <td class="text-center text-success">{{ $row->total_sent }}</td>
                            <td class="text-center text-danger">{{ $row->total_failed }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">
                                No data for selected filters
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer py-1">
            {{ $rows->links() }}
        </div>
    </div>
</div>
