@extends('Sistema.layouts.app')

@section('titulo', '👥 Clientes')
@section('subtitulo', 'Directorio y administración de clientes para compras al detal y mayoristas')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Clientes</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-user-plus"
        text="Nuevo Cliente"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <x-datatable
        id="datatable_clientes"
        :headers="[
            'Cliente / Documento',
            'Teléfono',
            'Correo',
            'Tipo Cliente',
            'Acciones',
        ]"
    />

    <x-modal id="modalCliente" title="Nuevo Cliente" subtitle="Completa la información del cliente"
        icon="fas fa-user text-warning fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioCliente"
        submitText="Guardar">

        <form id="formularioCliente">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" label="Nombre / Razón Comercial" icon="fas fa-user" placeholder="Ej. Juan" required
                    maxlength="100" col="col-md-6" />

                <x-input name="apellido" label="Apellido" icon="fas fa-user" placeholder="Ej. Pérez" required
                    maxlength="100" col="col-md-6" />

                <x-input-documento
                    selectName="tipo_cedula"
                    inputName="cedula_numero"
                    required
                    col="col-md-6"
                />

                <x-input-telefono
                    selectName="codigo_pais"
                    inputName="telefono_numero"
                    col="col-md-6"
                />

                <x-select name="tipo_cliente" id="tipo_cliente" label="Tipo de Cliente" icon="fas fa-tag" required
                    col="col-md-6">
                    <option value="detal">Detal / Particular</option>
                    <option value="mayorista">Mayorista / Empresa</option>
                </x-select>

                <x-input name="correo" id="correo" type="email" label="Correo Electrónico" icon="fas fa-envelope"
                    placeholder="cliente@ejemplo.com" maxlength="150" col="col-md-6" optionalText="Opcional" />

                <x-input name="direccion" id="direccion" label="Dirección de Habitación / Fiscal"
                    icon="fas fa-map-marker-alt" placeholder="Calle 123, Sector, Casa/Apto 456" maxlength="255"
                    col="col-12" optionalText="Opcional" />
            </div>
        </form>

    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/cliente.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/cliente.js')) ?: time() }}">
    </script>
@endsection
