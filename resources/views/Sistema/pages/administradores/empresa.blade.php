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
                        <i class="fas fa-image"></i> Logo de la Empresa <span class="badge bg-light text-muted ms-1">Opcional</span>
                    </label>
                    <div class="d-flex align-items-center gap-3">
                        <div id="contenedorPreviewLogo" class="border rounded-3 p-1 bg-light d-flex align-items-center justify-content-center" style="width: 56px; height: 56px; min-width: 56px; overflow: hidden;">
                            <i class="fas fa-building text-muted fs-4" id="iconoPlaceholderLogo"></i>
                            <img id="previewLogo" src="" alt="Logo preview" class="img-fluid rounded-2 d-none" style="max-height: 100%; object-fit: contain;">
                        </div>
                        <div class="flex-grow-1">
                            <input type="file" class="form-control form-control-executive" id="logo" name="logo" accept="image/png, image/jpeg, image/webp, image/svg+xml">
                            <small class="text-muted" style="font-size: 0.76rem;">Formatos permitidos: PNG, JPG, WEBP o SVG (Máx. 2MB)</small>
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
