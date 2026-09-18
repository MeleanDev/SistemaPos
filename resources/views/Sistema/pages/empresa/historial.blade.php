@extends('Sistema.layouts.app')

@section('subtitulo', 'Historial de Facturas y Órdenes')

@section('contenido')
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-clock"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Historial de Facturas y Órdenes</h2>
                    <p class="text-muted mb-0 small">Consulta y gestiona las ventas realizadas</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <!-- Filtros Rápidos -->
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="filtros-historial-rapido">
                <span class="text-muted small fw-bold mt-1 me-2"><i class="fas fa-filter text-primary"></i> Filtros rápidos:</span>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                    onclick="filtrarRapidoHistorial('', this)">
                    Todas
                </button>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill"
                    onclick="filtrarRapidoHistorial('Pagada', this)">
                    <i class="fas fa-check-circle me-1"></i> Pagadas
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill"
                    onclick="filtrarRapidoHistorial('Pendiente', this)">
                    <i class="fas fa-clock me-1"></i> Pendientes (Por Cobrar)
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                    onclick="filtrarRapidoHistorial('Anulada', this)">
                    <i class="fas fa-ban me-1"></i> Anuladas
                </button>
            </div>

            <!-- Filtros Avanzados -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_fecha_inicio" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Fecha Inicio
                    </label>
                    <input type="date" class="form-control rounded-pill" id="filtro_fecha_inicio">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_fecha_fin" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Fecha Fin
                    </label>
                    <input type="date" class="form-control rounded-pill" id="filtro_fecha_fin">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_estado" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-wallet text-primary me-1"></i> Estado de Pago
                    </label>
                    <select class="form-select rounded-pill" id="filtro_estado">
                        <option value="">Todos los Estados</option>
                        <option value="Pagada">Pagada</option>
                        <option value="Pendiente">Pendiente (Por Cobrar)</option>
                        <option value="Anulada">Anulada</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <button type="button" class="btn btn-outline-danger rounded-pill w-100 shadow-sm"
                        onclick="limpiarFiltrosHistorial()" title="Restablecer todos los filtros">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle border-bottom" id="datatable_historial" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="border-0 rounded-start">Fecha</th>
                            <th class="border-0">Comprobantes</th>
                            <th class="border-0">Paciente</th>
                            <th class="border-0">Total USD</th>
                            <th class="border-0">Pagado USD</th>
                            <th class="border-0">Deuda USD</th>
                            <th class="border-0">Estado</th>
                            <th class="border-0 text-end rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Llenado por AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para Abonar -->
    <div class="modal fade" id="modalAbonar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 bg-light rounded-top-4 pb-0">
                    <h5 class="modal-title fw-bold">Registrar Abono</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formularioAbono">
                        <input type="hidden" id="abono_factura_id">
                        <input type="hidden" id="abono_tasa_cambio">
                        <input type="hidden" id="abono_deuda_restante">

                        <div class="mb-3 text-center">
                            <span class="text-muted d-block small mb-1">Deuda Restante de la Factura:</span>
                            <h3 class="fw-bold text-danger mb-0" id="txtDeudaRestante">$0.00</h3>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label text-muted small">Método de Pago</label>
                                <select class="form-select rounded-pill" id="abono_metodo_pago" required>
                                    <option value="" selected disabled>Seleccione método...</option>
                                    <option value="Efectivo (USD)">Efectivo (USD)</option>
                                    <option value="Zelle">Zelle</option>
                                    <option value="Binance">Binance</option>
                                    <option value="Pago Movil">Pago Móvil</option>
                                    <option value="Punto de Venta">Punto de Venta</option>
                                    <option value="Biopago">Biopago</option>
                                    <option value="Transf. (Bs)">Transf. (Bs)</option>
                                    <option value="Efectivo (Bs)">Efectivo (Bs)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Monto (Abono USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i
                                            class="fas fa-dollar-sign"></i></span>
                                    <input type="number" step="0.01"
                                        class="form-control border-start-0 rounded-end-pill" id="abono_monto_usd"
                                        required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label text-muted small">Equivalente (Abono BS)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 rounded-start-pill">Bs.</span>
                                    <input type="number" step="0.01"
                                        class="form-control border-start-0 rounded-end-pill" id="abono_monto_bs" readonly>
                                </div>
                            </div>

                            <div class="col-12" id="contenedor_referencia" style="display:none;">
                                <label class="form-label text-muted small">Nro. de Referencia</label>
                                <input type="text" class="form-control rounded-pill" id="abono_referencia"
                                    placeholder="Opcional pero recomendado para pagos digitales">
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 ms-2" id="btnGuardarAbono">
                                <i class="fas fa-save"></i> Registrar Abono
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Anular -->
    <div class="modal fade" id="modalAnular" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <div class="modal-header border-0 bg-danger text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-exclamation-triangle"></i> Anular Factura</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formularioAnular">
                        <input type="hidden" id="anular_factura_id">

                        <p class="text-muted small mb-3">Está a punto de anular esta factura. Por favor, ingrese el motivo
                            de la anulación. El sistema registrará automáticamente su nombre y apellido.</p>

                        <div class="mb-3">
                            <label class="form-label text-muted small">Motivo / Observación</label>
                            <textarea class="form-control rounded-4" id="anular_observacion" rows="3" required
                                placeholder="Ej. Error en la carga de exámenes, paciente se retiró..."></textarea>
                        </div>

                        <div class="mt-4 pt-3 border-top text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger rounded-pill px-4 ms-2"
                                id="btnGuardarAnulacion">
                                <i class="fas fa-ban"></i> Confirmar Anulación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalAddExamen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-light p-4 pb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                            <i class="fas fa-flask fs-5"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-1">Gestionar Exámenes de la Factura</h5>
                            <div class="d-flex flex-wrap gap-2 align-items-center" id="header_badges_factura">
                                <span id="badge_correlativo" class="badge rounded-pill px-3 py-1 fw-bold small" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;"></span>
                                <span id="badge_orden" class="badge rounded-pill px-3 py-1 fw-semibold small" style="background-color: #f8fafc; color: #475569; border: 1px solid #cbd5e1;"></span>
                                <span id="badge_convenio_factura" class="badge rounded-pill px-3 py-1 fw-bold small" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;"><i class="fas fa-handshake me-1"></i><span id="txt_convenio_factura">Tarifa Estándar</span></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="formularioAddExamen">
                    <input type="hidden" id="add_factura_id">

                    <div class="modal-body p-4 pt-2">
                        <!-- Barra de Resumen Financiero -->
                        <div class="row g-2 mb-4">
                            <div class="col-4">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-4 border border-primary border-opacity-10 text-center">
                                    <span class="text-muted small text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Total Factura</span>
                                    <span class="fw-bold text-primary h5 mb-0" id="resumen_total_usd">$0.00</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-success bg-opacity-10 p-3 rounded-4 border border-success border-opacity-10 text-center">
                                    <span class="text-muted small text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Total Pagado</span>
                                    <span class="fw-bold text-success h5 mb-0" id="resumen_pagado_usd">$0.00</span>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="bg-danger bg-opacity-10 p-3 rounded-4 border border-danger border-opacity-10 text-center" id="box_resumen_deuda">
                                    <span class="text-muted small text-uppercase fw-bold d-block mb-1" style="font-size: 0.7rem;">Saldo / Deuda</span>
                                    <span class="fw-bold text-danger h5 mb-0" id="resumen_deuda_usd">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Exámenes Registrados -->
                        <div class="card border-0 bg-light rounded-4 shadow-sm p-3 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold text-dark mb-0 d-flex align-items-center">
                                    <i class="fas fa-list-alt text-primary me-2"></i> Exámenes Registrados en la Orden
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill ms-2 fs-6 px-2 py-0" id="badge_count_actuales">0</span>
                                </h6>
                            </div>
                            
                            <div class="table-responsive bg-white rounded-3 border border-light shadow-xs" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tabla_examenes_actuales">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 border-0">Servicio</th>
                                            <th class="text-end border-0">Precio ($)</th>
                                            <th class="text-center border-0">Estado</th>
                                            <th class="text-center pe-3 border-0" style="width: 70px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted small">
                                                <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Cargando exámenes...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex align-items-center text-muted small mt-2">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                <span style="font-size: 0.8rem;">Al eliminar un examen se descontará su valor del total. Debe quedar al menos 1 examen en la orden.</span>
                            </div>
                        </div>

                        <!-- Sección: Añadir Nuevos Exámenes -->
                        <div class="card border-0 bg-light rounded-4 shadow-sm p-3 mb-2">
                            <h6 class="fw-bold text-dark mb-3 d-flex align-items-center">
                                <i class="fas fa-plus-circle text-success me-2"></i> Añadir Nuevos Exámenes
                            </h6>

                            <div class="mb-3">
                                <label class="form-label text-muted small fw-semibold">Buscar Examen o Perfil para agregar</label>
                                <select class="form-select w-100" id="buscador_servicios_historial" style="width: 100%"></select>
                            </div>

                            <div class="table-responsive bg-white rounded-3 border border-light shadow-xs mb-1" style="max-height: 180px; overflow-y: auto;">
                                <table class="table table-hover align-middle mb-0" id="tabla_nuevos_examenes">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="ps-3 border-0">Servicio a Añadir</th>
                                            <th class="text-end border-0">Precio ($)</th>
                                            <th class="text-center pe-3 border-0" style="width: 50px;"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="fila_vacia_add">
                                            <td colspan="3" class="text-center text-muted small py-4">
                                                <i class="fas fa-cart-plus fa-2x text-muted opacity-50 mb-2 d-block"></i>
                                                <span>Busca y selecciona exámenes arriba para añadirlos</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot id="tfoot_nuevos" style="display: none;">
                                        <tr class="table-light border-top">
                                            <th class="ps-3 text-end">Total Adicional a Añadir:</th>
                                            <th class="text-end text-success fw-bold h6 mb-0" id="total_nuevo_usd">$0.00</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
                        <button type="button" class="btn btn-light rounded-pill px-4 shadow-sm" data-bs-dismiss="modal">
                            <i class="fas fa-times me-1"></i> Cerrar
                        </button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm fw-bold" id="btnGuardarAddExamen" disabled>
                            <i class="fas fa-plus me-1"></i> Guardar y Añadir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        window.canAnular =
            {{ auth()->user()->hasRole(['Admin', 'SuperAdmin']) || auth()->user()->can('anular_facturas')? 'true': 'false' }};
    </script>
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/historial.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/historial.js')) ?: time() }}">
    </script>
@endsection
