<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>DICT Sign In</title>
  <link rel="stylesheet" href="<?php echo e(asset('css/sign.css')); ?>?v=<?php echo e(filemtime(public_path('css/sign.css'))); ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
  <div class="signin-page">
    <div class="overlay"></div>

    <main class="signin-layout">
      <section class="brand-panel" aria-label="Department information">
        <div class="logo-row">
          <div class="logo-glow">
            <img src="<?php echo e(asset('images/dict_logo.png')); ?>" alt="DICT Logo">
          </div>
          <div class="logo-glow">
            <img src="<?php echo e(asset('images/bagong_pilipinas.png')); ?>" alt="Bagong Pilipinas Logo">
          </div>
        </div>

        <h1 class="brand-title">
          Department of Information and<br>
          Communications Technology<br>
          Provincial Office 1
        </h1>
      </section>

      <div class="brand-divider" aria-hidden="true"></div>

      <section class="signin-panel" aria-label="Sign in form">
        <div class="signin-copy">
          <h2>Sign In</h2>

          <?php if(session('status')): ?>
            <p class="feedback feedback-success"><?php echo e(session('status')); ?></p>
          <?php endif; ?>

          <?php if(session('error')): ?>
            <p class="feedback feedback-error"><?php echo e(session('error')); ?></p>
          <?php endif; ?>

          <?php if($errors->has('email')): ?>
            <p class="feedback feedback-error"><?php echo e($errors->first('email')); ?></p>
          <?php endif; ?>
        </div>

        <form class="signin-form" method="POST" action="">
          <?php echo csrf_field(); ?>
          <input
            type="email"
            name="email"
            class="email-input"
            placeholder="Email"
            value="<?php echo e(old('email')); ?>"
            required
            autofocus
          >

          <button type="submit" class="otp-button">
            Send OTP
            <span aria-hidden="true">&rsaquo;</span>
          </button>
        </form>
      </section>
    </main>

    <footer class="signin-footer">&copy; DICT PO1 2026. All Rights Reserved</footer>
  </div>
</body>
</html>

<?php /**PATH C:\xampp_new\htdocs\daily_accomplishment_report\resources\views/super_admin/super_adminSignin.blade.php ENDPATH**/ ?>