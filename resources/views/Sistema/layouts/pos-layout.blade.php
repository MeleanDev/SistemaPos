<!DOCTYPE html>
<html dir="ltr" lang="es">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Sistema POS - Punto de Venta Pantalla Completa">
        <meta name="author" content="POS System">

        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#0f172a">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('estilos/imgPropio/favicon.ico') }}">

        <title>POS | @yield('titulo', 'Punto de Venta')</title>

        <link href="{{ asset('estilos/dist/css/style.css') }}" rel="stylesheet">
        <!-- Font Awesome Icons -->
        <link href="{{ asset('estilos/dist/css/icons/font-awesome/css/fontawesome-all.min.css') }}" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

        <!-- Select2 para selectores con búsqueda -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />

        <style>
            :root {
                --pos-header-height: 52px;
                --pos-footer-height: 32px;
                --pos-bg: #f1f5f9;
            }

            html, body {
                height: 100vh;
                max-height: 100vh;
                overflow: hidden !important;
                margin: 0;
                padding: 0;
                background-color: var(--pos-bg);
                font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
                user-select: none;
            }

            #pos-kiosk-app {
                height: 100vh;
                max-height: 100vh;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }

            .pos-top-header {
                height: var(--pos-header-height);
                min-height: var(--pos-header-height);
                max-height: var(--pos-header-height);
                background-color: #0f172a;
                color: #ffffff;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 1rem;
                z-index: 1040;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            }

            .pos-main-workspace {
                flex: 1;
                height: calc(100vh - var(--pos-header-height) - var(--pos-footer-height));
                max-height: calc(100vh - var(--pos-header-height) - var(--pos-footer-height));
                overflow: hidden;
                padding: 0.5rem;
            }

            .pos-bottom-statusbar {
                height: var(--pos-footer-height);
                min-height: var(--pos-footer-height);
                max-height: var(--pos-footer-height);
                background-color: #0f172a;
                color: #94a3b8;
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 1rem;
                font-size: 0.74rem;
                font-family: monospace;
                border-top: 1px solid #1e293b;
                z-index: 1040;
            }

            .pos-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 1rem;
                box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.05);
            }

            .pos-scroll-custom::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            .pos-scroll-custom::-webkit-scrollbar-track {
                background: #f8fafc;
            }
            .pos-scroll-custom::-webkit-scrollbar-thumb {
                background: #cbd5e1;
                border-radius: 9999px;
            }
            .pos-scroll-custom::-webkit-scrollbar-thumb:hover {
                background: #94a3b8;
            }
        </style>

        @stack('css')
        @yield('css')
    </head>

    <body>
        <div id="pos-kiosk-app">
            @yield('contenido')
        </div>

        <script src="{{ asset('estilos/assets/libs/jquery/dist/jquery.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- Componentes Globales de Alertas y Manejo AJAX -->
        @include('Sistema.components.alert.sweetalert')
        @include('Sistema.components.alert.toast')

        <!-- Componentes JavaScript Reutilizables POS -->
        @include('Sistema.components.js-components')

        <script>
            function alternarPantallaCompleta() {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch((err) => {
                        console.warn("Fullscreen request error:", err);
                    });
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    }
                }
            }

            function actualizarRelojPos() {
                const ahora = new Date();
                const str = ahora.toLocaleTimeString('es-VE', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
                const el = document.getElementById('posLiveClock');
                if (el) {
                    el.textContent = str;
                }
            }
            setInterval(actualizarRelojPos, 1000);
            actualizarRelojPos();
        </script>

        @yield('scripts')
        @stack('scripts')
    </body>

</html>
