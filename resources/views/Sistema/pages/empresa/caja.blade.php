@extends('Sistema.layouts.app')

@section('titulo', '💵 Cajas y Control de Turnos')
@section('subtitulo', 'Administración de cajas físicas, aperturas de turno, arqueos, cortes X y cierres Z')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Cajas y Turnos</span>
@endsection

@section('acciones')
    <div class="d-flex gap-2">
        @can('cajas.crear')
            <x-btn-action
                icon="fas fa-cash-register"
                text="Nueva Caja"
                onclick="crearCaja()"
                class="btn-outline-dark"
            />
        @endcan
        @can('cajas.aperturar')
            <x-btn-action
                icon="fas fa-key"
                text="Aperturar Turno"
                onclick="abrirModalApertura()"
            />
        @endcan
        @can('pos.acceso')
            <a href="{{ route('pos') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm">
                <i class="fas fa-shopping-cart me-1"></i> Ir al POS
            </a>
        @endcan
    </div>
@endsection

@section('contenido')
    <!-- Banner de Turno Activo del Usuario -->
    <div id="contenedorTurnoActivo" class="mb-4" style="display: none;">
        <div class="card bg-white border rounded-4 shadow-xs p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success-subtle text-success border border-success-subtle rounded-4 d-flex align-items-center justify-content-center p-3" style="width: 56px; height: 56px; min-width: 56px;">
                        <i class="fas fa-cash-register fa-2x"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                <i class="fas fa-circle fa-beat me-1"></i> TURNO EN CURSO
                            </span>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-semibold">
                                Turno #<span id="bannerTurnoId"></span>
                            </span>
                        </div>
                        <h4 class="fw-bold mb-0 text-dark mt-1">Caja: <span id="bannerCajaNombre" class="text-primary"></span></h4>
                        <p class="text-muted mb-0 small mt-1">
                            Aperturado el <span id="bannerFechaApertura" class="fw-semibold text-dark"></span> por <strong class="text-dark" id="bannerUsuarioNombre"></strong>
                        </p>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    @can('cajas.movimientos')
                        <button type="button" class="btn btn-outline-dark rounded-pill px-3 py-2 fw-semibold shadow-xs" id="btnBannerMovimiento">
                            <i class="fas fa-exchange-alt me-1"></i> Movimiento de Caja
                        </button>
                    @endcan
                    <button type="button" class="btn btn-warning rounded-pill px-3 py-2 fw-semibold shadow-xs text-dark" id="btnBannerCorteX">
                        <i class="fas fa-file-invoice-dollar me-1"></i> Ver Corte X
                    </button>
                    <button type="button" class="btn btn-danger rounded-pill px-3 py-2 fw-semibold shadow-xs" id="btnBannerCerrarZ">
                        <i class="fas fa-lock me-1"></i> Cerrar Turno (Z)
                    </button>
                    @can('pos.acceso')
                        <a href="{{ route('pos') }}" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-xs">
                            <i class="fas fa-shopping-cart me-1"></i> Ir al POS
                        </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    <!-- Navegación por Pestañas Ejecutivas -->
    <div class="card border rounded-4 p-2 shadow-xs mb-4 bg-white">
        <ul class="nav nav-pills nav-fill gap-2 p-1" id="pills-tab-cajas" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2"
                    id="pills-cajas-tab" data-bs-toggle="pill" data-bs-target="#pills-cajas" type="button" role="tab"
                    aria-controls="pills-cajas" aria-selected="true">
                    <i class="fas fa-cash-register"></i>
                    <span>Cajas Físicas</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill fw-bold py-2.5 d-flex align-items-center justify-content-center gap-2"
                    id="pills-turnos-tab" data-bs-toggle="pill" data-bs-target="#pills-turnos" type="button" role="tab"
                    aria-controls="pills-turnos" aria-selected="false">
                    <i class="fas fa-history"></i>
                    <span>Historial de Turnos y Arqueos</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Contenido de las Pestañas -->
    <div class="tab-content" id="pills-tabContentCajas">
        <!-- Pestaña 1: Cajas Físicas -->
        <div class="tab-pane fade show active" id="pills-cajas" role="tabpanel" aria-labelledby="pills-cajas-tab">
            <x-datatable
                id="datatable_cajas"
                :headers="[
                    'Caja / Código',
                    'Almacén Asignado',
                    'Estado Turno Actual',
                    'Estado Caja',
                    'Acciones',
                ]"
            />
        </div>

        <!-- Pestaña 2: Historial de Turnos -->
        <div class="tab-pane fade" id="pills-turnos" role="tabpanel" aria-labelledby="pills-turnos-tab">
            <x-datatable
                id="datatable_turnos"
                :headers="[
                    'Turno #',
                    'Caja',
                    'Cajero Responsable',
                    'Apertura',
                    'Cierre',
                    'Fondo Apertura',
                    'Cierre Arqueo',
                    'Diferencia',
                    'Estado',
                    'Acciones',
                ]"
            />
        </div>
    </div>

    <!-- Modal Crear / Editar Caja -->
    <x-modal id="modalCaja" title="Nueva Caja" subtitle="Configuración de caja registradora o punto de cobro"
        icon="fas fa-cash-register text-primary fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioCaja"
        submitText="Guardar Caja">

        <form id="formularioCaja">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" id="caja_nombre" label="Nombre de la Caja" icon="fas fa-tag" placeholder="Ej. Caja Principal 01" required
                    maxlength="100" col="col-md-7" />

                <x-input name="codigo" id="caja_codigo" label="Código Identificador" icon="fas fa-barcode" placeholder="Ej. CAJ-01"
                    maxlength="50" col="col-md-5" optionalText="Opcional" />

                <x-select name="almacen_id" id="caja_almacen_id" label="Almacén Asociado" icon="fas fa-warehouse" col="col-md-6" optionalText="Opcional">
                    <option value="">-- Sin almacén fijo / Global --</option>
                </x-select>

                <x-input name="descripcion" id="caja_descripcion" label="Descripción / Ubicación" icon="fas fa-info-circle" placeholder="Ej. Mostrador principal, área de repuestos"
                    maxlength="500" col="col-md-6" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>

    <!-- Modal Apertura de Turno -->
    <x-modal id="modalAperturaTurno" title="Apertura de Turno" subtitle="Inicia la sesión de facturación y registra el fondo de caja"
        icon="fas fa-door-open text-success fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioApertura"
        submitText="Aperturar Turno">

        <form id="formularioApertura">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    <div class="alert bg-primary-subtle border border-primary-subtle text-primary rounded-4 py-2.5 px-3 small mb-0">
                        <i class="fas fa-info-circle me-1"></i> Selecciona la caja donde operarás e introduce el efectivo con el que inicias para dar cambio.
                    </div>
                </div>

                <x-select name="caja_id" id="apertura_caja_id" label="Seleccionar Caja" icon="fas fa-cash-register" required col="col-md-6">
                    <option value="">-- Cargando cajas disponibles... --</option>
                </x-select>

                <x-select name="user_id" id="apertura_user_id" label="Cajero Asignado (Usuario)" icon="fas fa-user-tie" required col="col-md-6">
                    <option value="">-- Seleccionar Cajero --</option>
                </x-select>

                <x-input name="monto_apertura_usd" id="monto_apertura_usd" type="number" step="0.01" min="0" label="Fondo Inicial en USD ($)" icon="fas fa-dollar-sign" addonText="$" required col="col-md-6" value="0.00" />

                <x-input name="monto_apertura_bs" id="monto_apertura_bs" type="number" step="0.01" min="0" label="Fondo Inicial en Bolívares (Bs.)" icon="fas fa-coins" addonText="Bs." required col="col-md-6" value="0.00" />

                <x-input name="observaciones" id="apertura_observaciones" label="Observaciones de Apertura" icon="fas fa-comment" placeholder="Ej. Inicio de jornada turno matutino..." maxlength="500" col="col-12" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>

    <!-- Modal Corte X / Reporte En Vivo -->
    <div class="modal fade" id="modalCorteX" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fas fa-file-invoice-dollar text-warning me-2"></i> Reporte Corte X (Parcial de Turno)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="contenidoCorteX">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"></div>
                        <p class="text-muted mt-2">Cargando desglose del turno...</p>
                    </div>
                </div>
                <div class="modal-footer bg-white border-top border-light-subtle py-2">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    <a id="btnImprimirCorteX" href="#" target="_blank" class="btn btn-dark rounded-pill px-4">
                        <i class="fas fa-print me-1"></i> Imprimir Ticket 80mm
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Movimiento de Caja (Ingreso / Egreso) -->
    <x-modal id="modalMovimientoCaja" title="Registrar Movimiento de Caja" subtitle="Registra una entrada o salida de efectivo menor durante el turno activo"
        icon="fas fa-exchange-alt text-warning fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioMovimientoCaja"
        submitText="Registrar Movimiento">

        <form id="formularioMovimientoCaja">
            @csrf
            <input type="hidden" id="movimiento_caja_turno_id" name="caja_turno_id">

            <div class="row g-3">
                <div class="col-12">
                    <div class="alert bg-primary-subtle border border-primary-subtle text-primary rounded-4 py-2.5 px-3 small mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <span><i class="fas fa-info-circle me-1"></i> El monto ingresado se sumará o descontará automáticamente del efectivo esperado en gaveta al cierre.</span>
                        <span class="badge bg-white text-primary border border-primary-subtle rounded-pill px-2.5 py-1 fw-bold" id="movimientoCajaEtiquetaTurno">Turno Activo</span>
                    </div>
                </div>

                <x-select name="tipo" id="movimiento_tipo" label="Tipo de Movimiento" icon="fas fa-arrows-alt-v" required col="col-md-6">
                    <option value="egreso">Salida / Retiro de Efectivo (-)</option>
                    <option value="ingreso">Entrada / Ingreso de Efectivo (+)</option>
                </x-select>

                <x-input name="comprobante_referencia" id="movimiento_referencia" label="Nro. Vale / Recibo / Referencia" icon="fas fa-hashtag" placeholder="Ej. VALE-001" maxlength="100" col="col-md-6" optionalText="Opcional" />

                <x-input name="monto_usd" id="movimiento_monto_usd" type="number" step="0.01" min="0" label="Efectivo en USD ($)" icon="fas fa-dollar-sign" addonText="$" required col="col-md-6" value="0.00" />

                <x-input name="monto_bs" id="movimiento_monto_bs" type="number" step="0.01" min="0" label="Efectivo en Bolívares (Bs.)" icon="fas fa-coins" addonText="Bs." required col="col-md-6" value="0.00" />

                <x-input name="concepto" id="movimiento_concepto" label="Concepto o Motivo del Movimiento" icon="fas fa-tag" placeholder="Ej. Retiro de $10 para compra de bolsas / pago de flete..." required maxlength="255" col="col-12" />

                <x-input name="observaciones" id="movimiento_observaciones" label="Observaciones Adicionales" icon="fas fa-comment" placeholder="Detalle opcional de quién autorizó o recibió el efectivo..." maxlength="500" col="col-12" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>

    <!-- Modal Cierre de Turno Z (Arqueo Físico) -->
    <x-modal id="modalCierreZ" title="Cierre de Turno y Arqueo Z" subtitle="Realiza el conteo físico de dinero para finalizar la jornada"
        icon="fas fa-lock text-danger fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioCierreZ"
        submitText="Confirmar y Cerrar Turno">

        <form id="formularioCierreZ">
            @csrf
            <input type="hidden" id="cierre_turno_id" name="turno_id">

            <div class="row g-3">
                <div class="col-12" id="contenedorAvisoCierreCiego" style="display: none;">
                    <div class="alert bg-warning-subtle border border-warning-subtle text-warning-emphasis rounded-4 py-2.5 px-3 small mb-0">
                        <i class="fas fa-eye-slash me-1"></i> <strong>Cierre a ciegas:</strong> Ingresa el efectivo físico contado en gaveta para cada moneda. La diferencia de arqueo se mostrará al confirmar el cierre.
                    </div>
                </div>

                <div class="col-12" id="contenedorResumenCierreZ">
                    <div class="card bg-white border rounded-4 p-3 mb-2 shadow-xs">
                        <div class="row g-2 text-center">
                            <div class="col-6 col-md-2">
                                <span class="text-muted small d-block">Apertura USD</span>
                                <h6 class="fw-bold text-dark mb-0" id="resumenAperturaUsd">$0.00</h6>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted small d-block">Ventas USD</span>
                                <h6 class="fw-bold text-success mb-0" id="resumenVentasUsd">$0.00</h6>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted small d-block">Ingresos Caja</span>
                                <h6 class="fw-bold text-success mb-0" id="resumenIngresosCajaUsd">+$0.00</h6>
                                <small class="text-muted font-monospace" style="font-size: 0.7rem;" id="resumenIngresosCajaBs">+Bs. 0.00</small>
                            </div>
                            <div class="col-6 col-md-2">
                                <span class="text-muted small d-block">Retiros / Egresos</span>
                                <h6 class="fw-bold text-danger mb-0" id="resumenEgresosCajaUsd">-$0.00</h6>
                                <small class="text-muted font-monospace" style="font-size: 0.7rem;" id="resumenEgresosCajaBs">-Bs. 0.00</small>
                            </div>
                            <div class="col-6 col-md-2" id="colResumenEsperadoUsd">
                                <span class="text-muted small d-block">Esperado USD</span>
                                <h6 class="fw-bold text-primary mb-0" id="resumenEsperadoUsd">$0.00</h6>
                            </div>
                            <div class="col-6 col-md-2" id="colResumenEsperadoBs">
                                <span class="text-muted small d-block">Esperado Bs.</span>
                                <h6 class="fw-bold text-primary mb-0" id="resumenEsperadoBs">Bs. 0.00</h6>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <x-input name="monto_cierre_usd" id="monto_cierre_usd" type="number" step="0.01" min="0" label="Conteo Físico USD en Gaveta ($)" icon="fas fa-dollar-sign" addonText="$" required col="col-12" value="0.00" />
                    <div id="alertaDiferenciaUsd" class="small mt-1 fw-semibold text-muted font-monospace">Diferencia: $0.00</div>
                </div>

                <div class="col-md-6">
                    <x-input name="monto_cierre_bs" id="monto_cierre_bs" type="number" step="0.01" min="0" label="Conteo Físico Bs. en Gaveta (Bs.)" icon="fas fa-coins" addonText="Bs." required col="col-12" value="0.00" />
                    <div id="alertaDiferenciaBs" class="small mt-1 fw-semibold text-muted font-monospace">Diferencia: Bs. 0.00</div>
                </div>

                <x-input name="observaciones" id="cierre_observaciones" label="Observaciones de Cierre / Justificación de Arqueo" icon="fas fa-comment" placeholder="Ej. Cuadre perfecto sin faltantes, billetes entregados al administrador..." maxlength="500" col="col-12" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script src="{{ asset('estilos/jsPropios/caja.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/caja.js')) ?: time() }}"></script>
@endsection
