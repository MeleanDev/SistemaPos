<!DOCTYPE html>
<html dir="ltr" lang="es">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Kiosco Digital Consultor de Precios - Pantalla TV">
        <meta name="author" content="POS System">

        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#0b1120">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('estilos/imgPropio/favicon.ico') }}">

        <title>{{ $empresa->nombre ?? 'Kiosco' }} | Consultor de Precios</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500;600;700;800&display=swap" rel="stylesheet">

        <link href="{{ asset('estilos/dist/css/style.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

        <style>
            :root {
                --kiosk-bg: #0b1120;
                --kiosk-surface: #0f172a;
                --kiosk-card-bg: #1e293b;
                --kiosk-card-border: rgba(255, 255, 255, 0.08);
                --kiosk-card-coincidencias-bg: #0f172a;
                --kiosk-item-bg: #172338;
                --kiosk-item-hover-bg: #1e304d;
                --kiosk-item-border: rgba(59, 130, 246, 0.25);
                --kiosk-item-hover-border: #3b82f6;
                --kiosk-text-main: #f8fafc;
                --kiosk-text-muted: #94a3b8;
                --kiosk-primary: #3b82f6;
                --kiosk-input-bg: #1e293b;
                --kiosk-input-text: #ffffff;
                --kiosk-topbar-bg: rgba(15, 23, 42, 0.88);
                --kiosk-footer-bg: rgba(11, 17, 32, 0.95);
                --kiosk-glow: radial-gradient(ellipse at center, rgba(59, 130, 246, 0.18) 0%, rgba(16, 185, 129, 0.1) 40%, rgba(11, 17, 32, 0) 72%);
                --kiosk-toggle-bg: rgba(255, 255, 255, 0.08);
                --kiosk-toggle-color: #f8fafc;
                --kiosk-tasa-bg: rgba(245, 158, 11, 0.14);
                --kiosk-tasa-color: #fbbf24;
                --kiosk-card-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6);
            }

            [data-theme="light"] {
                --kiosk-bg: #f1f5f9;
                --kiosk-surface: #ffffff;
                --kiosk-card-bg: #ffffff;
                --kiosk-card-border: rgba(203, 213, 225, 0.8);
                --kiosk-card-coincidencias-bg: #ffffff;
                --kiosk-item-bg: #f8fafc;
                --kiosk-item-hover-bg: #f1f5f9;
                --kiosk-item-border: rgba(59, 130, 246, 0.35);
                --kiosk-item-hover-border: #2563eb;
                --kiosk-text-main: #0f172a;
                --kiosk-text-muted: #475569;
                --kiosk-primary: #2563eb;
                --kiosk-input-bg: #ffffff;
                --kiosk-input-text: #0f172a;
                --kiosk-topbar-bg: rgba(255, 255, 255, 0.95);
                --kiosk-footer-bg: rgba(241, 245, 249, 0.98);
                --kiosk-glow: radial-gradient(ellipse at center, rgba(59, 130, 246, 0.12) 0%, rgba(16, 185, 129, 0.08) 40%, rgba(241, 245, 249, 0) 72%);
                --kiosk-toggle-bg: rgba(15, 23, 42, 0.06);
                --kiosk-toggle-color: #0f172a;
                --kiosk-tasa-bg: rgba(245, 158, 11, 0.15);
                --kiosk-tasa-color: #b45309;
                --kiosk-card-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.1);
            }

            * {
                box-sizing: border-box;
            }

            html, body {
                min-height: 100vh;
                margin: 0;
                padding: 0;
                background-color: var(--kiosk-bg);
                color: var(--kiosk-text-main);
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
                user-select: none;
                -webkit-font-smoothing: antialiased;
                overflow-x: hidden;
                transition: background-color 0.25s ease, color 0.25s ease;
            }

            .font-mono-num {
                font-family: 'JetBrains Mono', monospace;
            }

            .kiosk-text-heading {
                color: var(--kiosk-text-main) !important;
            }

            .kiosk-text-sub {
                color: var(--kiosk-text-muted) !important;
            }

            #kiosk-app-wrapper {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                position: relative;
                overflow-x: hidden;
            }

            /* Ambient Glow Effect */
            .kiosk-ambient-glow {
                position: absolute;
                top: -140px;
                left: 50%;
                transform: translateX(-50%);
                width: 75vw;
                max-width: 900px;
                height: 420px;
                background: var(--kiosk-glow);
                filter: blur(55px);
                pointer-events: none;
                z-index: 1;
            }

            /* Topbar */
            .kiosk-topbar {
                position: relative;
                z-index: 10;
                min-height: 70px;
                padding: 0.6rem 2rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: var(--kiosk-topbar-bg);
                backdrop-filter: blur(16px);
                border: none;
            }

            .kiosk-logo-box {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .kiosk-logo-img {
                width: clamp(38px, 4vw, 48px);
                height: clamp(38px, 4vw, 48px);
                object-fit: contain;
                border-radius: 12px;
                background: var(--kiosk-toggle-bg);
                padding: 3px;
                border: none;
            }

            .kiosk-logo-placeholder {
                width: clamp(38px, 4vw, 48px);
                height: clamp(38px, 4vw, 48px);
                border-radius: 12px;
                background: linear-gradient(135deg, #2563eb, #10b981);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.2rem;
                color: #ffffff;
                box-shadow: 0 4px 16px rgba(37, 99, 235, 0.35);
            }

            .kiosk-main-content {
                position: relative;
                z-index: 10;
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: clamp(0.75rem, 2vw, 1.75rem);
                width: 100%;
            }

            /* Bottom Status Footer (Sin líneas divisorias) */
            .kiosk-footer {
                position: relative;
                z-index: 10;
                min-height: 44px;
                padding: 0.5rem 2rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                background: var(--kiosk-footer-bg);
                font-size: 0.82rem;
                color: var(--kiosk-text-muted);
                border: none;
            }

            /* Scanner Laser Animation */
            .scanner-laser-container {
                position: relative;
                width: clamp(100px, 12vw, 140px);
                height: clamp(100px, 12vw, 140px);
                margin: 0 auto;
                border-radius: clamp(1.5rem, 2.5vw, 2.2rem);
                background: var(--kiosk-toggle-bg);
                display: flex;
                align-items: center;
                justify-content: center;
                overflow: hidden;
                box-shadow: inset 0 2px 12px rgba(0, 0, 0, 0.1);
            }

            .scanner-laser-beam {
                position: absolute;
                left: 0;
                right: 0;
                height: 4px;
                background: linear-gradient(90deg, transparent, #3b82f6, #10b981, #3b82f6, transparent);
                box-shadow: 0 0 16px 3px #10b981;
                animation: scanLaser 2.2s ease-in-out infinite alternate;
            }

            @keyframes scanLaser {
                0% { top: 12%; opacity: 0.3; }
                50% { opacity: 1; }
                100% { top: 86%; opacity: 0.3; }
            }

            /* Master Rounded Cards */
            .kiosk-card {
                background: var(--kiosk-card-bg);
                border-radius: clamp(1.5rem, 2.8vw, 2.5rem);
                border: 1px solid var(--kiosk-card-border);
                box-shadow: var(--kiosk-card-shadow);
                color: var(--kiosk-text-main);
            }

            .kiosk-card-coincidencias {
                background: var(--kiosk-card-coincidencias-bg) !important;
                border: 1px solid var(--kiosk-card-border) !important;
            }

            .kiosk-item-card {
                background: var(--kiosk-item-bg);
                border-radius: clamp(1.2rem, 2vw, 1.8rem);
                border: 1px solid var(--kiosk-item-border);
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.25);
                transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            }

            .kiosk-item-card:hover {
                background: var(--kiosk-item-hover-bg);
                border-color: var(--kiosk-item-hover-border);
                transform: translateY(-4px) scale(1.01);
                box-shadow: 0 16px 36px -6px rgba(37, 99, 235, 0.3);
            }

            .kiosk-btn {
                background: var(--kiosk-toggle-bg);
                border: 1px solid var(--kiosk-card-border);
                color: var(--kiosk-text-main);
                padding: 0.55rem 1.25rem;
                border-radius: 9999px;
                font-size: 0.86rem;
                font-weight: 600;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                text-decoration: none;
                cursor: pointer;
            }

            .kiosk-btn:hover {
                background: rgba(59, 130, 246, 0.15);
                color: var(--kiosk-primary);
                transform: translateY(-1px);
            }

            .kiosk-theme-toggle-btn {
                background: var(--kiosk-toggle-bg);
                color: var(--kiosk-toggle-color);
                border: 1px solid var(--kiosk-card-border);
                padding: clamp(0.35rem, 1vw, 0.45rem) clamp(0.75rem, 1.2vw, 1rem);
                border-radius: 9999px;
                font-size: clamp(0.75rem, 1vw, 0.85rem);
                font-weight: 700;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .kiosk-theme-toggle-btn:hover {
                transform: scale(1.04);
            }

            .kiosk-tasa-badge {
                background: var(--kiosk-tasa-bg);
                color: var(--kiosk-tasa-color);
                padding: clamp(0.35rem, 1vw, 0.45rem) clamp(0.75rem, 1.5vw, 1.35rem);
                border-radius: 9999px;
                gap: clamp(0.35rem, 0.8vw, 0.6rem);
                border: 1px solid var(--kiosk-card-border);
            }

            /* Auto-Reset Countdown Bar */
            .kiosk-countdown-bar-wrapper {
                width: 100%;
                height: 6px;
                background: rgba(255, 255, 255, 0.06);
                border-radius: 9999px;
                overflow: hidden;
            }

            .kiosk-countdown-bar-fill {
                height: 100%;
                width: 100%;
                background: linear-gradient(90deg, #10b981, #3b82f6);
                transition: width 0.1s linear;
            }

            /* Pulsing Badge */
            .badge-live-pulse {
                width: 9px;
                height: 9px;
                border-radius: 50%;
                background-color: #10b981;
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
                animation: pulseGlow 1.8s infinite;
            }

            @keyframes pulseGlow {
                0% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
                }
                70% {
                    transform: scale(1);
                    box-shadow: 0 0 0 8px rgba(16, 185, 129, 0);
                }
                100% {
                    transform: scale(0.95);
                    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0);
                }
            }

            /* Responsive media queries */
            @media (min-width: 992px) {
                html, body {
                    height: 100vh;
                    max-height: 100vh;
                    overflow: hidden !important;
                }
                #kiosk-app-wrapper {
                    height: 100vh;
                    max-height: 100vh;
                    overflow: hidden;
                }
                .kiosk-main-content {
                    overflow-y: auto;
                }
            }

            @media (max-width: 768px) {
                .kiosk-topbar {
                    padding: 0.6rem 1rem;
                    gap: 0.5rem;
                }
                .kiosk-footer {
                    padding: 0.6rem 1rem;
                    flex-direction: column;
                    text-align: center;
                    gap: 0.35rem;
                    font-size: 0.74rem;
                }
                .kiosk-main-content {
                    padding: 0.75rem 0.5rem;
                    align-items: flex-start;
                }
            }
        </style>

        <script>
            (function() {
                const savedTheme = localStorage.getItem('kiosk_theme_consultor') || 'dark';
                if (savedTheme === 'light') {
                    document.documentElement.setAttribute('data-theme', 'light');
                }
            })();
        </script>

        @stack('css')
        @yield('css')
    </head>

    <body>
        <div id="kiosk-app-wrapper">
            <div class="kiosk-ambient-glow"></div>

            <!-- Topbar TV Header -->
            <header class="kiosk-topbar">
                <div class="kiosk-logo-box">
                    @if (!empty($empresa->logo))
                        <img src="{{ asset('storage/' . $empresa->logo) }}" alt="{{ $empresa->nombre }}" class="kiosk-logo-img" onerror="this.onerror=null; this.src='{{ asset('estilos/imgPropio/logo.png') }}';">
                    @else
                        <div class="kiosk-logo-placeholder">
                            <i class="fas fa-barcode"></i>
                        </div>
                    @endif
                    <div>
                        <div class="fw-bold kiosk-text-heading fs-5 text-truncate" style="letter-spacing: -0.02em; line-height: 1.2; max-width: clamp(130px, 28vw, 320px);">
                            {{ $empresa->nombre ?? 'Sistema POS' }}
                        </div>
                        <div class="kiosk-text-sub small fw-semibold text-truncate d-none d-sm-block" style="font-size: 0.75rem; letter-spacing: 0.03em; max-width: clamp(130px, 28vw, 320px);">
                            {{ $empresa->razon_social ?? 'CONSULTOR DIGITAL DE PRECIOS' }}
                        </div>
                    </div>
                </div>

                <div class="d-none d-lg-flex align-items-center gap-4">
                    <div class="d-flex align-items-center gap-2" style="background: transparent; border: none;">
                        <div class="badge-live-pulse"></div>
                        <span class="kiosk-text-sub small">Estado:</span>
                        <span class="fw-bold small kiosk-text-heading">Lector Listo</span>
                    </div>

                    <div class="d-flex align-items-center gap-2 font-mono-num" style="background: transparent; border: none;">
                        <i class="far fa-clock text-primary"></i>
                        <span id="kioskLiveClock" class="fw-bold kiosk-text-heading fs-6">--:--:--</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="button" id="btnToggleTemaKiosco" class="kiosk-theme-toggle-btn font-mono-num" onclick="toggleTemaKiosco()" title="Alternar entre Fondo Oscuro y Claro">
                        <i class="fas fa-sun" id="iconoTemaKiosco"></i>
                        <span id="textoTemaKiosco" class="d-none d-md-inline">Claro</span>
                    </button>

                    <div class="font-mono-num d-inline-flex align-items-center kiosk-tasa-badge">
                        <i class="fas fa-coins" style="font-size: clamp(0.85rem, 1.1vw, 1.05rem);"></i>
                        <span style="font-size: clamp(0.72rem, 1vw, 0.85rem); font-weight: 500;" class="d-none d-sm-inline">Tasa Oficial:</span>
                        <span style="font-size: clamp(0.72rem, 1vw, 0.85rem); font-weight: 500;" class="d-inline d-sm-none">Tasa:</span>
                        <strong style="font-size: clamp(0.95rem, 1.2vw, 1.1rem); font-weight: 800;" id="badgeTasaConsultor">{{ number_format($tasaUsd ?? 1.0, 2, '.', '') }}</strong>
                        <span style="font-size: clamp(0.72rem, 1vw, 0.85rem);">Bs.</span>
                    </div>
                </div>
            </header>

            <!-- Main Workspace Container -->
            <main class="kiosk-main-content">
                @yield('contenido')
            </main>

            <!-- Bottom Status Footer (Completamente sin líneas divisorias) -->
            <footer class="kiosk-footer font-mono-num">
                <div class="d-flex align-items-center gap-2 justify-content-center">
                    <span><i class="fas fa-barcode text-primary me-1"></i> Compatible con pistolas y lectores USB / Bluetooth</span>
                </div>
                <div class="d-flex align-items-center gap-3 justify-content-center">
                    <span id="kioskLiveDate" class="kiosk-text-sub fw-semibold">--/--/----</span>
                    <span class="kiosk-text-sub opacity-50">|</span>
                    <span class="kiosk-text-sub fw-semibold">{{ $empresa->rif ?? 'RIF' }}</span>
                </div>
            </footer>
        </div>

        <script src="{{ asset('estilos/assets/libs/jquery/dist/jquery.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>

        <script>
            function aplicarTemaKiosco(tema) {
                const root = document.documentElement;
                const icono = document.getElementById('iconoTemaKiosco');
                const texto = document.getElementById('textoTemaKiosco');
                
                if (tema === 'light') {
                    root.setAttribute('data-theme', 'light');
                    localStorage.setItem('kiosk_theme_consultor', 'light');
                    if (icono) {
                        icono.className = 'fas fa-moon';
                        icono.style.color = '#3b82f6';
                    }
                    if (texto) {
                        texto.textContent = 'Oscuro';
                    }
                } else {
                    root.removeAttribute('data-theme');
                    localStorage.setItem('kiosk_theme_consultor', 'dark');
                    if (icono) {
                        icono.className = 'fas fa-sun';
                        icono.style.color = '#fbbf24';
                    }
                    if (texto) {
                        texto.textContent = 'Claro';
                    }
                }
            }

            function toggleTemaKiosco() {
                const esClaro = document.documentElement.getAttribute('data-theme') === 'light';
                aplicarTemaKiosco(esClaro ? 'dark' : 'light');
            }

            const temaInicial = localStorage.getItem('kiosk_theme_consultor') || 'dark';
            aplicarTemaKiosco(temaInicial);

            function actualizarRelojKiosco() {
                const ahora = new Date();
                const strHora = ahora.toLocaleTimeString('es-VE', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const strFecha = ahora.toLocaleDateString('es-VE', { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' });
                
                const elHora = document.getElementById('kioskLiveClock');
                if (elHora) {
                    elHora.textContent = strHora;
                }
                const elFecha = document.getElementById('kioskLiveDate');
                if (elFecha) {
                    elFecha.textContent = strFecha.toUpperCase();
                }
            }
            setInterval(actualizarRelojKiosco, 1000);
            actualizarRelojKiosco();
        </script>

        @yield('scripts')
        @stack('scripts')
    </body>

</html>
