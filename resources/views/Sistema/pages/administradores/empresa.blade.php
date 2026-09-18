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
    <div class="card card-executive mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row align-items-center g-3">
                <div class="col-md-7 col-lg-8">
                    <div class="input-group input-group-executive">
                        <span class="input-group-text bg-transparent border-end-0 text-muted">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="buscadorEmpresas"
                            class="form-control form-control-executive border-start-0 ps-0"
                            placeholder="Buscar empresa por RIF, nombre comercial, razón social o correo..."
                            autocomplete="off">
                    </div>
                </div>
                <div class="col-md-5 col-lg-4 text-md-end text-start">
                    <span id="contadorEmpresas"
                        class="badge bg-light-primary text-primary px-3 py-2 rounded-pill fw-bold fs-6">
                        <i class="fas fa-building me-1"></i> Cargando empresas...
                    </span>
                </div>
            </div>
        </div>
    </div>

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
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/empresa.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/empresa.js')) ?: time() }}">
    </script>
@endsection
