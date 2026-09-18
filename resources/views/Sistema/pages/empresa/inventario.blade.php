@extends('Sistema.layouts.app')

@section('titulo', 'Productos y Servicios')
@section('subtitulo', 'Control de Insumos, Reactivos y Catálogo de Servicios')

@section('contenido')
    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-boxes"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Productos y Servicios</h2>
                    <p class="text-muted mb-0 small">Administración de insumos físicos con control de stock y catálogo de servicios facturables</p>
                </div>
            </div>
        </div>
        <div class="col-12 col-md text-md-end">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="crear()">
                <i class="fas fa-plus me-2"></i> Nuevo Registro
            </button>
        </div>
    </div>

    <!-- Contenedor Principal -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            
            <!-- Filtros Rápidos -->
            <div class="row g-3 mb-4 align-items-center">
                <!-- Filtro por Tipo -->
                <div class="col-12 col-lg-auto">
                    <div class="d-flex flex-wrap gap-2 align-items-center" id="filtros-tipo-rapido">
                        <span class="text-muted small fw-bold mt-1 me-2">
                            <i class="fas fa-tags text-primary"></i> Tipo:
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                            onclick="filtrarRapidoTipo('', this)">
                            <i class="fas fa-th-large me-1"></i> Todos
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill"
                            onclick="filtrarRapidoTipo('producto', this)">
                            <i class="fas fa-box me-1"></i> Productos Físicos
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm rounded-pill"
                            onclick="filtrarRapidoTipo('servicio', this)">
                            <i class="fas fa-tag me-1"></i> Servicios
                        </button>
                    </div>
                </div>

                <div class="col-12 col-lg-auto border-start d-none d-lg-block" style="height: 28px;"></div>

                <!-- Filtro por Stock (Productos) -->
                <div class="col-12 col-lg-auto" id="seccion-filtro-stock">
                    <div class="d-flex flex-wrap gap-2 align-items-center" id="filtros-inventario-rapido">
                        <span class="text-muted small fw-bold mt-1 me-2">
                            <i class="fas fa-filter text-primary"></i> Stock:
                        </span>
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill active"
                            onclick="filtrarRapidoStock('', this)">
                            <i class="fas fa-boxes me-1"></i> Todos
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill"
                            onclick="filtrarRapidoStock('disponible', this)">
                            <i class="fas fa-check-circle me-1"></i> En Stock (> 5)
                        </button>
                        <button type="button" class="btn btn-outline-warning btn-sm rounded-pill"
                            onclick="filtrarRapidoStock('bajo', this)">
                            <i class="fas fa-exclamation-triangle me-1"></i> Stock Bajo (≤ 5)
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                            onclick="filtrarRapidoStock('agotado', this)">
                            <i class="fas fa-times-circle me-1"></i> Agotados (0)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de Inventario / Servicios -->
            <div class="table-responsive">
                <table id="datatable_inventario" class="table table-hover align-middle border-bottom" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 border-0 rounded-start">Nombre del Ítem</th>
                            <th class="border-0 text-center">Tipo</th>
                            <th class="border-0 text-center">Precio Venta (USD)</th>
                            <th class="border-0 text-center">Disponibilidad / Stock</th>
                            <th class="border-0">Descripción</th>
                            <th class="border-0 text-end pe-3 rounded-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Registrar / Editar / Ver Producto o Servicio -->
    <div id="modalInventario" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloModal"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <div id="colorModal" class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloModal">
                        <i class="fas fa-box-open me-2"></i> Nuevo Registro
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-white">
                    <form id="formularioInventario">
                        @csrf

                        <!-- Selector de Tipo de Ítem -->
                        <div class="bg-white p-3 rounded-4 shadow-sm border mb-3">
                            <label class="form-label text-dark fw-bold mb-2 small d-block">
                                <i class="fas fa-tags text-primary me-1"></i> Tipo de Registro <span class="text-danger">*</span>
                            </label>
                            <div class="row g-2" id="selector-tipo-registro">
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipo_producto" value="producto" checked onchange="cambiarTipoRegistro('producto')">
                                    <label class="btn btn-outline-primary w-100 rounded-4 p-3 d-flex flex-column align-items-center gap-2" for="tipo_producto">
                                        <i class="fas fa-box fa-2x"></i>
                                        <span class="fw-bold">Producto Físico</span>
                                        <span class="text-muted small text-center" style="font-size: 0.75rem;">Control de existencias, stock, insumos y Kardex</span>
                                    </label>
                                </div>
                                <div class="col-6">
                                    <input type="radio" class="btn-check" name="tipo" id="tipo_servicio" value="servicio" onchange="cambiarTipoRegistro('servicio')">
                                    <label class="btn btn-outline-info w-100 rounded-4 p-3 d-flex flex-column align-items-center gap-2" for="tipo_servicio">
                                        <i class="fas fa-tag fa-2x"></i>
                                        <span class="fw-bold">Servicio Facturable</span>
                                        <span class="text-muted small text-center" style="font-size: 0.75rem;">Tomas a domicilio, honorarios (sin stock ni Kardex)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Información Principal -->
                        <div class="bg-white p-4 rounded-4 shadow-sm border mb-3">
                            <h6 class="text-primary border-bottom pb-2 mb-3 fw-bold" id="seccion_titulo_info">
                                <i class="fas fa-info-circle me-1"></i> Información del Registro
                            </h6>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="nombre" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Nombre del Ítem <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted px-3" id="icon-nombre-item">
                                            <i class="fas fa-box"></i>
                                        </span>
                                        <input type="text" maxlength="150" autocomplete="off" required
                                            class="form-control rounded-end-pill px-3 border-start-0" id="nombre" name="nombre"
                                            placeholder="Ej. Toma de muestra a domicilio, Tubos con EDTA, Inyectadora 5cc...">
                                    </div>
                                </div>

                                <!-- CAMPOS ESPECÍFICOS DE PRODUCTO FÍSICO -->
                                <div class="col-md-6 campo-producto">
                                    <label for="cantidad" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Cantidad / Stock Inicial <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-calculator"></i>
                                        </span>
                                        <input type="number" min="0" step="0.01" autocomplete="off"
                                            class="form-control rounded-end-pill px-3 border-start-0" id="cantidad"
                                            name="cantidad" placeholder="0">
                                    </div>
                                </div>

                                <div class="col-md-6 campo-producto">
                                    <label for="unidad_medida" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Unidad de Medida <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-pill text-muted px-3">
                                            <i class="fas fa-tag"></i>
                                        </span>
                                        <select class="form-select rounded-end-pill px-3 border-start-0" id="unidad_medida"
                                            name="unidad_medida">
                                            <option value="" selected disabled>Seleccione una unidad...</option>
                                            <option value="Unidad(es)">Unidad(es)</option>
                                            <option value="Cajas">Cajas</option>
                                            <option value="Paquetes">Paquetes</option>
                                            <option value="Frascos">Frascos</option>
                                            <option value="Tubos">Tubos</option>
                                            <option value="Kits">Kits</option>
                                            <option value="Pruebas">Pruebas / Tests</option>
                                            <option value="Litros">Litros (L)</option>
                                            <option value="Mililitros">Mililitros (ml)</option>
                                            <option value="Kg">Kilogramos (Kg)</option>
                                            <option value="Gramos">Gramos (g)</option>
                                            <option value="Metros">Metros (m)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- TOGGLE DE VENTA PARA PRODUCTO FÍSICO -->
                                <div class="col-12 campo-producto">
                                    <div class="p-3 bg-white rounded-4 border border-primary border-opacity-25 shadow-xs d-flex align-items-center justify-content-between flex-wrap gap-3" style="background: linear-gradient(135deg, rgba(239, 246, 255, 0.7) 0%, rgba(255, 255, 255, 0.95) 100%);">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 42px; height: 42px;">
                                                <i class="fas fa-shopping-cart fs-6"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;">¿Este producto se vende directamente en caja / recepción?</h6>
                                                <span class="text-secondary small" style="font-size: 0.78rem;">Activa esta opción para definir su precio y poder facturarlo a pacientes en el punto de ingreso.</span>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch fs-4 mb-0 pe-2">
                                            <input class="form-check-input shadow-none cursor-pointer" type="checkbox" role="switch" id="se_vende" name="se_vende" value="1" onchange="togglePrecioVentaProducto(this.checked)" style="cursor: pointer;">
                                        </div>
                                    </div>
                                </div>

                                <!-- PRECIO DE VENTA (Obligatorio en servicio, condicional en producto) -->
                                <div class="col-md-12" id="bloque-precio-venta" style="display: none;">
                                    <label for="precio_venta" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Precio de Venta al Público (USD) <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 rounded-start-pill text-success fw-bold px-3">
                                            <i class="fas fa-dollar-sign"></i>
                                        </span>
                                        <input type="number" min="0" step="0.01" autocomplete="off"
                                            class="form-control rounded-end-pill px-3 border-start-0 fw-bold text-success fs-5" id="precio_venta"
                                            name="precio_venta" placeholder="0.00">
                                    </div>
                                    <span class="text-muted small ms-2" style="font-size: 0.75rem;">Monto en USD que se cobrará al facturar este ítem en el punto de venta.</span>
                                </div>

                                <!-- INFO ALERT SERVICIO -->
                                <div class="col-12 campo-servicio" style="display: none;">
                                    <div class="p-3 rounded-4 border border-info border-opacity-25 d-flex align-items-center gap-3" style="background: linear-gradient(135deg, rgba(240, 249, 255, 0.8) 0%, rgba(255, 255, 255, 0.95) 100%);">
                                        <div class="bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs" style="width: 40px; height: 40px;">
                                            <i class="fas fa-info-circle fs-6"></i>
                                        </div>
                                        <div class="small">
                                            <strong class="text-dark d-block mb-0.5">Servicio Continuo:</strong>
                                            <span class="text-secondary">Este servicio siempre estará disponible para la venta en caja, no requiere control de stock físico y no genera movimientos en el Kardex.</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label for="descripcion" class="form-label text-dark fw-bold mb-1 ms-1 small">
                                        Descripción o Observaciones <span class="text-secondary fw-normal">(Opcional)</span>
                                    </label>
                                    <textarea class="form-control rounded-4 px-3 py-2" id="descripcion" name="descripcion" rows="3"
                                        placeholder="Detalles sobre el servicio, especificaciones técnicas, ubicación o notas generales..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </button>
                            <button type="submit" form="formularioInventario" id="guardarModal"
                                class="btn btn-primary rounded-pill px-4 ms-2 shadow-sm">
                                <i class="fas fa-save me-1"></i> Guardar
                            </button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Kardex / Historial de Movimientos -->
    <div id="modalKardex" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="tituloKardex"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-0 bg-primary text-white rounded-top-4 pb-3">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="tituloKardex">
                        <i class="fas fa-history me-2"></i> Historial de Movimientos (Kardex)
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4 bg-light">
                    <!-- Banner Informativo del Producto -->
                    <div class="bg-white p-3 rounded-4 shadow-sm border mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-xs">
                                <i class="fas fa-box fa-lg"></i>
                            </div>
                            <div>
                                <span class="text-muted small d-block">Producto seleccionado</span>
                                <h5 class="fw-bold text-dark mb-0" id="kardexProductoNombre">-</h5>
                            </div>
                        </div>
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 fs-6">
                                <i class="fas fa-clipboard-list me-1"></i> Registro de Trazabilidad
                            </span>
                        </div>
                    </div>

                    <!-- Tabla de Movimientos -->
                    <div class="table-responsive rounded-4 shadow-sm border bg-white">
                        <table id="tablaKardex" class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3 border-0 rounded-start">Fecha y Hora</th>
                                    <th class="border-0">Usuario Responsable</th>
                                    <th class="border-0 text-center">Tipo</th>
                                    <th class="border-0 text-center">Cantidad</th>
                                    <th class="border-0">Motivo</th>
                                    <th class="border-0 text-end pe-3 rounded-end">Referencia</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Historial inyectado por JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer bg-white border-top p-3 text-end">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i> Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/inventario.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/inventario.js')) ?: time() }}">
    </script>
@endsection
