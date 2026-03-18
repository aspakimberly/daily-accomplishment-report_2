<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification</title>
    <link rel="stylesheet" href="{{ asset('css/verify-otp.css') }}?v={{ filemtime(public_path('css/verify-otp.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page">
        <div class="overlay"></div>

        <div class="content">
            <section class="left-panel" aria-label="Department information">
                <div class="logos">
                    <img src="{{ asset('images/dict_logo.png') }}" alt="DICT Logo">
                    <img src="{{ asset('images/bagong_pilipinas.png') }}" alt="Bagong Pilipinas Logo">
                </div>

                <h1 class="office-title">
                    Department of Information and<br>
                    Communications Technology<br>
                    Provincial Office 1
                </h1>
            </section>

            <div class="divider" aria-hidden="true"></div>

            <section class="right-panel" aria-label="Email verification form">
                <div class="form-shell">
                    <h2 class="page-title">Email Verification</h2>
                    <p class="page-copy">A verification code has been sent to your registered email address.</p>

                    @if (session('status'))
                        <p class="feedback feedback-success">{{ session('status') }}</p>
                    @endif

                    @if (session('error'))
                        <p class="feedback feedback-error">{{ session('error') }}</p>
                    @endif

                    @if ($errors->any())
                        <p class="feedback feedback-error">{{ $errors->first() }}</p>
                    @endif

                    <form method="POST" action="{{ route('auth.verify') }}" class="verify-form" data-otp-form>
                        @csrf
                        <input type="hidden" name="email" value="{{ old('email', $email) }}">
                        <input type="hidden" name="otp" value="{{ old('otp') }}" data-otp-hidden>

                        @php
                            $otpDigits = array_pad(str_split((string) old('otp', '')), 6, '');
                        @endphp

                        <div class="otp-group" role="group" aria-label="Enter 6 digit verification code">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    maxlength="1"
                                    class="otp-input"
                                    value="{{ $otpDigits[$i] ?? '' }}"
                                    data-otp-input
                                    {{ $i === 0 ? 'autofocus' : '' }}
                                >
                            @endfor
                        </div>

                        <div class="form-meta">
                            <div class="meta-left">
                                <span class="timer" data-otp-timer>1:30</span>
                                <button type="submit" form="resend-otp-form" class="resend-link" data-resend-button disabled>Resend OTP</button>
                            </div>

                            <button type="submit" class="verify-button">
                                Verify
                                <span aria-hidden="true">&rsaquo;</span>
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('auth.resend-otp') }}" id="resend-otp-form" class="resend-form">
                        @csrf
                    </form>
                </div>
            </section>
        </div>

        <footer class="footer">&copy; DICT PO1 2026. All Rights Reserved</footer>
    </div>

    <script>
        window.otpConfig = {
            resendAvailableAt: @json($resendAvailableAt),
        };
    </script>
    <script src="{{ asset('js/verify-otp.js') }}" defer></script>
</body>
</html>

