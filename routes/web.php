<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

function darAuthenticatedUser(Request $request): ?User
{
    $userId = $request->session()->get('authenticated_user_id');

    if (!$userId) {
        return null;
    }

    try {
        return User::find($userId);
    } catch (QueryException) {
        return null;
    }
}

function darManagedRoles(): array
{
    return ['super_admin', 'hr-super-admin', 'admin', 'ph-admin', 'staff', 'special_access'];
}

function darSafeHasTable(string $table): bool
{
    static $cache = [];

    $key = 'table:' . $table;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        return $cache[$key] = Schema::hasTable($table);
    } catch (\Throwable) {
        return $cache[$key] = false;
    }
}

function darSafeHasColumn(string $table, string $column): bool
{
    static $cache = [];

    $key = 'column:' . $table . ':' . $column;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        return $cache[$key] = Schema::hasColumn($table, $column);
    } catch (\Throwable) {
        return $cache[$key] = false;
    }
}

function darDashboardRoute(?string $role): string
{
    return match ($role) {
        'super_admin', 'hr-super-admin' => 'dashboard.super-admin',
        'admin', 'ph-admin' => 'dashboard.admin',
        'staff', 'special_access' => 'dashboard.staff',
        default => 'login',
    };
}

function darCanManageUsers(?string $role): bool
{
    return in_array((string) $role, ['super_admin', 'hr-super-admin', 'admin', 'ph-admin'], true);
}

function darCanAccessAudit(?string $role): bool
{
    return in_array((string) $role, ['super_admin', 'hr-super-admin', 'admin', 'ph-admin'], true);
}

function darIsAdminRole(?string $role): bool
{
    return in_array((string) $role, ['admin', 'ph-admin'], true);
}

function darIsSuperAdminRole(?string $role): bool
{
    return in_array((string) $role, ['super_admin', 'hr-super-admin'], true);
}

function darEnsureAuthenticated(Request $request, ?callable $guard = null): RedirectResponse|User
{
    $user = darAuthenticatedUser($request);

    if (!$user || $user->status !== 'active') {
        $request->session()->forget([
            'authenticated_user_id',
            'otp_login_email',
            'otp_expires_at',
            'otp_requested_at',
        ]);

        return redirect()->route('login')->with('error', 'Please sign in to continue.');
    }

    if ($guard !== null && !$guard($user)) {
        return redirect()->route(darDashboardRoute($user->role));
    }

    return $user;
}

function darUserFormOptions(): array
{
    return [
        'hr-super-admin' => [
            'label' => 'HR - Super Admin',
            'fields' => ['name', 'email', 'position', 'project', 'bureau'],
            'projectOptions' => ['Di DigiGov', 'ILCDB', 'Cybersecurity', 'PNPKI', 'FW4A', 'IDB', 'MISS', 'NBP', 'GECS', 'DTC', 'SPARK', 'AFD'],
            'bureauOptions' => ['Regional Office', 'Provincial Office', 'Field Office', 'TCO', 'AFD'],
        ],
        'ph-admin' => [
            'label' => 'PH - Admin',
            'fields' => ['name', 'email', 'position', 'division', 'office'],
            'divisionOptions' => ['DigiGov', 'ILCDB', 'NPPB', 'Cybersecurity', 'PNPKI', 'ILCDB', 'MISS', 'NBP', 'GECS', 'OTC', 'SPARK', 'AFD'],
            'officeOptions' => ['Regional Office', 'Provincial Office', 'TOD', 'AFD'],
        ],
        'staff' => [
            'label' => 'Staff',
            'fields' => ['name', 'email', 'position', 'project', 'bureau', 'office'],
            'projectOptions' => ['DigiGov', 'ILCDB', 'Cybersecurity', 'PNPKI', 'FW4A', 'ILD', 'MISS', 'NBP', 'GECS', 'OTC', 'SPARK', 'AFD'],
            'bureauOptions' => ['Regional Office', 'Provincial Office', 'Field Office', 'TCO', 'AFD'],
            'officeOptions' => ['Regional Office', 'Provincial Office', 'Field Office', 'TCO', 'AFD'],
        ],
        'special_access' => [
            'label' => 'Special Access',
            'fields' => ['name', 'email', 'institution', 'division', 'office'],
            'divisionLabel' => 'Office Assigned',
            'divisionOptions' => ['DigiGov', 'ILCDB', 'NPPB', 'Cybersecurity', 'PNPKI', 'FW4A', 'ILB', 'MISS', 'NBP', 'GECS', 'DTC', 'SPARK', 'AFD'],
            'officeOptions' => ['Regional Office', 'Provincial Office', 'Field Office', 'TCD', 'AFD'],
        ],
    ];
}

function darStoreOtpForUser(User $user, Request $request): string
{
    $otp = (string) random_int(100000, 999999);
    $expiresAt = now()->addMinutes(5);

    $updates = ['otp_hash' => Hash::make($otp)];

    if (darSafeHasColumn('users', 'otp_expiration')) {
        $updates['otp_expiration'] = $expiresAt;
    }

    if (darSafeHasColumn('users', 'otp_code')) {
        $updates['otp_code'] = null;
    }

    $user->forceFill($updates)->save();

    $request->session()->put('otp_login_email', $user->email);
    $request->session()->put('otp_expires_at', $expiresAt->toIso8601String());
    $request->session()->put('otp_requested_at', now()->toIso8601String());

    return $otp;
}

