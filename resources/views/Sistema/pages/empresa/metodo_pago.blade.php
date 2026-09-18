@extends('Sistema.layouts.app')

@section('titulo', '💳 Métodos de Pago')
@section('subtitulo', 'Catálogo de métodos y formas de pago permitidas en el sistema')

@section('rutas')
    <a href="{{ route('metodo_pago') }}">Ventas</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Métodos de Pago</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nuevo Método"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <x-datatable
        id="datatable_metodos_pago"
        :headers="[
            'Método de Pago',
            'Descripción / Detalle',
            'Fecha Creación',
            'Acciones',
        ]"
    />

    <x-modal
        id="modalMetodoPago"
        title="Nuevo Método de Pago"
        subtitle="Completa la información del método de pago"
        icon="fas fa-credit-card text-warning fs-5"
        size="modal-md"
        headerColor="bg-dark text-white"
        formId="formularioMetodoPago"
        submitText="Guardar"
    >
        <form id="formularioMetodoPago">
            @csrf
            <div class="row g-3">
                <x-input
                    name="nombre"
                    label="Nombre del Método"
                    icon="fas fa-credit-card"
                    placeholder="Ej. Pago Móvil, Efectivo USD, Zelle..."
                    required
                    maxlength="100"
                    col="col-12"
                />

                <x-input
                    name="descripcion"
                    id="descripcion"
                    label="Descripción / Detalle"
                    icon="fas fa-align-left"
                    placeholder="Ej. Pago en moneda nacional mediante red interbancaria"
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
        src="{{ asset('estilos/jsPropios/metodo_pago.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/metodo_pago.js')) ?: time() }}">
    </script>
@endsection
