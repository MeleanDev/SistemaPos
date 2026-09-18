@extends('Sistema.layouts.app')

@section('titulo', 'Órdenes de Servicios')
@section('subtitulo', 'Recepción de Resultados')

@section('contenido')
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-flask"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Órdenes de Servicios</h2>
                    <p class="text-muted mb-0 small">Recepción, procesamiento y validación de resultados</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <!-- Filtros Rápidos -->
            <div class="d-flex flex-wrap gap-2 mb-3 align-items-center" id="filtros-ordenes-rapido">
                <span class="text-muted small fw-bold mt-1 me-2"><i class="fas fa-filter text-primary"></i> Filtros rápidos:</span>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                    onclick="filtrarRapidoOrdenes('', this)">
                    Todas
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                    onclick="filtrarRapidoOrdenes('Pendiente', this)">
                    <i class="fas fa-clock me-1"></i> Pendientes
                </button>
                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill"
                    onclick="filtrarRapidoOrdenes('En Proceso', this)">
                    <i class="fas fa-spinner me-1"></i> En Proceso
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill"
                    onclick="filtrarRapidoOrdenes('Transcrita', this)">
                    <i class="fas fa-keyboard me-1"></i> Transcritas
                </button>
                <button type="button" class="btn btn-outline-info btn-sm rounded-pill"
                    onclick="filtrarRapidoOrdenes('Validada', this)">
                    <i class="fas fa-check-circle me-1"></i> Validadas
                </button>
                <button type="button" class="btn btn-outline-success btn-sm rounded-pill"
                    onclick="filtrarRapidoOrdenes('Completada', this)">
                    <i class="fas fa-check-double me-1"></i> Completadas
                </button>
            </div>

            <!-- Filtros Avanzados -->
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_estado" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-tag text-primary me-1"></i> Estado de la Orden
                    </label>
                    <select id="filtro_estado" class="form-select rounded-pill">
                        <option value="">Todos los Estados</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Transcrita">Transcrita</option>
                        <option value="Validada">Validada</option>
                        <option value="Entregada">Entregada</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_fecha_inicio" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Fecha Inicio
                    </label>
                    <input type="date" id="filtro_fecha_inicio" class="form-control rounded-pill">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="filtro_fecha_fin" class="form-label text-muted small fw-semibold">
                        <i class="fas fa-calendar-alt text-primary me-1"></i> Fecha Fin
                    </label>
                    <input type="date" id="filtro_fecha_fin" class="form-control rounded-pill">
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <button type="button" class="btn btn-outline-danger rounded-pill w-100 shadow-sm"
                        onclick="limpiarFiltrosOrdenes()" title="Restablecer todos los filtros">
                        <i class="fas fa-undo me-1"></i> Limpiar
                    </button>
                </div>
            </div>
            <!-- Fin Filtros -->

            <div class="table-responsive">
                <table id="datatable_ordenes" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Código / Factura</th>
                            <th class="border-0">Paciente</th>
                            <th class="border-0 text-center">Fecha y Hora</th>
                            <th class="border-0 text-center">Estado</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/orden_servicio.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/orden_servicio.js')) ?: time() }}">
    </script>
@endsection