function darSendOtpMail(User $user, string $otp): void
{
    Mail::raw(
        "Your login code is {$otp}\nThis code expires in 5 minutes.",
        static function ($message) use ($user): void {
            $message->to($user->email)->subject('Your DICT login code');
        }
    );
}

function darOtpCooldownRemaining(Request $request): int
{
    $requestedAt = $request->session()->get('otp_requested_at');

    if (!$requestedAt) {
        return 0;
    }

    $availableAt = Carbon::parse($requestedAt)->addSeconds(90);

    return now()->lt($availableAt) ? now()->diffInSeconds($availableAt) : 0;
}

function darManagedUsersQuery(string $search = '', ?string $scope = null, string $roleFilter = '')
{
    return User::query()
        ->whereIn('role', darManagedRoles())
        ->when($scope === 'archive', fn ($query) => $query->where('status', '!=', 'active'))
        ->when($scope === 'active', fn ($query) => $query->where('status', 'active'))
        ->when($roleFilter !== '', fn ($query) => $query->where('role', $roleFilter))
        ->when($search !== '', function ($query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function ($subQuery) use ($like) {
                $subQuery
                    ->where('name', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('position', 'like', $like)
                    ->orWhere('project', 'like', $like)
                    ->orWhere('bureau', 'like', $like)
                    ->orWhere('division', 'like', $like)
                    ->orWhere('office', 'like', $like)
                    ->orWhere('institution', 'like', $like);
            });
        });
}

function darBuildDashboardData(Request $request, User $user, string $mode = 'dashboard'): array
{
    $search = trim((string) $request->query('search', ''));
    $scope = match ($mode) {
        'archive' => 'archive',
        'active' => 'active',
        default => null,
    };
    $availableRoleFilters = [
        'staff' => 'Staff',
        'special_access' => 'Special Access',
        'admin' => 'Admin',
        'ph-admin' => 'PH Admin',
        'super_admin' => 'Super Admin',
        'hr-super-admin' => 'HR Super Admin',
    ];
    $roleFilter = trim((string) $request->query('filter', ''));

    if (!array_key_exists($roleFilter, $availableRoleFilters)) {
        $roleFilter = '';
    }

    $users = darManagedUsersQuery($search, $scope, $roleFilter)
        ->orderBy('name')
        ->get([
            'id',
            'name',
            'email',
            'avatar_path',
            'position',
            'project',
            'bureau',
            'division',
            'office',
            'institution',
            'role',
            'status',
        ]);

    $allUsersCount = darManagedUsersQuery()->count();
    $archiveCount = darManagedUsersQuery('', 'archive')->count();
    $activeCount = darManagedUsersQuery('', 'active')->count();

    return [
        'title' => match ($mode) {
            'users' => 'Users',
            'archive' => 'Archive Users',
            'active' => 'Active Users',
            'reports' => 'Reports',
            default => 'Dashboard',
        },
        'mode' => $mode,
        'user' => $user,
        'search' => $search,
        'filter' => $roleFilter,
        'filterLabel' => 'Role',
        'filterOptions' => $availableRoleFilters,
        'users' => $users,
        'counts' => [
            'users' => $allUsersCount,
            'archive' => $archiveCount,
            'active' => $activeCount,
        ],
        'stats' => [
            [
                'key' => 'users',
                'label' => 'Users',
                'count' => $allUsersCount,
                'meta' => 'Registered accounts',
                'tone' => 'purple',
                'route' => route('dashboard.users'),
            ],
            [
                'key' => 'archive',
                'label' => 'Archive',
                'count' => $archiveCount,
                'meta' => 'Archived accounts',
                'tone' => 'yellow',
                'route' => route('dashboard.archive'),
            ],
            [
                'key' => 'active',
                'label' => 'Active',
                'count' => $activeCount,
                'meta' => 'Accessible accounts',
                'tone' => 'green',
                'route' => route('dashboard.active'),
            ],
        ],
        'canManageUsers' => darCanManageUsers($user->role),
        'canAccessAudit' => darCanAccessAudit($user->role),
        'userFormOptions' => darUserFormOptions(),
        'initialRole' => old('role', 'hr-super-admin'),
    ];
}

function darLogActivity(?User $user, string $action, string $description): void
{
    if (!darSafeHasTable('activity_logs')) {
        return;
    }

    $payload = [
        'created_at' => now(),
        'updated_at' => now(),
    ];

    $optionalColumns = [
        'user_id' => $user?->id,
        'action' => $action,
        'event' => $action,
        'description' => $description,
        'details' => $description,
    ];

    foreach ($optionalColumns as $column => $value) {
        if (darSafeHasColumn('activity_logs', $column)) {
            $payload[$column] = $value;
        }
    }

    try {
        DB::table('activity_logs')->insert($payload);
    } catch (\Throwable) {
        // Ignore logging failures to avoid breaking the main flow.
    }
}

