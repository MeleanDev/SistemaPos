@extends('Sistema.layouts.app')

@section('titulo', '🚚 Proveedores')
@section('subtitulo', 'Directorio y administración de proveedores para compras e inventario')

@section('rutas')
    <a href="{{ route('proveedor') }}">Compras</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Proveedores</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-truck-loading"
        text="Nuevo Proveedor"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <x-datatable
        id="datatable_proveedores"
        :headers="[
            'Proveedor / RIF',
            'Razón Social',
            'Contacto',
            'Teléfono',
            'Correo',
            'Acciones',
        ]"
    />

    <x-modal
        id="modalProveedor"
        title="Nuevo Proveedor"
        subtitle="Completa la información del proveedor"
        icon="fas fa-truck text-warning fs-5"
        size="modal-lg"
        headerColor="bg-dark text-white"
        formId="formularioProveedor"
        submitText="Guardar"
    >
        <form id="formularioProveedor">
            @csrf
            <div class="row g-3">
                <x-input
                    name="nombre"
                    label="Nombre Comercial"
                    icon="fas fa-store"
                    placeholder="Ej. Repuestos Los Andes"
                    required
                    maxlength="150"
                    col="col-md-6"
                />

                <x-input
                    name="razon_social"
                    label="Razón Social / Legal"
                    icon="fas fa-building"
                    placeholder="Ej. Distribuidora Los Andes C.A."
                    required
                    maxlength="150"
                    col="col-md-6"
                />

                <x-input-documento
                    selectName="tipo_cedula"
                    inputName="cedula_numero"
                    label="RIF / Identificación Fiscal"
                    defaultType="J-"
                    required
                    col="col-md-6"
                />

                <x-input
                    name="nombre_contacto"
                    label="Persona de Contacto"
                    icon="fas fa-user-tie"
                    placeholder="Ej. Carlos Gómez"
                    maxlength="100"
                    col="col-md-6"
                    optionalText="Opcional"
                />

                <x-input-telefono
                    selectName="codigo_pais"
                    inputName="telefono_numero"
                    col="col-md-6"
                />

                <x-input
                    name="correo"
                    id="correo"
                    type="email"
                    label="Correo Electrónico"
                    icon="fas fa-envelope"
                    placeholder="proveedor@ejemplo.com"
                    maxlength="150"
                    col="col-md-6"
                    optionalText="Opcional"
                />

                <x-input
                    name="direccion"
                    id="direccion"
                    label="Dirección Fiscal / Depósito"
                    icon="fas fa-map-marker-alt"
                    placeholder="Av. Principal, Edif. Centro, Local 1"
                    maxlength="255"
                    col="col-12"
                    optionalText="Opcional"
                />
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/proveedor.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/proveedor.js')) ?: time() }}">
    </script>
@endsection
