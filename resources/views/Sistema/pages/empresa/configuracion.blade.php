@extends('Sistema.layouts.app')

@section('titulo', '⚙️ Configuración de Empresa')
@section('subtitulo', 'Perfil corporativo, identidad visual, monedas operativas y tasas de cambio oficiales')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Configuración</span>
@endsection

@section('contenido')
<div class="row g-4 mb-4">
    
    <!-- COLUMNA 1: IDENTIDAD, DATOS FISCALES Y PREVIEW DE TICKET -->
    <div class="col-12 col-xl-7">
        <div class="card card-executive border rounded-4 shadow-sm p-3 p-sm-4 bg-white mb-4">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-building-columns fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Perfil Corporativo</h5>
                        <small class="text-muted">Identidad fiscal, datos comerciales y parámetros generales</small>
                    </div>
                </div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold font-monospace align-self-start align-self-sm-auto">
                    <i class="fas fa-fingerprint me-1"></i> Empresa Activa
                </span>
            </div>

            <form id="formEmpresa" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    
                    <!-- LOGO DE LA EMPRESA CON PREVIEW Y CONTROLES RESPONSIVE -->
                    <div class="col-12">
                        <label class="form-label-executive mb-2">
                            <i class="fas fa-image text-primary me-1"></i> Logotipo Corporativo
                        </label>
                        <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3 p-3 border rounded-4 bg-white shadow-xs">
                            <div id="contenedorPreviewLogo" class="border rounded-4 bg-white p-2 d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width: 88px; height: 88px; min-width: 88px; overflow: hidden; position: relative;">
                                <i class="fas fa-building text-muted fs-2" id="placeholderLogo"></i>
                                <img id="previewLogo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1' height='1'%3E%3C/svg%3E" alt="Logo de la Empresa" class="img-fluid rounded-3 d-none" style="max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1 text-center text-sm-start w-100">
                                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2 mb-2">
                                    <label for="logo" class="btn btn-outline-primary btn-sm rounded-pill px-3 cursor-pointer mb-0">
                                        <i class="fas fa-upload me-1"></i> Seleccionar Imagen
                                    </label>
                                    <button type="button" id="btnQuitarLogo" class="btn btn-outline-secondary btn-sm rounded-pill px-3 d-none">
                                        <i class="fas fa-times me-1"></i> Restablecer
                                    </button>
                                </div>
                                <div class="text-muted" style="font-size: 0.76rem; line-height: 1.4;">
                                    Formatos admitidos: <strong>PNG, JPG, WEBP, SVG</strong> (Máx. 2MB).<br class="d-none d-sm-inline">
                                    Recomendado logotipo con fondo transparente para tickets y reportes.
                                </div>
                                <input type="file" class="d-none" id="logo" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                            </div>
                        </div>
                    </div>

                    <!-- RIF ESTRUCTURADO (PREFIJO + NÚMERO) -->
                    <div class="col-12 col-md-5">
                        <label class="form-label-executive" for="rif_numero">
                            <i class="fas fa-id-card text-primary me-1"></i> RIF / Documento Fiscal <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-executive">
                            <select class="form-select bg-white font-monospace fw-bold" id="rif_prefijo" style="max-width: 78px; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                                <option value="J-" selected>J-</option>
                                <option value="V-">V-</option>
                                <option value="G-">G-</option>
                                <option value="E-">E-</option>
                                <option value="P-">P-</option>
                            </select>
                            <input type="text" class="form-control form-control-executive font-monospace" id="rif_numero" placeholder="12345678-0" maxlength="15" required style="border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                        </div>
                        <input type="hidden" name="rif" id="rif">
                    </div>

                    <!-- NOMBRE COMERCIAL -->
                    <div class="col-12 col-md-7">
                        <x-input name="nombre" id="nombre" label="Nombre Comercial" icon="fas fa-store"
                            placeholder="Ej. Inversiones Mi Negocio C.A." required maxlength="150" />
                    </div>

                    <!-- RAZÓN SOCIAL LEGAL -->
                    <div class="col-12">
                        <x-input name="razon_social" id="razon_social" label="Razón Social Legal Completa" icon="fas fa-file-contract"
                            placeholder="Ej. Inversiones Mi Negocio Compañía Anónima" required maxlength="150" />
                    </div>

                    <!-- TELÉFONO DE CONTACTO ESTRUCTURADO -->
                    <div class="col-12 col-md-6">
                        <label class="form-label-executive" for="telefono_numero">
                            <i class="fas fa-phone text-primary me-1"></i> Teléfono de Contacto
                            <span class="text-muted fw-normal ms-1">(Opcional)</span>
                        </label>
                        <div class="input-group input-group-executive">
                            <select class="form-select bg-white font-monospace" id="telefono_prefijo" style="max-width: 95px; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                                <option value="+58" selected>🇻🇪 +58</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+57">🇨🇴 +57</option>
                                <option value="+56">🇨🇱 +56</option>
                                <option value="+51">🇵🇪 +51</option>
                                <option value="+55">🇧🇷 +55</option>
                                <option value="+593">🇪🇨 +593</option>
                                <option value="+34">🇪🇸 +34</option>
                                <option value="+54">🇦🇷 +54</option>
                                <option value="+507">🇵🇦 +507</option>
                                <option value="+52">🇲🇽 +52</option>
                            </select>
                            <input type="tel" class="form-control form-control-executive font-monospace" id="telefono_numero" placeholder="412 1234567" maxlength="18" style="border-top-right-radius: 12px; border-bottom-right-radius: 12px;">
                        </div>
                        <input type="hidden" name="telefono" id="telefono">
                    </div>

                    <!-- CORREO ELECTRÓNICO -->
                    <div class="col-12 col-md-6">
                        <x-input type="email" name="correo" id="correo" label="Correo Electrónico" icon="fas fa-envelope"
                            placeholder="Ej. contacto@minegocio.com" maxlength="150" optionalText="Opcional" />
                    </div>

                    <!-- DIRECCIÓN FISCAL -->
                    <div class="col-12">
                        <x-input name="direccion" id="direccion" label="Dirección Fiscal" icon="fas fa-map-marker-alt"
                            placeholder="Ej. Av. Principal, Edificio Central, Piso 1, Local 2" required maxlength="255" />
                    </div>

                    <!-- MÓDULOS ESPECIALES / FEATURE FLAGS (EXECUTIVE CARDS) -->
                    <div class="col-12 mt-2">
                        <label class="form-label-executive mb-2">
                            <i class="fas fa-toggle-on text-primary me-1"></i> Módulos y Funcionalidades Opcionales
                        </label>
                        <div class="row g-3">
                            <div class="col-12 col-sm-6">
                                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100 d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                                <i class="fas fa-motorcycle"></i>
                                            </span>
                                            <div>
                                                <label class="form-check-label fw-bold text-dark d-block mb-0 cursor-pointer" for="maneja_motos">Módulo de Motos</label>
                                                <small class="text-muted" style="font-size: 0.75rem;">Control de NIV, Motor, Chasis y Seriales</small>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-0 ms-2">
                                            <input class="form-check-input fs-5 cursor-pointer" type="checkbox" role="switch" id="maneja_motos" name="maneja_motos" value="1">
                                        </div>
                                    </div>
                                    <div class="pt-2 border-top text-muted" style="font-size: 0.72rem;">
                                        Habilita compras y recepción de unidades serializadas en el inventario.
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-sm-6">
                                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100 d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-start justify-content-between mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-info-subtle text-info rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                                                <i class="fas fa-user-tag"></i>
                                            </span>
                                            <div>
                                                <label class="form-check-label fw-bold text-dark d-block mb-0 cursor-pointer" for="maneja_vendedores">Fuerza de Venta</label>
                                                <small class="text-muted" style="font-size: 0.75rem;">Preventas en espera y comisiones</small>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-0 ms-2">
                                            <input class="form-check-input fs-5 cursor-pointer" type="checkbox" role="switch" id="maneja_vendedores" name="maneja_vendedores" value="1">
                                        </div>
                                    </div>
                                    <div class="pt-2 border-top text-muted" style="font-size: 0.72rem;">
                                        Permite asignar asesores de venta a tickets y liquidar comisiones.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BOTÓN GUARDAR EMPRESA -->
                    <div class="col-12 text-end mt-4 pt-2 border-top">
                        <button type="submit" id="btnGuardarEmpresa" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm w-100 w-sm-auto">
                            <i class="fas fa-save me-1"></i> Guardar Cambios de Perfil
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TARJETA DE PREVIEW DE MEMBRETE / TICKET POS EN TIEMPO REAL -->
        <div class="card card-executive border rounded-4 shadow-sm p-3 p-sm-4 bg-white">
            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                        <i class="fas fa-receipt"></i>
                    </span>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Previsualización del Membrete del Ticket POS</h6>
                        <small class="text-muted">Así se imprimirán los encabezados de facturación y comprobantes</small>
                    </div>
                </div>
                <span class="badge bg-white text-primary border border-primary-subtle shadow-xs rounded-pill px-3 py-1 font-monospace align-self-start align-self-sm-auto fw-semibold" style="font-size: 0.74rem;">
                    <i class="fas fa-print me-1"></i> Formato 80mm
                </span>
            </div>

            <!-- CONTENEDOR TIPO TICKET TERMICO ELEGANTE BLANCO -->
            <div class="mx-auto border rounded-4 p-4 bg-white shadow-xs w-100" style="max-width: 400px; border-style: dashed !important; border-width: 1.5px !important; border-color: #cbd5e1 !important;">
                <div class="text-center font-monospace">
                    <!-- LOGO EN TICKET -->
                    <div id="ticketPreviewLogoContainer" class="mb-3 d-none">
                        <img id="ticketPreviewLogo" src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='1' height='1'%3E%3C/svg%3E" alt="Logo Ticket" style="max-height: 52px; max-width: 160px; object-fit: contain;">
                    </div>
                    
                    <!-- DATOS FISCALES -->
                    <h6 class="fw-bold mb-1 text-dark text-break fs-6" id="ticketPreviewNombre">NOMBRE DE LA EMPRESA</h6>
                    <div class="text-muted small mb-1 text-break" id="ticketPreviewRazonSocial">Razón Social Legal</div>
                    
                    <div class="fw-bold text-dark font-monospace text-break mb-1" style="font-size: 0.85rem;" id="ticketPreviewRif">
                        RIF: J-00000000-0
                    </div>
                    
                    <div class="text-muted text-break mb-1" style="font-size: 0.75rem; line-height: 1.35;" id="ticketPreviewDireccion">Dirección fiscal de la empresa</div>
                    <div class="text-muted text-break" style="font-size: 0.74rem;" id="ticketPreviewContacto">Telf: --- | Email: ---</div>
                    
                    <!-- LÍNEA PUNTEADA TICKET -->
                    <div class="my-2.5 border-top" style="border-style: dotted !important; border-color: #cbd5e1 !important;"></div>
                    
                    <div class="d-flex flex-wrap justify-content-between gap-1 text-muted" style="font-size: 0.72rem;">
                        <span>FECHA: {{ date('d/m/Y H:i') }}</span>
                        <span>FACTURA: #00001</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- COLUMNA 2: GESTIÓN MULTI-MONEDA, TASAS DE CAMBIO & CALCULADORA -->
    <div class="col-12 col-xl-5">
        <div class="card card-executive border rounded-4 shadow-sm p-3 p-sm-4 bg-white mb-4">
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                        <i class="fas fa-coins fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Monedas & Tasas Oficiales</h5>
                        <small class="text-muted">Parámetros de conversión para ventas y caja</small>
                    </div>
                </div>
            </div>

            <!-- MONEDA BASE FIJA PROTEGIDA -->
            <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-success rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px;">
                        <i class="fas fa-lock text-white"></i>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-bold text-dark" style="font-size: 0.92rem;">Bolívares (VES)</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill font-monospace px-2 py-0.5 fw-bold" style="font-size: 0.70rem;">Moneda Base</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.74rem;">Unidad oficial del sistema contable (Tasa fija: 1.00 Bs.)</small>
                    </div>
                </div>
                <span class="badge bg-white text-dark border shadow-xs rounded-pill px-3 py-1.5 font-monospace fw-bold align-self-end align-self-sm-auto" style="font-size: 0.88rem;">1.00 Bs.</span>
            </div>

            <!-- FORMULARIO DE TASAS DE MONEDAS SECUNDARIAS -->
            <form id="formTasasMonedas">
                @csrf
                <div class="d-flex flex-column gap-3 mb-4" id="contenedorMonedas">
                    <!-- Cargado reactivamente vía JS -->
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                        <span>Cargando monedas configuradas...</span>
                    </div>
                </div>

                <div class="text-end pt-3 border-top">
                    <button type="submit" id="btnGuardarTasas" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm text-white w-100 w-sm-auto">
                        <i class="fas fa-sync-alt me-1"></i> Guardar y Actualizar Tasas
                    </button>
                </div>
            </form>
        </div>

        <!-- CALCULADORA / CONVERSOR RÁPIDO DE PRECIOS -->
        <div class="card card-executive border rounded-4 shadow-sm p-3 p-sm-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-warning-subtle text-warning rounded-circle p-2 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                        <i class="fas fa-calculator text-dark"></i>
                    </span>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark">Calculadora de Precios en Vivo</h6>
                        <small class="text-muted">Simula conversiones con las tasas ingresadas en el formulario</small>
                    </div>
                </div>
            </div>

            <div class="row g-2 align-items-center">
                <div class="col-12 col-sm-6">
                    <label class="form-label-executive small mb-1" for="calcMonto">Monto de Prueba:</label>
                    <div class="input-group input-group-executive">
                        <input type="number" step="0.01" min="0" class="form-control form-control-executive font-monospace text-end fw-bold" id="calcMonto" value="10.00" placeholder="10.00">
                    </div>
                </div>
                <div class="col-12 col-sm-6">
                    <label class="form-label-executive small mb-1" for="calcMoneda">Moneda Origen:</label>
                    <select class="form-select bg-white font-monospace fw-semibold" id="calcMoneda" style="border-radius: 12px; height: 42px;">
                        <option value="USD" selected>USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="COP">COP (COP$)</option>
                    </select>
                </div>
            </div>

            <!-- RESULTADO REACTIVO -->
            <div class="card border rounded-4 p-3 bg-white shadow-xs mt-3 text-center">
                <small class="text-muted fw-semibold d-block mb-1">Equivalente Estimado en Moneda Oficial:</small>
                <div class="font-monospace fw-bold text-success fs-4" id="calcResultado">
                    0.00 Bs.
                </div>
                <small class="text-muted" style="font-size: 0.72rem;" id="calcDetalleTasa">
                    Calculando con tasa activa ingresada arriba
                </small>
            </div>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/configuracion.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/configuracion.js')) ?: time() }}">
    </script>
@endsection