function darBuildProfileData(User $user): array
{
    $positionOptions = User::query()
        ->whereNotNull('position')
        ->where('position', '!=', '')
        ->distinct()
        ->orderBy('position')
        ->pluck('position')
        ->all();

    $projectOptions = collect(darUserFormOptions())
        ->pluck('projectOptions')
        ->flatten()
        ->filter()
        ->unique()
        ->values()
        ->all();

    $bureauOptions = collect(darUserFormOptions())
        ->pluck('bureauOptions')
        ->flatten()
        ->filter()
        ->unique()
        ->values()
        ->all();

    $nameParts = preg_split('/\s+/', trim($user->name ?? ''), 2) ?: ['', ''];
    $profileImageUrl = null;
    $signatureImageUrl = null;

    if ($user->avatar_path) {
        $profileImageUrl = route('media.public', ['path' => ltrim($user->avatar_path, '/')]);
    }

    if ($user->signature_path) {
        $signatureImageUrl = route('media.public', ['path' => ltrim($user->signature_path, '/')]);
    }

    return [
        'title' => 'Edit Profile',
        'user' => $user,
        'firstName' => old('first_name', $nameParts[0] ?? ''),
        'lastName' => old('last_name', $nameParts[1] ?? ''),
        'position' => old('position', $user->position),
        'project' => old('project', $user->project),
        'bureau' => old('bureau', $user->bureau),
        'positionOptions' => array_values(array_unique(array_filter(array_merge($positionOptions, [$user->position])))),
        'projectOptions' => array_values(array_unique(array_filter(array_merge($projectOptions, [$user->project])))),
        'bureauOptions' => array_values(array_unique(array_filter(array_merge($bureauOptions, [$user->bureau])))),
        'canAccessAudit' => darCanAccessAudit($user->role),
        'profileImageUrl' => $profileImageUrl,
        'signatureImageUrl' => $signatureImageUrl,
    ];
}

function darAuditActionMeta(string $action): array
{
    $normalized = strtolower(trim($action));

    return match ($normalized) {
        'login' => ['label' => 'Login', 'icon' => '🔐', 'status' => 'Success', 'tone' => 'success'],
        'logout' => ['label' => 'Logout', 'icon' => '🚪', 'status' => 'Info', 'tone' => 'info'],
        'otp_requested', 'otp_resent' => ['label' => 'OTP Request', 'icon' => '🔑', 'status' => 'Info', 'tone' => 'info'],
        'profile_updated' => ['label' => 'Profile Update', 'icon' => '👤', 'status' => 'Updated', 'tone' => 'updated'],
        'user_created' => ['label' => 'Create Record', 'icon' => '➕', 'status' => 'Success', 'tone' => 'success'],
        'user_updated' => ['label' => 'Edit Record', 'icon' => '✏️', 'status' => 'Updated', 'tone' => 'updated'],
        'user_archived' => ['label' => 'Archive Record', 'icon' => '🗑️', 'status' => 'Warning', 'tone' => 'warning'],
        'user_restored' => ['label' => 'Restore Record', 'icon' => '♻️', 'status' => 'Success', 'tone' => 'success'],
        default => ['label' => ucwords(str_replace('_', ' ', $normalized ?: 'activity')), 'icon' => '📋', 'status' => 'Info', 'tone' => 'info'],
    };
}

function darAuditDateFilterRange(string $value): array
{
    return match ($value) {
        'today' => [now()->startOfDay(), now()->endOfDay()],
        'week' => [now()->startOfWeek(), now()->endOfWeek()],
        'month' => [now()->startOfMonth(), now()->endOfMonth()],
        default => [null, null],
    };
}

function darFormatRoleLabel(?string $role): string
{
    $role = trim((string) $role);

    return $role === '' ? 'System' : ucwords(str_replace('_', ' ', str_replace('-', ' ', $role)));
}

function darAdminReportsQuery(string $search = '', ?string $status = null, string $dateFilter = '')
{
    return DB::table('reports')
        ->leftJoin('users', 'users.id', '=', 'reports.user_id')
        ->when($status !== null, fn ($query) => $query->where('reports.status', $status))
        ->when($dateFilter !== '', function ($query) use ($dateFilter) {
            $since = match ($dateFilter) {
                'today' => now()->startOfDay(),
                'week' => now()->subDays(7),
                'month' => now()->subDays(30),
                default => null,
            };

            if ($since) {
                $query->whereRaw('COALESCE(reports.submitted_at, reports.created_at) >= ?', [$since]);
            }
        })
        ->when($search !== '', function ($query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function ($subQuery) use ($like) {
                $subQuery
                    ->where('users.name', 'like', $like)
                    ->orWhere('reports.file_name', 'like', $like)
                    ->orWhere('reports.status', 'like', $like);
            });
        });
}

