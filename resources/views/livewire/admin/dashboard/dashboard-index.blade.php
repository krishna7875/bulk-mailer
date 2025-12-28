<div>
    {{-- Date Selector --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="page-title mb-0">Dashboard</h2>

        <input type="date"
               class="form-control form-control-sm w-auto"
               wire:model="date">
    </div>

    {{-- Metrics --}}
    <div class="row row-cards">

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Total Shooters</div>
                    <div class="h1">{{ $stats['total_shooters'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Gmail Connected</div>
                    <div class="h1 text-success">{{ $stats['gmail_connected_shooters'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Available Targets</div>
                    <div class="h1">{{ $stats['available_targets'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Assigned (Selected Date)</div>
                    <div class="h1">{{ $stats['assigned'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Sent</div>
                    <div class="h1 text-primary">{{ $stats['sent'] }}</div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="subheader">Failed</div>
                    <div class="h1 text-danger">{{ $stats['failed'] }}</div>
                </div>
            </div>
        </div>

    </div>
</div>
