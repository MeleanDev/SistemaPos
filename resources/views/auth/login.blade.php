<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>DataVault System — Iniciar Sesión</title>

    <!-- PWA Head (Manifest, Icons, Mobile Meta) -->
    @include('Sistema.components.pwa-head')
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --dv-primary: #4f46e5;
            --dv-primary-hover: #4338ca;
            --dv-accent: #0ea5e9;
            --dv-bg: #090d16;
            --dv-surface: #0f172a;
            --dv-text-main: #0f172a;
            --dv-text-muted: #64748b;
        }

        * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: var(--dv-bg);
            background-image:
                radial-gradient(at 10% 10%, rgba(79, 70, 229, 0.22) 0px, transparent 55%),
                radial-gradient(at 90% 90%, rgba(14, 165, 233, 0.18) 0px, transparent 55%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.85) 0px, transparent 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 16px;
            color: #ffffff;
        }

        /* Contenedor Principal Flotante */
        .dv-auth-card {
            width: 100%;
            max-width: 1040px;
            background: #ffffff;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }

        @media (min-width: 992px) {
            .dv-auth-card {
                flex-direction: row;
                min-height: 620px;
            }
        }

        /* Panel Izquierdo / Brand Hero */
        .dv-brand-section {
            background: linear-gradient(150deg, #0b1329 0%, #0f172a 60%, #1e1b4b 100%);
            color: #ffffff;
            padding: 40px 36px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        @media (min-width: 992px) {
            .dv-brand-section {
                width: 44%;
                min-width: 44%;
                border-bottom: none;
                border-right: 1px solid rgba(255, 255, 255, 0.08);
                padding: 48px 40px;
            }
        }

        /* Efecto de luz ambiental en Brand */
        .dv-brand-section::after {
            content: '';
            position: absolute;
            bottom: -80px;
            right: -80px;
            width: 240px;
            height: 240px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.35) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Logo y Título */
        .dv-logo-box {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 24px;
        }

        .dv-brand-logo {
            width: 58px;
            height: 58px;
            min-width: 58px;
            border-radius: 16px;
            object-fit: cover;
            border: 2px solid rgba(14, 165, 233, 0.6);
            box-shadow: 0 8px 20px rgba(14, 165, 233, 0.35);
            background: #0b1120;
        }

        @media (min-width: 992px) {
            .dv-logo-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
                margin-bottom: 28px;
            }
            .dv-brand-logo {
                width: 76px;
                height: 76px;
                min-width: 76px;
                border-radius: 20px;
            }
        }

        .dv-brand-name {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            color: #ffffff;
            margin: 0;
        }

        @media (min-width: 992px) {
            .dv-brand-name {
                font-size: 1.85rem;
            }
        }

        .dv-brand-name span {
            color: #38bdf8;
        }

        .dv-brand-tagline {
            color: #cbd5e1;
            font-size: 0.90rem;
            line-height: 1.55;
            margin-bottom: 28px;
            font-weight: 400;
        }

        /* Características / Feature Pills (Alta Visibilidad) */
        .dv-features-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 24px;
        }

        .dv-feature-item {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .dv-feature-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(56, 189, 248, 0.35);
            transform: translateX(3px);
        }

        .dv-feature-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.4), rgba(14, 165, 233, 0.4));
            border: 1px solid rgba(56, 189, 248, 0.4);
            color: #38bdf8;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .dv-feature-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0;
            line-height: 1.25;
        }

        .dv-feature-sub {
            font-size: 0.74rem;
            color: #94a3b8;
            margin: 0;
            line-height: 1.25;
        }

        /* Status Badge */
        .dv-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            border-radius: 50px;
            color: #34d399;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .dv-status-indicator {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 8px #10b981;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Panel Derecho / Formulario */
        .dv-form-section {
            background: #ffffff;
            color: var(--dv-text-main);
            padding: 32px 24px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            flex: 1;
        }

        @media (min-width: 576px) {
            .dv-form-section {
                padding: 44px 38px;
            }
        }

        @media (min-width: 992px) {
            .dv-form-section {
                padding: 52px 48px;
            }
        }

        .dv-form-header {
            margin-bottom: 24px;
        }

        .dv-form-title {
            font-size: 1.65rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .dv-form-subtitle {
            color: #64748b;
            font-size: 0.88rem;
            margin: 0;
        }

        /* Inputs y Labels */
        .dv-label {
            font-size: 0.84rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dv-input-wrapper {
            position: relative;
            margin-bottom: 18px;
        }

        .dv-input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.95rem;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .dv-form-input {
            width: 100%;
            height: 48px;
            padding: 10px 14px 10px 42px;
            font-size: 0.92rem;
            font-weight: 600;
            color: #0f172a;
            background-color: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            transition: all 0.2s ease;
        }

        .dv-form-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }

        .dv-form-input:focus {
            background-color: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.12);
            outline: none;
        }

        .dv-input-wrapper:focus-within .dv-input-icon {
            color: #4f46e5;
        }

        .dv-pwd-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            padding: 6px 8px;
            cursor: pointer;
            border-radius: 8px;
            transition: color 0.2s ease;
            z-index: 5;
            font-size: 0.95rem;
        }

        .dv-pwd-toggle:hover {
            color: #4f46e5;
        }

        /* Checkbox */
        .dv-checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            user-select: none;
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin: 0;
        }

        .dv-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 5px;
            border: 1.5px solid #94a3b8;
            cursor: pointer;
            accent-color: #4f46e5;
        }

        /* Botón de Ingreso */
        .dv-btn-login {
            width: 100%;
            height: 50px;
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px -4px rgba(79, 70, 229, 0.45);
            transition: all 0.25s ease;
            cursor: pointer;
            margin-top: 10px;
        }

        .dv-btn-login:hover {
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
            transform: translateY(-2px);
            box-shadow: 0 12px 25px -4px rgba(79, 70, 229, 0.55);
            color: #ffffff;
        }

        .dv-btn-login:active {
            transform: translateY(0);
        }

        .dv-btn-login:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        /* Alerta de Error */
        .dv-error-card {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 12px 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: #991b1b;
            font-size: 0.85rem;
            line-height: 1.4;
        }

        /* Enlaces secundarios */
        .dv-link {
            color: #4f46e5;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.15s ease;
        }

        .dv-link:hover {
            color: #3730a3;
            text-decoration: underline;
        }

        /* PWA Install Button en Login */
        .dv-btn-pwa-login {
            background: #eff6ff;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
            border-radius: 50px;
            padding: 6px 14px;
            font-size: 0.78rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .dv-btn-pwa-login:hover {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>

<body>

    <div class="dv-auth-card">
        
        <!-- ============================================================ -->
        <!-- PANEL IZQUIERDO: BRANDING, STATUS & CARACTERÍSTICAS          -->
        <!-- ============================================================ -->
        <div class="dv-brand-section">
            <div>
                <!-- Topbar Branding: Status & Versión -->
                <div class="d-flex justify-content-between align-items-center mb-3 mb-lg-4">
                    <span class="dv-status-pill">
                        <span class="dv-status-indicator"></span> Sistema Conectado
                    </span>
                    <span class="badge rounded-pill font-monospace" style="background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; font-size: 0.72rem; padding: 5px 10px;">
                        v2.0 PWA
                    </span>
                </div>

                <!-- Logo & Título -->
                <div class="dv-logo-box">
                    <img src="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}" alt="DataVault System Logo" class="dv-brand-logo">
                    <div>
                        <h1 class="dv-brand-name">DATAVAULT <span>SYSTEM</span></h1>
                        <p class="text-white-50 small mb-0 d-none d-sm-block d-lg-none" style="font-size: 0.78rem;">Punto de Venta & Inventario</p>
                    </div>
                </div>

                <p class="dv-brand-tagline d-none d-lg-block">
                    Plataforma integral de Punto de Venta (POS), Control de Inventario Multi-Almacén y Gestión Empresarial Centralizada.
                </p>

                <!-- Píldoras de Valor Agregado (Visibles en pantallas medianas y grandes) -->
                <div class="dv-features-list d-none d-md-flex">
                    <div class="dv-feature-item">
                        <div class="dv-feature-icon">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <div>
                            <p class="dv-feature-title">Acceso Seguro & Roles</p>
                            <p class="dv-feature-sub">Sesiones protegidas y permisos por usuario.</p>
                        </div>
                    </div>

                    <div class="dv-feature-item">
                        <div class="dv-feature-icon">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <div>
                            <p class="dv-feature-title">Punto de Venta Multimoneda</p>
                            <p class="dv-feature-sub">Facturación ágil en Dólares ($) y Bolívares (Bs).</p>
                        </div>
                    </div>

                    <div class="dv-feature-item">
                        <div class="dv-feature-icon">
                            <i class="fas fa-boxes-stacked"></i>
                        </div>
                        <div>
                            <p class="dv-feature-title">Inventario & Kardex En Vivo</p>
                            <p class="dv-feature-sub">Control en tiempo real de stock y movimientos.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del Panel de Marca -->
            <div class="pt-3 border-top border-white border-opacity-10 d-flex justify-content-between align-items-center text-white-50 small">
                <span style="font-size: 0.75rem;">&copy; {{ date('Y') }} DataVault</span>
                <!-- Botón Instalar PWA si está disponible -->
                <button type="button" class="dv-btn-pwa-login d-none" data-pwa-install title="Instalar App POS">
                    <i class="fas fa-download"></i> Instalar App
                </button>
                <span class="d-none d-sm-inline" style="font-size: 0.75rem;"><i class="fas fa-lock text-info me-1"></i> SSL 256-Bit</span>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- PANEL DERECHO: FORMULARIO DE ACCESO                         -->
        <!-- ============================================================ -->
        <div class="dv-form-section">
            
            <div class="dv-form-header">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge rounded-circle d-inline-flex align-items-center justify-content-center" style="background-color: #eef2ff; color: #4f46e5; width: 32px; height: 32px;">
                        <i class="fas fa-key" style="font-size: 0.85rem;"></i>
                    </span>
                    <span class="fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.08em; color: #4f46e5;">Acceso Autorizado</span>
                </div>
                <h2 class="dv-form-title">Iniciar Sesión</h2>
                <p class="dv-form-subtitle">Ingresa tus credenciales para acceder al sistema.</p>
            </div>

            <!-- ALERTA DE ERRORES -->
            @if ($errors->any())
                <div class="dv-error-card">
                    <i class="fas fa-circle-exclamation fs-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <strong class="d-block mb-1">Credenciales incorrectas</strong>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- FORMULARIO -->
            <form method="POST" action="{{ route('login') }}" id="formLogin" onsubmit="alEnviarLogin(event)">
                @csrf

                <!-- CAMPO: IDENTIFICACIÓN / CÉDULA -->
                <div class="mb-3">
                    <label for="name" class="dv-label">
                        <i class="fas fa-id-card text-primary"></i> Identificación / Cédula / Usuario
                    </label>
                    <div class="dv-input-wrapper">
                        <i class="fas fa-user dv-input-icon"></i>
                        <input 
                            id="name" 
                            type="text" 
                            name="name" 
                            value="{{ old('name') }}" 
                            class="dv-form-input @error('name') is-invalid @enderror" 
                            placeholder="Ej. V-12345678 o usuario" 
                            required 
                            autofocus 
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- CAMPO: CONTRASEÑA -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="dv-label mb-0">
                            <i class="fas fa-lock text-primary"></i> Contraseña
                        </label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="dv-link small" style="font-size: 0.80rem;">
                                ¿Olvidaste tu contraseña?
                            </a>
                        @endif
                    </div>
                    <div class="dv-input-wrapper">
                        <i class="fas fa-shield-halved dv-input-icon"></i>
                        <input 
                            id="password" 
                            type="password" 
                            name="password" 
                            class="dv-form-input @error('password') is-invalid @enderror" 
                            placeholder="••••••••" 
                            required 
                            autocomplete="current-password"
                        >
                        <button type="button" class="dv-pwd-toggle" id="btnTogglePassword" onclick="togglePasswordVisibility()" title="Mostrar u ocultar contraseña">
                            <i class="fas fa-eye" id="iconoPassword"></i>
                        </button>
                    </div>
                </div>

                <!-- RECORDAR SESIÓN -->
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <label class="dv-checkbox-wrap">
                        <input type="checkbox" id="remember_me" name="remember" class="dv-checkbox">
                        <span>Recordar sesión en este equipo</span>
                    </label>
                </div>

                <!-- BOTÓN DE ENVÍO -->
                <button type="submit" id="btnSubmitLogin" class="dv-btn-login">
                    <span>Ingresar al Sistema</span>
                    <i class="fas fa-arrow-right-to-bracket"></i>
                </button>
            </form>

            <!-- FOOTER INFORMATIVO -->
            <div class="text-center mt-4 pt-3 border-top">
                <p class="text-muted small mb-0" style="font-size: 0.80rem;">
                    ¿Problemas para acceder? Contacta al administrador del sistema.
                </p>
            </div>

        </div>

    </div>

    <!-- Scripts Interactivos -->
    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = document.getElementById('iconoPassword');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function alEnviarLogin(e) {
            const btn = document.getElementById('btnSubmitLogin');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Verificando credenciales...';
        }
    </script>

    <!-- Componentes PWA Scripts (Service worker, install prompt, offline banner) -->
    @include('Sistema.components.pwa-scripts')

</body>

</html>
