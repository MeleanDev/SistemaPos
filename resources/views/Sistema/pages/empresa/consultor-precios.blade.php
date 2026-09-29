@extends('Sistema.layouts.app')

@section('titulo', '🏷️ Consultor de Precios')
@section('subtitulo', 'Verificación instantánea de productos, códigos internos y precios con IVA en Dólares ($) y Bolívares (Bs.)')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Consultor de Precios</span>
@endsection

@section('acciones')
    <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill bg-white text-dark border shadow-xs px-3 py-2 font-monospace d-inline-flex align-items-center gap-1.5" style="font-size: 0.85rem;">
            <i class="fas fa-coins text-warning"></i>
            <span class="text-muted">Tasa Activa:</span>
            <strong class="text-primary" id="badgeTasaConsultor">{{ number_format($tasaUsd ?? 1.0, 4, '.', '') }}</strong> <span class="text-muted">Bs.</span>
        </span>
    </div>
@endsection

@section('contenido')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">

            <!-- CAJA DE BÚSQUEDA GIGANTE & LECTOR DE CÓDIGO DE BARRA -->
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 p-md-4 mb-4">
                <form id="formularioConsultorPrecios" onsubmit="event.preventDefault(); ejecutarBusqueda($('#inputBuscarProductoConsultor').val(), true);">
                    <div class="d-flex flex-wrap flex-md-nowrap align-items-center gap-2">
                        <div class="position-relative flex-grow-1">
                            <div class="d-flex align-items-center position-absolute top-50 start-0 translate-middle-y ps-3.5 text-primary pointer-events-none" style="font-size: 1.35rem;">
                                <i class="fas fa-barcode"></i>
                            </div>
                            <input type="text"
                                id="inputBuscarProductoConsultor"
                                class="form-control form-control-lg rounded-pill ps-5 pe-5 border-2 font-monospace shadow-none"
                                placeholder="Escanee código de barra o escriba SKU / Nombre del producto..."
                                autocomplete="off"
                                autofocus
                                style="height: 60px; font-size: 1.15rem; border-color: #cbd5e1;">
                            <div class="position-absolute top-50 end-0 translate-middle-y pe-3 d-flex align-items-center gap-2">
                                <div id="spinnerCargandoConsultor" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <button type="button"
                                    id="btnLimpiarBusqueda"
                                    class="btn btn-sm btn-light rounded-circle p-1 d-flex align-items-center justify-content-center text-muted shadow-xs"
                                    onclick="limpiarBusqueda()"
                                    title="Limpiar búsqueda (Esc)"
                                    style="width: 32px; height: 32px; display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-4 shadow-xs font-monospace fw-bold d-flex align-items-center justify-content-center gap-2" style="height: 60px; min-width: 140px;">
                            <i class="fas fa-search"></i>
                            <span>Buscar</span>
                        </button>
                    </div>
                </form>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 pt-2 border-top font-monospace small text-muted">
                    <div class="d-flex align-items-center gap-3">
                        <span><i class="fas fa-keyboard text-secondary me-1"></i> <kbd class="bg-light text-dark border shadow-xs px-1.5 py-0.5 rounded">Enter</kbd> Consultar</span>
                        <span><i class="fas fa-times-circle text-secondary me-1"></i> <kbd class="bg-light text-dark border shadow-xs px-1.5 py-0.5 rounded">Esc</kbd> Limpiar</span>
                    </div>
                    <div class="text-primary fw-semibold">
                        <i class="fas fa-bolt text-warning me-1"></i> Compatible con pistolas y lectores de código de barra
                    </div>
                </div>
            </div>

            <!-- CONTENEDOR DE RESULTADO (HERO CARD O GRID DE COINCIDENCIAS) -->
            <div id="contenedorResultadoConsultor" style="display: none;"></div>

            <!-- ESTADO INICIAL / KIOSCO EN ESPERA -->
            <div id="contenedorEstadoInicial" class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center">
                <div class="avatar-executive-lg rounded-circle bg-primary bg-opacity-10 text-primary mx-auto mb-4 d-flex align-items-center justify-content-center"
                    style="width: 84px; height: 84px; font-size: 2.5rem;">
                    <i class="fas fa-barcode"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Listo para Consultar Precios</h4>
                <p class="text-muted mx-auto mb-4 font-monospace" style="max-width: 540px;">
                    Acerque el producto al lector de código de barras o escriba su código interno (SKU) / nombre para ver instantáneamente su precio con IVA en Dólares ($) y Bolívares (Bs.).
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-1.5 font-monospace shadow-xs">
                        <i class="fas fa-check text-success me-1"></i> Precios Finales Con IVA
                    </span>
                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-1.5 font-monospace shadow-xs">
                        <i class="fas fa-check text-success me-1"></i> Conversión Multi-Moneda Automática
                    </span>
                    <span class="badge rounded-pill bg-light text-secondary border px-3 py-1.5 font-monospace shadow-xs">
                        <i class="fas fa-check text-success me-1"></i> Búsqueda Rápida por SKU
                    </span>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('estilos/jsPropios/consultorPrecio.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/consultorPrecio.js')) ?: time() }}"></script>
@endsection
