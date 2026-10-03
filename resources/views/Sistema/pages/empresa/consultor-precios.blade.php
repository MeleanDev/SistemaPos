@extends('Sistema.layouts.consultor-layout')

@section('titulo', '🏷️ Kiosco Consultor de Precios')

@section('contenido')
    <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center py-2" style="max-width: 1280px; margin: 0 auto;">

        <!-- INPUT DE CAPTURA DEL ESCÁNER (PERPETUO AUTO-FOCUS) -->
        <form id="formularioConsultorPrecios" class="w-100 mb-3 mb-md-4" style="max-width: 720px;" onsubmit="event.preventDefault(); ejecutarBusqueda($('#inputBuscarProductoConsultor').val(), true);">
            <div class="position-relative w-100">
                <div class="position-absolute text-primary d-flex align-items-center justify-content-center pointer-events-none" style="left: clamp(14px, 2vw, 22px); top: 50%; transform: translateY(-50%); font-size: clamp(1.1rem, 1.4vw, 1.35rem); width: 24px; height: 24px; z-index: 5;">
                    <i class="fas fa-barcode"></i>
                </div>
                <input type="text"
                    id="inputBuscarProductoConsultor"
                    class="form-control form-control-lg rounded-pill font-mono-num"
                    placeholder="Escanee código de barra o escriba SKU / Nombre..."
                    autocomplete="off"
                    autofocus
                    style="min-height: 54px; height: clamp(54px, 6vw, 62px); font-size: clamp(0.92rem, 1.25vw, 1.15rem); padding-left: clamp(46px, 5vw, 62px); padding-right: clamp(46px, 5vw, 62px); background: var(--kiosk-input-bg); color: var(--kiosk-input-text); border: 1px solid var(--kiosk-card-border); box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.15);">
                
                <div class="position-absolute d-flex align-items-center gap-2" style="right: clamp(14px, 2vw, 20px); top: 50%; transform: translateY(-50%); z-index: 5;">
                    <div id="spinnerCargandoConsultor" class="spinner-border spinner-border-sm text-primary" role="status" style="display: none;">
                        <span class="visually-hidden">Buscando...</span>
                    </div>
                    <button type="button"
                        id="btnLimpiarBusqueda"
                        class="btn btn-sm rounded-circle p-1 d-flex align-items-center justify-content-center shadow-xs"
                        onclick="limpiarBusqueda()"
                        title="Limpiar (Esc)"
                        style="width: 32px; height: 32px; display: none; background: var(--kiosk-toggle-bg); color: var(--kiosk-text-muted); border: 1px solid var(--kiosk-card-border);">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </form>

        <!-- CONTENEDOR DINÁMICO DE RESULTADO (HERO CARD O GRID) -->
        <div id="contenedorResultadoConsultor" class="w-100" style="display: none;"></div>

        <!-- ESTADO INICIAL / PANTALLA DE BIENVENIDA Y ESPERA KIOSCO -->
        <div id="contenedorEstadoInicial" class="kiosk-card p-4 p-sm-5 text-center w-100" style="max-width: 860px;">
            <div class="scanner-laser-container mb-3 mb-md-4">
                <div class="scanner-laser-beam"></div>
                <i class="fas fa-barcode" style="font-size: clamp(2.8rem, 4.5vw, 4rem); opacity: 0.35;"></i>
            </div>

            <h1 class="fw-bold kiosk-text-heading mb-2" style="font-size: clamp(1.6rem, 3.2vw, 2.8rem); letter-spacing: -0.03em;">
                Escanee el Código del Producto
            </h1>
            <p class="kiosk-text-sub mx-auto mb-4 font-mono-num" style="max-width: 620px; font-size: clamp(0.88rem, 1.25vw, 1.2rem); line-height: 1.5;">
                Acerque el código de barras al lector para consultar instantáneamente su precio con IVA en <strong class="text-emerald-500">Dólares ($)</strong> y <strong class="text-primary">Bolívares (Bs.)</strong>.
            </p>

            <div class="d-flex flex-wrap justify-content-center gap-2 gap-sm-3">
                <div class="px-3 px-sm-4 py-2 rounded-pill d-flex align-items-center gap-2 fw-semibold" style="background: rgba(16, 185, 129, 0.15); color: #10b981; font-size: clamp(0.78rem, 1vw, 0.92rem); border: 1px solid rgba(16, 185, 129, 0.25);">
                    <i class="fas fa-check-circle"></i>
                    <span>Precios Finales con IVA</span>
                </div>
                <div class="px-3 px-sm-4 py-2 rounded-pill d-flex align-items-center gap-2 fw-semibold" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6; font-size: clamp(0.78rem, 1vw, 0.92rem); border: 1px solid rgba(59, 130, 246, 0.25);">
                    <i class="fas fa-sync-alt"></i>
                    <span>Tasa BCV del Día</span>
                </div>
                <div class="px-3 px-sm-4 py-2 rounded-pill d-flex align-items-center gap-2 fw-semibold" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; font-size: clamp(0.78rem, 1vw, 0.92rem); border: 1px solid rgba(245, 158, 11, 0.25);">
                    <i class="fas fa-bolt"></i>
                    <span>Lectura Instantánea</span>
                </div>
            </div>
        </div>

    </div>
@endsection

@section('scripts')
    <script src="{{ asset('estilos/jsPropios/consultorPrecio.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/consultorPrecio.js')) ?: time() }}"></script>
@endsection
