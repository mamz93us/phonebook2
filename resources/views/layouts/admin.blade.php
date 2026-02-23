<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - {{ config('app.name', 'Phonebook') }}</title>

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
            <a class="navbar-brand fw-bold" href="/admin">📱 Admin Panel</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/branches*') ? 'active' : '' }}" href="/admin/branches">
                            Branches
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/contacts*') ? 'active' : '' }}" href="/admin/contacts">
                            Contacts
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/activity-logs*') ? 'active' : '' }}" href="/admin/activity-logs">
                            Activity Logs
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/phone-logs*') ? 'active' : '' }}" href="/admin/phone-logs">
                            Phone XML Logs
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/extensions*') ? 'active' : '' }}" href="/admin/extensions">
                            <i class="bi bi-telephone-fill me-1"></i>Extensions
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/trunks*') ? 'active' : '' }}" href="/admin/trunks">
                            <i class="bi bi-diagram-3-fill me-1"></i>Trunks
                        </a>
                    </li>

                    @can('manage-settings')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}" href="/admin/settings">
                            Settings
                        </a>
                    </li>
                    @endcan
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
                            @can('manage-users')
                            <li>
                                <a class="dropdown-item {{ request()->is('admin/users*') ? 'active' : '' }}"
                                   href="{{ route('admin.users.index') }}">
                                    <i class="bi bi-people me-2"></i>Manage Users
                                </a>
                            </li>
                            @endcan
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
