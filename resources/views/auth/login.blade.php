<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        // Sama persis dengan logic layouts/admin.blade.php: ambil dari
        // System > Settings (App\Models\AppSetting), fallback ke
        // config('app.name') kalau belum pernah diisi. Dibungkus try/catch
        // supaya halaman login tidak ikut error kalau tabel app_settings
        // belum ter-migrate di server ini -- halaman login WAJIB tetap
        // bisa diakses apapun kondisi datanya.
        $__loginSetting = null;
        try {
            $__loginSetting = \App\Models\AppSetting::query()->first();
        } catch (\Throwable $e) {
            $__loginSetting = null;
        }
        $__loginAppName = $__loginSetting?->app_name ?: config('app.name', 'POS & Sales');
        $__loginLogoUrl = $__loginSetting?->logo_path ? $__loginSetting->logoUrl() : null;
        if (! $__loginLogoUrl) {
            $__loginLogoUrl = file_exists(public_path('images/logo.png')) ? asset('images/logo.png')
                : (file_exists(public_path('images/logo.svg')) ? asset('images/logo.svg') : null);
        }
    @endphp
    <title>{{ $__loginAppName }} - Login</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=nunito:400,600,700,800&display=swap" rel="stylesheet" />

    {{--
        CSS halaman login sengaja ditulis mandiri di sini (bukan Tailwind/
        Vite) supaya tampilannya tidak bergantung pada build asset, dan
        halaman auth lain (register, reset password, dst) yang masih
        memakai layouts/guest tidak ikut berubah.
    --}}
    <style>
        :root {
            --login-blue: #7b9ee8;
            --login-sky: #8ec9e8;
            --login-text: #2f3542;
            --login-muted: #9aa3b2;
            --login-border: #e2e6ee;
            --login-fill: #f6f8fc;
            --login-error: #d64545;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; margin: 0; }

        body {
            font-family: 'Nunito', ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
            color: var(--login-text);
            background: linear-gradient(160deg, #f7f9fd 0%, #eef2fa 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            -webkit-font-smoothing: antialiased;
        }

        .login-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 20px;
            padding: 40px 32px 32px;
            box-shadow: 0 10px 40px rgba(60, 80, 130, 0.10);
        }

        /* ---- Area logo ----
           Taruh file logo di public/images/logo.png (atau .svg) --
           otomatis tampil menggantikan placeholder ini. */
        .login-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 32px;
        }
        .login-logo img {
            max-width: 200px;
            max-height: 72px;
            object-fit: contain;
        }
        .login-logo-placeholder {
            width: 200px;
            height: 64px;
            border: 2px dashed #ccd3e0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.12em;
            color: #b3bccb;
        }

        .field { margin-bottom: 18px; }

        .field label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--login-muted);
            margin-bottom: 6px;
        }

        .input-wrap { position: relative; }

        .input-wrap input[type="email"],
        .input-wrap input[type="password"],
        .input-wrap input[type="text"] {
            width: 100%;
            height: 46px;
            padding: 0 16px;
            font-family: inherit;
            font-size: 15px;
            color: var(--login-text);
            background: var(--login-fill);
            border: 1px solid var(--login-border);
            border-radius: 10px;
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .input-wrap.has-toggle input { padding-right: 46px; }

        .input-wrap input:focus {
            background: #fff;
            border-color: var(--login-blue);
            box-shadow: 0 0 0 4px rgba(123, 158, 232, 0.18);
        }

        .input-wrap input.is-invalid { border-color: var(--login-error); }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            border-radius: 8px;
            color: #a6aebd;
            cursor: pointer;
        }
        .toggle-password:hover { color: var(--login-blue); }
        .toggle-password:focus-visible { outline: 2px solid var(--login-blue); }
        .toggle-password svg { width: 20px; height: 20px; }
        .toggle-password .icon-hide { display: none; }
        .toggle-password.is-visible .icon-show { display: none; }
        .toggle-password.is-visible .icon-hide { display: block; }

        .field-error {
            margin: 6px 2px 0;
            font-size: 13px;
            color: var(--login-error);
        }

        .row-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: -4px 0 24px;
            font-size: 13px;
        }

        .remember {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--login-muted);
            cursor: pointer;
            user-select: none;
        }
        .remember input {
            width: 16px;
            height: 16px;
            accent-color: var(--login-blue);
            cursor: pointer;
        }

        .forgot {
            color: var(--login-muted);
            text-decoration: underline;
        }
        .forgot:hover { color: var(--login-blue); }

        .btn-login {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 999px;
            font-family: inherit;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #fff;
            background: linear-gradient(90deg, var(--login-blue) 0%, var(--login-sky) 100%);
            box-shadow: 0 6px 18px rgba(123, 158, 232, 0.35);
            cursor: pointer;
            transition: transform .12s, box-shadow .12s, filter .12s;
        }
        .btn-login:hover { filter: brightness(1.04); box-shadow: 0 8px 22px rgba(123, 158, 232, 0.45); }
        .btn-login:active { transform: translateY(1px); }
        .btn-login:focus-visible { outline: 3px solid rgba(123, 158, 232, 0.5); outline-offset: 2px; }

        .status-box {
            margin-bottom: 18px;
            padding: 10px 14px;
            font-size: 13px;
            color: #2f7a4d;
            background: #edf8f1;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <main class="login-card">
        <div class="login-logo">
            @if ($__loginLogoUrl)
                <img src="{{ $__loginLogoUrl }}" alt="{{ $__loginAppName }}">
            @else
                <div class="login-logo-placeholder">{{ \Illuminate\Support\Str::limit(strtoupper($__loginAppName), 16, '') }}</div>
            @endif
        </div>

        @if (session('status'))
            <div class="status-box">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div class="field">
                <label for="email">{{ __('Email') }}</label>
                <div class="input-wrap">
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                           required autofocus autocomplete="username">
                </div>
                @error('email')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="password">{{ __('Password') }}</label>
                <div class="input-wrap has-toggle">
                    <input id="password" type="password" name="password"
                           class="{{ $errors->has('password') ? 'is-invalid' : '' }}"
                           required autocomplete="current-password">
                    <button type="button" class="toggle-password" id="toggle-password"
                            aria-label="Tampilkan password" aria-pressed="false">
                        {{-- ikon mata (tampilkan) --}}
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                        {{-- ikon mata dicoret (sembunyikan) --}}
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M17.94 17.94A10.9 10.9 0 0 1 12 19c-6.4 0-10-7-10-7a18.6 18.6 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A10.9 10.9 0 0 1 12 5c6.4 0 10 7 10 7a18.5 18.5 0 0 1-2.16 3.19"/>
                            <path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/>
                            <path d="M1 1l22 22"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="field-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="row-options">
                <label class="remember" for="remember_me">
                    <input id="remember_me" type="checkbox" name="remember">
                    {{ __('Remember me') }}
                </label>

                @if (Route::has('password.request'))
                    <a class="forgot" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
                @endif
            </div>

            <button type="submit" class="btn-login">{{ __('Sign in') }}</button>
        </form>
    </main>

    <script>
        (function () {
            var btn = document.getElementById('toggle-password');
            var input = document.getElementById('password');
            if (!btn || !input) return;

            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.classList.toggle('is-visible', show);
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
            });
        })();
    </script>
</body>
</html>
