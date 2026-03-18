<?php
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
?>

<header class="app-topbar">
    <div class="brand-group">
        <img src="<?php echo e(asset('images/dict_logo.png')); ?>" alt="DICT Logo" class="brand-logo">
        <img src="<?php echo e(asset('images/bagong_pilipinas.png')); ?>" alt="Bagong Pilipinas Logo" class="brand-logo">
    </div>

    <nav class="nav-links" aria-label="Primary">
        <a href="<?php echo e(route('dashboard.home')); ?>" class="<?php echo e($active === 'home' ? 'is-active' : ''); ?>">Home</a>
        <a href="<?php echo e(route('dashboard')); ?>" class="<?php echo e($active === 'dashboard' ? 'is-active' : ''); ?>">Dashboard</a>
        <?php if($isSuperAdminNavigation): ?>
            <a href="<?php echo e(route('reports.index')); ?>" class="<?php echo e($active === 'reports' ? 'is-active' : ''); ?>">Reports</a>
        <?php endif; ?>
        <?php if($canAccessAudit || $isAdminNavigation): ?>
            <a href="<?php echo e(route('audit.index')); ?>" class="<?php echo e($active === 'audit' ? 'is-active' : ''); ?>">Audit Log</a>
        <?php endif; ?>
    </nav>

    <div class="topbar-actions">
        <div class="notification-menu" data-notification-menu>
            <button type="button" class="icon-button notification-toggle" aria-label="Notifications" aria-expanded="false" data-notification-toggle>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-4h-1v-5.1a6 6 0 0 0-4.5-5.82V6a1.5 1.5 0 0 0-3 0v1.08A6 6 0 0 0 6 12.9V18H5a1 1 0 0 0 0 2h14a1 1 0 1 0 0-2Zm-3 0H8v-5.1a4 4 0 1 1 8 0Z"/>
                </svg>
                <?php if($canViewNotifications && $pendingNotificationsCount > 0): ?>
                    <span class="notification-badge"><?php echo e($pendingNotificationsCount > 99 ? '99+' : $pendingNotificationsCount); ?></span>
                <?php endif; ?>
            </button>

            <div class="notification-panel" data-notification-panel hidden>
                <div class="notification-panel-header">
                    <strong>Submission Alerts</strong>
                    <?php if($canViewNotifications): ?>
                        <a href="<?php echo e($notificationRoute); ?>">View all</a>
                    <?php endif; ?>
                </div>

                <?php if(! $canViewNotifications): ?>
                    <p class="notification-empty">Notifications are available for admin and super admin accounts.</p>
                <?php elseif($submissionNotifications->isEmpty()): ?>
                    <p class="notification-empty">No pending submissions right now.</p>
                <?php else: ?>
                    <div class="notification-list">
                        <?php $__currentLoopData = $submissionNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $submittedAt = $notification->submitted_at ?: $notification->created_at;
                            ?>
                            <a href="<?php echo e($notificationRoute); ?>" class="notification-item">
                                <span class="notification-dot" aria-hidden="true"></span>
                                <span class="notification-copy">
                                    <strong><?php echo e($notification->user_name ?: 'A user'); ?></strong>
                                    <span>submitted <?php echo e($notification->file_name ?: 'a report file'); ?></span>
                                    <small>
                                        <?php echo e($submittedAt ? \Illuminate\Support\Carbon::parse($submittedAt)->diffForHumans() : 'Just now'); ?>

                                    </small>
                                </span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo e(route('profile.edit')); ?>" class="icon-button" aria-label="Edit profile">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5a1 1 0 0 0 2 0c0-1.45 2.61-3 6-3s6 1.55 6 3a1 1 0 0 0 2 0c0-2.76-3.58-5-8-5Zm0-11a2 2 0 1 1-2 2 2 2 0 0 1 2-2Z"/>
            </svg>
        </a>
    </div>
</header>
<script src="<?php echo e(asset('js/topbar.js')); ?>" defer></script>
<?php /**PATH C:\xampp_new\htdocs\daily_accomplishment_report\resources\views/components/topbar.blade.php ENDPATH**/ ?>