function darParseReportDate(?string $value, bool $endOfDay = false): ?Carbon
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    try {
        $date = Carbon::createFromFormat('Y-m-d', $value);
    } catch (\Throwable) {
        return null;
    }

    return $endOfDay ? $date->endOfDay() : $date->startOfDay();
}

function darResolveQuickDateRange(string $quickFilter): array
{
    $today = now();

    return match ($quickFilter) {
        'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
        'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()],
        'month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
        default => [null, null],
    };
}

function darAdminReportsDateRange(Request $request): array
{
    $availableQuickFilters = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
    ];
    $quickFilter = trim((string) $request->query('quick', ''));

    if (!array_key_exists($quickFilter, $availableQuickFilters)) {
        $quickFilter = '';
    }

    $fromDate = darParseReportDate($request->query('from_date'));
    $toDate = darParseReportDate($request->query('to_date'), true);

    if (($fromDate === null || $toDate === null) && $quickFilter !== '') {
        [$quickFrom, $quickTo] = darResolveQuickDateRange($quickFilter);
        $fromDate ??= $quickFrom;
        $toDate ??= $quickTo;
    }

    if ($fromDate && $toDate && $fromDate->gt($toDate)) {
        [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
    }

    return [
        'from' => $fromDate,
        'to' => $toDate,
        'fromInput' => $fromDate?->format('Y-m-d') ?? trim((string) $request->query('from_date', '')),
        'toInput' => $toDate?->format('Y-m-d') ?? trim((string) $request->query('to_date', '')),
        'quick' => $quickFilter,
        'quickOptions' => $availableQuickFilters,
    ];
}

function darBuildAdminDashboardData(Request $request, User $user, string $mode = 'dashboard'): array
{
    $search = trim((string) $request->query('search', ''));
    $status = match ($mode) {
        'approved' => 'approved',
        'pending' => 'pending',
        'revisions' => 'for_revision',
        default => null,
    };
    $dateRange = darAdminReportsDateRange($request);

    $reports = DB::table('reports')
        ->leftJoin('users', 'users.id', '=', 'reports.user_id')
        ->when($status !== null, fn ($query) => $query->where('reports.status', $status))
        ->when($dateRange['from'], fn ($query, $fromDate) => $query->whereRaw('COALESCE(reports.submitted_at, reports.created_at) >= ?', [$fromDate]))
        ->when($dateRange['to'], fn ($query, $toDate) => $query->whereRaw('COALESCE(reports.submitted_at, reports.created_at) <= ?', [$toDate]))
        ->when($search !== '', function ($query) use ($search) {
            $like = '%' . $search . '%';

            $query->where(function ($subQuery) use ($like) {
                $subQuery
                    ->where('users.name', 'like', $like)
                    ->orWhere('reports.file_name', 'like', $like)
                    ->orWhere('reports.status', 'like', $like);
            });
        })
        ->orderByDesc(DB::raw('COALESCE(reports.submitted_at, reports.created_at)'))
        ->get([
            'reports.id',
            'reports.file_name',
            'reports.status',
            'reports.file_path',
            'reports.submitted_at',
            'reports.reviewed_at',
            'users.name as user_name',
            'users.avatar_path as user_avatar_path',
        ]);

    $totalReports = darAdminReportsQuery()->count();
    $approvedReports = darAdminReportsQuery('', 'approved')->count();
    $pendingReports = darAdminReportsQuery('', 'pending')->count();
    $revisionReports = darAdminReportsQuery('', 'for_revision')->count();
    $latestApprovedAt = darAdminReportsQuery('', 'approved')->max('reports.reviewed_at');
    $routePrefix = darIsAdminRole($user->role) ? 'admin.dashboard' : 'reports';

    return [
        'title' => match ($mode) {
            'approved' => 'Approved Reports',
            'pending' => 'Pending Reports',
            'revisions' => 'Reports For Revision',
            'employees' => 'Employees Reports',
            'reports' => 'Reports',
            default => 'Admin Dashboard',
        },
        'mode' => $mode,
        'user' => $user,
        'search' => $search,
        'fromDate' => $dateRange['fromInput'],
        'toDate' => $dateRange['toInput'],
        'quickFilter' => $dateRange['quick'],
        'quickFilterOptions' => $dateRange['quickOptions'],
        'reports' => $reports,
        'counts' => [
            'employees' => $totalReports,
            'approved' => $approvedReports,
            'pending' => $pendingReports,
            'revisions' => $revisionReports,
        ],
        'latestApprovedAt' => $latestApprovedAt,
        'canAccessAudit' => darCanAccessAudit($user->role),
        'canManageReportRecords' => darIsAdminRole($user->role),
        'reportRoutes' => [
            'employees' => route($routePrefix . '.employees'),
            'approved' => route($routePrefix . '.approved'),
            'pending' => route($routePrefix . '.pending'),
            'revisions' => route($routePrefix . '.revisions'),
            'back' => darIsAdminRole($user->role) ? route('dashboard.admin') : route('reports.index'),
        ],
    ];
}

Route::get('/', function (Request $request) {
    return view('super_admin.super_adminSignin');
})->name('login');

