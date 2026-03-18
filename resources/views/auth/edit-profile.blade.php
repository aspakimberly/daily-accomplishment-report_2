<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Edit Profile' }}</title>
    <link rel="stylesheet" href="{{ asset('css/edit-profile.css') }}?v={{ filemtime(public_path('css/edit-profile.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="profile-page">
        <div class="page-label">Edit Profile</div>

        <main class="profile-shell">
            @include('components.topbar', ['active' => '', 'canAccessAudit' => $canAccessAudit, 'user' => $user])

            <form method="POST" action="{{ route('profile.update') }}" class="profile-update-shell" enctype="multipart/form-data">
                @csrf

                <section class="profile-summary">
                    <div class="identity-card">
                        <label class="avatar-upload">
                            @if ($profileImageUrl)
                                <img src="{{ $profileImageUrl }}" alt="{{ $user->name }}" class="avatar-preview" data-avatar-preview>
                            @else
                                <div class="avatar-mark" data-avatar-placeholder>
                                    {{ strtoupper(collect(explode(' ', $user->name))->filter()->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('')) }}
                                </div>
                            @endif
                            <input type="file" name="profile_image" accept="image/*" class="hidden-file-input" data-avatar-input>
                            <span class="upload-badge">Upload Photo</span>
                        </label>

                        <div class="identity-copy">
                            <strong>{{ $user->name }}</strong>
                            <label class="signature-button">
                                Upload Signature
                                <input type="file" name="signature_image" accept="image/*" class="hidden-file-input" data-signature-input>
                            </label>
                            @if ($signatureImageUrl)
                                <img src="{{ $signatureImageUrl }}" alt="Signature of {{ $user->name }}" class="signature-preview" data-signature-preview>
                            @endif
                        </div>
                    </div>

                    <button type="button" class="signout-button" data-open-signout-modal>Sign Out</button>
                </section>

                <section class="profile-content">
                <h1>Edit profile</h1>
                <p class="section-title">Personal Information</p>

                @if (session('profile_status'))
                    <p class="flash-message flash-success">{{ session('profile_status') }}</p>
                @endif

                @if ($errors->any())
                    <p class="flash-message flash-error">{{ $errors->first() }}</p>
                @endif

                <div class="profile-form">
                    <div class="two-column">
                        <label class="field-block">
                            <span>First Name</span>
                            <input type="text" name="first_name" value="{{ $firstName }}" required>
                        </label>

                        <label class="field-block">
                            <span>Last Name</span>
                            <input type="text" name="last_name" value="{{ $lastName }}" required>
                        </label>
                    </div>

                    <label class="field-block field-block-wide">
                        <span>Position</span>
                        <select name="position">
                            <option value="">Position</option>
                            @foreach ($positionOptions as $option)
                                <option value="{{ $option }}" {{ $position === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field-block field-block-wide">
                        <span>Project</span>
                        <select name="project">
                            <option value="">Project</option>
                            @foreach ($projectOptions as $option)
                                <option value="{{ $option }}" {{ $project === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field-block field-block-wide">
                        <span>Bureau</span>
                        <select name="bureau">
                            <option value="">Bureau</option>
                            @foreach ($bureauOptions as $option)
                                <option value="{{ $option }}" {{ $bureau === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
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
                <a href="{{ route('logout') }}" class="modal-button modal-button-primary signout-confirm-link">Confirm</a>
            </div>
        </section>
    </div>

    <script src="{{ asset('js/profile.js') }}" defer></script>
</body>
</html>

