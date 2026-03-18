<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/dashboard.css')); ?>?v=<?php echo e(filemtime(public_path('css/dashboard.css'))); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-page">
        <main class="dashboard-shell">
            <?php echo $__env->make('components.topbar', [
                'active' => $mode === 'reports' ? 'reports' : 'dashboard',
                'canAccessAudit' => $canAccessAudit,
                'user' => $user,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <section class="dashboard-content">
                <?php if(session('user_status')): ?>
                    <p class="flash-message flash-success"><?php echo e(session('user_status')); ?></p>
                <?php endif; ?>

                <?php if(session('user_error')): ?>
                    <p class="flash-message flash-error"><?php echo e(session('user_error')); ?></p>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <p class="flash-message flash-error"><?php echo e($errors->first()); ?></p>
                <?php endif; ?>

                <?php if($mode === 'dashboard'): ?>
                    <section class="stats-grid" aria-label="Dashboard summary">
                        <?php $__currentLoopData = $stats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($stat['route']); ?>" class="stat-card stat-card-<?php echo e($stat['tone']); ?>">
                                <div class="stat-copy">
                                    <span class="stat-label"><?php echo e($stat['label']); ?></span>
                                    <div class="stat-number-row">
                                        <strong><?php echo e($stat['count']); ?></strong>
                                        <span>Users</span>
                                    </div>
                                    <span class="stat-meta"><?php echo e($stat['meta']); ?></span>
                                </div>
                                <div class="stat-icon stat-icon-<?php echo e($stat['tone']); ?>" aria-hidden="true">
                                    <span class="stat-illustration stat-illustration-<?php echo e($stat['tone']); ?>">
                                        <?php if($stat['key'] === 'users'): ?>
                                            <span class="ill-avatar"></span>
                                            <span class="ill-shoulders"></span>
                                        <?php elseif($stat['key'] === 'archive'): ?>
                                            <span class="ill-lock-body"></span>
                                            <span class="ill-lock-shackle"></span>
                                            <span class="ill-lock-dot"></span>
                                        <?php else: ?>
                                            <span class="ill-ring"></span>
                                            <span class="ill-center"></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </section>
                <?php else: ?>
                    <div class="section-header">
                        <a href="<?php echo e(route('dashboard')); ?>" class="back-link" aria-label="Back to dashboard">&lt;</a>
                        <div>
                            <h1><?php echo e($title); ?></h1>
                            <div class="section-subcopy">
                                <span class="user-badge-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2.24-8 5a1 1 0 0 0 2 0c0-1.45 2.61-3 6-3s6 1.55 6 3a1 1 0 0 0 2 0c0-2.76-3.58-5-8-5Z"/></svg>
                                </span>
                                <span>
                                    <?php echo e($mode === 'archive' ? $counts['archive'] : ($mode === 'active' ? $counts['active'] : $counts['users'])); ?>

                                    <?php echo e($mode === 'archive' ? 'archived users' : 'users'); ?>

                                </span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <section class="table-panel">
                    <div class="table-toolbar">
                        <div class="toolbar-main-actions">
                            <form method="GET" action="<?php echo e(url()->current()); ?>" class="search-filter-bar" data-search-filter-form>
                                <div class="search-form">
                                    <input type="search" name="search" value="<?php echo e($search); ?>" placeholder="Search users, email, bureau, or position" aria-label="Search users" data-live-search>
                                    <button type="submit" aria-label="Search">
                                        <svg viewBox="0 0 24 24"><path d="M10 4a6 6 0 1 0 3.87 10.59l4.27 4.27a1 1 0 0 0 1.42-1.42l-4.27-4.27A6 6 0 0 0 10 4Zm0 2a4 4 0 1 1-4 4 4 4 0 0 1 4-4Z"/></svg>
                                    </button>
                                </div>
                                <div class="toolbar-filter-actions">
                                    <label class="filter-select">
                                        <span><?php echo e($filterLabel); ?></span>
                                        <select name="filter" aria-label="Filter users by role" data-live-filter>
                                            <option value="">All roles</option>
                                            <?php $__currentLoopData = $filterOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $filterValue => $filterText): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <option value="<?php echo e($filterValue); ?>" <?php echo e($filter === $filterValue ? 'selected' : ''); ?>><?php echo e($filterText); ?></option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                    </label>

                                    <button type="submit" class="filter-button">Apply</button>

                                    <?php if($search !== '' || $filter !== ''): ?>
                                        <a href="<?php echo e(url()->current()); ?>" class="filter-reset">Reset</a>
                                    <?php endif; ?>
                                </div>
                            </form>
                        </div>

                        <?php if($canManageUsers && $mode !== 'reports'): ?>
                            <button type="button" class="add-button" data-open-user-modal aria-label="Add user">
                                <span class="add-button-icon" aria-hidden="true">+</span>
                                <span>Add User</span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Position</th>
                                    <th><?php echo e($mode === 'reports' ? 'Role' : 'Project'); ?></th>
                                    <th><?php echo e($mode === 'reports' ? 'Status' : 'Bureau'); ?></th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $listedUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <?php
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
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if($avatarUrl): ?>
                                                <img src="<?php echo e($avatarUrl); ?>" alt="<?php echo e($listedUser->name); ?>" class="avatar-badge avatar-badge-image">
                                            <?php else: ?>
                                                <div class="avatar-badge"><?php echo e($initials); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo e($listedUser->name); ?></td>
                                        <td><?php echo e($listedUser->email); ?></td>
                                        <td><?php echo e($listedUser->position ?: 'N/A'); ?></td>
                                        <td><?php echo e($mode === 'reports' ? str_replace('_', ' ', $listedUser->role) : ($listedUser->project ?: $listedUser->division ?: 'N/A')); ?></td>
                                        <td><?php echo e($mode === 'reports' ? ucfirst($listedUser->status) : ($listedUser->bureau ?: $listedUser->office ?: 'N/A')); ?></td>
                                        <td>
                                            <div class="action-icons">
                                                <?php if($canManageUsers): ?>
                                                    <?php if($listedUser->status === 'active'): ?>
                                                        <form id="archive-user-<?php echo e($listedUser->id); ?>" method="POST" action="<?php echo e(route('dashboard.users.archive', $listedUser)); ?>">
                                                            <?php echo csrf_field(); ?>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="row-action"
                                                            data-confirm-trigger
                                                            data-form-id="archive-user-<?php echo e($listedUser->id); ?>"
                                                            data-confirm-title="Archive User Account?"
                                                            data-confirm-message="<?php echo e($listedUser->name); ?> will no longer have access to the system. You can restore this account at any time from the Archive Users."
                                                        >
                                                            <svg viewBox="0 0 24 24"><path d="M12 5c5.23 0 9.27 4.62 10 6-.73 1.38-4.77 6-10 6S2.73 12.38 2 11c.73-1.38 4.77-6 10-6Zm0 2.5A3.5 3.5 0 1 0 15.5 11 3.5 3.5 0 0 0 12 7.5Zm-7.71 10.29 14-14 1.42 1.42-14 14Z"/></svg>
                                                        </button>
                                                    <?php else: ?>
                                                        <form id="restore-user-<?php echo e($listedUser->id); ?>" method="POST" action="<?php echo e(route('dashboard.users.restore', $listedUser)); ?>">
                                                            <?php echo csrf_field(); ?>
                                                        </form>
                                                        <button
                                                            type="button"
                                                            class="row-action row-action-restore"
                                                            data-confirm-trigger
                                                            data-form-id="restore-user-<?php echo e($listedUser->id); ?>"
                                                            data-confirm-title="Restore User Account?"
                                                            data-confirm-message="<?php echo e($listedUser->name); ?> will regain access to the system. You can archive the account again at any time."
                                                        >
                                                            <svg viewBox="0 0 24 24"><path d="M12 5a7 7 0 1 1-6.92 8H3l2.75-3L8.5 13H6.94A5 5 0 1 0 12 7a4.94 4.94 0 0 0-3.13 1.1L7.45 6.68A7 7 0 0 1 12 5Z"/></svg>
                                                        </button>
                                                    <?php endif; ?>
                                                <?php endif; ?>

                                                <?php if($canManageUsers && $mode !== 'reports'): ?>
                                                    <button type="button" class="row-action row-action-edit" data-open-user-modal data-mode="edit" data-user='<?php echo json_encode($editPayload, 15, 512) ?>'>
                                                        <svg viewBox="0 0 24 24"><path d="m4 16.25 9.19-9.19 3.75 3.75L7.75 20H4Zm13.71-9.04a1 1 0 0 0 0-1.42l-1.5-1.5a1 1 0 0 0-1.42 0l-.89.89 3.75 3.75Z"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="7" class="empty-state">No records found for the current filter.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </main>
    </div>

    <?php if($canManageUsers): ?>
        <div class="modal-backdrop <?php echo e($errors->any() ? 'is-visible' : ''); ?>" data-user-modal>
            <section class="user-modal" role="dialog" aria-modal="true" aria-labelledby="user-modal-title">
                <header class="user-modal-header">
                    <h2 id="user-modal-title" data-user-modal-title>Create New User</h2>
                </header>

                <form
                    method="POST"
                    action="<?php echo e(route('dashboard.users.store')); ?>"
                    class="user-form"
                    data-user-form
                    data-store-action="<?php echo e(route('dashboard.users.store')); ?>"
                    data-update-template="<?php echo e(url('/dashboard/users/__USER__')); ?>"
                >
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="_method" value="" data-user-form-method>

                    <div class="role-options">
                        <?php $__currentLoopData = $userFormOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roleValue => $config): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <label class="radio-option">
                                <input type="radio" name="role" value="<?php echo e($roleValue); ?>" data-role-radio <?php echo e($initialRole === $roleValue ? 'checked' : ''); ?>>
                                <span><?php echo e($config['label']); ?></span>
                            </label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>

                    <div class="field-stack">
                        <div class="field-row" data-field="name">
                            <input type="text" name="name" placeholder="Name" value="<?php echo e(old('name')); ?>" required>
                        </div>
                        <div class="field-row" data-field="email">
                            <input type="email" name="email" placeholder="Email" value="<?php echo e(old('email')); ?>" required>
                        </div>
                        <div class="field-row" data-field="position">
                            <input type="text" name="position" placeholder="Position" value="<?php echo e(old('position')); ?>">
                        </div>
                        <div class="field-row" data-field="institution">
                            <input type="text" name="institution" placeholder="Institution" value="<?php echo e(old('institution')); ?>">
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
    <?php endif; ?>

    <?php echo $__env->make('components.confirm-modal', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <script>
        window.dashboardConfig = {
            userFormOptions: <?php echo json_encode($userFormOptions, 15, 512) ?>,
            initialRole: <?php echo json_encode($initialRole, 15, 512) ?>,
            oldValues: {
                role: <?php echo json_encode(old('role'), 15, 512) ?>,
                name: <?php echo json_encode(old('name'), 15, 512) ?>,
                email: <?php echo json_encode(old('email'), 15, 512) ?>,
                position: <?php echo json_encode(old('position'), 15, 512) ?>,
                institution: <?php echo json_encode(old('institution'), 15, 512) ?>,
                project: <?php echo json_encode(old('project'), 15, 512) ?>,
                bureau: <?php echo json_encode(old('bureau'), 15, 512) ?>,
                division: <?php echo json_encode(old('division'), 15, 512) ?>,
                office: <?php echo json_encode(old('office'), 15, 512) ?>,
            }
        };
    </script>
    <script src="<?php echo e(asset('js/dashboard.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/search-filter.js')); ?>" defer></script>
</body>
</html>



<?php /**PATH C:\xampp_new\htdocs\daily_accomplishment_report\resources\views/auth/dashboard.blade.php ENDPATH**/ ?>