Route::get('/media/public/{path}', function (Request $request, string $path) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');

    if (
        $normalizedPath === ''
        || str_contains($normalizedPath, '../')
        || str_starts_with($normalizedPath, '/')
        || !Storage::disk('public')->exists($normalizedPath)
    ) {
        abort(404);
    }

    return Storage::disk('public')->response($normalizedPath);
})->where('path', '.*')->name('media.public');

Route::post('/', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $email = strtolower(trim($validated['email']));

    try {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'active')
            ->whereIn('role', darManagedRoles())
            ->first();
    } catch (QueryException) {
        return redirect()
            ->route('login')
            ->with('error', 'Database connection failed. Update your MySQL credentials in the .env file and try again.');
    }

    if (!$user) {
        return redirect()
            ->route('login')
            ->with('status', 'If the email is registered and active, a login code has been sent.');
    }

    try {
        $otp = darStoreOtpForUser($user, $request);
        darSendOtpMail($user, $otp);
        darLogActivity($user, 'otp_requested', 'OTP requested for sign in.');
    } catch (\Throwable) {
        $user->forceFill([
            'otp_hash' => null,
            ...(darSafeHasColumn('users', 'otp_expiration') ? ['otp_expiration' => null] : []),
            ...(darSafeHasColumn('users', 'otp_code') ? ['otp_code' => null] : []),
        ])->save();

        $request->session()->forget(['otp_login_email', 'otp_expires_at', 'otp_requested_at']);

        return redirect()
            ->route('login')
            ->with('error', 'The login code could not be sent right now. Please try again.');
    }

    return redirect()
        ->route('auth.verify-form', ['email' => $user->email])
        ->with('status', 'If the email is registered and active, a login code has been sent.');
})->name('auth.send-otp');

Route::get('/verify-otp', function (Request $request) {
    $requestedAt = $request->session()->get('otp_requested_at');
    $resendAvailableAt = $requestedAt ? Carbon::parse($requestedAt)->addSeconds(90) : now();

    return view('auth.verify-otp', [
        'email' => old('email', $request->query('email', $request->session()->get('otp_login_email'))),
        'resendAvailableAt' => $resendAvailableAt->toIso8601String(),
    ]);
})->name('auth.verify-form');

Route::post('/verify-otp/resend', function (Request $request) {
    $email = $request->session()->get('otp_login_email');

    if (!$email) {
        return redirect()->route('login')->with('error', 'Please enter your email to request a new OTP.');
    }

    $remaining = darOtpCooldownRemaining($request);

    if ($remaining > 0) {
        return back()->with('error', 'Please wait ' . $remaining . ' seconds before resending the OTP.');
    }

    try {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->where('status', 'active')
            ->whereIn('role', darManagedRoles())
            ->first();
    } catch (QueryException) {
        return redirect()
            ->route('login')
            ->with('error', 'Database connection failed. Update your MySQL credentials in the .env file and try again.');
    }

    if (!$user) {
        return redirect()->route('login')->with('error', 'The requested account could not be found.');
    }

    try {
        $otp = darStoreOtpForUser($user, $request);
        darSendOtpMail($user, $otp);
        darLogActivity($user, 'otp_resent', 'OTP resent for sign in.');
    } catch (\Throwable) {
        return back()->with('error', 'The login code could not be resent right now. Please try again.');
    }

    return redirect()
        ->route('auth.verify-form', ['email' => $user->email])
        ->with('status', 'A new OTP code has been sent to your registered email.');
})->name('auth.resend-otp');

Route::post('/verify-otp', function (Request $request) {
    $validated = $request->validate([
        'email' => ['required', 'email'],
        'otp' => ['required', 'digits:6'],
    ]);

    $email = strtolower(trim($validated['email']));

    try {
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->where('status', 'active')
            ->whereIn('role', darManagedRoles())
            ->first();
    } catch (QueryException) {
        return redirect()
            ->route('login')
            ->with('error', 'Database connection failed. Update your MySQL credentials in the .env file and try again.');
    }

    if (!$user || !$user->otp_hash || !Hash::check($validated['otp'], $user->otp_hash)) {
        return back()->withErrors([
            'otp' => 'The OTP is invalid or has already been used.',
        ])->withInput();
    }

    $expiration = null;

    if (darSafeHasColumn('users', 'otp_expiration') && $user->otp_expiration) {
        $expiration = $user->otp_expiration;
    } elseif ($request->session()->has('otp_expires_at')) {
        $expiration = Carbon::parse($request->session()->get('otp_expires_at'));
    }

    if ($expiration && now()->greaterThan($expiration)) {
        $user->forceFill([
            'otp_hash' => null,
            ...(darSafeHasColumn('users', 'otp_expiration') ? ['otp_expiration' => null] : []),
        ])->save();

        return redirect()
            ->route('login')
            ->with('error', 'The OTP has expired. Please request a new code.');
    }

    $user->forceFill([
        'otp_hash' => null,
        ...(darSafeHasColumn('users', 'otp_expiration') ? ['otp_expiration' => null] : []),
        ...(darSafeHasColumn('users', 'otp_code') ? ['otp_code' => null] : []),
    ])->save();

    $request->session()->put('authenticated_user_id', $user->id);
    $request->session()->forget(['otp_login_email', 'otp_expires_at', 'otp_requested_at']);
    $request->session()->regenerate();

    darLogActivity($user, 'login', 'User signed in successfully.');

    return redirect()->route('dashboard.home');
})->name('auth.verify');

