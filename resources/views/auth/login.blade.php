<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>DataVault System - Iniciar Sesión</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}">

    <style>
        :root {
            --dv-primary: #0284c7;
            --dv-primary-dark: #0369a1;
            --dv-indigo: #4f46e5;
            --dv-dark-bg: #0b1120;
            --dv-card-bg: #111827;
            --dv-border: rgba(255, 255, 255, 0.1);
            --dv-cyan-glow: rgba(14, 165, 233, 0.25);
        }

        * {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: var(--dv-dark-bg);
            background-image: 
                radial-gradient(at 15% 15%, rgba(14, 165, 233, 0.18) 0px, transparent 50%),
                radial-gradient(at 85% 85%, rgba(79, 70, 229, 0.18) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(15, 23, 42, 0.8) 0px, transparent 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px 15px;
            color: #f8fafc;
        }

        /* Contenedor Principal Flotante */
        .dv-auth-container {
            width: 100%;
            max-width: 1060px;
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7),
                        0 0 40px -10px rgba(14, 165, 233, 0.2);
            overflow: hidden;
        }

        /* Panel Izquierdo / Branding */
        .dv-brand-panel {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.95) 0%, rgba(11, 17, 32, 0.98) 100%);
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 45px 40px;
        }

        .dv-brand-panel::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.08) 0%, transparent 60%);
            pointer-events: none;
        }

        .dv-logo-wrapper {
            position: relative;
            display: inline-block;
            margin-bottom: 25px;
        }

        .dv-logo-img {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid rgba(14, 165, 233, 0.5);
            box-shadow: 0 0 35px rgba(14, 165, 233, 0.35),
                        0 10px 25px rgba(0, 0, 0, 0.5);
            background-color: #0b1120;
            transition: transform 0.4s ease, box-shadow 0.4s ease;
        }

        .dv-logo-img:hover {
            transform: scale(1.04) rotate(2deg);
            box-shadow: 0 0 45px rgba(14, 165, 233, 0.5),
                        0 12px 30px rgba(0, 0, 0, 0.6);
        }

        .dv-brand-title {
            font-size: 2.1rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.15;
            background: linear-gradient(135deg, #ffffff 40%, #bae6fd 80%, #38bdf8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }

        .dv-brand-subtitle {
            color: #94a3b8;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 30px;
        }

        /* Tarjetas de Beneficios / Pills */
        .dv-feature-pill {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 12px 16px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.25s ease;
        }

        .dv-feature-pill:hover {
            background: rgba(14, 165, 233, 0.08);
            border-color: rgba(14, 165, 233, 0.3);
            transform: translateX(4px);
        }

        .dv-feature-icon {
            width: 36px;
            height: 36px;
            min-width: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, rgba(14, 165, 233, 0.2) 0%, rgba(79, 70, 229, 0.2) 100%);
            border: 1px solid rgba(14, 165, 233, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #38bdf8;
            font-size: 1rem;
        }

        .dv-feature-text {
            font-size: 0.86rem;
            font-weight: 600;
            color: #e2e8f0;
            margin: 0;
            line-height: 1.3;
        }

        .dv-feature-desc {
            font-size: 0.76rem;
            color: #94a3b8;
            margin: 0;
        }

        /* Panel Derecho / Formulario */
        .dv-form-panel {
            background: #ffffff;
            padding: 48px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: #0f172a;
        }

        .dv-form-header {
            margin-bottom: 30px;
        }

        .dv-form-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 6px;
        }

        .dv-form-subtitle {
            color: #64748b;
            font-size: 0.90rem;
        }

        /* Campos de Entrada */
        .dv-label {
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            margin-bottom: 7px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dv-input-group {
            position: relative;
            margin-bottom: 20px;
        }

        .dv-input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .dv-input {
            width: 100%;
            height: 50px;
            padding: 10px 16px 10px 45px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #0f172a;
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            transition: all 0.25s ease;
        }

        .dv-input:focus {
            background-color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15);
            outline: none;
        }

        .dv-input:focus + .dv-input-icon,
        .dv-input-group:focus-within .dv-input-icon {
            color: #0284c7;
        }

        .dv-toggle-pwd {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            padding: 6px 8px;
            cursor: pointer;
            border-radius: 8px;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .dv-toggle-pwd:hover {
            color: #0284c7;
        }

        /* Botón de Ingreso */
        .dv-btn-submit {
            width: 100%;
            height: 52px;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 50%, #4f46e5 100%);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.45);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            margin-top: 10px;
        }

        .dv-btn-submit:hover {
            background: linear-gradient(135deg, #0369a1 0%, #1d4ed8 50%, #4338ca 100%);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px -5px rgba(37, 99, 235, 0.55);
            color: #ffffff;
        }

        .dv-btn-submit:active {
            transform: translateY(0);
            box-shadow: 0 6px 15px -3px rgba(37, 99, 235, 0.4);
        }

        .dv-btn-submit:disabled {
            opacity: 0.75;
            cursor: not-allowed;
            transform: none;
        }

        /* Checkbox Recordarme */
        .dv-checkbox-label {
            font-size: 0.86rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            user-select: none;
        }

        .dv-checkbox {
            width: 18px;
            height: 18px;
            border-radius: 6px;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            accent-color: #0284c7;
        }

        /* Alerta de Error */
        .dv-alert {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 22px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: #991b1b;
            font-size: 0.86rem;
            line-height: 1.4;
        }

        .dv-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
            border-radius: 50px;
            color: #4ade80;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }

        .dv-status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: #22c55e;
            box-shadow: 0 0 8px #22c55e;
            animation: pulse-dot 2s infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .dv-brand-panel {
                padding: 35px 25px;
                text-align: center;
                border-right: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            }
            .dv-logo-img {
                width: 110px;
                height: 110px;
            }
            .dv-brand-title {
                font-size: 1.8rem;
            }
            .dv-feature-pills-container {
                display: none;
            }
            .dv-form-panel {
                padding: 35px 24px;
            }
        }
    </style>
</head>

<body>

    <div class="dv-auth-container">
        <div class="row g-0">
            
            <!-- PANEL IZQUIERDO: BRANDING CORPORATIVO DATAVAULT -->
            <div class="col-lg-5 dv-brand-panel">
                <div>
                    <!-- Badge de Estado Activo -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <span class="dv-status-badge">
                            <span class="dv-status-dot"></span> Bóveda Activa
                        </span>
                        <span class="badge rounded-pill px-3 py-1 font-monospace" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); color: #94a3b8; font-size: 0.72rem;">
                            v2.0 Enterprise
                        </span>
                    </div>

                    <!-- Logo Circular Oficial de DataVault System -->
                    <div class="text-center text-lg-start dv-logo-wrapper">
                        <img src="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}" alt="DataVault System Logo" class="dv-logo-img">
                    </div>

                    <!-- Título y Eslogan -->
                    <h1 class="dv-brand-title">DATAVAULT<br><span style="color: #38bdf8;">SYSTEM</span></h1>
                    <p class="dv-brand-subtitle">
                        Sistema integral de Punto de Venta (POS), Control de Inventario Multi-Almacén y Gestión Empresarial Centralizada.
                    </p>

                    <!-- Píldoras de Valor Agregado / Características -->
                    <div class="dv-feature-pills-container">
                        <div class="dv-feature-pill">
                            <div class="dv-feature-icon">
                                <i class="fas fa-shield-halved"></i>
                            </div>
                            <div>
                                <p class="dv-feature-text">Seguridad Bóveda 256-Bit</p>
                                <p class="dv-feature-desc">Control de acceso por roles y permisos granulares.</p>
                            </div>
                        </div>

                        <div class="dv-feature-pill">
                            <div class="dv-feature-icon">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <div>
                                <p class="dv-feature-text">Punto de Venta Multimoneda</p>
                                <p class="dv-feature-desc">Facturación ágil en Dólares ($ USD) y Bolívares (Bs. VES).</p>
                            </div>
                        </div>

                        <div class="dv-feature-pill">
                            <div class="dv-feature-icon">
                                <i class="fas fa-boxes-stacked"></i>
                            </div>
                            <div>
                                <p class="dv-feature-text">Inventario & Kardex En Vivo</p>
                                <p class="dv-feature-desc">Control exacto de existencias y traslados por almacén.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pie del Panel Izquierdo -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex justify-content-between align-items-center text-muted small">
                    <span style="font-size: 0.74rem;">&copy; {{ date('Y') }} DataVault System</span>
                    <span style="font-size: 0.74rem;"><i class="fas fa-lock me-1 text-info"></i> Conexión Cifrada</span>
                </div>
            </div>

            <!-- PANEL DERECHO: FORMULARIO DE ACCESO -->
            <div class="col-lg-7 dv-form-panel">
                
                <div class="dv-form-header">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="background-color: #e0f2fe; color: #0284c7; width: 34px; height: 34px;">
                            <i class="fas fa-key"></i>
                        </span>
                        <span class="fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.08em; color: #0284c7;">Portal de Acceso</span>
                    </div>
                    <h2 class="dv-form-title">Bienvenido de Nuevo</h2>
                    <p class="dv-form-subtitle">Ingresa tus credenciales autorizadas para iniciar sesión en el sistema.</p>
                </div>

                <!-- ALERTA DE ERRORES / CREDENCIALES INVÁLIDAS -->
                @if ($errors->any())
                    <div class="dv-alert">
                        <i class="fas fa-circle-exclamation fs-5 flex-shrink-0 mt-1"></i>
                        <div>
                            <strong class="d-block mb-1">Error de Autenticación</strong>
                            @foreach ($errors->all() as $error)
                                <span>{{ $error }}</span><br>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- FORMULARIO DE LOGIN -->
                <form method="POST" action="{{ route('login') }}" id="formLogin" onsubmit="alEnviarLogin(event)">
                    @csrf

                    <!-- CAMPO: IDENTIFICACIÓN / CÉDULA -->
                    <div class="mb-3">
                        <label for="name" class="dv-label">
                            <i class="fas fa-id-card text-primary"></i> Identificación / Cédula / Usuario
                        </label>
                        <div class="dv-input-group">
                            <i class="fas fa-user dv-input-icon"></i>
                            <input 
                                id="name" 
                                type="text" 
                                name="name" 
                                value="{{ old('name') }}" 
                                class="dv-input @error('name') is-invalid @enderror" 
                                placeholder="Ej. V-12345678 o usuario" 
                                required 
                                autofocus 
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <!-- CAMPO: CONTRASEÑA -->
                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="dv-label mb-0">
                                <i class="fas fa-lock text-primary"></i> Contraseña
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-decoration-none fw-semibold small" style="color: #0284c7; font-size: 0.80rem;">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            @endif
                        </div>
                        <div class="dv-input-group">
                            <i class="fas fa-shield-halved dv-input-icon"></i>
                            <input 
                                id="password" 
                                type="password" 
                                name="password" 
                                class="dv-input @error('password') is-invalid @enderror" 
                                placeholder="••••••••" 
                                required 
                                autocomplete="current-password"
                            >
                            <button type="button" class="dv-toggle-pwd" id="btnTogglePassword" onclick="togglePasswordVisibility()" title="Mostrar/Ocultar contraseña">
                                <i class="fas fa-eye" id="iconoPassword"></i>
                            </button>
                        </div>
                    </div>

                    <!-- RECORDARME -->
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <label class="d-flex align-items-center gap-2 dv-checkbox-label">
                            <input type="checkbox" id="remember_me" name="remember" class="dv-checkbox">
                            <span>Recordar mi sesión en este equipo</span>
                        </label>
                    </div>

                    <!-- BOTÓN DE ENVÍO -->
                    <button type="submit" id="btnSubmitLogin" class="dv-btn-submit">
                        <span>Ingresar a DataVault</span>
                        <i class="fas fa-arrow-right-to-bracket"></i>
                    </button>
                </form>

                <!-- FOOTER DE ASISTENCIA -->
                <div class="text-center mt-4 pt-3 border-top">
                    <p class="text-muted small mb-0">
                        ¿Problemas para acceder? Contacta al administrador del sistema o soporte técnico.
                    </p>
                </div>

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
</body>

</html>
