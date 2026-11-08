@extends('Sistema.layouts.app')

@section('titulo', '💼 Vendedores')
@section('subtitulo', 'Directorio de asesores de ventas, porcentajes de comisión y liquidaciones')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Vendedores</span>
@endsection

@section('acciones')
    @can('reportes.vendedores')
        <a href="{{ route('reportes.vendedores') }}" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-chart-line me-1"></i> Reporte de Ventas
        </a>
    @endcan
    @can('vendedores.crear')
        <x-btn-action
            icon="fas fa-user-plus"
            text="Nuevo Vendedor"
            onclick="crear()"
        />
    @endcan
@endsection

@section('contenido')
    <ul class="nav nav-pills mb-3 gap-2" id="tabsVendedores" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active px-3 py-2 fw-semibold" id="tab-directorio-btn" data-bs-toggle="pill" data-bs-target="#tab-directorio" type="button" role="tab">
                <i class="fas fa-users-cog me-2"></i>Directorio de Vendedores
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link px-3 py-2 fw-semibold" id="tab-comisiones-btn" data-bs-toggle="pill" data-bs-target="#tab-comisiones" type="button" role="tab">
                <i class="fas fa-hand-holding-usd me-2"></i>Comisiones Pendientes
            </button>
        </li>
    </ul>

    <div class="tab-content" id="tabsVendedoresContent">
        <div class="tab-pane fade show active" id="tab-directorio" role="tabpanel">
            <x-datatable
                id="datatable_vendedores"
                :headers="[
                    'Vendedor / Documento',
                    'Teléfono',
                    'Correo',
                    '% Comisión',
                    'Estado',
                    'Acciones',
                ]"
            />
        </div>

        <div class="tab-pane fade" id="tab-comisiones" role="tabpanel">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body p-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold text-muted mb-1">Vendedor</label>
                            <select id="filtro_vendedor_comision" class="form-select form-select-sm">
                                <option value="">-- Seleccionar Vendedor --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Desde</label>
                            <input type="date" id="filtro_fecha_inicio_comision" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold text-muted mb-1">Hasta</label>
                            <input type="date" id="filtro_fecha_fin_comision" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-2 d-flex gap-1">
                            <button type="button" class="btn btn-primary btn-sm flex-fill" onclick="consultarComisionesPendientes()">
                                <i class="fas fa-search me-1"></i> Consultar
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="limpiarFiltrosComisiones()" title="Limpiar filtros">
                                <i class="fas fa-eraser"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="text-muted small fw-semibold">Facturas Pendientes</div>
                        <div class="fs-4 fw-bold text-dark" id="kpi_cantidad_ventas">0</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="text-muted small fw-semibold">Total Comisión USD</div>
                        <div class="fs-4 fw-bold text-success" id="kpi_total_usd">$0.00</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3">
                        <div class="text-muted small fw-semibold">Total Comisión Bs</div>
                        <div class="fs-4 fw-bold text-primary" id="kpi_total_bs">0.00 Bs</div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-0 fw-bold text-dark"><i class="fas fa-list me-2 text-primary"></i>Facturas con Comisión por Liquidar</h6>
                        <small class="text-muted" id="resumen_seleccion_texto">Selecciona las facturas a incluir en la liquidación.</small>
                    </div>
                    @can('vendedores.liquidar')
                        <button type="button" class="btn btn-success btn-sm px-3 shadow-sm" id="btnAbrirLiquidarComisiones" disabled onclick="abrirModalLiquidar()">
                            <i class="fas fa-hand-holding-usd me-1"></i> Liquidar Seleccionadas (<span id="contador_seleccionadas">0</span>)
                        </button>
                    @endcan
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0" id="tabla_comisiones_pendientes">
                            <thead class="bg-white border-bottom text-muted small text-uppercase">
                                <tr>
                                    <th class="text-center" style="width: 40px;">
                                        <input type="checkbox" class="form-check-input" id="check_todos_comision" onchange="toggleTodosComision(this)">
                                    </th>
                                    <th>Factura</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Total Factura</th>
                                    <th class="text-center">% Com.</th>
                                    <th class="text-end">Comisión USD</th>
                                    <th class="text-end">Comisión Bs</th>
                                </tr>
                            </thead>
                            <tbody id="tbody_comisiones_pendientes">
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">
                                        <i class="fas fa-search me-1"></i> Selecciona un vendedor para consultar comisiones pendientes.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-modal id="modalVendedor" title="Nuevo Vendedor" subtitle="Completa la información del asesor de ventas"
        icon="fas fa-user-tie text-primary fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioVendedor"
        submitText="Guardar">

        <form id="formularioVendedor">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" id="nombre" label="Nombre Completo" icon="fas fa-user" placeholder="Ej. Carlos Mendoza" required
                    maxlength="150" col="col-md-6" />

                <x-input-documento
                    label="Documento de Identidad"
                    selectName="tipo_documento"
                    inputName="documento"
                    :types="['V' => 'V-', 'E' => 'E-', 'J' => 'J-', 'G' => 'G-', 'P' => 'P-']"
                    defaultType="V"
                    required
                    col="col-md-6"
                />

                <x-input name="telefono" id="telefono" label="Teléfono de Contacto" icon="fas fa-phone"
                    placeholder="Ej. 0414-1234567" maxlength="30" col="col-md-6" optionalText="Opcional" />

                <x-input name="correo" id="correo" type="email" label="Correo Electrónico" icon="fas fa-envelope"
                    placeholder="vendedor@empresa.com" maxlength="150" col="col-md-6" optionalText="Opcional" />

                <x-input name="comision_porcentaje" id="comision_porcentaje" type="number" step="0.01" min="0" max="100"
                    label="% Comisión por Venta" icon="fas fa-percentage" addonText="%" addonPosition="right" placeholder="Ej. 3.50"
                    value="0.00" required col="col-md-6" />

                <x-select2
                    name="user_id"
                    id="user_id"
                    label="Usuario del Sistema"
                    icon="fas fa-user-lock"
                    col="col-md-6"
                    optionalText="Opcional"
                    placeholder="-- Sin usuario asignado / Vendedor externo --"
                    modalParent="#modalVendedor"
                    allowClear="true"
                >
                    <option value="">-- Sin usuario asignado / Vendedor externo --</option>
                </x-select2>
            </div>
        </form>

    </x-modal>

    @can('vendedores.liquidar')
        <x-modal id="modalLiquidarComisiones" title="Liquidar Comisiones" subtitle="Registrar pago de comisiones pendientes del asesor"
            icon="fas fa-hand-holding-usd text-success fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formLiquidarComisiones"
            submitText="Confirmar Liquidación">

            <form id="formLiquidarComisiones">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <div class="card border p-3">
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Vendedor</small>
                                    <span class="fw-bold text-dark" id="modal_liquidar_vendedor">-</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Facturas</small>
                                    <span class="fw-bold text-dark" id="modal_liquidar_facturas">0</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total USD</small>
                                    <span class="fw-bold text-success fs-5" id="modal_liquidar_usd">$0.00</span>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted d-block">Total Bs</small>
                                    <span class="fw-bold text-primary fs-5" id="modal_liquidar_bs">0.00 Bs</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="check_egreso_caja" name="registrar_egreso_caja" value="1" onchange="toggleEgresoCaja(this)">
                            <label class="form-check-label fw-semibold" for="check_egreso_caja">Registrar egreso de dinero en Caja / Turno</label>
                        </div>
                    </div>

                    <div id="seccion_egreso_caja" class="col-12 d-none">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Turno de Caja</label>
                                <select id="liquidar_caja_turno_id" name="caja_turno_id" class="form-select form-select-sm">
                                    <option value="">-- Seleccionar Turno Abierto --</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Método de Pago</label>
                                <select id="liquidar_metodo_pago_id" name="metodo_pago_id" class="form-select form-select-sm">
                                    <option value="">-- Método --</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Moneda de Egreso</label>
                                <select id="liquidar_moneda" name="moneda" class="form-select form-select-sm">
                                    <option value="USD">USD ($)</option>
                                    <option value="VES">VES (Bs)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-semibold">Observaciones / Concepto</label>
                        <textarea id="liquidar_observaciones" name="observaciones" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Ej. Pago de comisiones acumuladas..."></textarea>
                    </div>
                </div>
            </form>
        </x-modal>
    @endcan
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script>
        const puedeLiquidarComisiones = @json(Auth::user()->can('vendedores.liquidar'));
        const puedeEditarVendedor = @json(Auth::user()->can('vendedores.editar'));
        const puedeEliminarVendedor = @json(Auth::user()->can('vendedores.eliminar'));
        const puedeVerVendedor = @json(Auth::user()->can('vendedores.ver'));
    </script>
    <script
        src="{{ asset('estilos/jsPropios/vendedor.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/vendedor.js')) ?: time() }}">
    </script>
@endsection
