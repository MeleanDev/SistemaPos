@extends('Sistema.layouts.app')

@section('titulo', '🏢 Empresas / Sedes')
@section('subtitulo', 'Directorio y administración de razones sociales, sedes y filiales del sistema')

@section('rutas')
    <a href="{{ route('empresa') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Empresas</span>
@endsection

@section('acciones')
    <x-btn-action icon="fas fa-building" text="Nueva Empresa" onclick="crear()" />
@endsection

@section('contenido')
    <x-search-filter
        inputId="buscadorEmpresas"
        counterId="contadorEmpresas"
        placeholder="Buscar empresa por RIF, nombre comercial, razón social o correo..."
        loadingText="Cargando empresas..."
        counterIcon="fas fa-building"
    />

    <div class="row g-4" id="contenedorEmpresas">
    </div>

    <x-modal id="modalEmpresa" title="Nueva Empresa" subtitle="Completa la información de la empresa o sede"
        icon="fas fa-building text-warning fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioEmpresa"
        submitText="Guardar">
        <form id="formularioEmpresa">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" label="Nombre Comercial" icon="fas fa-store" placeholder="Ej. Mi Empresa Central"
                    required maxlength="150" col="col-md-6" />

                <x-input name="razon_social" label="Razón Social / Legal" icon="fas fa-landmark"
                    placeholder="Ej. Distribuidora Central C.A." required maxlength="150" col="col-md-6" />

                <x-input-documento selectName="tipo_cedula" inputName="cedula_numero" label="RIF / Identificación Fiscal"
                    defaultType="J-" required col="col-md-6" />

                <x-input-telefono selectName="codigo_pais" inputName="telefono_numero" col="col-md-6" />

                <x-input name="correo" id="correo" type="email" label="Correo Electrónico" icon="fas fa-envelope"
                    placeholder="empresa@ejemplo.com" maxlength="150" col="col-12" optionalText="Opcional" />

                <x-input name="direccion" id="direccion" label="Dirección Fiscal" icon="fas fa-map-marker-alt"
                    placeholder="Av. Principal, Edificio Centro, Local 1-A" required maxlength="255" col="col-12" />

                <div class="col-12">
                    <label class="form-label-executive">
                        <i class="fas fa-image"></i> Logo de la Empresa <span class="badge bg-light-subtle text-secondary border ms-1">Opcional</span>
                    </label>
                    <div class="d-flex align-items-center gap-3 p-2.5 border rounded-4 bg-white shadow-xs">
                        <div id="contenedorPreviewLogo" class="border rounded-3 p-1 bg-white shadow-xs d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; min-width: 56px; overflow: hidden; background-color: #f8fafc;">
                            <i class="fas fa-building text-muted fs-4" id="iconoPlaceholderLogo"></i>
                            <img id="previewLogo" src="" alt="Logo preview" class="img-fluid rounded-2 d-none" style="max-height: 100%; object-fit: contain;">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-executive" id="logo" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                            <small class="text-muted" style="font-size: 0.76rem;">Formatos permitidos: PNG, JPG, WEBP o SVG (Máx. 2MB)</small>
                        </div>
                    </div>
                </div>

                <!-- Switch: Módulo de Motos y Seriales Únicos -->
                <div class="col-12">
                    <div class="p-3 rounded-4 border bg-white shadow-xs d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary border border-primary-subtle" style="width: 42px; height: 42px; min-width: 42px; font-size: 1.15rem;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;">¿Maneja Motos / Seriales Únicos?</h6>
                                <p class="mb-0 text-muted small" style="font-size: 0.78rem;">Habilita el módulo de recepción y control por N.I.V., Chasis, Motor y Certificado de Origen.</p>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-4 mb-0">
                            <input class="form-check-input" type="checkbox" role="switch" id="maneja_motos" name="maneja_motos" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/empresa.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/empresa.js')) ?: time() }}">
    </script>
@endsection
