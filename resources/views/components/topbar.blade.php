@php
    $active = $active ?? 'home';
    $canAccessAudit = $canAccessAudit ?? false;
    $isAdminNavigation = isset($user) && in_array($user->role, ['admin', 'ph-admin'], true);
    $isSuperAdminNavigation = isset($user) && in_array($user->role, ['super_admin', 'hr-super-admin'], true);
    $canViewNotifications = $isAdminNavigation || $isSuperAdminNavigation;
    $notificationRoute = $isSuperAdminNavigation ? route('reports.pending') : route('admin.dashboard.pending');
    $submissionNotifications = collect();
    $pendingNotificationsCount = 0;

    if ($canViewNotifications) {
        $submissionNotifications = \Illuminate\Support\Facades\DB::table('reports')
            ->leftJoin('users', 'users.id', '=', 'reports.user_id')
            ->where('reports.status', 'pending')
            ->orderByDesc(\Illuminate\Support\Facades\DB::raw('COALESCE(reports.submitted_at, reports.created_at)'))
            ->limit(6)
            ->get([
                'reports.id',
                'reports.file_name',
                'reports.submitted_at',
                'reports.created_at',
                'users.name as user_name',
            ]);

        $pendingNotificationsCount = \Illuminate\Support\Facades\DB::table('reports')
            ->where('status', 'pending')
            ->count();
    }
@endphp

<header class="app-topbar">
    <div class="brand-group">
        <img src="{{ asset('images/dict_logo.png') }}" alt="DICT Logo" class="brand-logo">
        <img src="{{ asset('images/bagong_pilipinas.png') }}" alt="Bagong Pilipinas Logo" class="brand-logo">
    </div>

    <nav class="nav-links" aria-label="Primary">
        <a href="{{ route('dashboard.home') }}" class="{{ $active === 'home' ? 'is-active' : '' }}">Home</a>
        <a href="{{ route('dashboard') }}" class="{{ $active === 'dashboard' ? 'is-active' : '' }}">Dashboard</a>
        @if ($isSuperAdminNavigation)
            <a href="{{ route('reports.index') }}" class="{{ $active === 'reports' ? 'is-active' : '' }}">Reports</a>
        @endif
        @if ($canAccessAudit || $isAdminNavigation)
            <a href="{{ route('audit.index') }}" class="{{ $active === 'audit' ? 'is-active' : '' }}">Audit Log</a>
        @endif
    </nav>

    <div class="topbar-actions">
        <div class="notification-menu" data-notification-menu>
            <button type="button" class="icon-button notification-toggle" aria-label="Notifications" aria-expanded="false" data-notification-toggle>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-4h-1v-5.1a6 6 0 0 0-4.5-5.82V6a1.5 1.5 0 0 0-3 0v1.08A6 6 0 0 0 6 12.9V18H5a1 1 0 0 0 0 2h14a1 1 0 1 0 0-2Zm-3 0H8v-5.1a4 4 0 1 1 8 0Z"/>
                </svg>
                @if ($canViewNotifications && $pendingNotificationsCount > 0)
                    <span class="notification-badge">{{ $pendingNotificationsCount > 99 ? '99+' : $pendingNotificationsCount }}</span>
                @endif
            </button>

            <div class="notification-panel" data-notification-panel hidden>
                <div class="notification-panel-header">
                    <strong>Submission Alerts</strong>
                    @if ($canViewNotifications)
                        <a href="{{ $notificationRoute }}">View all</a>
                    @endif
                </div>

                @if (! $canViewNotifications)
                    <p class="notification-empty">Notifications are available for admin and super admin accounts.</p>
                @elseif ($submissionNotifications->isEmpty())
                    <p class="notification-empty">No pending submissions right now.</p>
                @else
                    <div class="notification-list">
                        @foreach ($submissionNotifications as $notification)
                            @php
                                $submittedAt = $notification->submitted_at ?: $notification->created_at;
                            @endphp
                            <a href="{{ $notificationRoute }}" class="notification-item">
                                <span class="notification-dot" aria-hidden="true"></span>
                                <span class="notification-copy">
                                    <strong>{{ $notification->user_name ?: 'A user' }}</strong>
                                    <span>submitted {{ $notification->file_name ?: 'a report file' }}</span>
                                    <small>
                                        {{ $submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->diffForHumans() : 'Just now' }}
                                    </small>
                                </span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <a href="{{ route('profile.edit') }}" class="icon-button" aria-label="Edit profile">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5a1 1 0 0 0 2 0c0-1.45 2.61-3 6-3s6 1.55 6 3a1 1 0 0 0 2 0c0-2.76-3.58-5-8-5Zm0-11a2 2 0 1 1-2 2 2 2 0 0 1 2-2Z"/>
            </svg>
        </a>
    </div>
</header>
<script src="{{ asset('js/topbar.js') }}" defer></script>
