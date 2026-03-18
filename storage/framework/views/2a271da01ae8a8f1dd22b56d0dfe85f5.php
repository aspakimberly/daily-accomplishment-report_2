<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($title ?? 'Edit Profile'); ?></title>
    <link rel="stylesheet" href="<?php echo e(asset('css/edit-profile.css')); ?>?v=<?php echo e(filemtime(public_path('css/edit-profile.css'))); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="profile-page">
        <div class="page-label">Edit Profile</div>

        <main class="profile-shell">
            <?php echo $__env->make('components.topbar', ['active' => '', 'canAccessAudit' => $canAccessAudit, 'user' => $user], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

            <form method="POST" action="<?php echo e(route('profile.update')); ?>" class="profile-update-shell" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>

                <section class="profile-summary">
                    <div class="identity-card">
                        <label class="avatar-upload">
                            <?php if($profileImageUrl): ?>
                                <img src="<?php echo e($profileImageUrl); ?>" alt="<?php echo e($user->name); ?>" class="avatar-preview" data-avatar-preview>
                            <?php else: ?>
                                <div class="avatar-mark" data-avatar-placeholder>
                                    <?php echo e(strtoupper(collect(explode(' ', $user->name))->filter()->map(fn ($part) => substr($part, 0, 1))->take(2)->implode(''))); ?>

                                </div>
                            <?php endif; ?>
                            <input type="file" name="profile_image" accept="image/*" class="hidden-file-input" data-avatar-input>
                            <span class="upload-badge">Upload Photo</span>
                        </label>

                        <div class="identity-copy">
                            <strong><?php echo e($user->name); ?></strong>
                            <label class="signature-button">
                                Upload Signature
                                <input type="file" name="signature_image" accept="image/*" class="hidden-file-input" data-signature-input>
                            </label>
                            <?php if($signatureImageUrl): ?>
                                <img src="<?php echo e($signatureImageUrl); ?>" alt="Signature of <?php echo e($user->name); ?>" class="signature-preview" data-signature-preview>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="button" class="signout-button" data-open-signout-modal>Sign Out</button>
                </section>

                <section class="profile-content">
                <h1>Edit profile</h1>
                <p class="section-title">Personal Information</p>

                <?php if(session('profile_status')): ?>
                    <p class="flash-message flash-success"><?php echo e(session('profile_status')); ?></p>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <p class="flash-message flash-error"><?php echo e($errors->first()); ?></p>
                <?php endif; ?>

                <div class="profile-form">
                    <div class="two-column">
                        <label class="field-block">
                            <span>First Name</span>
                            <input type="text" name="first_name" value="<?php echo e($firstName); ?>" required>
                        </label>

                        <label class="field-block">
                            <span>Last Name</span>
                            <input type="text" name="last_name" value="<?php echo e($lastName); ?>" required>
                        </label>
                    </div>

                    <label class="field-block field-block-wide">
                        <span>Position</span>
                        <select name="position">
                            <option value="">Position</option>
                            <?php $__currentLoopData = $positionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php echo e($position === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>

                    <label class="field-block field-block-wide">
                        <span>Project</span>
                        <select name="project">
                            <option value="">Project</option>
                            <?php $__currentLoopData = $projectOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php echo e($project === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>

                    <label class="field-block field-block-wide">
                        <span>Bureau</span>
                        <select name="bureau">
                            <option value="">Bureau</option>
                            <?php $__currentLoopData = $bureauOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($option); ?>" <?php echo e($bureau === $option ? 'selected' : ''); ?>><?php echo e($option); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="save-button">Save Changes</button>
                    </div>
                </div>
                </section>
            </form>
        </main>
    </div>

    <div class="confirm-backdrop" data-signout-modal>
        <section class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="signout-modal-title">
            <div class="signout-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M10 3a1 1 0 0 1 1 1v3a1 1 0 1 1-2 0V5H5v14h4v-2a1 1 0 1 1 2 0v3a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm6.29 4.29a1 1 0 0 1 1.42 0l4 4a1 1 0 0 1 0 1.42l-4 4a1 1 0 0 1-1.42-1.42L18.59 13H10a1 1 0 1 1 0-2h8.59l-2.3-2.29a1 1 0 0 1 0-1.42Z"/></svg>
            </div>
            <h2 id="signout-modal-title">Sign Out</h2>
            <p class="confirm-message">Please confirm if you wish to sign out from your current session.</p>

            <div class="confirm-actions">
                <button type="button" class="modal-button modal-button-secondary" data-close-signout-modal>Cancel</button>
                <a href="<?php echo e(route('logout')); ?>" class="modal-button modal-button-primary signout-confirm-link">Confirm</a>
            </div>
        </section>
    </div>

    <script src="<?php echo e(asset('js/profile.js')); ?>" defer></script>
</body>
</html>

<?php /**PATH C:\xampp_new\htdocs\daily_accomplishment_report\resources\views/auth/edit-profile.blade.php ENDPATH**/ ?>