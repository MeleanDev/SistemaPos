@extends('Sistema.layouts.app')

@section('titulo', '🛒 Punto de Venta (POS)')
@section('subtitulo', 'Facturación rápida, cobros multimoneda, control de caja, créditos y devoluciones')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Punto de Venta (POS)</span>
@endsection

@push('css')
<style>
    .btn-outline-purple {
        color: #7e22ce;
        border-color: #d8b4fe;
        background-color: transparent;
    }
    .btn-outline-purple:hover,
    .btn-check:checked + .btn-outline-purple {
        color: #ffffff;
        background-color: #7e22ce;
        border-color: #7e22ce;
    }
    .bg-purple-subtle {
        background-color: #faf5ff !important;
    }
    .text-purple-emphasis {
        color: #6b21a8 !important;
    }
    .btn-pos-action {
        transition: all 0.2s ease;
        background-color: #ffffff;
        font-size: 0.85rem;
        padding: 0.5rem 0.85rem;
    }
    .btn-pos-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    }
    .fila-pos-item {
        transition: background-color 0.15s ease;
    }
    .fila-pos-item:hover {
        background-color: #f8fafc !important;
    }
    .fila-pos-item.table-active {
        background-color: #eff6ff !important;
    }
    .kbd-shortcut {
        display: inline-block;
        padding: 0.15rem 0.4rem;
        font-size: 0.72rem;
        font-weight: 700;
        font-family: monospace;
        line-height: 1;
        color: #475569;
        background-color: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 0.35rem;
        box-shadow: 0 1px 1px rgba(0,0,0,0.05);
    }
    .card-pos-header {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .total-card-executive {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        border: 1px solid #334155;
        border-radius: 1rem;
    }
</style>
@endpush

@section('contenido')
<div class="container-fluid px-2 px-md-3 py-1">

    <!-- CONTENEDOR PRINCIPAL DEL POS -->
    <div class="row g-2">
        
        <!-- COLUMNA PRINCIPAL: CABECERA, CARRITO, BUSCADOR Y ACCIONES -->
        <div class="col-12">
            <div class="card border rounded-4 shadow-sm bg-white overflow-hidden mb-2">
                
                <!-- ========================================================================= -->
                <!-- 1. CABECERA POS: CLIENTE, DATOS, TIPO DE VENTA (DETAL/MAYOR) & TASA DEL DÍA -->
                <!-- ========================================================================= -->
                <div class="p-2.5 p-md-3 bg-light border-bottom">
                    <div class="row g-2.5 align-items-center">
                        
                        <!-- BÚSQUEDA Y SELECCIÓN DE CLIENTE -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="d-flex flex-column h-100 justify-content-between">
                                <label class="form-label-executive mb-1 text-truncate">
                                    <i class="fas fa-id-card text-primary me-1"></i> Identificación del Cliente
                                </label>
                                <div class="position-relative">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-secondary border-end-0 rounded-start-pill ps-3">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" id="posInputCliente" class="form-control form-control-executive border-start-0 font-monospace ps-1" placeholder="Cédula, RIF o Nombre..." autocomplete="off">
                                        <button type="button" class="btn btn-outline-primary rounded-end-pill px-3" id="btnNuevoClientePos" onclick="abrirModalNuevoCliente()" title="Registrar Nuevo Cliente">
                                            <i class="fas fa-user-plus"></i>
                                        </button>
                                    </div>
                                    <div id="dropdownClientesPos" class="list-group position-absolute w-100 mt-1 shadow-lg rounded-3" style="z-index: 1060; display: none; max-height: 260px; overflow-y: auto; background: #ffffff;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- DATOS DEL CLIENTE SELECCIONADO (CARD INTERACTIVA) -->
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="d-flex flex-column h-100 justify-content-between">
                                <label class="form-label-executive mb-1 text-truncate">
                                    <i class="fas fa-user-check text-success me-1"></i> Cliente Seleccionado
                                </label>
                                <div class="p-2 rounded-3 bg-white border d-flex align-items-center justify-content-between shadow-xs" style="min-height: 44px;">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden me-1">
                                        <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="text-truncate">
                                            <div class="d-flex align-items-center gap-1 text-truncate">
                                                <strong class="text-dark font-monospace text-truncate" style="font-size: 0.88rem;" id="posClienteNombre">Consumidor Final</strong>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill font-monospace px-2 py-0.5" style="font-size: 0.68rem;" id="posClienteTipoBadge">Detal</span>
                                            </div>
                                            <div class="text-muted small font-monospace text-truncate" style="font-size: 0.74rem;">
                                                <span id="posClienteCedula">V-00000000</span> • <span id="posClienteTelefono">Sin teléfono</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2 py-1" onclick="abrirModalEditarCliente()" title="Modificar Datos del Cliente" style="font-size: 0.75rem;">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1" onclick="resetearClienteDefecto()" title="Restablecer Consumidor Final" style="font-size: 0.75rem;">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SELECTOR / CHECKLIST: VENTA AL DETAL VS VENTA AL MAYOR -->
                        <div class="col-12 col-md-6 col-xl-3">
                            <div class="d-flex flex-column h-100 justify-content-between">
                                <label class="form-label-executive mb-1 text-truncate">
                                    <i class="fas fa-tags text-primary me-1"></i> Modalidad de Venta
                                </label>
                                <div class="btn-group w-100 shadow-xs" role="group" aria-label="Tipo de Venta" style="height: 44px;">
                                    <input type="radio" class="btn-check" name="pos_tipo_venta" id="tipoVentaDetal" value="detal" checked onchange="cambiarTipoVenta('detal')">
                                    <label class="btn btn-outline-primary rounded-start-pill fw-bold d-flex align-items-center justify-content-center py-1" for="tipoVentaDetal" style="font-size: 0.84rem;">
                                        <i class="fas fa-store me-1.5"></i> Venta al Detal
                                    </label>

                                    <input type="radio" class="btn-check" name="pos_tipo_venta" id="tipoVentaMayor" value="mayor" onchange="cambiarTipoVenta('mayor')">
                                    <label class="btn btn-outline-purple rounded-end-pill fw-bold d-flex align-items-center justify-content-center py-1" for="tipoVentaMayor" style="font-size: 0.84rem;">
                                        <i class="fas fa-boxes-stacked me-1.5"></i> Venta al Mayor
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- TASA DEL DÍA, ALMACÉN & PANTALLA COMPLETA -->
                        <div class="col-12 col-md-6 col-xl-2">
                            <div class="d-flex flex-column h-100 justify-content-between">
                                <div class="d-flex align-items-center justify-content-between mb-1">
                                    <label class="form-label-executive mb-0 text-truncate">
                                        <i class="fas fa-coins text-success me-1"></i> Tasa & Almacén
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0" onclick="toggleSidebarMenu()" title="Contraer/Expandir Menú Lateral (Ctrl+B)" style="font-size: 0.70rem;">
                                        <i class="fas fa-bars"></i>
                                    </button>
                                </div>
                                <div class="p-1.5 rounded-3 bg-white border d-flex flex-column justify-content-center shadow-xs" style="min-height: 44px;">
                                    <div class="d-flex align-items-center justify-content-between mb-0.5">
                                        <span class="badge rounded-pill px-2 py-0.5 fw-bold font-monospace text-truncate" style="background-color: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.80rem;">
                                            <i class="fas fa-dollar-sign me-0.5"></i> 1 USD = <span id="posBadgeTasaDia">1.0000</span> Bs.
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center gap-1 font-monospace text-muted small" style="font-size: 0.72rem;">
                                        <i class="fas fa-warehouse text-secondary"></i>
                                        <select id="posSelectAlmacen" class="form-select form-select-sm py-0 ps-1 pe-3 border-0 bg-transparent fw-semibold text-dark text-truncate" style="font-size: 0.74rem;" onchange="cambiarAlmacenActivo()">
                                            <!-- Almacenes cargados por JS -->
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 2. TABLA DE PRODUCTOS (CARRITO DE VENTA)                                 -->
                <!-- ========================================================================= -->
                <div class="table-responsive" style="min-height: 320px; max-height: calc(100vh - 430px); overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="tablaPosVenta">
                        <thead class="table-light sticky-top font-monospace" style="z-index: 5; font-size: 0.78rem;">
                            <tr>
                                <th style="width: 120px;">Código</th>
                                <th style="min-width: 250px;">Producto / Descripción</th>
                                <th class="text-center" style="width: 140px;">Cantidad</th>
                                <th class="text-end" style="width: 140px;">Precio Unit.</th>
                                <th class="text-center" style="width: 110px;">IVA</th>
                                <th class="text-end" style="width: 150px;">Total Renglón</th>
                                <th class="text-center" style="width: 60px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="contenedorFilasPos">
                            <tr id="filaPosVacia">
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-cash-register fa-3x mb-3 text-secondary opacity-50 d-block"></i>
                                    <h6 class="fw-bold text-dark mb-1">Carrito de Venta Vacío</h6>
                                    <span class="small">Escanea un código de barras o escribe <strong class="text-primary">*código</strong> para especificar cantidad y almacén.</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ========================================================================= -->
                <!-- 3. CAMPO INFERIOR DE BÚSQUEDA / ESCÁNER Y CONSULTA RÁPIDA                -->
                <!-- ========================================================================= -->
                <div class="p-2.5 bg-light border-top position-relative">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-9 col-sm-8">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white text-primary border-end-0 rounded-start-pill ps-3">
                                    <i class="fas fa-barcode fs-4"></i>
                                </span>
                                <input type="text" id="posInputBuscadorProducto" class="form-control form-control-executive border-start-0 rounded-end-pill font-monospace ps-2" placeholder="Escanear código / serial (NIV, Chasis, Motor) o escribir *código para elegir cantidad y almacén... [Enter]" autocomplete="off">
                            </div>
                            <!-- RESULTADOS FLOTANTES DE BÚSQUEDA INTERACTIVA -->
                            <div id="dropdownProductosPos" class="list-group position-absolute w-100 start-0 mt-1 shadow-lg rounded-4" style="z-index: 1070; display: none; max-height: 320px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1;"></div>
                        </div>

                        <div class="col-md-3 col-sm-4 text-end">
                            <span class="badge bg-white text-dark border font-monospace px-3 py-2 fw-bold shadow-xs rounded-pill" style="font-size: 0.85rem;" id="posContadorItems">
                                <i class="fas fa-shopping-basket text-primary me-1"></i> 0 Ítems (0 Unid.)
                            </span>
                        </div>
                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 4. PANEL DE TOTALES: SUB-TOTALES, TOTAL IVA Y TOTAL EN VENTA              -->
                <!-- ========================================================================= -->
                <div class="p-3 bg-white border-top">
                    <div class="row g-2 align-items-center">
                        
                        <!-- SUB-TOTAL NETO -->
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-4 bg-light border d-flex align-items-center justify-content-between shadow-xs">
                                <div>
                                    <span class="text-muted small font-monospace d-block">Sub-Total Neto:</span>
                                    <h5 class="fw-bold mb-0 text-dark font-monospace" id="posTotalNetoUsd">$ 0.00</h5>
                                </div>
                                <span class="badge rounded-pill px-2.5 py-1 font-monospace fw-semibold shadow-xs" id="posTotalNetoBs" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 0.84rem;">
                                    Bs. 0.00
                                </span>
                            </div>
                        </div>

                        <!-- TOTAL EN IVA -->
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 rounded-4 bg-light border d-flex align-items-center justify-content-between shadow-xs">
                                <div>
                                    <span class="text-muted small font-monospace d-block">Total en IVA:</span>
                                    <h5 class="fw-bold mb-0 text-dark font-monospace" id="posTotalIvaUsd">$ 0.00</h5>
                                </div>
                                <span class="badge rounded-pill px-2.5 py-1 font-monospace fw-semibold shadow-xs" id="posTotalIvaBs" style="background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 0.84rem;">
                                    Bs. 0.00
                                </span>
                            </div>
                        </div>

                        <!-- TOTAL EN VENTA DESTACADO -->
                        <div class="col-12 col-md-4">
                            <div class="p-2.5 total-card-executive text-white d-flex align-items-center justify-content-between shadow-sm">
                                <div>
                                    <span class="text-white-50 small font-monospace d-block">TOTAL A PAGAR:</span>
                                    <h3 class="fw-bold mb-0 text-warning font-monospace" id="posTotalVentaUsd" style="letter-spacing: -0.5px;">$ 0.00</h3>
                                </div>
                                <div class="text-end">
                                    <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs d-block" id="posTotalVentaBs" style="background-color: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.25); font-size: 0.95rem;">
                                        Bs. 0.00
                                    </span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ========================================================================= -->
                <!-- 5. BARRA DE ATAJOS Y BOTONES DE ACCIÓN (FOOTER DEL POS)                  -->
                <!-- ========================================================================= -->
                <div class="p-3 bg-light border-top">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        
                        <!-- BOTONES DE ATAJO SECUNDARIOS -->
                        <div class="d-flex flex-wrap gap-1.5 align-items-center">
                            <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold shadow-xs btn-pos-action" onclick="abrirModalReimprimir()" title="Reimprimir Ticket [F2]">
                                <i class="fas fa-print me-1 text-primary"></i> <span class="d-none d-md-inline">Reimprimir</span> <span class="kbd-shortcut ms-1">F2</span>
                            </button>

                            <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold shadow-xs btn-pos-action" onclick="abrirModalDevolucion()" title="Devolución de Factura [F6]">
                                <i class="fas fa-undo-alt me-1 text-warning"></i> <span class="d-none d-md-inline">Devolución</span> <span class="kbd-shortcut ms-1">F6</span>
                            </button>

                            <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold shadow-xs btn-pos-action" onclick="abrirModalCuentasEspera()" title="Cuentas en Espera [F7]">
                                <i class="fas fa-pause-circle me-1 text-info"></i> <span class="d-none d-md-inline">Cuentas en Espera</span> <span class="kbd-shortcut ms-1">F7</span>
                            </button>

                            <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold shadow-xs btn-pos-action" onclick="abrirModalConsultaProducto()" title="Consultar Producto [F8]">
                                <i class="fas fa-search me-1 text-success"></i> <span class="d-none d-md-inline">Consultar</span> <span class="kbd-shortcut ms-1">F8</span>
                            </button>

                            <button type="button" class="btn btn-outline-secondary rounded-pill fw-bold shadow-xs btn-pos-action" onclick="modificarRenglonSeleccionado()" title="Modificar Renglón Seleccionado [F9]">
                                <i class="fas fa-edit me-1 text-secondary"></i> <span class="d-none d-md-inline">Modificar</span> <span class="kbd-shortcut ms-1">F9</span>
                            </button>

                            <button type="button" class="btn btn-outline-danger rounded-pill fw-bold shadow-xs btn-pos-action" onclick="limpiarPantallaPos()" title="Limpiar Pantalla [F10]">
                                <i class="fas fa-trash-alt me-1"></i> <span class="d-none d-md-inline">Limpiar</span> <span class="kbd-shortcut ms-1">F10</span>
                            </button>
                        </div>

                        <!-- BOTONES PRINCIPALES: FACTURAR & SALIR -->
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold" title="Salir al Dashboard [Esc]">
                                <i class="fas fa-sign-out-alt me-1"></i> <span class="d-none d-sm-inline">Salir</span> <span class="kbd-shortcut ms-1">Esc</span>
                            </a>

                            <button type="button" class="btn btn-success rounded-pill px-4 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2" id="btnFacturarPos" onclick="abrirModalCobro()" style="font-size: 1.05rem;">
                                <i class="fas fa-cash-register fs-5"></i>
                                <span>Facturar</span>
                                <span class="badge bg-white text-success rounded-pill px-2 py-0.5 font-monospace fw-bold" style="font-size: 0.78rem;">F4</span>
                            </button>
                        </div>

                    </div>
                </div>

            </div>
        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- MODAL DE COBRO Y FACTURACIÓN MULTIMONEDA (PAGOS, VUELTO & CRÉDITO CXC)    -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCobroVenta" tabindex="-1" aria-labelledby="modalCobroVentaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-cash-register text-success fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalCobroVentaLabel">Procesar Pago & Facturar Venta</h5>
                        <small class="text-white-50">Ingresa los métodos de pago recibidos del cliente</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                
                <!-- RESUMEN DE TOTAL A PAGAR -->
                <div class="card card-executive border-0 p-3 mb-3 rounded-4 shadow-sm text-white" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-white-50 small font-monospace">TOTAL FACTURA A PAGAR:</span>
                            <h2 class="fw-bold mb-0 text-warning font-monospace" id="cobroModalTotalUsd">$ 0.00</h2>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs" style="background-color: rgba(255, 255, 255, 0.15); color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.25); font-size: 1.05rem;" id="cobroModalTotalBs">
                                Bs. 0.00
                            </span>
                            <small class="text-white-50 d-block mt-1 font-monospace" style="font-size: 0.72rem;">Tasa: <span id="cobroModalTasa">1.0000</span> Bs.</small>
                        </div>
                    </div>
                </div>

                <!-- CONDICIÓN DE PAGO: CONTADO VS CRÉDITO (CXC) -->
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-6">
                            <label class="form-label-executive mb-1"><i class="fas fa-hand-holding-usd text-primary me-1"></i> Condición de Pago</label>
                            <select id="cobroCondicionPago" class="form-select form-select-executive" onchange="toggleCondicionPagoCobro()">
                                <option value="contado" selected>Contado (Pago Total Inmediato)</option>
                                <option value="credito">Crédito / Cuenta por Cobrar (CXC)</option>
                            </select>
                        </div>

                        <div class="col-md-6" id="contenedorDiasCreditoPos" style="display: none;">
                            <label class="form-label-executive mb-1"><i class="fas fa-clock text-warning me-1"></i> Días de Crédito Otorgados</label>
                            <div class="input-group">
                                <input type="number" id="cobroDiasCredito" class="form-control form-control-executive font-monospace" value="15" min="1" max="365" oninput="calcularVencimientoCobro()">
                                <span class="input-group-text bg-light text-muted small font-monospace" id="cobroFechaVenceBadge">Vence: --</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- FORMULARIO DE INGRESO DE FORMA DE PAGO -->
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-plus-circle text-success me-1"></i> Agregar Forma de Pago</h6>
                    
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label-executive mb-1">Método de Pago</label>
                            <select id="cobroSelectMetodo" class="form-select form-select-executive font-monospace" onchange="actualizarMonedaPagoSeleccionada()">
                                <!-- Cargados dinámicamente -->
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label-executive mb-1">Monto a Entregar</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white fw-bold text-primary font-monospace" id="cobroSimboloMonedaPago">$</span>
                                <input type="number" step="any" min="0.01" id="cobroInputMonto" class="form-control form-control-executive font-monospace fw-bold text-end" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label-executive mb-1">Referencia <small class="text-muted">(Opcional)</small></label>
                            <input type="text" id="cobroInputReferencia" class="form-control form-control-executive font-monospace" placeholder="Ej. 1234">
                        </div>

                        <div class="col-md-1 text-end">
                            <button type="button" class="btn btn-primary rounded-circle shadow-xs" onclick="agregarPagoALista()" title="Añadir Pago" style="width: 40px; height: 40px; padding: 0;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TABLA DE PAGOS AGREGADOS -->
                <div class="card border rounded-4 mb-3 bg-white shadow-xs overflow-hidden">
                    <div class="p-2.5 bg-light border-bottom d-flex align-items-center justify-content-between">
                        <strong class="text-dark small"><i class="fas fa-list-check text-primary me-1"></i> Pagos Registrados</strong>
                        <span class="badge bg-secondary rounded-pill font-monospace" id="cobroContadorPagos">0 Pagos</span>
                    </div>
                    <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0" id="tablaCobroPagos">
                            <thead class="table-light font-monospace small">
                                <tr>
                                    <th>Método</th>
                                    <th>Moneda</th>
                                    <th class="text-end">Monto Original</th>
                                    <th class="text-end">Equivalente USD</th>
                                    <th>Referencia</th>
                                    <th class="text-center" style="width: 40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasCobroPagos">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3 small">No has ingresado ningún pago aún.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- BALANCES: TOTAL PAGADO, FALTANTE Y VUELTO -->
                <div class="row g-2 font-monospace">
                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small">Total Pagado:</span>
                            <strong class="text-dark" id="cobroBalancePagadoUsd">$ 0.00</strong>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small" id="cobroLabelFaltante">Resta por Pagar:</span>
                            <strong class="text-danger" id="cobroBalanceFaltanteUsd">$ 0.00</strong>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-2.5 rounded-3 bg-white border d-flex justify-content-between align-items-center shadow-xs">
                            <span class="text-muted small">Cambio / Vuelto:</span>
                            <strong class="text-success" id="cobroBalanceVueltoUsd">$ 0.00</strong>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success rounded-pill px-5 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2" id="btnConfirmarVentaFinal" onclick="procesarVentaFinal()">
                    <i class="fas fa-check-circle fs-5"></i>
                    <span>Completar Venta & Imprimir Ticket</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE DEVOLUCIÓN DE VENTA                                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDevolucion" tabindex="-1" aria-labelledby="modalDevolucionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-undo-alt text-warning fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDevolucionLabel">Devolución de Factura & Reintegro de Inventario</h5>
                        <small class="text-white-50">Busca la factura emitida para revertir los productos al stock</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                <!-- BUSCADOR DE FACTURA -->
                <div class="row g-2 mb-3">
                    <div class="col-md-9">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-white"><i class="fas fa-receipt text-primary"></i></span>
                            <input type="text" id="devInputBusquedaFactura" class="form-control form-control-executive font-monospace" placeholder="Ingresa el código de comprobante (Ej: VEN-00001)...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="button" class="btn btn-primary btn-lg rounded-pill w-100 fw-bold shadow-xs" onclick="buscarFacturaParaDevolucion()">
                            <i class="fas fa-search me-1"></i> Buscar Factura
                        </button>
                    </div>
                </div>

                <!-- CONTENEDOR DE DATOS DE FACTURA ENCONTRADA -->
                <div id="contenedorDetallesFacturaDevolucion" style="display: none;">
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom pb-2 mb-2">
                            <div>
                                <h6 class="fw-bold text-dark mb-0" id="devFacturaCodigo">VEN-00001</h6>
                                <span class="text-muted small" id="devFacturaCliente">Cliente: --</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 font-monospace fw-bold" onclick="marcarTodoDevolucion()">
                                    <i class="fas fa-check-double me-1"></i> Devolver Todo
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-2.5 py-1 font-monospace" onclick="desmarcarTodoDevolucion()">
                                    <i class="fas fa-times me-1"></i> Limpiar
                                </button>
                                <div class="text-end ms-2">
                                    <span class="badge bg-success rounded-pill px-3 py-1 font-monospace fw-bold" id="devFacturaTotal">$ 0.00</span>
                                    <small class="text-muted d-block" id="devFacturaFecha">Fecha: --</small>
                                </div>
                            </div>
                        </div>

                        <!-- TABLA DE RENGLONES A DEVOLVER -->
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle mb-0">
                                <thead class="table-light font-monospace small">
                                    <tr>
                                        <th>Producto</th>
                                        <th class="text-center">Cant. Facturada</th>
                                        <th class="text-end">Precio Unit.</th>
                                        <th class="text-center" style="width: 140px;">Cant. a Devolver</th>
                                        <th class="text-end" style="width: 120px;">Subtotal Dev.</th>
                                    </tr>
                                </thead>
                                <tbody id="contenedorFilasItemsDevolucion">
                                    <!-- Cargados dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- MOTIVO DE DEVOLUCIÓN -->
                    <div class="card border rounded-4 p-3 bg-white shadow-xs">
                        <label class="form-label-executive mb-1"><i class="fas fa-comment text-secondary me-1"></i> Motivo de la Devolución <span class="text-danger">*</span></label>
                        <input type="text" id="devInputMotivo" class="form-control form-control-executive" placeholder="Ej. Devolución de cliente en mostrador..." value="Devolución de cliente en mostrador">
                    </div>
                </div>

            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <button type="button" class="btn btn-warning rounded-pill px-5 py-2 fw-bold shadow-sm" id="btnConfirmarDevolucion" style="display: none;" onclick="ejecutarDevolucion()">
                    <i class="fas fa-undo me-1"></i> Procesar Devolución & Reintegrar Stock
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE CUENTAS EN ESPERA (PAUSAR / RECUPERAR)                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalCuentasEspera" tabindex="-1" aria-labelledby="modalCuentasEsperaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-pause-circle text-info fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalCuentasEsperaLabel">Cuentas en Espera (Pedidos Pausados)</h5>
                        <small class="text-white-50">Pausa la venta actual para atender a otro cliente o recupera un pedido guardado</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                <!-- PAUSAR VENTA ACTUAL -->
                <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-bookmark text-primary me-1"></i> Pausar Venta Actual</h6>
                    <div class="input-group">
                        <input type="text" id="inputNotaEspera" class="form-control form-control-executive font-monospace" placeholder="Nota o referencia (Ej. Cliente Camisa Azul, Mostrador #2)...">
                        <button type="button" class="btn btn-primary rounded-end-pill px-4 fw-bold" onclick="guardarCarritoEnEspera()">
                            <i class="fas fa-save me-1"></i> Poner en Espera
                        </button>
                    </div>
                </div>

                <!-- LISTA DE CUENTAS EN ESPERA -->
                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden">
                    <div class="p-2.5 bg-light border-bottom">
                        <strong class="text-dark small"><i class="fas fa-history text-secondary me-1"></i> Ventas Pausadas Disponibles</strong>
                    </div>
                    <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light font-monospace small">
                                <tr>
                                    <th>Referencia / Nota</th>
                                    <th>Cliente</th>
                                    <th>Hora</th>
                                    <th class="text-end">Total USD</th>
                                    <th class="text-center" style="width: 140px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasCuentasEspera">
                                <!-- Cargados dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE CONSULTA DE PRODUCTO / VERIFICADOR DE PRECIOS & EXISTENCIAS      -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalConsultaProducto" tabindex="-1" aria-labelledby="modalConsultaProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-search-dollar text-success fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalConsultaProductoLabel">Verificador de Precios & Existencias</h5>
                        <small class="text-white-50">Consulta precios detal, mayorista y stock disponible. Haz clic en cualquier producto para cargarlo a la venta.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                <div class="input-group input-group-lg mb-3">
                    <span class="input-group-text bg-white border-end-0 rounded-start-pill ps-3"><i class="fas fa-search text-primary"></i></span>
                    <input type="text" id="inputConsultaProdFiltro" class="form-control form-control-executive border-start-0 rounded-end-pill font-monospace" placeholder="Buscar por código de barras, SKU, nombre o categoría..." oninput="filtrarConsultaProductos()">
                </div>

                <div class="card border rounded-4 bg-white shadow-xs overflow-hidden">
                    <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light sticky-top font-monospace small" style="z-index: 3;">
                                <tr>
                                    <th style="width: 120px;">Código</th>
                                    <th>Producto / Descripción</th>
                                    <th class="text-center" style="width: 140px;">Stock Total</th>
                                    <th class="text-end" style="width: 140px;">Precio Detal</th>
                                    <th class="text-end" style="width: 140px;">Precio Mayor</th>
                                    <th class="text-center" style="width: 100px;">IVA</th>
                                    <th class="text-center" style="width: 110px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="contenedorFilasConsultaProductos">
                                <!-- Cargados dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE SELECCIÓN DE CANTIDAD Y ALMACÉN (PREFIJO * O EDICIÓN DETALLADA) -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalDetalleVentaProducto" tabindex="-1" aria-labelledby="modalDetalleVentaProductoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar-executive-sm rounded-circle bg-warning bg-opacity-20 text-warning d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fas fa-boxes-stacked fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalDetalleVentaProductoLabel">Detalle de Renglón & Despacho</h5>
                        <small class="text-white-50">Configura la cantidad exacta a vender y el almacén de salida</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formDetalleVentaProducto" onsubmit="confirmarAgregarConDetalle(event)">
                <input type="hidden" id="modalDetalleProdId">
                <input type="hidden" id="modalDetalleProdTipo">

                <div class="modal-body p-4 bg-light-subtle">
                    <!-- INFORMACIÓN DEL ARTÍCULO SELECCIONADO -->
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 border-bottom pb-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div id="modalDetalleIcono" class="avatar-executive-sm rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; font-size: 1.3rem;">
                                    <i class="fas fa-box"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0 font-monospace" id="modalDetalleProdNombre">Nombre del Producto</h6>
                                    <div class="small font-monospace text-muted mt-0.5">
                                        <span id="modalDetalleProdCodigo" class="badge bg-light text-secondary border">#0000</span>
                                        <span id="modalDetalleProdCategoria" class="ms-1">General</span>
                                        <span id="modalDetalleBadgeIva" class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle font-monospace ms-1">IVA 16%</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end font-monospace">
                                <span class="text-muted small d-block">Tarifa Detal / Mayor:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold" id="modalDetallePrecioDetal">$ 0.00</span>
                                    <span class="badge bg-purple-subtle text-purple-emphasis border border-purple-subtle rounded-pill px-2.5 py-1 fw-bold" id="modalDetallePrecioMayor">$ 0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- SI ES MOTO / SERIAL MUESTRA DETALLES VEHICULARES -->
                        <div id="modalDetalleContenedorMoto" class="p-2 rounded-3 bg-warning-subtle text-warning-emphasis font-monospace small mb-2" style="display: none;">
                            <div class="row g-1">
                                <div class="col-md-4"><strong>NIV:</strong> <span id="modalDetalleNiv">--</span></div>
                                <div class="col-md-4"><strong>Motor:</strong> <span id="modalDetalleMotor">--</span></div>
                                <div class="col-md-4"><strong>Chasis:</strong> <span id="modalDetalleChasis">--</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- SELECTOR DE ALMACÉN Y STOCK DISPONIBLE -->
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <label class="form-label-executive mb-1"><i class="fas fa-warehouse text-primary me-1"></i> Almacén de Despacho <span class="text-danger">*</span></label>
                                <select id="modalDetalleSelectAlmacen" class="form-select form-select-executive font-monospace" onchange="actualizarStockAlmacenModalDetalle()">
                                    <!-- Opciones cargadas dinámicamente con stock -->
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label-executive mb-1"><i class="fas fa-cubes text-secondary me-1"></i> Stock en Almacén</label>
                                <div class="p-2 rounded-3 bg-light border d-flex align-items-center justify-content-between font-monospace" style="min-height: 42px;">
                                    <span class="text-muted small">Disponible:</span>
                                    <span id="modalDetalleStockBadge" class="badge bg-success rounded-pill px-3 py-1 fw-bold fs-6">0 UND</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SELECCIÓN DE CANTIDAD Y DESCUENTO -->
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-6">
                                <label class="form-label-executive mb-1"><i class="fas fa-calculator text-primary me-1"></i> Cantidad a Vender <span class="text-danger">*</span></label>
                                <div class="input-group input-group-lg">
                                    <button class="btn btn-outline-secondary px-3" type="button" onclick="alterarCantidadModalDetalle(-1)">-</button>
                                    <input type="number" step="any" min="0.001" id="modalDetalleInputCantidad" class="form-control form-control-executive text-center font-monospace fw-bold fs-4" value="1" oninput="actualizarSubtotalModalDetalle()" required>
                                    <button class="btn btn-outline-secondary px-3" type="button" onclick="alterarCantidadModalDetalle(1)">+</button>
                                </div>
                                <!-- BOTONES DE PRESET DE CANTIDADES RÁPIDAS -->
                                <div class="d-flex flex-wrap gap-1 mt-2" id="modalDetallePresets">
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(1)">+1</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(5)">+5</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(10)">+10</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(25)">+25</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(50)">+50</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill font-monospace px-2 py-0.5" onclick="sumarPresetModalDetalle(100)">+100</button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label-executive mb-1"><i class="fas fa-percent text-warning me-1"></i> Descuento Especial (%)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light text-muted font-monospace">%</span>
                                    <input type="number" step="any" min="0" max="100" id="modalDetalleInputDescuento" class="form-control form-control-executive font-monospace text-center fw-bold" value="0" oninput="actualizarSubtotalModalDetalle()">
                                </div>
                                <small class="text-muted font-monospace mt-1 d-block">Aplica únicamente a este renglón</small>
                            </div>
                        </div>
                    </div>

                    <!-- RESUMEN EN TIEMPO REAL DEL RENGLÓN -->
                    <div class="p-3 rounded-4 bg-light border d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <span class="text-muted small font-monospace d-block">Subtotal Estimado Renglón:</span>
                            <h4 class="fw-bold mb-0 text-dark font-monospace" id="modalDetalleSubtotalUsd">$ 0.00</h4>
                        </div>
                        <div class="text-end">
                            <span class="badge rounded-pill px-3 py-1.5 font-monospace fw-bold shadow-xs" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 1rem;" id="modalDetalleSubtotalBs">
                                Bs. 0.00
                            </span>
                            <small class="text-muted font-monospace d-block mt-0.5">Tasa: <span id="modalDetalleTasa">1.0000</span> Bs.</small>
                        </div>
                    </div>

                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-success rounded-pill px-5 py-2.5 fw-bold shadow-sm d-flex align-items-center gap-2">
                        <i class="fas fa-cart-plus fs-5"></i>
                        <span>Agregar al Carrito [Enter]</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL DE REIMPRESIÓN DE TICKET                                            -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalReimprimirTicket" tabindex="-1" aria-labelledby="modalReimprimirTicketLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-print text-primary fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalReimprimirTicketLabel">Reimprimir Comprobante / Ticket</h5>
                        <small class="text-white-50">Ingresa el código de comprobante para reimprimir</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-4 bg-light-subtle">
                <label class="form-label-executive mb-1">Código de Factura (Ej: VEN-00001)</label>
                <div class="input-group input-group-lg">
                    <span class="input-group-text bg-white"><i class="fas fa-receipt text-primary"></i></span>
                    <input type="text" id="inputCodigoReimprimir" class="form-control form-control-executive font-monospace" placeholder="VEN-00001">
                </div>
            </div>

            <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancelar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary rounded-pill px-3.5 py-2 fw-bold shadow-sm" onclick="ejecutarReimpresionCarta()">
                        <i class="fas fa-file-invoice me-1"></i> Factura Carta
                    </button>
                    <button type="button" class="btn btn-success rounded-pill px-3.5 py-2 fw-bold shadow-sm" onclick="ejecutarReimpresionTicket()">
                        <i class="fas fa-receipt me-1"></i> Ticket Térmico
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL RÁPIDO DE CLIENTE (CREAR O EDITAR)                                   -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalRapidoClientePos" tabindex="-1" aria-labelledby="modalRapidoClientePosLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white border-0 py-3 px-4 rounded-top-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-user-tag text-warning fs-4"></i>
                    <div>
                        <h5 class="modal-title fw-bold mb-0 text-white" id="modalRapidoClientePosLabel">Cliente</h5>
                        <small class="text-white-50">Ingresa los datos fiscales para emitir la factura</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="formRapidoClientePos" onsubmit="guardarClienteRapidoPos(event)">
                <input type="hidden" name="cliente_id" id="rapidoClienteId">
                <div class="modal-body p-4 bg-light-subtle">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-input name="cedula" id="rapido_cli_cedula" label="Cédula / RIF" icon="fas fa-id-card" placeholder="Ej. V-12345678 o J-12345678-0" required maxlength="20" />
                        </div>
                        <div class="col-md-6">
                            <x-select name="tipo_cliente" id="rapido_cli_tipo" label="Tipo de Cliente" icon="fas fa-tags" required>
                                <option value="detal" selected>Cliente al Detal</option>
                                <option value="mayorista">Cliente Mayorista</option>
                            </x-select>
                        </div>
                        <div class="col-md-6">
                            <x-input name="nombre" id="rapido_cli_nombre" label="Nombre" icon="fas fa-user" placeholder="Ej. Juan" required maxlength="100" />
                        </div>
                        <div class="col-md-6">
                            <x-input name="apellido" id="rapido_cli_apellido" label="Apellido" icon="fas fa-user" placeholder="Ej. Pérez" required maxlength="100" />
                        </div>
                        <div class="col-md-6">
                            <x-input name="telefono" id="rapido_cli_telefono" label="Teléfono" icon="fas fa-phone" placeholder="Ej. 0414-1234567" optionalText="Opcional" />
                        </div>
                        <div class="col-md-6">
                            <x-input type="email" name="correo" id="rapido_cli_correo" label="Correo Electrónico" icon="fas fa-envelope" placeholder="cliente@correo.com" optionalText="Opcional" />
                        </div>
                        <div class="col-12">
                            <x-input name="direccion" id="rapido_cli_direccion" label="Dirección Fiscal / Habitación" icon="fas fa-map-marker-alt" placeholder="Dirección..." optionalText="Opcional" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light border-0 py-3 px-4 rounded-bottom-4 d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    const urlDashboard = "{{ route('dashboard') }}";
    const urlPosDatos = "{{ url('/pos/datos') }}";
    const urlPosBuscarClientes = "{{ url('/pos/buscar-clientes') }}";
    const urlPosGuardarCliente = "{{ url('/pos/guardar-cliente-rapido') }}";
    const urlPosGuardarVenta = "{{ url('/pos/guardar') }}";
    const urlPosEnEsperaGuardar = "{{ url('/pos/en-espera/guardar') }}";
    const urlPosEnEsperaLista = "{{ url('/pos/en-espera/lista') }}";
    const urlPosEnEsperaRecuperar = "{{ url('/pos/en-espera') }}";
    const urlPosEnEsperaEliminar = "{{ url('/pos/en-espera') }}";
    const urlPosDevolucionBuscar = "{{ url('/pos/devolucion/buscar') }}";
    const urlPosDevolucionProcesar = "{{ url('/pos/devolucion/procesar') }}";
    const urlPosImprimir = "{{ url('/pos/imprimir') }}";
    const urlPosImprimirCarta = "{{ url('/pos/imprimir-carta') }}";
    const urlPosImprimirTicket = "{{ url('/pos/imprimir-ticket') }}";
</script>
<script src="{{ asset('estilos/jsPropios/pos.js') }}"></script>
@endsection
