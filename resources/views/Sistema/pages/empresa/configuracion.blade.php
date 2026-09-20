@extends('Sistema.layouts.app')

@section('titulo', '⚙️ Configuración de Empresa')
@section('subtitulo', 'Perfil corporativo, identidad visual, monedas operativas y tasas de cambio oficiales')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Configuración</span>
@endsection

@section('contenido')
<div class="row g-4">
    
    <!-- COLUMNA 1: IDENTIDAD Y DATOS DE LA EMPRESA -->
    <div class="col-lg-7">
        <div class="card card-executive border rounded-4 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-circle bg-primary-subtle text-primary p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-building fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Perfil de la Empresa</h5>
                        <small class="text-muted">Información legal, fiscal y datos de contacto</small>
                    </div>
                </div>
            </div>

            <form id="formEmpresa" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    
                    <!-- LOGO DE LA EMPRESA -->
                    <div class="col-12">
                        <label class="form-label-executive"><i class="fas fa-image text-primary me-1"></i> Logo Corporativo</label>
                        <div class="d-flex align-items-center gap-3 p-3 border rounded-4 bg-light-subtle">
                            <div id="contenedorPreviewLogo" class="border rounded-4 bg-white p-2 d-flex align-items-center justify-content-center shadow-xs" style="width: 80px; height: 80px; min-width: 80px; overflow: hidden;">
                                <i class="fas fa-building text-muted fs-2" id="placeholderLogo"></i>
                                <img id="previewLogo" src="" alt="Logo" class="img-fluid rounded-3 d-none" style="max-height: 100%; object-fit: contain;">
                            </div>
                            <div class="flex-grow-1">
                                <label for="logo" class="btn btn-outline-primary btn-sm rounded-pill px-3 mb-1 cursor-pointer">
                                    <i class="fas fa-upload me-1"></i> Seleccionar Imagen
                                </label>
                                <input type="file" class="d-none" id="logo" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                                <div class="text-muted" style="font-size: 0.75rem;">Formatos: PNG, JPG, WEBP, SVG (Máx. 2MB). Recomendado fondo transparente.</div>
                            </div>
                        </div>
                    </div>

                    <!-- RIF Y NOMBRE COMERCIAL -->
                    <div class="col-md-5">
                        <x-input name="rif" id="rif" label="RIF / Identificación Fiscal" icon="fas fa-id-card"
                            placeholder="Ej. J-12345678-0" required maxlength="20" />
                    </div>

                    <div class="col-md-7">
                        <x-input name="nombre" id="nombre" label="Nombre Comercial" icon="fas fa-store"
                            placeholder="Ej. Inversiones Mi Negocio C.A." required maxlength="150" />
                    </div>

                    <!-- RAZÓN SOCIAL -->
                    <div class="col-12">
                        <x-input name="razon_social" id="razon_social" label="Razón Social Legal" icon="fas fa-file-contract"
                            placeholder="Ej. Inversiones Mi Negocio Compañía Anónima" required maxlength="150" />
                    </div>

                    <!-- TELÉFONO Y CORREO -->
                    <div class="col-md-6">
                        <x-input type="tel" name="telefono" id="telefono" label="Teléfono de Contacto" icon="fas fa-phone"
                            placeholder="Ej. +58 412 1234567" maxlength="25" optionalText="Opcional" />
                    </div>

                    <div class="col-md-6">
                        <x-input type="email" name="correo" id="correo" label="Correo Electrónico" icon="fas fa-envelope"
                            placeholder="Ej. contacto@minegocio.com" maxlength="150" optionalText="Opcional" />
                    </div>

                    <!-- DIRECCIÓN FISCAL -->
                    <div class="col-12">
                        <x-input name="direccion" id="direccion" label="Dirección Fiscal" icon="fas fa-map-marker-alt"
                            placeholder="Ej. Av. Principal, Edificio Central, Piso 1, Local 2" required maxlength="255" />
                    </div>

                    <!-- BOTÓN GUARDAR EMPRESA -->
                    <div class="col-12 text-end mt-4">
                        <button type="submit" id="btnGuardarEmpresa" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm">
                            <i class="fas fa-save me-1"></i> Guardar Cambios de Empresa
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- COLUMNA 2: GESTIÓN MULTI-MONEDA & TASAS DE CAMBIO -->
    <div class="col-lg-5">
        <div class="card card-executive border rounded-4 shadow-sm p-4 h-100">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-executive-sm rounded-circle bg-success-subtle text-success p-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fas fa-coins fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Monedas & Tasas de Cambio</h5>
                        <small class="text-muted">Cotizaciones oficiales para conversión y ventas</small>
                    </div>
                </div>
            </div>

            <!-- MONEDA BASE FIJA -->
            <div class="alert alert-light border rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success rounded-circle p-2"><i class="fas fa-lock"></i></span>
                    <div>
                        <span class="fw-bold text-dark d-block">Moneda Base: Bolívares (VES)</span>
                        <small class="text-muted">Moneda oficial del sistema (Tasa fija: 1.0000 Bs.)</small>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 font-monospace fw-bold">1.00 Bs.</span>
            </div>

            <!-- FORMULARIO DE TASAS DE MONEDAS SECUNDARIAS -->
            <form id="formTasasMonedas">
                @csrf
                <div class="d-flex flex-column gap-3 mb-4" id="contenedorMonedas">
                    <!-- Cargado dinámicamente vía JS -->
                    <div class="text-center py-4 text-muted">
                        <div class="spinner-border spinner-border-sm text-primary me-1" role="status"></div>
                        <span>Cargando monedas...</span>
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" id="btnGuardarTasas" class="btn btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm text-white">
                        <i class="fas fa-sync-alt me-1"></i> Actualizar Tasas de Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/configuracion.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/configuracion.js')) ?: time() }}">
    </script>
@endsection
