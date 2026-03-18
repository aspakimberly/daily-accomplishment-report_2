<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}?v={{ filemtime(public_path('css/dashboard.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-page">
        <main class="dashboard-shell">
            @include('components.topbar', [
                'active' => $mode === 'reports' ? 'reports' : 'dashboard',
                'canAccessAudit' => $canAccessAudit,
                'user' => $user,
            ])

            <section class="dashboard-content">
                @if (session('user_status'))
                    <p class="flash-message flash-success">{{ session('user_status') }}</p>
                @endif

                @if (session('user_error'))
                    <p class="flash-message flash-error">{{ session('user_error') }}</p>
                @endif

                @if ($errors->any())
                    <p class="flash-message flash-error">{{ $errors->first() }}</p>
                @endif

                @if ($mode === 'dashboard')
                    <section class="stats-grid" aria-label="Dashboard summary">
                        @foreach ($stats as $stat)
                            <a href="{{ $stat['route'] }}" class="stat-card stat-card-{{ $stat['tone'] }}">
                                <div class="stat-copy">
                                    <span class="stat-label">{{ $stat['label'] }}</span>
                                    <div class="stat-number-row">
                                        <strong>{{ $stat['count'] }}</strong>
                                        <span>Users</span>
                                    </div>
                                    <span class="stat-meta">{{ $stat['meta'] }}</span>
                                </div>
                                <div class="stat-icon stat-icon-{{ $stat['tone'] }}" aria-hidden="true">
                                    <span class="stat-illustration stat-illustration-{{ $stat['tone'] }}">
                                        @if ($stat['key'] === 'users')
                                            <span class="ill-avatar"></span>
                                            <span class="ill-shoulders"></span>
                                        @elseif ($stat['key'] === 'archive')
                                            <span class="ill-lock-body"></span>
                                            <span class="ill-lock-shackle"></span>
                                            <span class="ill-lock-dot"></span>
                                        @else
                                            <span class="ill-ring"></span>
                                            <span class="ill-center"></span>
                                        @endif
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </section>
                @else
                    <div class="section-header">
                        <a href="{{ route('dashboard') }}" class="back-link" aria-label="Back to dashboard">&lt;</a>
                        <div>
                            <h1>{{ $title }}</h1>
                            <div class="section-subcopy">
                                <span class="user-badge-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5a1 1 0 0 0 2 0c0-1.45 2.61-3 6-3s6 1.55 6 3a1 1 0 0 0 2 0c0-2.76-3.58-5-8-5Z"/></svg>
                                </span>
                                <span>
                                    {{ $mode === 'archive' ? $counts['archive'] : ($mode === 'active' ? $counts['active'] : $counts['users']) }}
                                    {{ $mode === 'archive' ? 'archived users' : 'users' }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endif

                <section class="table-panel">
                    <div class="table-toolbar">
                        <div class="toolbar-main-actions">
                            <form method="GET" action="{{ url()->current() }}" class="search-filter-bar" data-search-filter-form>
                                <div class="search-form">
                                    <input type="search" name="search" value="{{ $search }}" placeholder="Search users, email, bureau, or position" aria-label="Search users" data-live-search>
                                    <button type="submit" aria-label="Search">
                                        <svg viewBox="0 0 24 24"><path d="M10 4a6 6 0 1 0 3.87 10.59l4.27 4.27a1 1 0 0 0 1.42-1.42l-4.27-4.27A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1-4 4 4 4 0 0 1 4-4Z"/></svg>
                                    </button>
                                </div>
                                <div class="toolbar-filter-actions">
                                    <label class="filter-select">
                                        <span>{{ $filterLabel }}</span>
                                        <select name="filter" aria-label="Filter users by role" data-live-filter>
                                            <option value="">All roles</option>
                                            @foreach ($filterOptions as $filterValue => $filterText)
                                                <option value="{{ $filterValue }}" {{ $filter === $filterValue ? 'selected' : '' }}>{{ $filterText }}</option>
                                            @endforeach
                                        </select>
                                    </label>

                                    <button type="submit" class="filter-button">Apply</button>

                                    @if ($search !== '' || $filter !== '')
                                        <a href="{{ url()->current() }}" class="filter-reset">Reset</a>
                                    @endif
                                </div>
                            </form>
                        </div>

                        @if ($canManageUsers && $mode !== 'reports')
                            <button type="button" class="add-button" data-open-user-modal aria-label="Add user">
                                <span class="add-button-icon" aria-hidden="true">+</span>
                                <span>Add User</span>
                            </button>
                        @endif
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th>{{ $mode === 'reports' ? 'Role' : 'Project' }}</th>
                                    <th>{{ $mode === 'reports' ? 'Status' : 'Bureau' }}</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $listedUser)
                                    @php
                                        $initials = strtoupper(collect(explode(' ', $listedUser->name))->filter()->map(fn ($part) => substr($part, 0, 1))->take(2)->implode(''));
                                        $avatarUrl = $listedUser->avatar_path ? route('media.public', ['path' => ltrim($listedUser->avatar_path, '/')]) : null;
                                        $editPayload = [
                                            'id' => $listedUser->id,
                                            'name' => $listedUser->name,
                                            'email' => $listedUser->email,
                                            'position' => $listedUser->position,
                                            'project' => $listedUser->project,
                                            'bureau' => $listedUser->bureau,
                                            'division' => $listedUser->division,
                                            'office' => $listedUser->office,
                                            'institution' => $listedUser->institution,
                                            'role' => $listedUser->role,
                                        ];
                                    @endphp
                                    <tr>
                                        <td>
                                            @if ($avatarUrl)
                                                <img src="{{ $avatarUrl }}" alt="{{ $listedUser->name }}" class="avatar-badge avatar-badge-image">
                                            @else
                                                <div class="avatar-badge">{{ $initials }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $listedUser->name }}</td>
                                        <td>{{ $listedUser->email }}</td>
                                        <td>{{ $listedUser->position ?: 'N/A' }}</td>
                                        <td>{{ $mode === 'reports' ? str_replace('_', ' ', $listedUser->role) : ($listedUser->project ?: $listedUser->division ?: 'N/A') }}</td>
                                        <td>{{ $mode === 'reports' ? ucfirst($listedUser->status) : ($listedUser->bureau ?: $listedUser->office ?: 'N/A') }}</td>
                                        <td>
                                            <div class="action-icons">
                                                @if ($canManageUsers)
                                                    @if ($listedUser->status === 'active')
                                                        <form id="archive-user-{{ $listedUser->id }}" method="POST" action="{{ route('dashboard.users.archive', $listedUser) }}">
                                                            @csrf
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="row-action"
                                                            data-confirm-trigger
                                                            data-form-id="archive-user-{{ $listedUser->id }}"
                                                            data-confirm-title="Archive User Account?"
                                                            data-confirm-message="{{ $listedUser->name }} will no longer have access to the system. You can restore this account at any time from the Archive Users."
                                                        >
                                                            <svg viewBox="0 0 24 24"><path d="M12 5c5.23 0 9.27 4.62 10 6-.73 1.38-4.77 6-10 6S2.73 12.38 2 11c.73-1.38 4.77-6 10-6Zm0 2.5A3.5 3.5 0 1 0 15.5 11 3.5 3.5 0 0 0 12 7.5Zm-7.71 10.29 14-14 1.42 1.42-14 14Z"/></svg>
                                                        </button>
                                                    @else
                                                        <form id="restore-user-{{ $listedUser->id }}" method="POST" action="{{ route('dashboard.users.restore', $listedUser) }}">
                                                            @csrf
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="row-action row-action-restore"
                                                            data-confirm-trigger
                                                            data-form-id="restore-user-{{ $listedUser->id }}"
                                                            data-confirm-title="Restore User Account?"
                                                            data-confirm-message="{{ $listedUser->name }} will regain access to the system. You can archive the account again at any time."
                                                        >
                                                            <svg viewBox="0 0 24 24"><path d="M12 5a7 7 0 1 1-6.92 8H3l2.75-3L8.5 13H6.94A5 5 0 1 0 12 7a4.94 4.94 0 0 0-3.13 1.1L7.45 6.68A7 7 0 0 1 12 5Z"/></svg>
                                                        </button>
                                                    @endif
                                                @endif

                                                @if ($canManageUsers && $mode !== 'reports')
                                                    <button type="button" class="row-action row-action-edit" data-open-user-modal data-mode="edit" data-user='@json($editPayload)'>
                                                        <svg viewBox="0 0 24 24"><path d="m4 16.25 9.19-9.19 3.75 3.75L7.75 20H4Zm13.71-9.04a1 1 0 0 0 0-1.42l-1.5-1.5a1 1 0 0 0-1.42 0l-.89.89 3.75 3.75Z"/></svg>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="empty-state">No records found for the current filter.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    @if ($canManageUsers)
        <div class="modal-backdrop {{ $errors->any() ? 'is-visible' : '' }}" data-user-modal>
            <section class="user-modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
                <header class="user-modal-header">
                    <h2 id="user-modal-title" data-user-modal-title>Create New User</h2>
                </header>

                <form
                    method="POST"
                    action="{{ route('dashboard.users.store') }}"
                    class="user-form"
                    data-user-form
                    data-store-action="{{ route('dashboard.users.store') }}"
                    data-update-template="{{ url('/dashboard/users/__USER__') }}"
                >
                    @csrf
                    <input type="hidden" name="_method" value="" data-user-form-method>

                    <div class="role-options">
                        @foreach ($userFormOptions as $roleValue => $config)
                            <label class="radio-option">
                                <input type="radio" name="role" value="{{ $roleValue }}" data-role-radio {{ $initialRole === $roleValue ? 'checked' : '' }}>
                                <span>{{ $config['label'] }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="field-stack">
                        <div class="field-row" data-field="name">
                            <input type="text" name="name" placeholder="Name" value="{{ old('name') }}" required>
                        </div>
                        <div class="field-row" data-field="email">
                            <input type="email" name="email" placeholder="Email" value="{{ old('email') }}" required>
                        </div>
                        <div class="field-row" data-field="position">
                            <input type="text" name="position" placeholder="Position" value="{{ old('position') }}">
                        </div>
                        <div class="field-row" data-field="institution">
                            <input type="text" name="institution" placeholder="Institution" value="{{ old('institution') }}">
                        </div>
                        <div class="field-row" data-field="project">
                            <select name="project"><option value="">Project</option></select>
                        </div>
                        <div class="field-row" data-field="bureau">
                            <select name="bureau"><option value="">Bureau</option></select>
                        </div>
                        <div class="field-row" data-field="division">
                            <select name="division" data-division-select><option value="">Division</option></select>
                        </div>
                        <div class="field-row" data-field="office">
                            <select name="office"><option value="">Office</option></select>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="modal-button modal-button-secondary" data-close-user-modal>Cancel</button>
                        <button type="submit" class="modal-button modal-button-primary">Save</button>
                    </div>
                </form>
            </section>
        </div>
    @endif

    @include('components.confirm-modal')

    <script>
        window.dashboardConfig = {
            userFormOptions: @json($userFormOptions),
            initialRole: @json($initialRole),
            oldValues: {
                role: @json(old('role')),
                name: @json(old('name')),
                email: @json(old('email')),
                position: @json(old('position')),
                institution: @json(old('institution')),
                project: @json(old('project')),
                bureau: @json(old('bureau')),
                division: @json(old('division')),
                office: @json(old('office')),
            }
        };
    </script>
    <script src="{{ asset('js/dashboard.js') }}" defer></script>
    <script src="{{ asset('js/search-filter.js') }}" defer></script>
</body>
</html>



