@extends('Sistema.layouts.app')

@section('titulo', 'Recepción y Caja')
@section('subtitulo', 'Punto de Venta / Nuevo Ingreso')

@section('contenido')
    <style>
        .pos-card {
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
            background-color: #ffffff;
            transition: all 0.2s ease-in-out;
        }

        .search-input {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 50rem;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            background-color: #ffffff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        .ticket-container {
            background-color: #fcfdfe;
        }

        .item-row {
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s ease;
        }

        .item-row:hover {
            background-color: #f8fafc;
        }

        .shortcut-badge {
            font-size: 0.7rem;
            background: #e2e8f0;
            color: #475569;
            border-radius: 6px;
            padding: 2px 7px;
            font-weight: 700;
        }

        .service-badge-pill {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .kpi-pago-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Cabecera e Indicador de Tasa -->
    <div class="row align-items-center justify-content-between mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-calculator"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Nuevo Ingreso Médico</h2>
                    <p class="text-muted mb-0 small">Recepción de pacientes, selección ágil de exámenes y facturación en caja</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-auto">
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm border d-flex align-items-center gap-2">
                <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle small d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div>
                    <span class="text-muted fw-bold d-block" style="font-size: 0.68rem; letter-spacing: 0.5px;">TASA OFICIAL BCV</span>
                    <span class="fs-5 text-success fw-extrabold lh-1" id="display-tasa-bcv">Bs. 0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- ==========================================
             COLUMNA IZQUIERDA: OPERATIVA
             ========================================== -->
        <div class="col-12 col-lg-7 d-flex flex-column gap-4 mb-4 mb-lg-0">

            <!-- BLOQUE PACIENTE -->
            <div class="card pos-card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary small">
                                <i class="fas fa-user-md"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Datos del Paciente</h6>
                        </div>
                        <span class="shortcut-badge">F2 / Alt+P</span>
                    </div>

                    <!-- Buscador de Paciente -->
                    <div class="input-group mb-0 position-relative">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg search-input border-start-0 rounded-end-pill ps-2"
                            id="buscar-cedula" placeholder="Buscar por cédula, nombre o apellido..." autocomplete="off" autofocus tabindex="1">

                        <!-- Lista de Resultados Pacientes Flotante -->
                        <div class="list-group position-absolute w-100 shadow-lg d-none mt-1 rounded-4 overflow-hidden"
                            style="z-index: 1050; top: 100%; max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0;"
                            id="lista-resultados-pacientes">
                        </div>
                    </div>

                    <!-- Resultado Paciente (Oculto) -->
                    <div class="d-none mt-3 p-3 bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25"
                        id="panel-paciente-seleccionado">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                    style="width: 48px; height: 48px;">
                                    <i class="fas fa-user fs-5"></i>
                                </div>
                                <div>
                                    <h5 class="mb-0 fw-bold text-dark" id="txt-paciente-nombre">-</h5>
                                    <div class="text-muted small mt-1">
                                        <span class="badge bg-white text-dark border me-2">Doc: <span id="txt-paciente-ci">-</span></span>
                                        <span><i class="fas fa-phone text-muted me-1"></i><span id="txt-paciente-tel">-</span></span>
                                    </div>
                                </div>
                            </div>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" tabindex="-1">
                                <i class="fas fa-exchange-alt me-1"></i> Cambiar (F2)
                            </button>
                        </div>
                        <!-- Selector de Tarifario / Convenio Activo -->
                        <div class="d-flex align-items-center gap-2 pt-2 border-top border-primary border-opacity-25 mt-2">
                            <span class="small fw-bold text-primary text-nowrap"><i class="fas fa-handshake me-1"></i>Tarifario / Plan:</span>
                            <select class="form-select form-select-sm rounded-pill border-primary border-opacity-50 fw-semibold bg-white" id="pos-convenio-select" onchange="cambiarConvenioOrden(this.value)">
                                <option value="">Particular (Precios Estándar)</option>
                                @if(isset($convenios))
                                    @foreach($convenios as $c)
                                        <option value="{{ $c->id }}" data-descuento="{{ $c->porcentaje_general }}">{{ $c->nombre }} {{ $c->porcentaje_general > 0 ? "({$c->porcentaje_general}% desc.)" : '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>

                    <!-- Botón Crear Nuevo Paciente (Se muestra mediante JS) -->
                    <div class="d-none mt-3 text-center" id="panel-nuevo-paciente">
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold" id="btn-abrir-modal-paciente">
                            <i class="fas fa-user-plus me-2"></i>Registrar Nuevo Paciente
                        </button>
                    </div>
                </div>
            </div>

            <!-- BLOQUE CATÁLOGO -->
            <div class="card pos-card border-0 shadow-sm rounded-4 flex-grow-1" style="opacity: 0.5; pointer-events: none;" id="bloque-catalogo">
                <div class="card-body p-4 position-relative">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="p-2 rounded-circle bg-info bg-opacity-10 text-info small">
                                <i class="fas fa-flask"></i>
                            </div>
                            <h6 class="fw-bold text-dark mb-0">Servicios y Exámenes Médicos</h6>
                        </div>
                        <span class="shortcut-badge">F4 / Alt+S</span>
                    </div>

                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted ps-3">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg search-input border-start-0 rounded-end-pill ps-2"
                            id="buscar-servicio" placeholder="Buscar perfil o examen clínico..." tabindex="8">
                    </div>

                    <!-- Lista de Resultados Flotante -->
                    <div class="list-group position-absolute w-100 shadow-lg d-none mt-1 rounded-4 overflow-hidden bg-white"
                        style="z-index: 1000; max-height: 280px; overflow-y: auto; border: 1px solid #e2e8f0;"
                        id="lista-resultados-servicios">
                        <!-- Items inyectados -->
                    </div>

                    <!-- Servicios Sugeridos Rápidos -->
                    <div class="mt-4">
                        <span class="text-uppercase text-muted fw-bold d-block mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                            Acceso Rápido / Más Solicitados:
                        </span>
                        <div class="d-flex flex-wrap gap-2" id="contenedor-acceso-rapido">
                            @foreach ($serviciosSugeridos as $s)
                                @php
                                    $esPerfil = $s->tipo === 'perfil';
                                    $esServicio = $s->tipo === 'servicio';
                                    $esProducto = $s->tipo === 'producto';
                                    $iconClass = $esPerfil ? 'fa-folder-open text-warning' : ($esServicio ? 'fa-tag text-info' : ($esProducto ? 'fa-box text-primary' : 'fa-vial text-primary'));
                                @endphp
                                <button type="button" class="btn btn-sm btn-light border rounded-pill px-3 py-1 shadow-xs fw-semibold text-secondary d-flex align-items-center gap-1"
                                    onclick='agregarAlCarrito(@json($s))'>
                                    <i class="fas {{ $iconClass }} small"></i>
                                    <span>{{ $s->nombre }}</span>
                                    <span class="badge rounded-pill ms-1 font-monospace fw-bold" style="background-color: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;">${{ number_format($s->precio, 2) }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==========================================
             COLUMNA DERECHA: TICKET Y TOTALES
             ========================================== -->
        <div class="col-12 col-lg-5">
            <div class="card pos-card border-0 shadow-sm rounded-4 h-100 d-flex flex-column" style="opacity: 0.5; pointer-events: none;"
                id="bloque-ticket">

                <div class="card-header bg-white border-bottom p-4 text-center">
                    <div class="d-flex align-items-center justify-content-center gap-2">
                        <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary small">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h5 class="fw-bold mb-0 text-dark">Orden en Proceso</h5>
                    </div>
                </div>

                <!-- Lista de Carrito -->
                <div class="card-body p-0 flex-grow-1 ticket-container"
                    style="min-height: 250px; max-height: 400px; overflow-y: auto;">
                    <div class="table-responsive">
                        <table class="table table-borderless mb-0">
                            <tbody id="carrito-body">
                                <!-- Carrito Vacío -->
                                <tr>
                                    <td class="text-center text-muted py-5">
                                        <div class="p-3 bg-light rounded-circle d-inline-flex mb-2">
                                            <i class="fas fa-shopping-cart fa-2x text-muted opacity-50"></i>
                                        </div>
                                        <p class="small mb-0 fw-semibold">Sin servicios agregados en la orden</p>
                                        <span class="text-muted" style="font-size: 0.75rem;">Busque o seleccione exámenes en el catálogo</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totales y Resumen Financiero -->
                <div class="card-footer bg-white border-top p-4">
                    <div class="d-flex justify-content-between text-muted small mb-2">
                        <span class="fw-semibold">Subtotal</span>
                        <span id="resumen-subtotal" class="fw-bold font-monospace">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-3 border-bottom pb-3">
                        <span class="fw-semibold">IVA (Exento Ley Médica)</span>
                        <span class="font-monospace">$0.00</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <span class="text-muted fw-bold d-block small" style="letter-spacing: 0.5px;">TOTAL FACTURA (USD)</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2 py-1 small">Divisas</span>
                        </div>
                        <span class="fs-2 fw-extrabold text-dark lh-1 font-monospace" id="resumen-total-usd">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4 p-2 bg-light rounded-3">
                        <span class="text-muted small fw-bold ps-1">TOTAL EQUIVALENTE (BS)</span>
                        <span class="fs-5 fw-bold text-primary lh-1 pe-1 font-monospace" id="resumen-total-bs">Bs. 0.00</span>
                    </div>

                    <!-- Botón que abre el Modal de Pagos Mixtos -->
                    <button
                        class="btn btn-primary w-100 py-3 fs-5 fw-bold rounded-pill shadow-sm d-flex justify-content-between align-items-center px-4"
                        id="btn-abrir-pago" tabindex="9" disabled>
                        <span><i class="fas fa-check-circle me-2"></i> Cobrar Orden</span>
                        <span class="shortcut-badge bg-white text-primary fs-6 py-1 px-2 shadow-xs">F8 / Alt+C</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL DE PAGOS MIXTOS (Profesional con Bs y USD)
         ========================================== -->
    <div class="modal fade" id="modalPagos" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-primary text-white pb-3 pt-4 px-4">
                    <h5 class="modal-title fw-bold">
                        <div class="d-flex align-items-center gap-2">
                            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                                style="width: 40px; height: 40px;">
                                <i class="fas fa-dollar-sign fs-5"></i>
                            </div>
                            Procesar Pagos y Facturación
                        </div>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-4 bg-light">

                    <!-- Resumen de deuda Ejecutivo con USD y BS -->
                    <div class="row g-3 text-center mb-4">
                        <!-- Total a Pagar -->
                        <div class="col-md-4">
                            <div class="p-3 kpi-pago-card h-100">
                                <span class="d-block small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total a Pagar</span>
                                <span class="fs-4 fw-extrabold text-dark d-block font-monospace" id="modal-total-pagar">$0.00</span>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill font-monospace small px-2 py-1 mt-1" id="modal-total-pagar-bs">Bs. 0.00</span>
                            </div>
                        </div>
                        <!-- Total Abonado -->
                        <div class="col-md-4">
                            <div class="p-3 kpi-pago-card h-100">
                                <span class="d-block small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Abonado</span>
                                <span class="fs-4 fw-extrabold text-success d-block font-monospace" id="modal-total-abonado">$0.00</span>
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill font-monospace small px-2 py-1 mt-1" id="modal-total-abonado-bs">Bs. 0.00</span>
                            </div>
                        </div>
                        <!-- Resta por Pagar -->
                        <div class="col-md-4">
                            <div class="p-3 kpi-pago-card h-100">
                                <span class="d-block small text-muted fw-bold text-uppercase mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Resta por Pagar</span>
                                <span class="fs-4 fw-extrabold text-danger d-block font-monospace" id="modal-total-restante">$0.00</span>
                                <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill font-monospace small px-2 py-1 mt-1" id="modal-total-restante-bs">Bs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario Agregar Pago Parcial -->
                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
                        <h6 class="fw-bold text-dark mb-3">
                            <i class="fas fa-plus-circle text-primary me-2"></i>Registrar Método de Pago
                        </h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold mb-1">Método de Pago</label>
                                <select class="form-select rounded-pill fw-bold" id="pago-metodo">
                                    <option value="Efectivo (USD)" data-moneda="usd">💵 Efectivo (USD)</option>
                                    <option value="Zelle" data-moneda="usd">💳 Zelle</option>
                                    <option value="Binance" data-moneda="usd">🟡 Binance</option>
                                    <option value="Pago Móvil" data-moneda="bs">📱 Pago Móvil</option>
                                    <option value="Punto de Venta" data-moneda="bs">📠 Punto de Venta</option>
                                    <option value="Biopago" data-moneda="bs">🖐️ Biopago</option>
                                    <option value="Transf. (Bs)" data-moneda="bs">🏦 Transf. (Bs)</option>
                                    <option value="Efectivo (Bs)" data-moneda="bs">💰 Efectivo (Bs)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted fw-bold mb-1" id="lbl-pago-monto">Monto (USD)</label>
                                <input type="number"
                                    class="form-control rounded-pill fw-bold text-success fs-5 text-center"
                                    id="pago-monto" step="0.01" min="0.01" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small text-muted fw-bold mb-1">N° Referencia / Lote</label>
                                <input type="text" class="form-control rounded-pill" id="pago-referencia"
                                    placeholder="Ej: 1234 (Obligatorio en digital)">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary rounded-pill w-100 py-2 fw-bold shadow-sm" id="btn-agregar-pago">
                                    <i class="fas fa-plus me-1"></i> Añadir
                                </button>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-3 offset-md-3">
                                <span class="badge bg-light text-muted border d-inline-block w-100 py-2 font-monospace small" id="pago-monto-bs">
                                    Bs. 0.00
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de pagos añadidos -->
                    <div class="bg-white p-3 rounded-4 shadow-sm border">
                        <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                            <h6 class="fw-bold small text-muted mb-0">PAGOS REGISTRADOS EN ESTA FACTURA:</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 border-0 rounded-start">Método</th>
                                        <th class="border-0">Referencia</th>
                                        <th class="text-end border-0">Monto USD</th>
                                        <th class="text-end border-0">Monto Bs</th>
                                        <th class="text-end pe-3 border-0 rounded-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="lista-pagos-mixtos">
                                    <!-- Filas de pagos agregados -->
                                    <tr>
                                        <td colspan="5" class="text-center text-muted small py-3">No hay pagos agregados aún</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-white p-3">
                    <!-- Toggle para Cuentas por Cobrar -->
                    <div class="form-check form-switch me-auto">
                        <input class="form-check-input fs-5" type="checkbox" role="switch" id="check-credito" style="cursor: pointer;">
                        <label class="form-check-label small text-muted fw-bold ps-2" for="check-credito">
                            Permitir Deuda (Generar Cuenta por Cobrar)
                        </label>
                    </div>
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-dark border" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" id="btn-finalizar-factura" disabled>
                        <i class="fas fa-check-circle me-2"></i> Confirmar y Facturar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MODAL CREAR PACIENTE
         ========================================== -->
    <div class="modal fade" id="modalCrearPaciente" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-primary text-white pb-3 pt-3 px-4">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="fas fa-user-plus me-2"></i> Nuevo Paciente
                        <button type="button" class="btn btn-sm btn-light text-primary ms-3 fw-bold rounded-pill shadow-sm" onclick="generarDatosAnonimosIngreso()">
                            <i class="fas fa-user-secret me-1"></i> Generar Anónimo
                        </button>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-4 bg-light">
                    <!-- Datos Personales -->
                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4 border">
                        <h6 class="fw-bold text-primary mb-4"><i class="fas fa-id-card me-2"></i> Datos Personales</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Primer Nombre <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" id="nuevo-paciente-nombreUno"
                                    placeholder="Ej. Juan">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Segundo Nombre</label>
                                <input type="text" class="form-control rounded-pill" id="nuevo-paciente-nombreDos"
                                    placeholder="Ej. Carlos">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Primer Apellido <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" id="nuevo-paciente-apellidoUno"
                                    placeholder="Ej. Pérez">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Segundo Apellido</label>
                                <input type="text" class="form-control rounded-pill" id="nuevo-paciente-apellidoDos"
                                    placeholder="Ej. Gómez">
                            </div>

                            <!-- Toggle menor de edad -->
                            <div class="col-12">
                                <div class="form-check form-switch ps-0 d-flex align-items-center gap-3 p-3 bg-light rounded-4 border">
                                    <input class="form-check-input ms-0 fs-4 shadow-none" type="checkbox" role="switch"
                                        id="nuevo-paciente-es-menor" style="cursor: pointer;">
                                    <label class="form-check-label fw-bold text-warning mb-0" for="nuevo-paciente-es-menor">
                                        <i class="fas fa-child me-1"></i> Paciente Menor de edad / Sin cédula
                                    </label>
                                </div>
                            </div>

                            <!-- Campo Cédula del paciente -->
                            <div class="col-md-5" id="ingreso-bloque-cedula">
                                <label class="form-label small fw-bold text-muted mb-1">Cédula <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select rounded-start-pill" style="max-width: 80px;"
                                        id="nuevo-paciente-tipo-cedula">
                                        <option value="V-">V-</option>
                                        <option value="E-">E-</option>
                                        <option value="X-">X-</option>
                                    </select>
                                    <input type="text" class="form-control rounded-end-pill" id="nuevo-paciente-cedula"
                                        placeholder="12345678">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-muted mb-1">Fec. Nacimiento <span
                                        class="text-danger">*</span></label>
                                <input type="date" class="form-control rounded-pill" id="nuevo-paciente-fecha" max="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold text-muted mb-1">Sexo <span
                                        class="text-danger">*</span></label>
                                <select class="form-select rounded-pill" id="nuevo-paciente-sexo">
                                    <option value="" disabled selected>...</option>
                                    <option value="M">Masculino</option>
                                    <option value="F">Femenino</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sección Representante Legal -->
                    <div class="bg-white p-4 rounded-4 shadow-sm mb-4 d-none border" id="ingreso-bloque-representante"
                        style="border-left: 4px solid #0d6efd !important;">
                        <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-2"
                                style="width:36px;height:36px;display:flex;align-items:center;justify-content:center;">
                                <i class="fas fa-shield-alt text-primary" style="font-size:0.85rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark" style="font-size:0.9rem;">Representante Legal</h6>
                                <small class="text-muted">Datos del padre, madre o tutor del menor</small>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Nombre del Representante <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill bg-white"
                                    id="nuevo-paciente-nombre-rep" placeholder="Ej. María Pérez">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Cédula del Representante <span
                                        class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-select rounded-start-pill bg-white" style="max-width: 80px;"
                                        id="nuevo-paciente-tipo-cedula-rep">
                                        <option value="V-">V-</option>
                                        <option value="E-">E-</option>
                                        <option value="P-">P-</option>
                                        <option value="X-">X-</option>
                                    </select>
                                    <input type="text" class="form-control rounded-end-pill bg-white"
                                        id="nuevo-paciente-cedula-rep" placeholder="12345678"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contacto y Ubicación -->
                    <div class="bg-white p-4 rounded-4 shadow-sm border">
                        <h6 class="fw-bold text-primary mb-4">
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <span id="ingreso-label-contacto">Contacto y Ubicación</span>
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <span id="ingreso-label-correo">Correo Electrónico (Opcional)</span>
                                </label>
                                <input type="email" class="form-control rounded-pill" id="nuevo-paciente-correo"
                                    placeholder="correo@ejemplo.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <span id="ingreso-label-telefono">Teléfono</span>
                                    <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <select class="form-select rounded-start-pill" style="max-width: 90px;"
                                        id="nuevo-paciente-cod-tel">
                                        <option value="+58">+58</option>
                                    </select>
                                    <input type="text" class="form-control rounded-end-pill" id="nuevo-paciente-telefono"
                                        placeholder="4121234567">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted mb-1">Telf. Emergencia</label>
                                <div class="input-group">
                                    <select class="form-select rounded-start-pill" style="max-width: 90px;"
                                        id="nuevo-paciente-cod-emerg">
                                        <option value="+58">+58</option>
                                    </select>
                                    <input type="text" class="form-control rounded-end-pill"
                                        id="nuevo-paciente-emergencia" placeholder="4141234567">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted mb-1">Dirección <span
                                        class="text-danger">*</span></label>
                                <input type="text" class="form-control rounded-pill" id="nuevo-paciente-direccion"
                                    placeholder="Calle 123, Casa 456">
                            </div>

                            <!-- Tarifario / Convenio Asignado -->
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="fas fa-handshake me-1 text-primary"></i>Tarifario / Plan Asignado
                                </label>
                                <select class="form-select rounded-pill" id="nuevo-paciente-convenio">
                                    <option value="">Particular / Precio Estándar</option>
                                    @if(isset($convenios))
                                        @foreach($convenios as $c)
                                            <option value="{{ $c->id }}" {{ $c->es_predeterminado ? 'selected' : '' }}>
                                                {{ $c->nombre }} {{ $c->porcentaje_general > 0 ? "({$c->porcentaje_general}% desc.)" : '' }} {{ $c->es_predeterminado ? '(Predeterminado)' : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-white border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold text-dark shadow-sm border"
                        data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm"
                        id="btn-guardar-paciente">
                        <i class="fas fa-save me-2"></i>Guardar <span
                            class="shortcut-badge ms-1 bg-white text-primary">Ctrl+Enter</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/ingreso.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/ingreso.js')) ?: time() }}">
    </script>
@endsection



