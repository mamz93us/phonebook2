<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SG Unified System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body { background: #f8f9fa; }
        .nav-link.active {
            font-weight: bold;
            background: rgba(255, 255, 255, 0.1);
        }
        .avatar-circle {
            width: 32px; height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700; color: #fff;
            flex-shrink: 0;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            @php $__settings = \App\Models\Setting::get(); @endphp
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold py-1" href="/admin">
                @if($__settings->company_logo)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($__settings->company_logo) }}"
                         alt="Logo" style="height:34px;width:auto;object-fit:contain;">
                @else
                    <span class="avatar-circle" style="background:linear-gradient(135deg,#1a56db,#6c47ff);font-size:15px;">SG</span>
                @endif
                <span class="d-none d-sm-inline">SG Unified System</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto">

                    {{-- ── VoIP dropdown (Contacts + UCM/Extensions) ── --}}
                    @canany(['view-contacts','view-extensions','view-trunks'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/contacts*','admin/extensions*','admin/trunks*','admin/gdms*') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-telephone-fill me-1"></i>VoIP
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow">
                            @can('view-contacts')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/contacts*') ? 'active' : '' }}"
                                   href="{{ route('admin.contacts.index') }}">
                                    <i class="bi bi-person-lines-fill me-2"></i>Contacts
                                </a>
                            </li>
                            @endcan
                            @canany(['view-extensions','view-trunks'])
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header text-secondary"><i class="bi bi-hdd-stack me-1"></i>UCM / PBX</h6></li>
                            @can('view-extensions')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/extensions*') ? 'active' : '' }}"
                                   href="{{ route('admin.extensions.index') }}">
                                    <i class="bi bi-telephone me-2"></i>Extensions
                                </a>
                            </li>
                            @endcan
                            @can('view-trunks')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/trunks*') ? 'active' : '' }}"
                                   href="{{ route('admin.trunks.index') }}">
                                    <i class="bi bi-hdd-network-fill me-2"></i>Trunks
                                </a>
                            </li>
                            @endcan
                            @can('view-extensions')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/gdms*') ? 'active' : '' }}"
                                   href="{{ route('admin.gdms.ucm') }}">
                                    <i class="bi bi-cloud-check-fill me-2"></i>UCM Status
                                </a>
                            </li>
                            @endcan
                            @endcanany
                        </ul>
                    </li>
                    @endcanany

                    {{-- ── Network dropdown ── --}}
                    @can('view-network')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/network*') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-diagram-3-fill me-1"></i>Network
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.network.overview') ? 'active' : '' }}"
                                   href="{{ route('admin.network.overview') }}">
                                    <i class="bi bi-speedometer2 me-2"></i>Overview
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.network.switches') ? 'active' : '' }}"
                                   href="{{ route('admin.network.switches') }}">
                                    <i class="bi bi-hdd-network me-2"></i>Switches
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.network.clients') ? 'active' : '' }}"
                                   href="{{ route('admin.network.clients') }}">
                                    <i class="bi bi-laptop me-2"></i>Clients
                                </a>
                            </li>
                            @can('view-network-events')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.network.events') ? 'active' : '' }}"
                                   href="{{ route('admin.network.events') }}">
                                    <i class="bi bi-clock-history me-2"></i>Change Monitor
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcan

                    {{-- ── Assets dropdown ── --}}
                    @canany(['view-assets','view-printers','view-credentials'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/devices*','admin/printers*','admin/credentials*') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-cpu me-1"></i>Assets
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow">
                            @can('view-assets')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/devices*') ? 'active' : '' }}"
                                   href="{{ route('admin.devices.index') }}">
                                    <i class="bi bi-cpu me-2"></i>Devices
                                </a>
                            </li>
                            @endcan
                            @can('view-printers')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/printers*') ? 'active' : '' }}"
                                   href="{{ route('admin.printers.index') }}">
                                    <i class="bi bi-printer-fill me-2"></i>Printers
                                </a>
                            </li>
                            @endcan
                            @can('view-credentials')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/credentials*') ? 'active' : '' }}"
                                   href="{{ route('admin.credentials.index') }}">
                                    <i class="bi bi-key-fill me-2"></i>Credentials
                                </a>
                            </li>
                            @endcan
                        </ul>
                    </li>
                    @endcanany

                    {{-- ── Identity dropdown ── --}}
                    @can('view-identity')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/identity*') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-people-fill me-1"></i>Identity
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow">
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.identity.users') ? 'active' : '' }}"
                                   href="{{ route('admin.identity.users') }}">
                                    <i class="bi bi-people me-2"></i>Users
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.identity.licenses') ? 'active' : '' }}"
                                   href="{{ route('admin.identity.licenses') }}">
                                    <i class="bi bi-patch-check me-2"></i>Licenses
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.identity.groups') ? 'active' : '' }}"
                                   href="{{ route('admin.identity.groups') }}">
                                    <i class="bi bi-collection me-2"></i>Groups
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.identity.sync-logs') ? 'active' : '' }}"
                                   href="{{ route('admin.identity.sync-logs') }}">
                                    <i class="bi bi-clock-history me-2"></i>Sync Logs
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endcan

                    {{-- ── Settings dropdown ── --}}
                    @canany(['manage-settings','manage-users','manage-permissions','view-phone-logs','view-activity-logs'])
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('admin/settings*','admin/users*','admin/permissions*','admin/phone-logs*','admin/activity-logs*','admin/branches*') ? 'active' : '' }}"
                           href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear-fill me-1"></i>Settings
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark shadow">
                            @can('manage-settings')
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.settings.index') ? 'active' : '' }}"
                                   href="{{ route('admin.settings.index') }}">
                                    <i class="bi bi-sliders me-2"></i>General Settings
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header text-secondary"><i class="bi bi-building me-1"></i>Organisation</h6></li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.settings.locations') || request()->is('admin/branches*') ? 'active' : '' }}"
                                   href="{{ route('admin.settings.locations') }}">
                                    <i class="bi bi-geo-alt-fill me-2"></i>Locations
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item {{ request()->routeIs('admin.settings.departments') ? 'active' : '' }}"
                                   href="{{ route('admin.settings.departments') }}">
                                    <i class="bi bi-grid-1x2-fill me-2"></i>Departments
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            @endcan
                            @can('manage-users')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/users*') ? 'active' : '' }}"
                                   href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-person-badge-fill me-2"></i>Users
                                </a>
                            </li>
                            @endcan
                            @can('manage-permissions')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/permissions*') ? 'active' : '' }}"
                                   href="{{ route('admin.permissions.index') }}">
                                    <i class="bi bi-shield-lock-fill me-2"></i>Permissions
                                </a>
                            </li>
                            @endcan
                            @canany(['view-phone-logs','view-activity-logs'])
                            <li><hr class="dropdown-divider"></li>
                            <li><h6 class="dropdown-header text-secondary"><i class="bi bi-journal-text me-1"></i>Logs</h6></li>
                            @can('view-phone-logs')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/phone-logs*') ? 'active' : '' }}"
                                   href="{{ route('admin.phone-logs.index') }}">
                                    <i class="bi bi-telephone-inbound-fill me-2"></i>Phone Logs
                                </a>
                            </li>
                            @endcan
                            @can('view-activity-logs')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/activity-logs*') ? 'active' : '' }}"
                                   href="{{ route('admin.activity-logs') }}">
                                    <i class="bi bi-shield-check me-2"></i>Audit Log
                                </a>
                            </li>
                            @endcan
                            @endcanany
                        </ul>
                    </li>
                    @endcanany

                </ul>

                {{-- ── Profile dropdown ── --}}
                <ul class="navbar-nav ms-2">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 py-1" href="#"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="avatar-circle">
                                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="d-none d-lg-inline">{{ auth()->user()->name }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li>
                                <span class="dropdown-item-text small">
                                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                    <div class="text-muted">{{ auth()->user()->email }}</div>
                                    <span class="badge bg-secondary mt-1">
                                        {{ \App\Models\User::roleLabel(auth()->user()->role ?? 'admin') }}
                                    </span>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="#"
                                   data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key me-2"></i>Change Password
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="/logout">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>

            </div>
        </div>
    </nav>

    <!-- PAGE CONTENT -->
    <div class="container mt-4 mb-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    {{-- ── Change Password Modal (global, available on every admin page) ── --}}
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.profile.password') }}">
                    @csrf @method('PUT')
                    <div class="modal-header bg-secondary text-white">
                        <h5 class="modal-title"><i class="bi bi-key me-2"></i>Change Password</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">New Password</label>
                            <input type="password" name="password" class="form-control" required minlength="8">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

</body>
</html>
