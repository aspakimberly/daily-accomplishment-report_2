<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Home Page' }}</title>
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}?v={{ filemtime(public_path('css/homepage.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="page-shell">
        <div class="page-label">Home Page</div>

        <main class="home-card">
            @include('components.topbar', ['active' => 'home', 'canAccessAudit' => $canAccessAudit, 'user' => $user])

            <section class="hero-section">
                <div class="hero-copy">
                    <div class="headline-rule" aria-hidden="true"></div>
                    <div>
                        <h1>Daily Accomplishment<br>Records System</h1>
                        <p>
                            A web-based system that records, monitors, and summarizes the daily
                            accomplishments of DICT PO1 personnel for efficient reporting and
                            performance tracking.
                        </p>
                        <a href="{{ route('dashboard') }}" class="cta-button">Get started</a>
                    </div>
                </div>

                <div class="hero-visual">
                    <div class="ring ring-one" aria-hidden="true"></div>
                    <div class="ring ring-two" aria-hidden="true"></div>
                    <div class="photo-frame">
                        <img src="{{ asset('images/dictpang.jpg') }}" alt="DICT Office" loading="lazy">
                    </div>
                    <div class="shadow-bar" aria-hidden="true"></div>
                </div>
            </section>

            <section class="feature-grid" aria-label="Key features">
                <article class="feature-card">
                    <h2>Easy Report Submission</h2>
                    <p>Provides a user-friendly interface for recording daily accomplishments quickly and accurately.</p>
                </article>

                <article class="feature-card feature-card-accent">
                    <h2>Real-Time Monitoring</h2>
                    <p>Allows users and administrators to monitor submission status and pending approvals in real time.</p>
                </article>

                <article class="feature-card">
                    <h2>Automated Summary Reports</h2>
                    <p>Automatically compiles submitted data into organized weekly and monthly reports.</p>
                </article>

                <article class="feature-card feature-card-highlight">
                    <h2>Secure Access</h2>
                    <p>Implements role-based authentication to ensure authorized access and accountability.</p>
                </article>
            </section>
        </main>
    </div>
</body>
</html>

