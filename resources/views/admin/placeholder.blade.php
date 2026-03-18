<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }}</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: Arial, sans-serif;
            background: #f4f7fb;
            color: #16324f;
        }

        main {
            width: min(560px, calc(100vw - 32px));
            padding: 32px;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 20px 50px rgba(12, 42, 76, 0.12);
        }

        h1 {
            margin: 0 0 12px;
            font-size: 28px;
        }

        p {
            margin: 0;
            line-height: 1.6;
        }

        .meta {
            margin-top: 14px;
            color: #4a6178;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <main>
        <h1>{{ $title ?? 'Admin' }}</h1>
        <p>{{ $message ?? 'This screen is temporarily unavailable.' }}</p>
        @if (!empty($email))
            <p class="meta">Pending account: {{ $email }}</p>
        @endif
    </main>
</body>
</html>
