@extends('Sistema.layouts.app')

@section('titulo', 'Configuración')
@section('subtitulo', 'Ajustes del Sistema')

@section('contenido')
    <style>
        .nav-pills-modern .nav-link {
            color: #64748b;
            padding: 12px 18px;
            border-radius: 50rem;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s ease-in-out;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            background-color: transparent;
        }

        .nav-pills-modern .nav-link:hover {
            color: #0d6efd;
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateX(3px);
        }

        .nav-pills-modern .nav-link.active {
            color: #ffffff;
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            box-shadow: 0 6px 15px rgba(13, 110, 253, 0.25);
            border-color: transparent;
        }

        .config-icon {
            width: 24px;
            text-align: center;
            margin-right: 12px;
            font-size: 1.05rem;
        }

        .section-badge-icon {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .tasa-kpi-card {
            background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);
            border: 1px solid #e2e8f0;
        }
    </style>

    <!-- Encabezado Principal -->
    <div class="row align-items-center mb-4">
        <div class="col-12 col-md-auto mb-3 mb-md-0">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-circle shadow-sm">
                    <i class="fas fa-cogs"></i>
                </div>
                <div>
                    <h2 class="mb-0 fw-bold text-dark h3">Configuración de la Empresa</h2>
                    <p class="text-muted mb-0 small">Ajustes generales del laboratorio, contacto, facturación y tasa de cambio</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Menú Lateral de Configuración -->
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center mb-3 ms-2">
                        <span class="text-uppercase text-muted fw-bold" style="font-size: 0.72rem; letter-spacing: 1px;">
                            Módulos de Ajuste
                        </span>
                    </div>
                    <div class="nav flex-column nav-pills-modern" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                        <button class="nav-link text-start active" id="v-pills-general-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-general" type="button" role="tab" aria-selected="true">
                            <i class="fas fa-building config-icon"></i> Perfil de Empresa
                        </button>

                        <button class="nav-link text-start" id="v-pills-finanzas-tab" data-bs-toggle="pill"
                            data-bs-target="#v-pills-finanzas" type="button" role="tab" aria-selected="false">
                            <i class="fas fa-exchange-alt config-icon"></i> Finanzas y Moneda
                        </button>
                    </div>

                    <div class="mt-4 pt-3 border-top px-2">
                        <div class="d-flex align-items-center text-muted small">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            <span>Conexión segura activa</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido de Configuración -->
        <div class="col-lg-9">
            <div class="tab-content" id="v-pills-tabContent">

                <!-- TAB 1: PERFIL DE EMPRESA -->
                <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel" tabindex="0">
                    <form id="formularioEmpresa" enctype="multipart/form-data">
                        @csrf

                        <!-- Card 1: Identificación y Suscripción -->
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="section-badge-icon bg-primary bg-opacity-10 text-primary">
                                        <i class="fas fa-hospital"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Información del Laboratorio</h6>
                                        <span class="text-muted small">Identidad legal y detalles de suscripción</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label small fw-bold text-muted">Nombre Comercial</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-building"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-start-0 rounded-end-pill" id="nombre" readonly disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">RIF / Documento Fiscal</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-id-card"></i>
                                            </span>
                                            <input type="text" class="form-control bg-light border-start-0 rounded-end-pill" id="rif" readonly disabled>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted">Dirección Fiscal</label>
                                        <textarea class="form-control bg-light rounded-4" id="direccion" rows="2" readonly disabled></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Plan Actual</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-primary bg-opacity-10 border-end-0 rounded-start-pill text-primary">
                                                <i class="fas fa-gem"></i>
                                            </span>
                                            <input type="text" class="form-control bg-primary bg-opacity-10 text-primary fw-bold border-start-0 rounded-end-pill"
                                                id="plan" readonly disabled>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Vencimiento de Suscripción</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-danger bg-opacity-10 border-end-0 rounded-start-pill text-danger">
                                                <i class="fas fa-calendar-alt"></i>
                                            </span>
                                            <input type="text" class="form-control bg-danger bg-opacity-10 text-danger fw-bold border-start-0 rounded-end-pill"
                                                id="fechaFinalSuscripcion" readonly disabled>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Contacto y Comunicaciones -->
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="section-badge-icon bg-info bg-opacity-10 text-info">
                                        <i class="fas fa-address-book"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Canales de Contacto</h6>
                                        <span class="text-muted small">Información reflejada en facturas y cabeceras de reportes</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Teléfono Principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0 rounded-end-pill" name="telefonoUno"
                                                id="telefonoUno" placeholder="Ej. 0414-1234567">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Teléfono Secundario</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-phone"></i>
                                            </span>
                                            <input type="text" class="form-control border-start-0 rounded-end-pill" name="telefonoDos"
                                                id="telefonoDos" placeholder="Ej. 0212-7654321">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Correo Principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-envelope"></i>
                                            </span>
                                            <input type="email" class="form-control border-start-0 rounded-end-pill" name="correo"
                                                id="correo" placeholder="laboratorio@empresa.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Correo Secundario / Copia</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-envelope-open"></i>
                                            </span>
                                            <input type="email" class="form-control border-start-0 rounded-end-pill" name="correoSecundario"
                                                id="correoSecundario" placeholder="administracion@empresa.com">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Parámetros de Facturación y Operaciones -->
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="section-badge-icon bg-warning bg-opacity-10 text-warning">
                                        <i class="fas fa-sliders-h"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Parámetros Operativos y Facturación</h6>
                                        <span class="text-muted small">Formato de documentos, correlativos y políticas de validación</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Formato de Impresión</label>
                                        <select class="form-select rounded-pill" name="formato_factura" id="formato_factura">
                                            <option value="ticket_80mm">Ticket Térmico (80mm)</option>
                                            <option value="ticket_58mm">Ticket Térmico (58mm)</option>
                                            <option value="a4">Hoja Completa / Carta (PDF)</option>
                                            <option value="a5">Media Hoja / Media Carta (PDF)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Reinicio N° Control (Facturas)</label>
                                        <select class="form-select rounded-pill" name="reinicio_numero_control" id="reinicio_numero_control">
                                            <option value="mensual">Mensual (Cada 1° de mes)</option>
                                            <option value="diario">Diario (Cada día a las 00:00)</option>
                                            <option value="semanal">Semanal (Cada lunes)</option>
                                            <option value="continuo">Continuo (Correlativo sin reinicio)</option>
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label small fw-bold text-muted">Reinicio N° Orden (Resultados)</label>
                                        <select class="form-select rounded-pill" name="reinicio_numero_orden" id="reinicio_numero_orden">
                                            <option value="continuo">Continuo (Correlativo sin reinicio)</option>
                                            <option value="diario">Diario (Cada día a las 00:00)</option>
                                            <option value="semanal">Semanal (Cada lunes)</option>
                                            <option value="mensual">Mensual (Cada 1° de mes)</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <div class="p-3 bg-light border rounded-4 d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="p-2 rounded-circle bg-primary bg-opacity-10 text-primary mt-1">
                                                    <i class="fas fa-check-circle"></i>
                                                </div>
                                                <div>
                                                    <label class="form-check-label fw-bold text-dark mb-1" for="requiere_validacion">
                                                        Requerir Validación Estricta de Resultados
                                                    </label>
                                                    <div class="text-muted small">
                                                        Fuerza a que los resultados transcritos sean firmados y aprobados por un Bioanalista antes de poder imprimirse o despacharse al paciente.
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-check form-switch m-0 ps-0">
                                                <input class="form-check-input fs-4 m-0 shadow-none" type="checkbox"
                                                    role="switch" id="requiere_validacion" name="requiere_validacion"
                                                    value="1" style="cursor:pointer">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4: Integraciones y Logo -->
                        <div class="card shadow-sm border-0 rounded-4 mb-4">
                            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="section-badge-icon bg-success bg-opacity-10 text-success">
                                        <i class="fab fa-telegram-plane"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Integraciones de Respaldo y Marca</h6>
                                        <span class="text-muted small">Alertas Telegram y logotipo institucional</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Telegram Chat ID (Canal de Respaldo)</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-primary rounded-start-pill">
                                                <i class="fab fa-telegram-plane"></i>
                                            </span>
                                            <input type="text" class="form-control rounded-end-pill border-start-0"
                                                name="telegram_chat_id" id="telegram_chat_id" placeholder="Ej. 123456789">
                                        </div>
                                        <div class="text-muted small mt-2 ps-2">
                                            <i class="fas fa-info-circle text-primary me-1"></i> Si un paciente no posee correo electrónico, los reportes se despacharán a este chat ID de Telegram para trazabilidad inmediata.
                                        </div>
                                    </div>

                                    <div class="col-md-12 mt-3">
                                        <label class="form-label small fw-bold text-muted">Logotipo del Laboratorio</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">
                                                <i class="fas fa-image"></i>
                                            </span>
                                            <input type="file" class="form-control rounded-end-pill border-start-0" name="logo"
                                                id="logo" accept="image/*">
                                        </div>
                                        <div class="text-muted small mt-1 ps-2">Formatos admitidos: PNG, JPG, JPEG o WEBP. Recomendado sobre fondo transparente.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botón Guardar Cambios -->
                        <div class="text-end mb-4">
                            <button type="submit" id="btnGuardarEmpresa" class="btn btn-primary px-5 py-2 fw-bold rounded-pill shadow-sm">
                                <i class="fas fa-save me-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>

                <!-- TAB 2: FINANZAS Y MONEDA (TASA BCV) -->
                <div class="tab-pane fade" id="v-pills-finanzas" role="tabpanel" tabindex="0">
                    <div class="row g-4">
                        <!-- KPI & Formulario de Actualización de Tasa -->
                        <div class="col-lg-5">
                            <div class="card shadow-sm border-0 rounded-4 h-100">
                                <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                                    <div class="section-badge-icon bg-success bg-opacity-10 text-success mx-auto mb-2">
                                        <i class="fas fa-money-bill-alt"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-0">Tasa Oficial de Cambio</h5>
                                    <p class="text-muted small">Valor de conversión de referencia</p>
                                </div>
                                <div class="card-body p-4 text-center">
                                    <!-- Tarjeta KPI con Tasa Actual -->
                                    <div class="p-4 rounded-4 tasa-kpi-card shadow-xs mb-4">
                                        <span class="text-uppercase text-muted fw-bold small d-block mb-1" style="letter-spacing: 0.5px;">
                                            Tasa Actual Registrada
                                        </span>
                                        <span class="fs-1 fw-extrabold text-success d-block" id="tasaActualSpan">
                                            Bs. 0.00
                                        </span>
                                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 mt-2 small">
                                            <i class="fas fa-check-circle me-1"></i> Moneda Base Activa
                                        </span>
                                    </div>

                                    <!-- Formulario para ingresar nueva tasa -->
                                    <form id="formularioTasa">
                                        @csrf
                                        <div class="text-start mb-3">
                                            <label for="tasa" class="form-label small fw-bold text-muted">Nuevo Valor de Conversión</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border-end-0 rounded-start-pill text-muted">Bs.</span>
                                                <input type="number" step="0.01" class="form-control border-start-0 rounded-end-pill py-2"
                                                    id="tasa" name="tasa" placeholder="0.00" required>
                                            </div>
                                        </div>
                                        <button type="submit" id="btnGuardarTasa"
                                            class="btn btn-success w-100 py-2 fw-bold rounded-pill shadow-sm">
                                            <i class="fas fa-sync-alt me-2"></i> Aplicar Nueva Tasa
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Historial de Fluctuación -->
                        <div class="col-lg-7">
                            <div class="card shadow-sm border-0 rounded-4 h-100">
                                <div class="card-header bg-white border-bottom pt-4 pb-3 px-4">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="section-badge-icon bg-primary bg-opacity-10 text-primary">
                                                <i class="fas fa-history"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold text-dark mb-0">Historial de Fluctuación</h6>
                                                <span class="text-muted small">Registro cronológico de ajustes cambiarios</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <div class="table-responsive">
                                        <table id="datatable_tasa"
                                            class="table table-hover align-middle mb-0"
                                            style="width:100%">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="ps-3 border-0 rounded-start">Fecha y Hora</th>
                                                    <th class="border-0 text-end pe-3 rounded-end">Monto Registrado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/configuracion.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/configuracion.js')) ?: time() }}">
    </script>
@endsection
