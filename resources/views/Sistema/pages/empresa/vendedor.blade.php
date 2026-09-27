@extends('Sistema.layouts.app')

@section('titulo', '💼 Vendedores')
@section('subtitulo', 'Directorio de asesores de ventas, porcentajes de comisión y asignaciones')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Vendedores</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-user-plus"
        text="Nuevo Vendedor"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <x-datatable
        id="datatable_vendedores"
        :headers="[
            'Vendedor / Documento',
            'Teléfono',
            'Correo',
            '% Comisión',
            'Estado',
            'Acciones',
        ]"
    />

    <x-modal id="modalVendedor" title="Nuevo Vendedor" subtitle="Completa la información del asesor de ventas"
        icon="fas fa-user-tie text-primary fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioVendedor"
        submitText="Guardar">

        <form id="formularioVendedor">
            @csrf
            <div class="row g-3">
                <x-input name="nombre" id="nombre" label="Nombre Completo" icon="fas fa-user" placeholder="Ej. Carlos Mendoza" required
                    maxlength="150" col="col-md-7" />

                <x-input-documento
                    label="Documento de Identidad"
                    selectName="tipo_documento"
                    inputName="documento"
                    required
                    col="col-md-5"
                />

                <x-input name="telefono" id="telefono" label="Teléfono de Contacto" icon="fas fa-phone"
                    placeholder="Ej. 0414-1234567" maxlength="30" col="col-md-6" optionalText="Opcional" />

                <x-input name="correo" id="correo" type="email" label="Correo Electrónico" icon="fas fa-envelope"
                    placeholder="vendedor@empresa.com" maxlength="150" col="col-md-6" optionalText="Opcional" />

                <x-input name="comision_porcentaje" id="comision_porcentaje" type="number" step="0.01" min="0" max="100"
                    label="% Comisión por Venta" icon="fas fa-percentage" addonText="%" addonPosition="right" placeholder="Ej. 3.50"
                    value="0.00" required col="col-md-6" helpText="Porcentaje aplicado sobre las ventas netas asignadas a este asesor." />

                <x-select name="user_id" id="user_id" label="Usuario de Sistema (Para Preventas en POS)" icon="fas fa-user-lock" col="col-md-6" optionalText="Opcional" helpText="Si el vendedor inicia sesión para crear órdenes en espera, asígnale su usuario.">
                    <option value="">-- Sin usuario asignado / Vendedor externo --</option>
                    @if(isset($usuarios))
                        @foreach($usuarios as $usr)
                            <option value="{{ $usr->id }}">{{ $usr->name }} ({{ $usr->nombre_completo ?: $usr->email }})</option>
                        @endforeach
                    @endif
                </x-select>
            </div>
        </form>

    </x-modal>
@endsection

@section('scripts')
    @include('Sistema.components.datatable')
    <script
        src="{{ asset('estilos/jsPropios/vendedor.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/vendedor.js')) ?: time() }}">
    </script>
@endsection
