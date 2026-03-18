<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/admin-dashboard.css') }}?v={{ filemtime(public_path('css/admin-dashboard.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-page">
        <main class="admin-shell">
            @include('components.topbar', ['active' => darIsSuperAdminRole($user->role) ? 'reports' : 'dashboard', 'canAccessAudit' => $canAccessAudit, 'user' => $user])

            <section class="admin-content">
                @if (in_array($mode, ['dashboard', 'reports'], true))
                    <section class="summary-grid">
                        <a href="{{ $reportRoutes['employees'] }}" class="summary-card summary-card-violet">
                            <div>
                                <span class="summary-label">Employees</span>
                                <div class="summary-value-row">
                                    <strong>{{ $counts['employees'] }}</strong>
                                    <span>Reports</span>
                                </div>
                                <p class="summary-meta">
                                    {{ $latestApprovedAt ? 'Last approved ' . \Illuminate\Support\Carbon::parse($latestApprovedAt)->format('M d, Y h:i A') : 'No approvals yet' }}
                                </p>
                            </div>
                            <div class="summary-icon summary-icon-violet">
                                <svg viewBox="0 0 24 24"><path d="M5 19h14v2H3V5h2Zm3-3.59 3.5-3.5 2.5 2.5L18.59 10 20 11.41 14 17.41l-2.5-2.5-2.09 2.09Z"/></svg>
                            </div>
                        </a>

                        <a href="{{ $reportRoutes['approved'] }}" class="summary-card summary-card-green">
                            <div>
                                <span class="summary-label">Approved</span>
                                <div class="summary-value-row">
                                    <strong>{{ $counts['approved'] }}</strong>
                                    <span>Reports</span>
                                </div>
                                <p class="summary-meta">
                                    {{ $latestApprovedAt ? '+1 Today' : 'No approved reports yet' }}
                                </p>
                            </div>
                            <div class="summary-icon summary-icon-green">
                                <svg viewBox="0 0 24 24"><path d="M20 7.5V17a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7.5l8 5.33Zm0-2.4L12 10.43 4 5.1V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2Z"/></svg>
                            </div>
                        </a>

                        <a href="{{ $reportRoutes['pending'] }}" class="summary-card summary-card-orange">
                            <div>
                                <span class="summary-label">Pending</span>
                                <div class="summary-value-row">
                                    <strong>{{ $counts['pending'] }}</strong>
                                    <span>Reports</span>
                                </div>
                                <p class="summary-meta">Waiting for review</p>
                            </div>
                            <div class="summary-icon summary-icon-orange">
                                <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm1 11h4v2h-6V7h2Z"/></svg>
                            </div>
                        </a>

                        <a href="{{ $reportRoutes['revisions'] }}" class="summary-card summary-card-red">
                            <div>
                                <span class="summary-label">For Revision</span>
                                <div class="summary-value-row">
                                    <strong>{{ $counts['revisions'] }}</strong>
                                    <span>Reports</span>
                                </div>
                                <p class="summary-meta">Need revision</p>
                            </div>
                            <div class="summary-icon summary-icon-red">
                                <svg viewBox="0 0 24 24"><path d="M12 8v5l3 3-1.4 1.4L10 13.4V8Zm0-6a10 10 0 1 0 10 10A10 10 0 0 0 12 2Z"/></svg>
                            </div>
                        </a>
                    </section>
                @else
                    <div class="section-header">
                        <a href="{{ $reportRoutes['back'] }}" class="back-link" aria-label="Back to dashboard">&lt;</a>
                        <div>
                            <h1>
                                {{ match($mode) {
                                    'employees' => 'Employees',
                                    'approved' => 'Approved',
                                    'pending' => 'Pending',
                                    'revisions' => 'For Revision',
                                    default => 'Dashboard',
                                } }}
                            </h1>
                            <p class="section-meta">
                                {{ match($mode) {
                                    'employees' => $counts['employees'] . ' reports',
                                    'approved' => $counts['approved'] . ' approved reports',
                                    'pending' => $counts['pending'] . ' pending reports',
                                    'revisions' => $counts['revisions'] . ' reports for revision',
                                    default => '',
                                } }}
                            </p>
                        </div>
                    </div>
                @endif

                <section class="table-panel">
                    @if (darIsSuperAdminRole($user->role))
                        <p class="monitor-label">Monitoring only. Super admin can view report activity but cannot modify report records here.</p>
                    @endif

                    <div class="table-toolbar">
                        <form method="GET" action="{{ url()->current() }}" class="search-filter-bar" data-search-filter-form>
                            <div class="date-filter-card">
                                <div class="date-filter-fields">
                                    <label class="date-field">
                                        <span>From Date</span>
                                        <input type="date" name="from_date" value="{{ $fromDate }}" aria-label="From Date" data-date-input>
                                    </label>

                                    <label class="date-field">
                                        <span>To Date</span>
                                        <input type="date" name="to_date" value="{{ $toDate }}" aria-label="To Date" data-date-input>
                                    </label>

                                    <input type="hidden" name="quick" value="{{ $quickFilter }}" data-quick-filter-input>

                                    <button type="submit" class="filter-button date-filter-apply">Apply Filter</button>
                                    <a href="{{ url()->current() }}" class="filter-reset date-filter-reset">Reset</a>
                                </div>

                                <div class="quick-filter-group" aria-label="Quick date filters">
                                    <span>Quick:</span>
                                    @foreach ($quickFilterOptions as $filterValue => $filterText)
                                        <button
                                            type="button"
                                            class="quick-filter-button {{ $quickFilter === $filterValue ? 'is-active' : '' }}"
                                            data-quick-filter-button
                                            data-quick-filter-value="{{ $filterValue }}"
                                        >
                                            {{ $filterText }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="search-form report-search-form">
                                <input type="search" name="search" value="{{ $search }}" placeholder="Search reports, file names, or staff" aria-label="Search reports" data-live-search>
                                <button type="submit" aria-label="Search">
                                    <svg viewBox="0 0 24 24"><path d="M10 4a6 6 0 1 0 3.87 10.59l4.27 4.27a1 1 0 0 0 1.42-1.42l-4.27-4.27A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1-4 4 4 4 0 0 1 4-4Z"/></svg>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>File Name</th>
                                    <th>Date Submitted</th>
                                    <th>Status</th>
                                    <th>Download</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($reports as $report)
                                    @php
                                        $initials = strtoupper(collect(explode(' ', $report->user_name ?? 'Un'))->filter()->map(fn ($part) => substr($part, 0, 1))->take(2)->implode(''));
                                        $avatarUrl = $report->user_avatar_path ? route('media.public', ['path' => ltrim($report->user_avatar_path, '/')]) : null;
                                        $statusClass = match($report->status) {
                                            'approved' => 'status-approved',
                                            'pending' => 'status-pending',
                                            'for_revision' => 'status-revision',
                                            default => 'status-default',
                                        };
                                        $submittedAt = $report->submitted_at ?: null;
                                    @endphp
                                    <tr>
                                        <td>
                                            @if ($avatarUrl)
                                                <img src="{{ $avatarUrl }}" alt="{{ $report->user_name ?: 'Unassigned User' }}" class="avatar-badge avatar-badge-image">
                                            @else
                                                <div class="avatar-badge">{{ $initials }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $report->user_name ?: 'Unassigned User' }}</td>
                                        <td>{{ $report->file_name ?: 'No file uploaded' }}</td>
                                        <td>{{ $submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->format('m/d/Y') : 'N/A' }}</td>
                                        <td><span class="status-pill {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($report->status ?? 'unknown')) }}</span></td>
                                        <td>
                                            @if ($report->file_path)
                                                <a href="{{ asset($report->file_path) }}" class="download-button" download>Export</a>
                                            @else
                                                <span class="download-button is-disabled">Export</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="empty-state">No reports found yet. Once staff submissions are stored, they will appear here.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>
    <script src="{{ asset('js/search-filter.js') }}" defer></script>
</body>
</html>




