@extends('Sistema.layouts.app')

@section('titulo', '🧾 Historial de Facturas & Ventas')
@section('subtitulo', 'Auditoría integral de comprobantes emitidos, cobros multimoneda, créditos y reimpresión de tickets')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Historial de Facturas</span>
@endsection

@section('acciones')
    <a href="{{ route('pos') }}" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
        <i class="fas fa-cash-register"></i>
        <span>Ir al Punto de Venta (POS)</span>
    </a>
@endsection

@section('contenido')
<div class="container-fluid px-0">

    <!-- 1. KPIS Y TARJETAS RESUMEN DE VENTAS -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">TOTAL FACTURADO HISTÓRICO</span>
                    <div class="avatar-executive-sm rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-dark font-monospace" id="kpiTotalUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-success-subtle text-success rounded-pill font-monospace" id="kpiTotalBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace"><span id="kpiConteoTotal">0</span> Facturas</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">VENTAS DE HOY</span>
                    <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-primary font-monospace" id="kpiTotalHoyUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace" id="kpiTotalHoyBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace"><span id="kpiConteoHoy">0</span> Emitidas Hoy</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">SALDO PENDIENTE A CRÉDITO</span>
                    <div class="avatar-executive-sm rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-warning-emphasis font-monospace" id="kpiTotalCreditoUsd">$ 0.00</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill font-monospace" id="kpiTotalCreditoBs">Bs. 0.00</span>
                    <small class="text-muted font-monospace"><span id="kpiConteoCredito">0</span> Por Cobrar</small>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border rounded-4 shadow-xs bg-white p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small font-monospace fw-semibold">DEVOLUCIONES & ANULACIONES</span>
                    <div class="avatar-executive-sm rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-0 text-danger font-monospace" id="kpiConteoDevueltas">0</h3>
                <div class="d-flex align-items-center justify-content-between mt-2">
                    <span class="badge bg-danger-subtle text-danger rounded-pill font-monospace">Auditoría Kardex</span>
                    <small class="text-muted font-monospace">Movimientos</small>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. BARRA DE FILTROS AVANZADOS -->
    <div class="card border rounded-4 shadow-xs bg-white p-3 mb-3">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-3">
                <label class="form-label-executive mb-1"><i class="fas fa-calendar-alt text-primary me-1"></i> Desde Fecha</label>
                <input type="date" id="filtroFechaInicio" class="form-control form-control-executive font-monospace" onchange="recargarTablaFacturas()">
            </div>
            <div class="col-12 col-md-3">
                <label class="form-label-executive mb-1"><i class="fas fa-calendar-alt text-primary me-1"></i> Hasta Fecha</label>
                <input type="date" id="filtroFechaFin" class="form-control form-control-executive font-monospace" onchange="recargarTablaFacturas()">
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label-executive mb-1"><i class="fas fa-money-check text-secondary me-1"></i> Condición</label>
                <select id="filtroCondicion" class="form-select form-select-executive" onchange="recargarTablaFacturas()">
                    <option value="">Todas</option>
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito (CXC)</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <label class="form-label-executive mb-1"><i class="fas fa-filter text-secondary me-1"></i> Estado</label>
                <select id="filtroEstado" class="form-select form-select-executive" onchange="recargarTablaFacturas()">
                    <option value="">Todos los Estados</option>
                    <option value="completada">Completada</option>
                    <option value="devuelta_parcial">Devuelta Parcial</option>
                    <option value="devuelta_total">Devuelta Total</option>
                    <option value="anulada">Anulada</option>
                </select>
            </div>
            <div class="col-12 col-md-2 text-end">
                <button type="button" class="btn btn-outline-secondary rounded-pill w-100 fw-bold" onclick="limpiarFiltrosFacturas()">
                    <i class="fas fa-broom me-1"></i> Limpiar Filtros
                </button>
            </div>
        </div>
    </div>

    <!-- 3. TABLA PRINCIPAL DE FACTURAS -->
    <x-datatable
        id="datatable_facturas"
        :headers="[
            'Comprobante',
            'Fecha & Hora',
            'Cliente',
            'Almacén',
            'Tipo / Condición',
            'Total Factura',
            'Pagado / Saldo',
            'Estado',
            'Acciones',
        ]"
    />

</div>

<!-- 4. MODAL DETALLE 360° DE LA FACTURA -->
<div class="modal fade" id="modalDetalleFactura" tabindex="-1" aria-labelledby="modalDetalleFacturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-3 bg-white bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; font-size: 1.3rem;">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDetalleFacturaLabel">Detalle Completo de Factura</h5>
                        <small class="text-white-50 font-monospace" id="modalFacturaCodigoHeader">Comprobante #--</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle" id="contenidoDetalleFactura">
                <!-- Cargado dinámicamente por JS -->
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" id="btnImprimirCartaModalDetalle">
                        <i class="fas fa-file-invoice me-1"></i> Factura Carta (Hoja Blanca)
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-3.5 py-2 fw-bold shadow-sm" id="btnReimprimirModalDetalle">
                        <i class="fas fa-receipt me-1"></i> Ticket Térmico
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script>
        const urlFacturasLista = "{{ url('/facturas/lista') }}";
        const urlFacturasKpis = "{{ url('/facturas/kpis') }}";
        const urlFacturasDetalle = "{{ url('/facturas') }}";
        const urlPosImprimirTicket = "{{ url('/pos/imprimir-ticket') }}";
        const urlPosImprimirCarta = "{{ url('/pos/imprimir-carta') }}";
    </script>
    <script src="{{ asset('estilos/jsPropios/facturas.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/facturas.js')) ?: time() }}"></script>
@endsection
