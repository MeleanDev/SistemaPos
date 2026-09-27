@extends('Sistema.layouts.app')

@section('titulo', '🧾 Historial de Facturas & Ventas')
@section('subtitulo', 'Auditoría integral de comprobantes emitidos, cobros multimoneda, créditos y reimpresión de tickets')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Historial de Facturas</span>
@endsection

@section('acciones')
    <x-button href="{{ route('pos') }}" variant="success" icon="fas fa-cash-register" text="Ir al Punto de Venta (POS)" />
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
            <x-input type="date" id="filtroFechaInicio" name="filtroFechaInicio" label="Desde Fecha" icon="fas fa-calendar-alt text-primary" col="col-12 col-md-3" class="font-monospace" onchange="recargarTablaFacturas()" />
            <x-input type="date" id="filtroFechaFin" name="filtroFechaFin" label="Hasta Fecha" icon="fas fa-calendar-alt text-primary" col="col-12 col-md-3" class="font-monospace" onchange="recargarTablaFacturas()" />
            <x-select id="filtroCondicion" name="filtroCondicion" label="Condición" icon="fas fa-money-check text-secondary" col="col-12 col-md-2" onchange="recargarTablaFacturas()">
                <option value="">Todas</option>
                <option value="contado">Contado</option>
                <option value="credito">Crédito (CXC)</option>
            </x-select>
            <x-select id="filtroEstado" name="filtroEstado" label="Estado" icon="fas fa-filter text-secondary" col="col-12 col-md-2" onchange="recargarTablaFacturas()">
                <option value="">Todos los Estados</option>
                <option value="completada">Completada</option>
                <option value="devuelta_parcial">Devuelta Parcial</option>
                <option value="devuelta_total">Devuelta Total</option>
                <option value="anulada">Anulada</option>
            </x-select>
            <div class="col-12 col-md-2 text-end">
                <x-button variant="outline-secondary" class="w-100" icon="fas fa-broom" text="Limpiar Filtros" onclick="limpiarFiltrosFacturas()" />
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
<x-modal
    id="modalDetalleFactura"
    title="Detalle Completo de Factura"
    subtitle="Comprobante #--"
    icon="fas fa-receipt text-success fs-5"
    size="modal-xl"
    headerColor="bg-dark text-white"
    :submitButton="false"
>
    <div id="contenidoDetalleFactura">
        <!-- Cargado dinámicamente por JS -->
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-executive-cancel" data-bs-dismiss="modal">
            <i class="fas fa-times me-1"></i> Cerrar
        </button>
        <div class="d-flex gap-2">
            <x-button variant="primary" id="btnImprimirCartaModalDetalle" icon="fas fa-file-invoice" text="Factura Carta (Hoja Blanca)" />
            <x-button variant="success" id="btnReimprimirModalDetalle" icon="fas fa-receipt" text="Ticket Térmico" />
        </div>
    </x-slot:footer>
</x-modal>
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
