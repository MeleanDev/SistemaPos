@extends('Sistema.layouts.app')

@section('titulo', '💰 Cuentas por Cobrar (CXC)')
@section('subtitulo', 'Control de créditos de clientes, facturas pendientes, abonos específicos y amortizaciones FIFO')

@section('rutas')
    <span class="active">Cuentas por Cobrar</span>
@endsection

@section('contenido')
    <!-- KPIs SUPERIORES -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-gradient" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);">
                <div class="card-body p-3 text-white">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-white-50 small fw-bold text-uppercase">Total por Cobrar</span>
                        <div class="bg-primary bg-opacity-25 rounded-circle p-2 text-primary">
                            <i class="fas fa-hand-holding-usd fs-5 text-info"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-white" id="kpi_total_usd">$0.00</h3>
                    <div class="small text-info fw-semibold" id="kpi_total_bs">Bs. 0.00</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Clientes Deudores</span>
                        <div class="bg-info bg-opacity-10 rounded-circle p-2 text-info">
                            <i class="fas fa-users fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark" id="kpi_clientes_count">0</h3>
                    <div class="small text-muted">Con facturas a crédito activas</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Facturas Pendientes</span>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 text-warning">
                            <i class="fas fa-file-invoice-dollar fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-dark" id="kpi_facturas_count">0</h3>
                    <div class="small text-muted">Documentos por liquidar</div>
                </div>
            </div>
        </div>

        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted small fw-bold text-uppercase">Facturas Vencidas</span>
                        <div class="bg-danger bg-opacity-10 rounded-circle p-2 text-danger">
                            <i class="fas fa-exclamation-triangle fs-5"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold mb-1 text-danger" id="kpi_vencidas_count">0</h3>
                    <div class="small text-danger fw-semibold" id="kpi_vencidas_monto">$0.00 en mora</div>
                </div>
            </div>
        </div>
    </div>

    <!-- DATATABLE PRINCIPAL: RESUMEN DE CLIENTES -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="fw-bold text-dark mb-0">Resumen Consolidado de Clientes con Deuda</h5>
                <p class="text-muted small mb-0">Selecciona un cliente para explorar el desglose de sus facturas o abonar a la deuda</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary rounded-pill px-3 shadow-none btn-sm" onclick="recargarTabla()">
                    <i class="fas fa-sync-alt me-1"></i> Actualizar
                </button>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="tabla_cxc_clientes" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="rounded-start">Cliente / Documento</th>
                            <th>Teléfono</th>
                            <th class="text-center">Facturas</th>
                            <th>Deuda Más Antigua</th>
                            <th>Estado / Alerta</th>
                            <th class="text-end">Total Deuda ($)</th>
                            <th class="text-end">Total Deuda (Bs)</th>
                            <th class="text-center rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- MODAL NIVEL 2: DETALLE Y ESTADO DE CUENTA DEL CLIENTE -->
    <div class="modal fade" id="modalDetalleCliente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-4 border-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 48px;">
                            <i class="fas fa-user-tag fs-5"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h5 class="modal-title fw-bold mb-0 text-white" id="det_cliente_nombre">Cargando cliente...</h5>
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-bold" id="det_cliente_cedula">V-00000000</span>
                            </div>
                            <span class="text-white-50 small" id="det_cliente_contacto">Teléfono: - | Límite Crédito: $0.00</span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-success rounded-pill px-3 shadow-sm fw-bold" id="btn_abono_general_cliente">
                            <i class="fas fa-coins me-1"></i> Abono General (FIFO)
                        </button>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                </div>

                <div class="modal-body p-4 bg-light">
                    <!-- RESUMEN DE SALDO DEL CLIENTE -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <span class="text-muted small fw-bold text-uppercase">Deuda Total Actual</span>
                                <h4 class="fw-bold text-danger mb-0 mt-1" id="det_deuda_total_usd">$0.00</h4>
                                <small class="text-muted" id="det_deuda_total_bs">Bs. 0.00</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <span class="text-muted small fw-bold text-uppercase">Facturas Pendientes</span>
                                <h4 class="fw-bold text-dark mb-0 mt-1" id="det_facturas_pendientes_count">0</h4>
                                <small class="text-muted">Por cancelar</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <span class="text-muted small fw-bold text-uppercase">Límite de Crédito</span>
                                <h4 class="fw-bold text-primary mb-0 mt-1" id="det_limite_credito">$0.00</h4>
                                <small class="text-muted" id="det_dias_credito">0 días de plazo</small>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100">
                                <span class="text-muted small fw-bold text-uppercase">Tasa BCV Aplicable</span>
                                <h4 class="fw-bold text-dark mb-0 mt-1" id="det_tasa_bcv">0.00 Bs/$</h4>
                                <small class="text-muted">Tasa en tiempo real</small>
                            </div>
                        </div>
                    </div>

                    <!-- PESTAÑAS: PENDIENTES VS HISTORIAL PAGADAS -->
                    <ul class="nav nav-pills nav-fill bg-white p-2 rounded-4 shadow-sm mb-3 gap-2" id="pills-tab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active rounded-pill fw-bold" id="tab-pendientes-btn" data-bs-toggle="pill" data-bs-target="#tab-pendientes" type="button" role="tab">
                                <i class="fas fa-clock me-1 text-warning"></i> Facturas Pendientes (<span id="badge_count_pendientes">0</span>)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link rounded-pill fw-bold" id="tab-pagadas-btn" data-bs-toggle="pill" data-bs-target="#tab-pagadas" type="button" role="tab">
                                <i class="fas fa-check-circle me-1 text-success"></i> Facturas Pagadas (<span id="badge_count_pagadas">0</span>)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="pills-tabContent">
                        <!-- TAB PENDIENTES -->
                        <div class="tab-pane fade show active" id="tab-pendientes" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tabla_facturas_pendientes">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">N° Factura / Venta</th>
                                                <th>Emisión</th>
                                                <th>Vencimiento</th>
                                                <th>Estado Mora</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end">Abonado</th>
                                                <th class="text-end text-danger">Saldo Restante</th>
                                                <th class="text-center pe-4">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_facturas_pendientes"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- TAB PAGADAS -->
                        <div class="tab-pane fade" id="tab-pagadas" role="tabpanel">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0" id="tabla_facturas_pagadas">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">N° Factura / Venta</th>
                                                <th>Emisión</th>
                                                <th>Vencimiento</th>
                                                <th class="text-end">Total Factura</th>
                                                <th class="text-end">Total Pagado</th>
                                                <th class="text-center">Estado</th>
                                                <th class="text-center pe-4">Abonos</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody_facturas_pagadas"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL OPERATIVO 1: ABONAR A FACTURA ESPECÍFICA -->
    <div class="modal fade" id="modalAbonarFactura" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-money-bill-wave text-warning fs-5"></i>
                        <h6 class="modal-title fw-bold text-white mb-0">Abonar a Factura Específica</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formAbonarFactura">
                    @csrf
                    <input type="hidden" name="cuenta_id" id="abono_cuenta_id">
                    <input type="hidden" name="tasa_cambio" id="abono_tasa_cambio">

                    <div class="modal-body p-4">
                        <!-- BANNER FACTURA -->
                        <div class="bg-light p-3 rounded-3 mb-3 border">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Factura:</span>
                                <span class="fw-bold text-dark" id="abono_factura_label">#0000</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="text-muted small">Saldo Pendiente:</span>
                                <span class="fw-bold text-danger fs-6" id="abono_saldo_label">$0.00</span>
                            </div>
                            <div class="text-end text-muted small" id="abono_saldo_bs_label">Bs. 0.00</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Método de Pago *</label>
                                <select class="form-select rounded-3 shadow-none" name="metodo_pago_id" id="abono_metodo_pago_id" required>
                                    <option value="">Seleccione forma de pago...</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Moneda del Pago *</label>
                                <select class="form-select rounded-3 shadow-none" name="moneda" id="abono_moneda" required onchange="recalcularAbonoEspecifico()">
                                    <option value="USD">Dólares ($ USD)</option>
                                    <option value="VES">Bolívares (Bs. VES)</option>
                                </select>
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Monto a Abonar *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 fw-bold" id="abono_moneda_sym">$</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control rounded-end-3 shadow-none fw-bold" name="monto" id="abono_monto" required placeholder="0.00" oninput="recalcularAbonoEspecifico()">
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <span class="small text-muted" id="abono_equivalente_label">Equivalente: Bs. 0.00</span>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-0 small" onclick="pagarTotalidadFactura()">
                                    Pagar Totalidad
                                </button>
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Fecha de Abono</label>
                                <input type="date" class="form-control rounded-3 shadow-none" name="fecha_abono" id="abono_fecha" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-6">
                                <label class="form-label small fw-bold text-dark mb-1">Referencia Bancaria</label>
                                <input type="text" class="form-control rounded-3 shadow-none" name="referencia" id="abono_referencia" placeholder="Ej. #123456" maxlength="100">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Notas / Observaciones</label>
                                <textarea class="form-control rounded-3 shadow-none" name="observaciones" id="abono_observaciones" rows="2" placeholder="Detalle adicional del pago (opcional)..." maxlength="255"></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light p-3 border-0">
                        <button type="button" class="btn btn-light rounded-pill px-3 shadow-none" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btn_guardar_abono_especifico">
                            <i class="fas fa-check-circle me-1"></i> Confirmar Abono
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL OPERATIVO 2: ABONO GENERAL A LA DEUDA (ALGORITMO FIFO) -->
    <div class="modal fade" id="modalAbonoGeneral" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-gradient text-white p-4 border-0" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                            <i class="fas fa-layer-group fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-white mb-0">Abono General a la Deuda (Cascada FIFO)</h5>
                            <span class="text-white-50 small">El monto amortizará automáticamente desde la deuda más antigua</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <form id="formAbonoGeneral">
                    @csrf
                    <input type="hidden" name="cliente_id" id="gen_cliente_id">
                    <input type="hidden" name="tasa_cambio" id="gen_tasa_cambio">

                    <div class="modal-body p-4">
                        <!-- BANNER DEUDA TOTAL -->
                        <div class="card border-0 bg-light rounded-4 p-3 mb-4">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div>
                                    <span class="text-muted small fw-bold">CLIENTE:</span>
                                    <h6 class="fw-bold text-dark mb-0" id="gen_cliente_label">Cliente</h6>
                                </div>
                                <div class="text-end">
                                    <span class="text-muted small fw-bold">DEUDA CONSOLIDADA:</span>
                                    <h5 class="fw-bold text-danger mb-0" id="gen_deuda_total_label">$0.00</h5>
                                    <small class="text-muted" id="gen_deuda_total_bs_label">Bs. 0.00</small>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Método de Pago *</label>
                                <select class="form-select rounded-3 shadow-none" name="metodo_pago_id" id="gen_metodo_pago_id" required>
                                    <option value="">Seleccione forma de pago...</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">Moneda *</label>
                                <select class="form-select rounded-3 shadow-none" name="moneda" id="gen_moneda" required onchange="recalcularAbonoGeneral()">
                                    <option value="USD">Dólares ($ USD)</option>
                                    <option value="VES">Bolívares (Bs. VES)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-dark mb-1">Monto a Entregar *</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 fw-bold" id="gen_moneda_sym">$</span>
                                    <input type="number" step="0.01" min="0.01" class="form-control rounded-end-3 shadow-none fw-bold" name="monto" id="gen_monto" required placeholder="0.00" oninput="recalcularAbonoGeneral()">
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between align-items-center">
                                <span class="small text-muted" id="gen_equivalente_label">Equivalente: Bs. 0.00</span>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 small fw-bold" onclick="pagarDeudaTotalGeneral()">
                                    <i class="fas fa-check-double me-1"></i> Pagar Toda la Deuda
                                </button>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Fecha de Abono</label>
                                <input type="date" class="form-control rounded-3 shadow-none" name="fecha_abono" id="gen_fecha" value="{{ date('Y-m-d') }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark mb-1">Referencia Bancaria</label>
                                <input type="text" class="form-control rounded-3 shadow-none" name="referencia" id="gen_referencia" placeholder="Ej. Comprobante / # Transf" maxlength="100">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold text-dark mb-1">Observaciones</label>
                                <textarea class="form-control rounded-3 shadow-none" name="observaciones" id="gen_observaciones" rows="2" placeholder="Detalles del abono general..." maxlength="255"></textarea>
                            </div>

                            <!-- PREVISUALIZACIÓN EN VIVO FIFO -->
                            <div class="col-12 mt-3">
                                <div class="card border border-success border-opacity-25 bg-success bg-opacity-10 rounded-4 p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="fw-bold text-success small"><i class="fas fa-stream me-1"></i> Previsualización de Distribución FIFO:</span>
                                        <span class="badge bg-success rounded-pill px-2 py-1 small" id="gen_preview_resumen">0 facturas cubiertas</span>
                                    </div>
                                    <div class="table-responsive bg-white rounded-3">
                                        <table class="table table-sm table-bordered align-middle mb-0 small" id="tabla_preview_fifo">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Factura</th>
                                                    <th>Fecha</th>
                                                    <th class="text-end">Saldo Actual</th>
                                                    <th class="text-end text-success">Monto a Aplicar</th>
                                                    <th class="text-end">Nuevo Saldo</th>
                                                    <th class="text-center">Estado Resultante</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbody_preview_fifo">
                                                <tr><td colspan="6" class="text-center text-muted py-2">Ingresa un monto para ver la cascada de amortización</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer bg-light p-3 border-0">
                        <button type="button" class="btn btn-light rounded-pill px-3 shadow-none" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" id="btn_guardar_abono_general">
                            <i class="fas fa-check-circle me-1"></i> Procesar Abono General
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL INFORMATIVO: HISTORIAL DE ABONOS DE FACTURA -->
    <div class="modal fade" id="modalHistorialAbonos" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white p-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-receipt text-warning fs-5"></i>
                        <h6 class="modal-title fw-bold text-white mb-0" id="hist_modal_title">Historial de Abonos - Factura</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Forma de Pago</th>
                                    <th>Referencia</th>
                                    <th class="text-end">Abono ($)</th>
                                    <th class="text-end">Abono (Bs)</th>
                                    <th>Registrado por</th>
                                    <th class="text-center">Ticket</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_historial_abonos"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/cxc.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/cxc.js')) ?: time() }}"></script>
@endsection
