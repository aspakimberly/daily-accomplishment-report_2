<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/audit-log.css') }}?v={{ filemtime(public_path('css/audit-log.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-page audit-page">
        <main class="dashboard-shell">
            @include('components.topbar', ['active' => 'audit', 'canAccessAudit' => $canAccessAudit, 'user' => $user])

            <section class="dashboard-content audit-content">
                <section class="audit-hero-card">
                    <div class="section-header">
                        <a href="{{ route('dashboard') }}" class="back-link" aria-label="Back to dashboard">&lt;</a>
                        <div>
                            <h1>Audit Logs</h1>
                            <p class="audit-page-desc">Track and monitor system activity with a cleaner review workflow for administrators.</p>
                        </div>
                    </div>
                    <div class="audit-hero-meta">
                        <span>Activity Monitor</span>
                        <strong>{{ $logs->count() }} visible records</strong>
                    </div>
                </section>

                <section class="audit-summary-grid" aria-label="Audit log summary">
                    <article class="audit-summary-card audit-summary-card-blue">
                        <div class="audit-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M4 5h16v2H4Zm0 6h16v2H4Zm0 6h10v2H4Z"/></svg>
                        </div>
                        <span class="audit-summary-label">Total Logs Today</span>
                        <strong>{{ $summary['totalToday'] }}</strong>
                        <small>Recent recorded activities</small>
                    </article>
                    <article class="audit-summary-card audit-summary-card-green">
                        <div class="audit-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="m9.55 18.2-4.9-4.9 1.4-1.4 3.5 3.5 8.4-8.4 1.4 1.4Z"/></svg>
                        </div>
                        <span class="audit-summary-label">Successful Logins</span>
                        <strong>{{ $summary['successfulLogins'] }}</strong>
                        <small>Authenticated sign-ins</small>
                    </article>
                    <article class="audit-summary-card audit-summary-card-sky">
                        <div class="audit-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5a1 1 0 0 0 2 0c0-1.45 2.61-3 6-3s6 1.55 6 3a1 1 0 0 0 2 0c0-2.76-3.58-5-8-5Z"/></svg>
                        </div>
                        <span class="audit-summary-label">Profile Updates</span>
                        <strong>{{ $summary['profileUpdates'] }}</strong>
                        <small>Account changes made</small>
                    </article>
                    <article class="audit-summary-card audit-summary-card-amber">
                        <div class="audit-summary-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 3 1 21h22Zm1 14h-2v-2h2Zm0-4h-2v-4h2Z"/></svg>
                        </div>
                        <span class="audit-summary-label">Warnings</span>
                        <strong>{{ $summary['warnings'] }}</strong>
                        <small>Review-worthy events</small>
                    </article>
                </section>

                <section class="audit-table-panel">
                    <form method="GET" action="{{ url()->current() }}" class="audit-filter-bar">
                        <div class="audit-filter-fields">
                            <div class="audit-search-field">
                                <span class="audit-search-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M10 4a6 6 0 1 0 3.87 10.59l4.27 4.27a1 1 0 0 0 1.42-1.42l-4.27-4.27A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1-4 4 4 4 0 0 1 4-4Z"/></svg>
                                </span>
                                <input type="search" name="search" value="{{ $search }}" placeholder="Search activity logs..." aria-label="Search activity logs">
                            </div>

                            <label class="audit-select-field">
                                <span>Role</span>
                                <select name="role" aria-label="Filter by role">
                                    <option value="">All Roles</option>
                                    @foreach ($availableRoles as $availableRole)
                                        <option value="{{ $availableRole }}" {{ $roleFilter === $availableRole ? 'selected' : '' }}>{{ darFormatRoleLabel($availableRole) }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="audit-select-field">
                                <span>Activity</span>
                                <select name="activity" aria-label="Filter by activity">
                                    <option value="">All Activities</option>
                                    @foreach ($availableActivities as $availableActivity)
                                        @php
                                            $rawValue = strtolower(str_replace(' ', '_', $availableActivity));
                                            $normalizedValue = match ($availableActivity) {
                                                'Create Record' => 'user_created',
                                                'Edit Record' => 'user_updated',
                                                'Archive Record' => 'user_archived',
                                                'Restore Record' => 'user_restored',
                                                'Profile Update' => 'profile_updated',
                                                'OTP Request' => 'otp_requested',
                                                default => $rawValue,
                                            };
                                        @endphp
                                        <option value="{{ $normalizedValue }}" {{ $activityFilter === $normalizedValue ? 'selected' : '' }}>{{ $availableActivity }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label class="audit-select-field">
                                <span>Date</span>
                                <select name="date" aria-label="Filter by date">
                                    <option value="">All Dates</option>
                                    <option value="today" {{ $dateFilter === 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="week" {{ $dateFilter === 'week' ? 'selected' : '' }}>This Week</option>
                                    <option value="month" {{ $dateFilter === 'month' ? 'selected' : '' }}>This Month</option>
                                </select>
                            </label>
                        </div>

                        <div class="audit-filter-actions">
                            <button type="submit" class="audit-apply-button">Apply Filters</button>
                            <a href="{{ url()->current() }}" class="audit-reset-button">Reset</a>
                        </div>
                    </form>

                    <div class="table-wrap">
                        <table class="audit-log-table">
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Description</th>
                                    <th>Role</th>
                                    <th>Date &amp; Time</th>
                                    <th>Status</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $index => $log)
                                    @php
                                        $detailsId = 'audit-details-' . $index;
                                    @endphp
                                    <tr class="audit-row">
                                        <td class="activity-cell">
                                            <span>{{ $log['activity_label'] }}</span>
                                            <span class="activity-icon" aria-hidden="true">{{ $log['activity_icon'] }}</span>
                                        </td>
                                        <td>{{ $log['description'] }}</td>
                                        <td>
                                            <div class="audit-user-cell">
                                                <strong>{{ darFormatRoleLabel($log['role']) }}</strong>
                                                <span>{{ $log['user_name'] }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $log['date_time'] }}</td>
                                        <td>
                                            <span class="audit-status-badge audit-status-{{ $log['status_tone'] }}">{{ $log['status'] }}</span>
                                        </td>
                                        <td>
                                            <button
                                                type="button"
                                                class="audit-detail-toggle"
                                                data-audit-toggle
                                                data-target="{{ $detailsId }}"
                                                aria-expanded="false"
                                                aria-controls="{{ $detailsId }}"
                                            >
                                                <span>Details</span>
                                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5z"/></svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr id="{{ $detailsId }}" class="audit-detail-row" hidden>
                                        <td colspan="6">
                                            <div class="audit-detail-card">
                                                <div class="audit-detail-grid">
                                                    <div><span>User</span><strong>{{ $log['user_name'] }}</strong></div>
                                                    <div><span>Role</span><strong>{{ darFormatRoleLabel($log['role']) }}</strong></div>
                                                    <div><span>Action</span><strong>{{ $log['activity_label'] }}</strong></div>
                                                    <div><span>Status</span><strong>{{ $log['status'] }}</strong></div>
                                                    <div><span>Date</span><strong>{{ $log['created_at'] ? $log['created_at']->format('M d, Y') : 'N/A' }}</strong></div>
                                                    <div><span>Time</span><strong>{{ $log['created_at'] ? $log['created_at']->format('h:i A') : 'N/A' }}</strong></div>
                                                    <div><span>IP Address</span><strong>{{ $log['ip_address'] }}</strong></div>
                                                    <div><span>Device</span><strong>{{ $log['device'] }}</strong></div>
                                                </div>
                                                <div class="audit-detail-description">
                                                    <span>Description</span>
                                                    <p>{{ $log['description'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">No audit logs available for the current filters.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <script src="{{ asset('js/audit-log.js') }}" defer></script>
</body>
</html>
