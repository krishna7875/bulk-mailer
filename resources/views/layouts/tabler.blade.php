<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Bulk Mailer') }}</title>

    {{-- Tabler Core CSS --}}
        
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler-vendors.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">


    {{-- Livewire Styles --}}
    @livewireStyles

    <style>
        html {
            overflow-y: scroll;
        }

        .pagination {
            margin: 0;
        }

        .pagination .page-link {
            padding: 2px 8px;
            font-size: 0.75rem;
            line-height: 1.2;
        }

    </style>
</head>

<body class="layout-fluid theme-light">

    <div class="page">

        {{-- SIDEBAR --}}
        <aside class="navbar navbar-vertical navbar-expand-lg navbar-dark bg-dark">
            <div class="container-fluid">

                {{-- Brand --}}
                <h1 class="navbar-brand navbar-brand-autodark">
                    <a href="{{ route('dashboard') }}">
                        {{ config('app.name', 'Bulk Mailer') }}
                    </a>
                </h1>

                {{-- Sidebar Menu --}}
                <div class="collapse navbar-collapse" id="sidebar-menu">
                    <ul class="navbar-nav pt-lg-3">

                        {{-- SUPER ADMIN ONLY --}}
                        @if(auth()->user()->role === 'super_admin')
                            
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('users.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-users"></i>
                                    </span>
                                    Users
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('shooters.index') }}">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    Shooters
                                </a>
                            </li>

                        @endif

                        {{-- ADMIN + SUPER ADMIN --}}
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('targets.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-users"></i>
                                </span>
                                Targets
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('mappings.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-link"></i>
                                </span>
                                Mappings
                            </a>
                        </li>

                        {{-- TEMP — Add only if route exists --}}
                        {{-- <li class="nav-item">
                            <a class="nav-link" href="{{ route('logs.index') }}">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-file-text"></i>
                                </span>
                                Logs
                            </a>
                        </li> --}}

                    </ul>
                </div>
            </div>
        </aside>

        {{-- MAIN CONTENT AREA --}}
        <div class="page-wrapper">

            {{-- TOP NAVBAR --}} 
            <header class="navbar navbar-expand-md navbar-light sticky-top d-print-none">
                <div class="container-fluid">

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#sidebar-menu">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="navbar-nav flex-row ms-auto">

                        {{-- USER DROPDOWN --}}
                        <div class="nav-item dropdown">
                            <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown">
                                <span class="avatar avatar-sm">
                                    {{ strtoupper(auth()->user()->name[0]) }}
                                </span>

                                <div class="d-none d-md-block ps-2">
                                    <div>{{ auth()->user()->name }}</div>
                                    <div class="small text-muted">{{ auth()->user()->role }}</div>
                                </div>
                            </a>

                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="dropdown-item">Logout</button>
                                </form>
                            </div>
                        </div>

                    </div>

                </div>
            </header>

            {{-- CONTENT WRAPPER --}}
            <div class="page-body pt-1">
                <div class="container-xl py-1">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>
            </div>

        </div> {{-- end page-wrapper --}}

    </div> {{-- end page --}}

    {{-- Tabler JS --}}
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>


    {{-- Livewire --}}
    @livewireScripts

    <script>
        window.addEventListener('notify', event => {
            const { type, message } = event.detail;

            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.style.position = 'fixed';
            alert.style.bottom = '20px';
            alert.style.right = '20px';
            alert.style.zIndex = 2000;
            alert.style.minWidth = '260px';

            alert.innerHTML = `
                <strong>${message}</strong>
                <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
            `;

            document.body.appendChild(alert);

            setTimeout(() => {
                alert.classList.remove('show');
                alert.remove();
            }, 3000);
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function confirmDelete(eventName, id, message = 'This record will be permanently deleted.') {
            Swal.fire({
                title: 'Are you sure?',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d63939',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch(eventName, { id: id });
                }
            });
        }
    </script>

    <script>
        function confirmDeleteShooter(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d63939',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.dispatch('deleteShooter', { id: id });
                }
            });
        }
    </script>



</body>
</html>
