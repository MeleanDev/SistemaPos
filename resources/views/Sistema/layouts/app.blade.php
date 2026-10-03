<!DOCTYPE html>
<html dir="ltr" lang="es">

    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Sistema POS - Punto de Venta, Control de Inventario, Ventas y Reportes">
        <meta name="keywords" content="punto de venta, pos, facturacion, ventas, inventario, control de caja">
        <meta name="author" content="POS System">
        <meta name="robots" content="index, follow">

        <meta property="og:type" content="website">
        <meta property="og:url" content="{{ config('app.url') }}">
        <meta property="og:title" content="Sistema POS">
        <meta property="og:description" content="Sistema POS - Punto de Venta y Control de Inventario">
        <meta property="og:image" content="{{ asset('estilos/imgPropio/logo.png') }}">

        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="apple-mobile-web-app-title" content="Sistema POS">
        <meta name="application-name" content="Sistema POS">
        <meta name="theme-color" content="#4f46e5">
        <link rel="apple-touch-icon" href="{{ asset('estilos/imgPropio/logo.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('estilos/imgPropio/favicon.ico') }}">

        <title>Sistema POS - @yield('subtitulo', 'Panel Principal')</title>

        <link href="{{ asset('estilos/dist/css/style.css') }}" rel="stylesheet">
        <!-- Font Awesome 6 Icons (Catálogo Completo) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">

        <!-- Select2 para selectores con búsqueda -->
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
            rel="stylesheet" />

        <!-- Chart.js para visualización de métricas -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <style>
            /* Layout & Header Transitions */
            #main-wrapper .topbar,
            #main-wrapper .topbar .top-navbar,
            #main-wrapper .topbar .top-navbar .navbar-header,
            #main-wrapper .topbar .top-navbar .navbar-collapse,
            #main-wrapper .left-sidebar,
            #main-wrapper .page-wrapper {
                transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
            }

            /* Logotipo Base */
            #main-wrapper .navbar-brand .logo-img {
                width: 38px !important;
                height: 38px !important;
                min-width: 38px !important;
                min-height: 38px !important;
                object-fit: cover !important;
                border-radius: 50% !important;
                border: 1.5px solid #0284c7 !important;
                display: block !important;
                flex-shrink: 0 !important;
            }

            #main-wrapper .navbar-brand .logo-text {
                font-weight: 700 !important;
                letter-spacing: -0.02em !important;
                white-space: nowrap !important;
                margin-left: 8px !important;
                color: #1e293b !important;
            }

            /* Contenedor Sidebar con scroll único gestionado por PerfectScrollbar */
            #main-wrapper .left-sidebar {
                overflow: hidden !important;
            }

            #main-wrapper .left-sidebar .scroll-sidebar {
                height: 100% !important;
                position: relative !important;
                overflow: hidden !important;
            }

            #main-wrapper .left-sidebar .scroll-sidebar .sidebar-nav {
                flex: 1 0 auto !important;
            }

            /* ============================================================== */
            /* ESCRITORIO Y TABLET (>= 768px)                                 */
            /* ============================================================== */
            @media (min-width: 768px) {
                #main-wrapper .topbar {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 80px !important;
                    z-index: 1050 !important;
                    background: #ffffff !important;
                    border-bottom: 1px solid #edf2f9 !important;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05) !important;
                    overflow: visible !important;
                }

                #main-wrapper .topbar .top-navbar {
                    height: 80px !important;
                    min-height: 80px !important;
                    padding: 0 !important;
                    display: flex !important;
                    align-items: center !important;
                    background: #ffffff !important;
                    overflow: visible !important;
                }

                #main-wrapper .navbar-brand .logo-text {
                    font-size: 1.15rem !important;
                    transition: opacity 0.2s ease, visibility 0.2s ease !important;
                }

                /* FULL SIDEBAR (260px) */
                #main-wrapper[data-sidebartype="full"] .topbar .top-navbar .navbar-header {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 260px !important;
                    min-width: 260px !important;
                    max-width: 260px !important;
                    height: 80px !important;
                    line-height: 80px !important;
                    background: #ffffff !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: flex-start !important;
                    padding: 0 20px !important;
                    z-index: 1060 !important;
                    border-right: 1px solid #edf2f9 !important;
                    overflow: hidden !important;
                }

                #main-wrapper[data-sidebartype="full"] .navbar-brand .logo-link {
                    justify-content: flex-start !important;
                    padding: 0 !important;
                }

                #main-wrapper[data-sidebartype="full"] .navbar-brand .logo-img {
                    margin: 0 !important;
                }

                #main-wrapper[data-sidebartype="full"] .navbar-brand .logo-text {
                    display: inline-block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }

                #main-wrapper[data-sidebartype="full"] .topbar .top-navbar .navbar-collapse {
                    position: static !important;
                    top: 0 !important;
                    margin-left: 260px !important;
                    width: calc(100% - 260px) !important;
                    height: 80px !important;
                    display: flex !important;
                    align-items: center !important;
                    background: #ffffff !important;
                    border-bottom: 1px solid #edf2f9 !important;
                    padding: 0 15px !important;
                    overflow: visible !important;
                }

                #main-wrapper[data-sidebartype="full"] .page-wrapper {
                    margin-left: 260px !important;
                    padding-top: 80px !important;
                }

                #main-wrapper[data-sidebartype="full"] .left-sidebar {
                    position: fixed !important;
                    top: 80px !important;
                    left: 0 !important;
                    bottom: 0 !important;
                    width: 260px !important;
                    height: calc(100vh - 80px) !important;
                    background: #ffffff !important;
                    border-right: 1px solid #edf2f9 !important;
                    z-index: 1040 !important;
                    padding-top: 15px !important;
                    overflow: hidden !important;
                }

                /* MINI SIDEBAR (70px) */
                #main-wrapper[data-sidebartype="mini-sidebar"] .topbar .top-navbar .navbar-header {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 70px !important;
                    min-width: 70px !important;
                    max-width: 70px !important;
                    height: 80px !important;
                    line-height: 80px !important;
                    background: #ffffff !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    padding: 0 !important;
                    z-index: 1060 !important;
                    border-right: 1px solid #edf2f9 !important;
                    overflow: hidden !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"] .navbar-brand .logo-link {
                    justify-content: center !important;
                    padding: 0 !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"] .navbar-brand .logo-img {
                    margin: 0 auto !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .navbar-brand .logo-text {
                    display: none !important;
                    opacity: 0 !important;
                    visibility: hidden !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"] .topbar .top-navbar .navbar-collapse {
                    position: static !important;
                    top: 0 !important;
                    margin-left: 70px !important;
                    width: calc(100% - 70px) !important;
                    height: 80px !important;
                    display: flex !important;
                    align-items: center !important;
                    background: #ffffff !important;
                    border-bottom: 1px solid #edf2f9 !important;
                    padding: 0 15px !important;
                    overflow: visible !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"] .page-wrapper {
                    margin-left: 70px !important;
                    padding-top: 80px !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"] .left-sidebar {
                    position: fixed !important;
                    top: 80px !important;
                    left: 0 !important;
                    bottom: 0 !important;
                    width: 70px !important;
                    height: calc(100vh - 80px) !important;
                    background: #ffffff !important;
                    border-right: 1px solid #edf2f9 !important;
                    z-index: 1040 !important;
                    padding-top: 15px !important;
                    overflow: hidden !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .hide-menu,
                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .nav-small-cap,
                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .list-divider,
                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .sidebar-link.has-arrow:after,
                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .first-level {
                    display: none !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .sidebar-item {
                    width: 100% !important;
                    text-align: center !important;
                    display: flex !important;
                    justify-content: center !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .sidebar-link {
                    width: 46px !important;
                    height: 46px !important;
                    padding: 0 !important;
                    margin: 4px auto !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: center !important;
                    border-radius: 12px !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"]:not(.sidebar-hovered) .sidebar-link i {
                    font-size: 1.25rem !important;
                    margin: 0 !important;
                    line-height: 1 !important;
                }

                /* HOVER TO EXPAND */
                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .topbar .top-navbar .navbar-header {
                    width: 260px !important;
                    min-width: 260px !important;
                    max-width: 260px !important;
                    padding: 0 20px !important;
                    justify-content: flex-start !important;
                    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08) !important;
                    z-index: 1070 !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .navbar-brand .logo-link {
                    justify-content: flex-start !important;
                    padding: 0 !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .navbar-brand .logo-img {
                    margin: 0 !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .navbar-brand .logo-text {
                    display: inline-block !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar {
                    width: 260px !important;
                    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08) !important;
                    z-index: 1065 !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .hide-menu,
                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .nav-small-cap,
                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .list-divider,
                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .sidebar-link.has-arrow:after {
                    display: block !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .first-level.in {
                    display: block !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .sidebar-item {
                    display: block !important;
                    width: 100% !important;
                    text-align: left !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .sidebar-link {
                    width: auto !important;
                    height: auto !important;
                    padding: 10px 15px !important;
                    margin: 2px 10px !important;
                    justify-content: flex-start !important;
                    border-radius: 8px !important;
                }

                #main-wrapper[data-sidebartype="mini-sidebar"].sidebar-hovered .left-sidebar .sidebar-link i {
                    font-size: 1rem !important;
                    margin-right: 10px !important;
                }
            }

            /* ============================================================== */
            /* MÓVIL (< 768px)                                                */
            /* ============================================================== */
            @media (max-width: 767.98px) {
                #main-wrapper .topbar {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    width: 100% !important;
                    height: 64px !important;
                    z-index: 1050 !important;
                    background: #ffffff !important;
                    border-bottom: 1px solid #edf2f9 !important;
                    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06) !important;
                    overflow: visible !important;
                }

                #main-wrapper .topbar .top-navbar {
                    height: 64px !important;
                    min-height: 64px !important;
                    padding: 0 12px !important;
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    width: 100% !important;
                    background: #ffffff !important;
                    overflow: visible !important;
                }

                #main-wrapper .topbar .top-navbar .navbar-header {
                    position: static !important;
                    height: 64px !important;
                    line-height: 64px !important;
                    width: auto !important;
                    border: none !important;
                    display: flex !important;
                    align-items: center !important;
                    background: transparent !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                }

                #main-wrapper .topbar .top-navbar .navbar-header .navbar-brand {
                    height: 64px !important;
                    width: auto !important;
                    display: flex !important;
                    align-items: center !important;
                    padding: 0 !important;
                    margin: 0 !important;
                }

                #main-wrapper .topbar .top-navbar .navbar-header .navbar-brand .logo-img {
                    width: 32px !important;
                    height: 32px !important;
                    min-width: 32px !important;
                    min-height: 32px !important;
                }

                #main-wrapper .topbar .top-navbar .navbar-collapse {
                    position: static !important;
                    top: 0 !important;
                    margin-left: 0 !important;
                    width: auto !important;
                    height: 64px !important;
                    display: flex !important;
                    align-items: center !important;
                    background: transparent !important;
                    border: none !important;
                    padding: 0 !important;
                    box-shadow: none !important;
                    overflow: visible !important;
                }

                #main-wrapper .left-sidebar {
                    position: fixed !important;
                    top: 64px !important;
                    left: -285px !important;
                    width: 285px !important;
                    height: calc(100vh - 64px) !important;
                    background: #ffffff !important;
                    border-right: 1px solid #edf2f9 !important;
                    box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15) !important;
                    z-index: 1060 !important;
                    padding-top: 10px !important;
                    transition: left 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
                    overflow: hidden !important;
                }

                #main-wrapper.show-sidebar .left-sidebar {
                    left: 0 !important;
                }

                #main-wrapper .page-wrapper {
                    padding-top: 64px !important;
                    margin-left: 0 !important;
                }
            }
        </style>

        <script>
            (function() {
                try {
                    var state = localStorage.getItem("sidebar_state");
                    if (state === "mini-sidebar" && window.innerWidth >= 768) {
                        window.__INITIAL_SIDEBAR_STATE = "mini-sidebar";
                    }
                } catch(e) {}
            })();
        </script>

        @stack('css')
        @yield('css')
    </head>

    <body>
        @include('Sistema.components.preloader')
        <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
            <script>
                if (window.__INITIAL_SIDEBAR_STATE === "mini-sidebar") {
                    var w = document.getElementById("main-wrapper");
                    if (w) {
                        w.setAttribute("data-sidebartype", "mini-sidebar");
                        w.classList.add("mini-sidebar");
                    }
                }
            </script>
            @include('Sistema.layouts.header')
            <!-- Backdrop para pantallas móviles (< 768px) -->
            <div class="sidebar-mobile-backdrop d-md-none" onclick="toggleSidebarMenu()" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(2px); z-index: 1055; transition: opacity 0.2s ease;"></div>
            @include('Sistema.layouts.sidebar')
            <div class="page-wrapper">
                @include('Sistema.components.breadcrumb')
                <div class="container-fluid">
                    @yield('contenido')
                </div>
                @include('Sistema.layouts.footer')
            </div>
        </div>

        <script src="{{ asset('estilos/assets/libs/jquery/dist/jquery.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('estilos/dist/js/app-style-switcher.js') }}"></script>
        <script src="{{ asset('estilos/dist/js/feather.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js') }}"></script>
        <script src="{{ asset('estilos/dist/js/sidebarmenu.js') }}"></script>
        <script src="{{ asset('estilos/dist/js/custom.min.js') }}"></script>
        <script src="{{ asset('estilos/assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- Componentes Globales de Alertas y Manejo AJAX -->
        @include('Sistema.components.alert.sweetalert')
        @include('Sistema.components.alert.toast')

        <!-- Componentes JavaScript Reutilizables POS -->
        @include('Sistema.components.js-components')

        <script>
            function updateSidebarToggleIcon(type) {
                const $icon = $("#iconToggleSidebarGlobal");
                if ($icon.length) {
                    if (type === "mini-sidebar") {
                        $icon.removeClass("fa-bars").addClass("fa-indent");
                    } else {
                        $icon.removeClass("fa-indent").addClass("fa-bars");
                    }
                }
            }

            function toggleSidebarMenu() {
                const $wrapper = $("#main-wrapper");
                if (window.innerWidth < 768) {
                    $wrapper.toggleClass("show-sidebar");
                    const isOpen = $wrapper.hasClass("show-sidebar");
                    $(".sidebar-mobile-backdrop").fadeToggle(200);
                } else {
                    const currentType = $wrapper.attr("data-sidebartype");
                    const newType = currentType === "mini-sidebar" ? "full" : "mini-sidebar";
                    $wrapper.attr("data-sidebartype", newType);
                    if (newType === "mini-sidebar") {
                        $wrapper.addClass("mini-sidebar");
                    } else {
                        $wrapper.removeClass("mini-sidebar sidebar-hovered");
                    }
                    localStorage.setItem("sidebar_state", newType);
                    updateSidebarToggleIcon(newType);
                }
            }
            window.toggleSidebarMenu = toggleSidebarMenu;

            $(document).ready(function() {
                const savedState = localStorage.getItem("sidebar_state") || "full";
                if (window.innerWidth >= 768) {
                    $("#main-wrapper").attr("data-sidebartype", savedState);
                    if (savedState === "mini-sidebar") {
                        $("#main-wrapper").addClass("mini-sidebar");
                    } else {
                        $("#main-wrapper").removeClass("mini-sidebar");
                    }
                    updateSidebarToggleIcon(savedState);
                }

                // Cerrar drawer en móvil al hacer clic en un enlace que no sea submenú
                $(document).on("click", "#sidebarnav a:not(.has-arrow)", function() {
                    if (window.innerWidth < 768) {
                        $("#main-wrapper").removeClass("show-sidebar");
                        $(".sidebar-mobile-backdrop").fadeOut(200);
                    }
                });

                // Efecto hover-to-expand inteligente sincronizado (Sidebar + Header)
                let hoverTimeout;
                $(document).on("mouseenter", "#main-wrapper[data-sidebartype='mini-sidebar'] .left-sidebar, #main-wrapper[data-sidebartype='mini-sidebar'] .navbar-header", function() {
                    if (window.innerWidth >= 768 && $("#main-wrapper").attr("data-sidebartype") === "mini-sidebar") {
                        clearTimeout(hoverTimeout);
                        $("#main-wrapper").addClass("sidebar-hovered");
                    }
                });
                $(document).on("mouseleave", "#main-wrapper[data-sidebartype='mini-sidebar'] .left-sidebar, #main-wrapper[data-sidebartype='mini-sidebar'] .navbar-header", function() {
                    if (window.innerWidth >= 768 && $("#main-wrapper").attr("data-sidebartype") === "mini-sidebar") {
                        hoverTimeout = setTimeout(function() {
                            $("#main-wrapper").removeClass("sidebar-hovered");
                        }, 120);
                    }
                });

                $(document).on("keydown", function(e) {
                    if (e.ctrlKey && (e.key === "b" || e.key === "B")) {
                        e.preventDefault();
                        toggleSidebarMenu();
                    }
                });
            });
        </script>

        @yield('scripts')
        @stack('scripts')

        {{--
        ==========================================================================
        MODAL PERFIL (Dejado comentado para futuras modificaciones)
        ==========================================================================
        <div id="modalPerfil" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="modalPerfilLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-0 pt-4 px-4 pb-2">
                        <h5 class="modal-title fw-bold text-dark" id="modalPerfilLabel">
                            <i class="fas fa-edit text-primary me-2"></i>Editar Perfil
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 pb-4 pt-2">
                        <ul class="nav nav-pills nav-justified bg-light p-1 rounded-pill mb-4" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active rounded-pill py-2 fw-medium" data-bs-toggle="tab"
                                    data-bs-target="#perfilDatos" type="button" role="tab">
                                    <i class="fas fa-user-circle me-2"></i>Datos del Perfil
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link rounded-pill py-2 fw-medium" data-bs-toggle="tab"
                                    data-bs-target="#perfilPassword" type="button" role="tab">
                                    <i class="fas fa-shield-alt me-2"></i>Contraseña
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content text-secondary">
                            <div class="tab-pane fade show active" id="perfilDatos" role="tabpanel">
                                <form id="formPerfilDatos" action="{{ route('profile.update') }}" method="POST">
                                    @csrf
                                    @method('patch')
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="perfil_name" class="form-label small fw-bold text-uppercase text-muted ms-2">Usuario</label>
                                            <input type="text" class="form-control form-control-lg bg-light border-0 rounded-pill px-4 fs-6" id="perfil_name" name="name" value="{{ Auth::check() ? Auth::user()->name : '' }}" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="perfil_email" class="form-label small fw-bold text-uppercase text-muted ms-2">Correo Electrónico</label>
                                            <input type="email" class="form-control form-control-lg bg-light border-0 rounded-pill px-4 fs-6" id="perfil_email" name="email" value="{{ Auth::check() ? Auth::user()->email : '' }}" required>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                            <i class="fas fa-save me-1"></i> Guardar Cambios
                                        </button>
                                    </div>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="perfilPassword" role="tabpanel">
                                <form id="formPerfilPassword" action="{{ route('password.update') }}" method="POST">
                                    @csrf
                                    @method('put')
                                    <div class="d-flex flex-column gap-3">
                                        <div>
                                            <label for="current_password" class="form-label small fw-bold text-uppercase text-muted ms-2">Contraseña Actual</label>
                                            <input type="password" class="form-control form-control-lg bg-light border-0 rounded-pill px-4 fs-6" id="current_password" name="current_password" required>
                                        </div>
                                        <div>
                                            <label for="password" class="form-label small fw-bold text-uppercase text-muted ms-2">Nueva Contraseña</label>
                                            <input type="password" class="form-control form-control-lg bg-light border-0 rounded-pill px-4 fs-6" id="password" name="password" required>
                                        </div>
                                        <div>
                                            <label for="password_confirmation" class="form-label small fw-bold text-uppercase text-muted ms-2">Confirmar Nueva Contraseña</label>
                                            <input type="password" class="form-control form-control-lg bg-light border-0 rounded-pill px-4 fs-6" id="password_confirmation" name="password_confirmation" required>
                                        </div>
                                    </div>
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-light rounded-pill px-4 me-2" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">
                                            <i class="fas fa-key me-1"></i> Actualizar Contraseña
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        --}}
    </body>

</html>