Route::get('/dashboard', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return redirect()->route(darDashboardRoute($user->role));
})->name('dashboard');

Route::get('/dashboard/home', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.homepage', [
        'title' => 'Home Page',
        'user' => $user,
        'canAccessAudit' => darCanAccessAudit($user->role),
    ]);
})->name('dashboard.home');

Route::get('/dashboard/super-admin', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => in_array($user->role, ['super_admin', 'hr-super-admin'], true));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.dashboard', darBuildDashboardData($request, $user, 'dashboard'));
})->name('dashboard.super-admin');

Route::get('/dashboard/admin', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => in_array($user->role, ['admin', 'ph-admin'], true));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'dashboard'));
})->name('dashboard.admin');

Route::get('/dashboard/admin/employees', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'employees'));
})->name('admin.dashboard.employees');

Route::get('/dashboard/admin/approved', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'approved'));
})->name('admin.dashboard.approved');

Route::get('/dashboard/admin/pending', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'pending'));
})->name('admin.dashboard.pending');

Route::get('/dashboard/admin/revisions', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'revisions'));
})->name('admin.dashboard.revisions');

Route::get('/dashboard/staff', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => in_array($user->role, ['staff', 'special_access'], true));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.dashboard', darBuildDashboardData($request, $user, 'dashboard'));
})->name('dashboard.staff');

Route::get('/dashboard/users', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.dashboard', darBuildDashboardData($request, $user, 'users'));
})->name('dashboard.users');

Route::get('/dashboard/archive', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.dashboard', darBuildDashboardData($request, $user, 'archive'));
})->name('dashboard.archive');

Route::get('/dashboard/active', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.dashboard', darBuildDashboardData($request, $user, 'active'));
})->name('dashboard.active');

Route::get('/reports', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsSuperAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'reports'));
})->name('reports.index');

Route::get('/reports/employees', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsSuperAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'employees'));
})->name('reports.employees');

Route::get('/reports/approved', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsSuperAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'approved'));
})->name('reports.approved');

Route::get('/reports/pending', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsSuperAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'pending'));
})->name('reports.pending');

Route::get('/reports/revisions', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darIsSuperAdminRole($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.admin-dashboard', darBuildAdminDashboardData($request, $user, 'revisions'));
})->name('reports.revisions');

