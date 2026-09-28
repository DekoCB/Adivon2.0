<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        <!-- Fonts: mismas que el resto del sistema (app-layout.blade.php) -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=bricolage-grotesque:600,700,800|hanken-grotesk:400,500,600,700|jetbrains-mono:400,500,600&display=swap" rel="stylesheet" />

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                /* Mismos colores que ya usa el resto del sistema: blue-900/950
                   de Tailwind para el azul institucional (sidebar, botones),
                   coral-600/700 del tailwind.config.js para el acento. */
                --navy: #1e3a8a;
                --navy-dark: #172554;
                --coral: #c6452e;
                --coral-dark: #a83a26;
            }
            *, *::before, *::after { box-sizing: border-box; }
            body {
                font-family: 'Hanken Grotesk', ui-sans-serif, system-ui, sans-serif;
                margin: 0;
                background: linear-gradient(160deg, var(--navy-dark), var(--navy) 70%);
                min-height: 100vh;
            }

            .auth-page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem 1.25rem;
            }

            .auth-card {
                width: 100%;
                max-width: 1040px;
                background: #fff;
                border-radius: 28px;
                overflow: hidden;
                box-shadow: 0 30px 80px -20px rgba(0,0,0,.5), 0 1px 0 rgba(255,255,255,.06) inset;
                display: grid;
                grid-template-columns: 1.05fr 0.95fr;
            }

            /* ── Panel izquierdo: formulario ──────────────────────── */
            .auth-form-panel {
                padding: 3rem 3rem;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }
            .auth-logo-small {
                display: block;
                height: 34px;
                width: auto;
                object-fit: contain;
                object-position: left;
                margin-bottom: 2.25rem;
            }
            .form-wrapper { width: 100%; max-width: 400px; margin: 0 auto; }
            .auth-footer {
                text-align: center;
                margin-top: 1.75rem;
                font-size: .76rem;
                color: #94a3b8;
            }

            /* ── Panel derecho: identidad visual ──────────────────── */
            .auth-art-panel {
                position: relative;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 3rem 2.5rem;
                background: linear-gradient(150deg, var(--navy-dark) 0%, var(--navy) 55%, var(--coral-dark) 130%);
                overflow: hidden;
                text-align: center;
            }
            /* Motivo de "techos" apilados — la misma silueta triangular del
               logo, repetida a gran escala como textura de fondo, en vez de
               un blob genérico sin relación con la marca. */
            .roof-shape {
                position: absolute;
                clip-path: polygon(50% 0%, 100% 85%, 0% 85%);
            }
            .roof-1 { width: 720px; height: 520px; top: -180px; left: -140px; background: rgba(255,255,255,.05); transform: rotate(-6deg); }
            .roof-2 { width: 520px; height: 380px; bottom: -160px; right: -120px; background: rgba(198,69,46,.28); transform: rotate(8deg); }
            .roof-3 { width: 340px; height: 250px; bottom: 10%; left: -80px; background: rgba(255,255,255,.04); transform: rotate(-4deg); }

            .auth-art-content { position: relative; z-index: 2; max-width: 360px; }
            .auth-logo-big {
                width: 100%;
                max-width: 300px;
                height: auto;
                object-fit: contain;
                margin: 0 auto 1.5rem;
                filter: drop-shadow(0 8px 24px rgba(0,0,0,.25));
            }
            .auth-art-tagline {
                font-family: 'Bricolage Grotesque', ui-sans-serif, sans-serif;
                font-size: 1.15rem;
                font-weight: 700;
                color: #fff;
                line-height: 1.35;
                margin: 0 0 .5rem;
            }
            .auth-art-sub {
                font-size: .85rem;
                color: rgba(255,255,255,.65);
                margin: 0;
            }

            /* ── Elementos de formulario compartidos (login/registro) ── */
            .auth-form-header { text-align: center; margin-bottom: 1.75rem; }
            .auth-form-header .welcome-icon {
                width: 52px; height: 52px;
                margin: 0 auto .9rem;
                border-radius: 14px;
                background: linear-gradient(135deg, var(--navy-dark), var(--navy));
                display: flex; align-items: center; justify-content: center;
                box-shadow: 0 4px 16px rgba(23,37,84,.25);
            }
            .auth-form-header .welcome-icon i { font-size: 1.3rem; color: #fff; }
            .auth-form-header h2 {
                font-family: 'Bricolage Grotesque', ui-sans-serif, sans-serif;
                font-size: 1.4rem; font-weight: 700; color: #0f172a; margin: 0 0 .3rem;
            }
            .auth-form-header p { font-size: .84rem; color: #64748b; margin: 0; }

            .input-group { margin-bottom: 1.1rem; }
            .input-group label {
                display: block;
                font-size: .78rem;
                font-weight: 600;
                color: #334155;
                margin-bottom: .35rem;
                letter-spacing: .02em;
            }
            .input-group .input-wrap { position: relative; }
            .input-group .input-wrap .input-icon {
                position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
                color: #94a3b8; font-size: .82rem; transition: color .2s; pointer-events: none;
            }
            .input-group .input-wrap input,
            .input-group .input-wrap select {
                width: 100%;
                padding: .7rem .875rem .7rem 2.7rem;
                border: 1.5px solid #e2e8f0;
                border-radius: 12px;
                font-size: .88rem;
                color: #0f172a;
                background: #f8fafc;
                transition: all .2s ease;
                outline: none;
                font-family: 'Hanken Grotesk', ui-sans-serif, sans-serif;
                appearance: none;
                -webkit-appearance: none;
            }
            .input-group .input-wrap select { padding-left: 2.7rem; }
            .input-group .input-wrap input:focus,
            .input-group .input-wrap select:focus {
                border-color: var(--navy);
                background: #fff;
                box-shadow: 0 0 0 3px rgba(30,58,138,.12);
            }
            .input-group .input-wrap input:focus ~ .input-icon,
            .input-group .input-wrap select:focus ~ .input-icon { color: var(--navy); }
            .input-group .input-wrap input::placeholder { color: #cbd5e1; }
            .input-group .hint { font-size: .72rem; color: #94a3b8; margin: .3rem 0 0; }
            .input-group .toggle-pw {
                position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
                background: none; border: none; cursor: pointer; color: #94a3b8;
                padding: 4px; font-size: .92rem; transition: color .2s;
            }
            .input-group .toggle-pw:hover { color: #475569; }

            .field-error { display: block; margin-top: .35rem; font-size: .74rem; color: #ef4444; }

            .extras-row {
                display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.4rem;
            }
            .remember-check { display: flex; align-items: center; gap: .4rem; cursor: pointer; }
            .remember-check input[type="checkbox"] {
                width: 16px; height: 16px; border-radius: 5px; border: 1.5px solid #cbd5e1;
                accent-color: var(--navy); cursor: pointer;
            }
            .remember-check span { font-size: .8rem; color: #64748b; }
            .forgot-link { font-size: .8rem; color: var(--navy); text-decoration: none; font-weight: 500; transition: color .2s; }
            .forgot-link:hover { color: var(--coral); text-decoration: underline; }

            .auth-btn {
                width: 100%;
                padding: .8rem;
                border: none;
                border-radius: 12px;
                font-family: 'Hanken Grotesk', ui-sans-serif, sans-serif;
                font-size: .89rem;
                font-weight: 600;
                color: #fff;
                background: linear-gradient(135deg, var(--navy-dark) 0%, var(--navy) 100%);
                cursor: pointer;
                transition: all .25s ease;
                box-shadow: 0 4px 12px rgba(23,37,84,.22);
                letter-spacing: .02em;
            }
            .auth-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 22px rgba(23,37,84,.3); }
            .auth-btn:active { transform: translateY(0); }
            .auth-btn i { margin-right: .5rem; }

            .auth-divider { display: flex; align-items: center; gap: .75rem; margin: 1.4rem 0; }
            .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e2e8f0; }
            .auth-divider span { font-size: .74rem; color: #94a3b8; font-weight: 500; }

            .auth-switch-row { text-align: center; }
            .auth-switch-row span { font-size: .82rem; color: #64748b; }
            .auth-switch-row a { font-size: .82rem; color: var(--navy); font-weight: 600; text-decoration: none; transition: color .2s; }
            .auth-switch-row a:hover { color: var(--coral); text-decoration: underline; }

            .auth-alert {
                display: flex; align-items: center; gap: .6rem;
                padding: .7rem 1rem; border-radius: 10px; margin-bottom: 1.2rem; font-size: .8rem;
                background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534;
            }
            .auth-alert.warn { background: #fefce8; border-color: #fde68a; color: #854d0e; }
            .auth-alert i { flex-shrink: 0; }

            /* ── Responsive ────────────────────────────────────────── */
            @media (max-width: 860px) {
                .auth-card { grid-template-columns: 1fr; max-width: 480px; }
                .auth-art-panel { display: none; }
                .auth-form-panel { padding: 2.5rem 1.75rem; }
                .auth-logo-small { height: 30px; margin: 0 auto 2rem; }
            }
        </style>
    </head>
    <body class="antialiased">
        @php $empresa = \App\Models\Empresa::instancia(); @endphp

        <div class="auth-page">
            <div class="auth-card">

                {{-- ── Panel izquierdo: formulario ── --}}
                <div class="auth-form-panel">
                    <div class="form-wrapper">
                        <img src="{{ asset('images/logo-transparente.png') }}" alt="{{ $empresa?->nombre_display ?? 'ADIVON' }}" class="auth-logo-small">

                        {{ $slot }}

                        <div class="auth-footer">
                            &copy; {{ date('Y') }} {{ $empresa?->nombre_display ?? 'Corporación Adivon SAC' }}. Todos los derechos reservados.
                        </div>
                    </div>
                </div>

                {{-- ── Panel derecho: identidad visual ── --}}
                <div class="auth-art-panel">
                    <div class="roof-shape roof-1"></div>
                    <div class="roof-shape roof-2"></div>
                    <div class="roof-shape roof-3"></div>

                    <div class="auth-art-content">
                        <img src="{{ asset('images/logo-blanco.png') }}" alt="{{ $empresa?->nombre_display ?? 'ADIVON' }}" class="auth-logo-big">
                        <p class="auth-art-tagline">Gestión total de tu operación, en un solo sistema.</p>
                        <p class="auth-art-sub">Ventas, compras, inventario y facturación electrónica SUNAT.</p>
                    </div>
                </div>

            </div>
        </div>
    </body>
</html>
