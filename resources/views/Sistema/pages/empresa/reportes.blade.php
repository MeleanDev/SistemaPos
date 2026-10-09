@extends('Sistema.layouts.app')

@section('titulo', '📊 Centro de Reportes & Auditoría')
@section('subtitulo', 'Generación de informes ejecutivos con consulta previa en pantalla, visualización e impresión de documentos formales y exportación a Excel')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Centro de Reportes</span>
@endsection

@section('acciones')
    <div class="d-flex align-items-center gap-2">
        <span class="badge bg-white text-dark border rounded-pill px-3 py-2 shadow-xs fw-semibold d-none d-md-inline-flex align-items-center">
            <i class="fas fa-chart-line text-primary me-2"></i>Tasa Oficial: <strong class="ms-1 text-primary">{{ number_format($tasa_usd, 2) }} Bs/$</strong>
        </span>
    </div>
@endsection

@section('contenido')
<div class="container-fluid px-0">

    <!-- BANNER EJECUTIVO CORPORATIVO -->
    <div class="card border-0 rounded-4 shadow-sm text-white mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 text-white-50 small mb-2">
                        <i class="fas fa-shield-alt text-warning"></i>
                        <span>Módulo de Reportería Financiera & Auditoría</span>
                    </div>
                    <h3 class="fw-bold mb-1 text-white">Centro Oficial de Reportes</h3>
                    <p class="text-white-50 mb-0">Seleccione los parámetros requeridos y use <strong>Vista Previa</strong> para auditar en pantalla, o exporte los documentos en formato <strong>PDF oficial</strong> y <strong>Excel</strong>.</p>
                </div>
                <div class="col-12 col-lg-4 text-start text-lg-end">
                    <div class="d-inline-flex flex-column align-items-start align-items-lg-end bg-white bg-opacity-10 p-3 rounded-4">
                        <span class="text-white-50 small fw-semibold text-uppercase">Informes Disponibles</span>
                        <span class="fs-5 fw-bold text-white"><i class="fas fa-file-invoice text-success me-2"></i>6 Módulos de Auditoría</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Modern Select2 Multi-Select Styles */
        .select2-modern-wrapper .select2-container {
            width: 100% !important;
            display: block !important;
        }
        .select2-modern-wrapper .select2-container .select2-selection--multiple {
            min-height: 48px !important;
            border: 1px solid #cbd5e1 !important;
            border-radius: 12px !important;
            padding: 4px 10px !important;
            background-color: #f8fafc !important;
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 4px !important;
            cursor: pointer !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .select2-modern-wrapper .select2-container--focus .select2-selection--multiple,
        .select2-modern-wrapper .select2-container--open .select2-selection--multiple {
            border-color: #4f46e5 !important;
            background-color: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15) !important;
        }
        .select2-modern-wrapper .select2-selection__rendered {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 5px !important;
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            list-style: none !important;
        }
        .select2-modern-wrapper .select2-selection__choice {
            background: #eef2ff !important;
            border: 1px solid #c7d2fe !important;
            color: #3730a3 !important;
            border-radius: 20px !important;
            padding: 3px 10px 3px 10px !important;
            font-size: 0.76rem !important;
            font-weight: 600 !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            margin: 2px 0 !important;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04) !important;
        }
        .select2-modern-wrapper .select2-selection__choice__remove {
            color: #6366f1 !important;
            border: none !important;
            background: transparent !important;
            font-size: 0.95rem !important;
            font-weight: bold !important;
            cursor: pointer !important;
            padding: 0 !important;
            margin: 0 !important;
            line-height: 1 !important;
            order: 2 !important;
        }
        .select2-modern-wrapper .select2-selection__choice__remove:hover {
            color: #ef4444 !important;
        }
        .select2-modern-wrapper .select2-search--inline {
            flex-grow: 1 !important;
            margin: 2px 0 !important;
        }
        .select2-modern-wrapper .select2-search__field {
            margin-top: 0 !important;
            height: 30px !important;
            font-size: 0.84rem !important;
            color: #1e293b !important;
            border: none !important;
            background: transparent !important;
            outline: none !important;
        }
        .select2-modern-wrapper .select2-search__field::placeholder {
            color: #94a3b8 !important;
            font-size: 0.82rem !important;
        }
        .select2-dropdown {
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05) !important;
            overflow: hidden !important;
            z-index: 1060 !important;
        }
        .select2-results__option {
            padding: 8px 14px !important;
            font-size: 0.84rem !important;
            color: #1e293b !important;
        }
        .select2-results__option--highlighted[aria-selected] {
            background-color: #4f46e5 !important;
            color: #ffffff !important;
        }
        .select2-results__option[aria-selected="true"] {
            background-color: #f1f5f9 !important;
            color: #64748b !important;
            font-weight: 600 !important;
        }
    </style>

    <!-- GRILLA DE REPORTES EJECUTIVOS RESPONSIVE -->
    <div class="row g-4">

        <!-- 1. REPORTE DE INGRESOS Y CAJA -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-cash-register fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold rounded-pill px-2.5 py-1 small">Finanzas & Ventas</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Ingresos & Métodos de Pago</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Consolidado de ventas facturadas, recaudación por forma de pago ($ USD y Bs. VES), vueltos y créditos otorgados.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <x-input
                            name="ing_fecha_inicio"
                            id="ing_fecha_inicio"
                            type="date"
                            label="Desde"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-01') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-input
                            name="ing_fecha_fin"
                            id="ing_fecha_fin"
                            type="date"
                            label="Hasta"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-d') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-select
                            name="ing_caja_id"
                            id="ing_caja_id"
                            label="Caja"
                            icon="fas fa-cash-register text-secondary"
                            col="col-12 col-sm-6"
                        >
                            <option value="">-- Todas las Cajas --</option>
                            @foreach($cajas as $caja)
                                <option value="{{ $caja->id }}">{{ $caja->nombre }}</option>
                            @endforeach
                        </x-select>
                        <x-select
                            name="ing_metodo_pago_id"
                            id="ing_metodo_pago_id"
                            label="Método de Pago"
                            icon="fas fa-credit-card text-secondary"
                            col="col-12 col-sm-6"
                        >
                            <option value="">-- Todos los Métodos --</option>
                            @foreach($metodos_pago as $metodo)
                                <option value="{{ $metodo->id }}">{{ $metodo->nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('ingresos', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('ingresos', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('ingresos', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('ingresos', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

        <!-- 2. REPORTE DE CARTERA (CXC / CXP) -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-file-invoice-dollar fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-danger bg-opacity-10 text-danger fw-semibold rounded-pill px-2.5 py-1 small">Créditos & Deudas</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Cartera de Créditos (CXC / CXP)</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Cuentas por cobrar a clientes, cuentas por pagar a proveedores, días de mora, cuentas al día y abonos recaudados.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <x-input
                            name="cred_fecha_inicio"
                            id="cred_fecha_inicio"
                            type="date"
                            label="Desde (Abonos)"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-01') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-input
                            name="cred_fecha_fin"
                            id="cred_fecha_fin"
                            type="date"
                            label="Hasta (Abonos)"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-d') }}"
                            col="col-12 col-sm-6"
                        />
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('creditos', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('creditos', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('creditos', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('creditos', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

        <!-- 3. REPORTE DE INVENTARIO Y VALORIZACION -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-info bg-opacity-10 text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-boxes-stacked fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-info bg-opacity-10 text-dark fw-semibold rounded-pill px-2.5 py-1 small">Stock & Almacenes</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Inventario & Valorización</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Valor total a costo y venta proyectada, desglose por almacén, categorías y reporte de stock crítico / reorden.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <x-select
                            name="inv_almacen_id"
                            id="inv_almacen_id"
                            label="Almacén"
                            icon="fas fa-warehouse text-secondary"
                            col="col-12 col-sm-6"
                        >
                            <option value="">-- Todos los Almacenes --</option>
                            @foreach($almacenes as $alm)
                                <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                            @endforeach
                        </x-select>
                        <x-select
                            name="inv_categoria_id"
                            id="inv_categoria_id"
                            label="Categoría"
                            icon="fas fa-tags text-secondary"
                            col="col-12 col-sm-6"
                        >
                            <option value="">-- Todas las Categorías --</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </x-select>
                        <div class="col-12">
                            <div class="form-check form-switch pt-1">
                                <input class="form-check-input" type="checkbox" role="switch" id="inv_bajo_stock" value="1">
                                <label class="form-check-label fw-semibold small text-danger" for="inv_bajo_stock">
                                    <i class="fas fa-exclamation-triangle me-1"></i> Filtrar únicamente artículos en Bajo Stock / Crítico
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('inventario', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('inventario', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('inventario', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('inventario', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

        <!-- 4. REPORTE DE RENTABILIDAD & MARGEN BRUTO -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-chart-pie fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-success bg-opacity-10 text-success fw-semibold rounded-pill px-2.5 py-1 small">Utilidad & Rentabilidad</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Rentabilidad & Utilidad Bruta</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Ganancia bruta real deducida contra costo de ventas (COGS) y ranking de productos más rentables.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <x-input
                            name="rent_fecha_inicio"
                            id="rent_fecha_inicio"
                            type="date"
                            label="Desde"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-01') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-input
                            name="rent_fecha_fin"
                            id="rent_fecha_fin"
                            type="date"
                            label="Hasta"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-d') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-select
                            name="rent_almacen_id"
                            id="rent_almacen_id"
                            label="Almacén / Sucursal"
                            icon="fas fa-warehouse text-secondary"
                            col="col-12"
                        >
                            <option value="">-- Todos los Almacenes --</option>
                            @foreach($almacenes as $alm)
                                <option value="{{ $alm->id }}">{{ $alm->nombre }}</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('rentabilidad', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('rentabilidad', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('rentabilidad', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('rentabilidad', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

        <!-- 5. REPORTE DE VENDEDORES & COMISIONES -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-user-tie fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-warning bg-opacity-10 text-dark fw-semibold rounded-pill px-2.5 py-1 small">Fuerza de Ventas</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Vendedores & Comisiones</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Ventas por asesor comercial, número de facturas cerradas y cálculo de comisiones ganadas.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <x-input
                            name="vend_fecha_inicio"
                            id="vend_fecha_inicio"
                            type="date"
                            label="Desde"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-01') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-input
                            name="vend_fecha_fin"
                            id="vend_fecha_fin"
                            type="date"
                            label="Hasta"
                            icon="fas fa-calendar-alt text-secondary"
                            value="{{ date('Y-m-d') }}"
                            col="col-12 col-sm-6"
                        />
                        <x-select
                            name="vend_vendedor_id"
                            id="vend_vendedor_id"
                            label="Vendedor Específico"
                            icon="fas fa-user-tie text-secondary"
                            col="col-12"
                        >
                            <option value="">-- Todos los Vendedores --</option>
                            @foreach($vendedores as $vend)
                                <option value="{{ $vend->id }}">{{ $vend->nombre }} ({{ $vend->comision_porcentaje }}%)</option>
                            @endforeach
                        </x-select>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('vendedores', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('vendedores', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('vendedores', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('vendedores', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

        <!-- 6. REPORTE DE EXISTENCIAS POR ALMACÉN (MATRIZ MULTIALMACÉN) -->
        <div class="col-12 col-xl-6">
            <div class="card border rounded-4 shadow-xs h-100 bg-white d-flex flex-column justify-content-between overflow-hidden">
                <div class="p-4 border-bottom bg-white">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                            <i class="fas fa-warehouse fs-5"></i>
                        </div>
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold rounded-pill px-2.5 py-1 small">Multialmacén & Stock</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Existencias por Almacén</h5>
                        </div>
                    </div>
                    <p class="text-muted small mb-0">
                        Matriz de existencias físicas desglosadas por cada almacén con filtro de productos específicos y categorías múltiples.
                    </p>
                </div>

                <div class="p-4 bg-white flex-grow-1">
                    <div class="row g-3">
                        <!-- BUSCADOR / SELECTOR MÚLTIPLE DE PRODUCTOS -->
                        <div class="col-12">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <label for="stock_producto_ids" class="form-label small fw-bold text-uppercase text-muted mb-0">
                                    <i class="fas fa-box text-primary me-1"></i> Filtrar Productos Seleccionados
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-light text-muted border px-2 py-0.5" id="badgeContadorProductos" style="font-size: 0.72rem;">Todos</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fw-semibold" style="font-size: 0.72rem;" onclick="limpiarSelectorModerno('#stock_producto_ids')">
                                        <i class="fas fa-times-circle me-0.5"></i>Limpiar
                                    </button>
                                </div>
                            </div>
                            <div class="select2-modern-wrapper">
                                <select name="stock_producto_ids[]" id="stock_producto_ids" class="select2-modern-multiselect" multiple="multiple" style="width: 100%;">
                                    @foreach($productos as $prod)
                                        <option value="{{ $prod->id }}">{{ $prod->codigo_interno }} — {{ $prod->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size: 0.73rem;">
                                <i class="fas fa-info-circle me-1 text-primary"></i>Deje vacío para consultar todo el inventario, o busque y seleccione varios artículos.
                            </small>
                        </div>

                        <!-- BUSCADOR / SELECTOR MÚLTIPLE DE CATEGORÍAS -->
                        <div class="col-12 col-md-7">
                            <div class="d-flex align-items-center justify-content-between mb-1.5">
                                <label for="stock_categoria_ids" class="form-label small fw-bold text-uppercase text-muted mb-0">
                                    <i class="fas fa-tags text-primary me-1"></i> Categorías
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge rounded-pill bg-light text-muted border px-2 py-0.5" id="badgeContadorCategorias" style="font-size: 0.72rem;">Todas</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-danger fw-semibold" style="font-size: 0.72rem;" onclick="limpiarSelectorModerno('#stock_categoria_ids')">
                                        <i class="fas fa-times-circle me-0.5"></i>Limpiar
                                    </button>
                                </div>
                            </div>
                            <div class="select2-modern-wrapper">
                                <select name="stock_categoria_ids[]" id="stock_categoria_ids" class="select2-modern-multiselect" multiple="multiple" style="width: 100%;">
                                    @foreach($categorias as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- TOGGLE SOLO CON STOCK -->
                        <div class="col-12 col-md-5 d-flex align-items-center">
                            <div class="form-check form-switch p-2.5 rounded-3 bg-light border w-100 mt-md-4">
                                <input class="form-check-input ms-0 me-2" type="checkbox" id="stock_solo_con_stock" value="1" style="cursor: pointer;">
                                <label class="form-check-label small fw-semibold text-dark" for="stock_solo_con_stock" style="cursor: pointer;">
                                    Solo con existencia (> 0)
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-3 bg-white border-top d-flex flex-column flex-sm-row flex-wrap gap-2 align-items-stretch align-items-sm-center">
                    <x-button variant="primary" icon="fas fa-eye" text="Vista Previa" onclick="generarReporte('stock_almacenes', 'previa')" class="flex-fill" />
                    <x-button variant="outline-dark" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="generarReporte('stock_almacenes', 'pdf_ver')" class="flex-fill" />
                    <x-button variant="outline-danger" icon="fas fa-download" text="Descargar PDF" onclick="generarReporte('stock_almacenes', 'pdf_descargar')" class="flex-fill" />
                    <x-button variant="outline-success" icon="fas fa-file-excel" text="Excel" onclick="generarReporte('stock_almacenes', 'excel')" class="flex-fill" />
                </div>
            </div>
        </div>

    </div>
</div>

<!-- MODAL: VISTA PREVIA RÁPIDA EN PANTALLA -->
<x-modal
    id="modalVistaPrevia"
    title="Vista Previa del Reporte"
    subtitle="Auditoría y consulta instantánea"
    icon="fas fa-chart-pie text-warning fs-5"
    size="modal-xl"
    headerColor="bg-dark text-white"
    :submitButton="false"
>
    <!-- BARRA SUPERIOR DE ACCIONES DIRECTAS EN EL MODAL -->
    <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3 p-3 bg-white border rounded-4 shadow-xs mb-4">
        <div class="d-flex align-items-center flex-wrap gap-2 flex-grow-1">
            <span class="badge rounded-pill px-3 py-2 fw-bold text-white text-uppercase font-monospace shadow-xs text-nowrap" id="previaBadgeModulo" style="background-color: #0f172a; font-size: 0.74rem; letter-spacing: 0.5px;">
                REPORTE
            </span>
            <div class="d-inline-flex align-items-center px-3 py-1.5 rounded-pill bg-white border shadow-xs text-dark small fw-semibold text-nowrap">
                <i class="fas fa-calendar-alt text-primary me-2"></i>
                <span id="previaPeriodoTexto" class="font-monospace text-nowrap">Período Seleccionado</span>
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 flex-shrink-0">
            <x-button variant="outline-dark" size="sm" icon="fas fa-file-pdf" iconColor="text-danger" text="Ver PDF" onclick="exportarDesdePrevia('pdf_ver')" />
            <x-button variant="outline-danger" size="sm" icon="fas fa-download" text="Descargar PDF" onclick="exportarDesdePrevia('pdf_descargar')" />
            <x-button variant="outline-success" size="sm" icon="fas fa-file-excel" text="Excel" onclick="exportarDesdePrevia('excel')" />
        </div>
    </div>

    <!-- CONTENEDOR DINÁMICO DE KPIS -->
    <div class="row g-3 mb-4" id="contenedorKpisPrevia">
        <!-- Inyección dinámica de KPIs -->
    </div>

    <!-- CONTENEDOR DE TABLA DE DETALLE -->
    <div class="card border rounded-4 overflow-hidden bg-white shadow-xs">
        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
            <span class="fw-bold text-dark fs-6" id="previaTituloTabla"><i class="fas fa-table-list text-primary me-2"></i>Detalle de Registros</span>
            <span class="badge bg-white text-dark border rounded-pill px-3 py-1.5 font-monospace fw-semibold" id="previaConteoRegistros">0 registros</span>
        </div>
        <div class="table-responsive" style="max-height: 440px;" id="contenedorTablaPrevia">
            <!-- Inyección dinámica de filas -->
        </div>
    </div>

    <x-slot:footer>
        <span class="text-muted small"><i class="fas fa-info-circle me-1"></i> Vista previa generada en tiempo real.</span>
        <x-button variant="outline-secondary" icon="fas fa-times" text="Cerrar" data-bs-dismiss="modal" />
    </x-slot:footer>
</x-modal>
@endsection

@section('scripts')
    <script src="{{ asset('estilos/jsPropios/reportes.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/reportes.js')) ?: time() }}"></script>
@endsection