Route::get('/audit-log', function (Request $request) {
    $user = darEnsureAuthenticated($request, fn (User $user) => darCanAccessAudit($user->role));

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    $search = trim((string) $request->query('search', ''));
    $roleFilter = trim((string) $request->query('role', ''));
    $activityFilter = trim((string) $request->query('activity', ''));
    $dateFilter = trim((string) $request->query('date', ''));
    $logs = collect();
    $availableRoles = [];
    $availableActivities = [];
    [$dateFrom, $dateTo] = darAuditDateFilterRange($dateFilter);

    if (darSafeHasTable('activity_logs')) {
        try {
            $baseQuery = DB::table('activity_logs')
                ->leftJoin('users', 'users.id', '=', 'activity_logs.user_id');

            $availableRoles = DB::table('activity_logs')
                ->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')
                ->selectRaw("COALESCE(users.role, activity_logs.role, '') as label")
                ->distinct()
                ->orderBy('label')
                ->pluck('label')
                ->filter()
                ->values()
                ->all();

            $availableActivities = DB::table('activity_logs')
                ->selectRaw("COALESCE(action, event, 'activity') as action_name")
                ->distinct()
                ->orderBy('action_name')
                ->pluck('action_name')
                ->map(fn ($action) => darAuditActionMeta((string) $action)['label'])
                ->unique()
                ->values()
                ->all();

            $query = (clone $baseQuery)
                ->when($search !== '', function ($query) use ($search) {
                    $like = '%' . $search . '%';

                    $query->where(function ($subQuery) use ($like) {
                        $subQuery
                            ->where('activity_logs.action', 'like', $like)
                            ->orWhere('activity_logs.event', 'like', $like)
                            ->orWhere('activity_logs.description', 'like', $like)
                            ->orWhere('activity_logs.details', 'like', $like)
                            ->orWhere('users.name', 'like', $like);
                    });
                })
                ->when($roleFilter !== '', function ($query) use ($roleFilter) {
                    $query->whereRaw("COALESCE(users.role, activity_logs.role, '') = ?", [$roleFilter]);
                })
                ->when($activityFilter !== '', function ($query) use ($activityFilter) {
                    $query->where(function ($subQuery) use ($activityFilter) {
                        if ($activityFilter === 'otp_requested') {
                            $subQuery
                                ->whereIn('activity_logs.action', ['otp_requested', 'otp_resent'])
                                ->orWhereIn('activity_logs.event', ['otp_requested', 'otp_resent']);

                            return;
                        }

                        $subQuery
                            ->where('activity_logs.action', $activityFilter)
                            ->orWhere('activity_logs.event', $activityFilter);
                    });
                })
                ->when($dateFrom, fn ($query, $from) => $query->where('activity_logs.created_at', '>=', $from))
                ->when($dateTo, fn ($query, $to) => $query->where('activity_logs.created_at', '<=', $to))
                ->orderByDesc('activity_logs.created_at')
                ->limit(100);

            $logs = collect($query->get([
                'activity_logs.user_id',
                'activity_logs.action',
                'activity_logs.event',
                'activity_logs.description',
                'activity_logs.details',
                'activity_logs.ip_address',
                'activity_logs.role',
                'activity_logs.created_at',
                'users.name as user_name',
                'users.role as user_role',
            ]))->map(function ($log) {
                $action = (string) ($log->action ?? $log->event ?? 'activity');
                $meta = darAuditActionMeta($action);
                $createdAt = $log->created_at ? Carbon::parse($log->created_at) : null;
                $userName = $log->user_name ?: ($log->user_id ? 'User #' . $log->user_id : 'System');

                return [
                    'action' => $action,
                    'activity' => $meta['icon'] . ' ' . $meta['label'],
                    'activity_icon' => $meta['icon'],
                    'activity_label' => $meta['label'],
                    'description' => $log->description ?? $log->details ?? 'System activity recorded.',
                    'status' => $meta['status'],
                    'status_tone' => $meta['tone'],
                    'created_at' => $createdAt,
                    'date_time' => $createdAt ? $createdAt->format('M d, Y') . ' • ' . $createdAt->format('h:i A') : 'N/A',
                    'user_id' => $log->user_id ?? null,
                    'user_name' => $userName,
                    'role' => $log->user_role ?: $log->role ?: 'N/A',
                    'ip_address' => $log->ip_address ?: 'Not recorded',
                    'device' => 'Not recorded',
                ];
            });
        } catch (\Throwable) {
            $logs = collect();
        }
    }

    $todayStart = now()->startOfDay();
    $summary = [
        'totalToday' => $logs->filter(fn ($log) => $log['created_at'] && $log['created_at']->gte($todayStart))->count(),
        'successfulLogins' => $logs->where('action', 'login')->count(),
        'profileUpdates' => $logs->where('action', 'profile_updated')->count(),
        'warnings' => $logs->where('status_tone', 'warning')->count(),
    ];

    return view('auth.audit-log', [
        'title' => 'Audit Log',
        'user' => $user,
        'logs' => $logs,
        'search' => $search,
        'roleFilter' => $roleFilter,
        'activityFilter' => $activityFilter,
        'dateFilter' => $dateFilter,
        'availableRoles' => $availableRoles,
        'availableActivities' => $availableActivities,
        'summary' => $summary,
        'canAccessAudit' => true,
    ]);
})->name('audit.index');

Route::post('/dashboard/users', function (Request $request) {
    $actor = darEnsureAuthenticated($request, fn (User $user) => darCanManageUsers($user->role));

    if ($actor instanceof RedirectResponse) {
        return $actor;
    }

    $formOptions = darUserFormOptions();

    $validated = $request->validate([
        'role' => ['required', 'in:' . implode(',', array_keys($formOptions))],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email'],
    ]);

    $selectedConfig = $formOptions[$validated['role']];
    $rules = [];

    foreach ($selectedConfig['fields'] as $field) {
        $rules[$field] = match ($field) {
            'position', 'institution' => ['required', 'string', 'max:255'],
            'project' => ['required', 'in:' . implode(',', $selectedConfig['projectOptions'])],
            'bureau' => ['required', 'in:' . implode(',', $selectedConfig['bureauOptions'])],
            'division' => ['required', 'in:' . implode(',', $selectedConfig['divisionOptions'])],
            'office' => ['required', 'in:' . implode(',', $selectedConfig['officeOptions'])],
            default => ['nullable'],
        };
    }

    $details = $request->validate($rules);

    User::query()->create([
        'name' => $validated['name'],
        'email' => strtolower(trim($validated['email'])),
        'password' => Hash::make((string) str()->random(24)),
        'position' => $details['position'] ?? null,
        'project' => $details['project'] ?? null,
        'bureau' => $details['bureau'] ?? null,
        'division' => $details['division'] ?? null,
        'office' => $details['office'] ?? null,
        'institution' => $details['institution'] ?? null,
        'role' => $validated['role'],
        'status' => 'active',
        'otp_code' => null,
        'otp_hash' => null,
        'otp_expiration' => null,
    ]);

    darLogActivity($actor, 'user_created', 'Created user account for ' . $validated['email'] . '.');

    return back()->with('user_status', 'New user created successfully.');
})->name('dashboard.users.store');

