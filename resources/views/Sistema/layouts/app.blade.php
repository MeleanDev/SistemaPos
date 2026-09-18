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

    </head>

    <body>
        @include('Sistema.components.preloader')
        <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
            data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
            @include('Sistema.layouts.header')
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

        @yield('scripts')

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