Route::put('/dashboard/users/{targetUser}', function (Request $request, User $targetUser) {
    $actor = darEnsureAuthenticated($request, fn (User $user) => darCanManageUsers($user->role));

    if ($actor instanceof RedirectResponse) {
        return $actor;
    }

    $formOptions = darUserFormOptions();

    $validated = $request->validate([
        'role' => ['required', 'in:' . implode(',', array_keys($formOptions))],
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $targetUser->id],
    ]);

    $selectedConfig = $formOptions[$validated['role']];
    $rules = [];

    foreach ($selectedConfig['fields'] as $field) {
        $rules[$field] = match ($field) {
            'position', 'institution' => ['required', 'string', 'max:255'],
            'project' => ['required', 'in:' . implode(',', $selectedConfig['projectOptions'])],
            'bureau' => ['required', 'in:' . implode(',', $selectedConfig['bureauOptions'])],
            'division' => ['required', 'in:' . implode(',', $selectedConfig['divisionOptions'])],
            'office' => ['required', 'in:' . implode(',', $selectedConfig['officeOptions'])],
            default => ['nullable'],
        };
    }

    $details = $request->validate($rules);

    $targetUser->forceFill([
        'name' => $validated['name'],
        'email' => strtolower(trim($validated['email'])),
        'position' => $details['position'] ?? null,
        'project' => $details['project'] ?? null,
        'bureau' => $details['bureau'] ?? null,
        'division' => $details['division'] ?? null,
        'office' => $details['office'] ?? null,
        'institution' => $details['institution'] ?? null,
        'role' => $validated['role'],
    ])->save();

    darLogActivity($actor, 'user_updated', 'Updated user account for ' . $targetUser->email . '.');

    return back()->with('user_status', 'User account updated successfully.');
})->name('dashboard.users.update');

Route::post('/dashboard/users/{targetUser}/archive', function (Request $request, User $targetUser) {
    $actor = darEnsureAuthenticated($request, fn (User $user) => darCanManageUsers($user->role));

    if ($actor instanceof RedirectResponse) {
        return $actor;
    }

    if ($targetUser->id === $actor->id) {
        return back()->with('user_error', 'You cannot archive your own account.');
    }

    $targetUser->forceFill(['status' => 'archived'])->save();
    darLogActivity($actor, 'user_archived', 'Archived user account for ' . $targetUser->email . '.');

    return back()->with('user_status', 'User archived successfully.');
})->name('dashboard.users.archive');

Route::post('/dashboard/users/{targetUser}/restore', function (Request $request, User $targetUser) {
    $actor = darEnsureAuthenticated($request, fn (User $user) => darCanManageUsers($user->role));

    if ($actor instanceof RedirectResponse) {
        return $actor;
    }

    $targetUser->forceFill(['status' => 'active'])->save();
    darLogActivity($actor, 'user_restored', 'Restored user account for ' . $targetUser->email . '.');

    return back()->with('user_status', 'User restored successfully.');
})->name('dashboard.users.restore');

Route::get('/profile/edit', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    return view('auth.edit-profile', darBuildProfileData($user));
})->name('profile.edit');

Route::post('/profile/edit', function (Request $request) {
    $user = darEnsureAuthenticated($request);

    if ($user instanceof RedirectResponse) {
        return $user;
    }

    $validated = $request->validate([
        'first_name' => ['required', 'string', 'max:255'],
        'last_name' => ['required', 'string', 'max:255'],
        'position' => ['nullable', 'string', 'max:255'],
        'project' => ['nullable', 'string', 'max:255'],
        'bureau' => ['nullable', 'string', 'max:255'],
        'profile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        'signature_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
    ]);

    $updates = [
        'name' => trim($validated['first_name'] . ' ' . $validated['last_name']),
        'position' => $validated['position'] ?: null,
        'project' => $validated['project'] ?: null,
        'bureau' => $validated['bureau'] ?: null,
    ];

    if ($request->hasFile('profile_image')) {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $updates['avatar_path'] = $request->file('profile_image')->store('profile-images', 'public');
    }

    if ($request->hasFile('signature_image')) {
        if ($user->signature_path) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $updates['signature_path'] = $request->file('signature_image')->store('signature-images', 'public');
    }

    $user->forceFill($updates)->save();

    darLogActivity($user, 'profile_updated', 'Updated personal profile.');

    return redirect()->route('profile.edit')->with('profile_status', 'Profile updated successfully.');
})->name('profile.update');

Route::match(['get', 'post'], '/logout', function (Request $request) {
    $user = darAuthenticatedUser($request);
    darLogActivity($user, 'logout', 'User signed out.');

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

Route::redirect('/login', '/');
Route::redirect('/signin', '/')->name('signin');
Route::redirect('/home', '/dashboard/home');
Route::redirect('/admin/login', '/')->name('admin.login');
Route::redirect('/super-admin/login', '/')->name('super_admin.superAdmin.login');
Route::redirect('/admin/verify-otp', '/verify-otp')->name('admin.verify-otp');
Route::redirect('/super-admin/verify-otp', '/verify-otp')->name('super_admin.superAdmin.verify-otp');
Route::redirect('/admin/dashboard', '/dashboard/admin')->name('admin.dashboard');
Route::redirect('/super-admin/dashboard', '/dashboard/super-admin')->name('super_admin.superAdmin.dashboard');


