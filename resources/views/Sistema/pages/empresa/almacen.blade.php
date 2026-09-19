@extends('Sistema.layouts.app')

@section('titulo', '🏬 Almacenes / Bodegas')
@section('subtitulo', 'Gestión y control de depósitos, bodegas y sucursales físicas de almacenamiento')

@section('rutas')
    <a href="{{ route('dashboard') }}">Almacenes / Bodegas</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Almacenes</span>
@endsection

@section('acciones')
    <x-btn-action icon="fas fa-warehouse" text="Nuevo Almacén" onclick="crear()" />
@endsection

@section('contenido')
    <x-search-filter
        inputId="buscadorAlmacenes"
        counterId="contadorAlmacenes"
        placeholder="Buscar almacén por código, nombre o dirección física..."
        loadingText="Cargando almacenes..."
        counterIcon="fas fa-warehouse"
    />

    <div class="row g-4" id="contenedorAlmacenes">
    </div>

    <x-modal id="modalAlmacen" title="Nuevo Almacén" subtitle="Completa la información del almacén o bodega"
        icon="fas fa-warehouse text-primary fs-5" size="modal-lg" headerColor="bg-dark text-white" formId="formularioAlmacen"
        submitText="Guardar">
        <form id="formularioAlmacen">
            @csrf
            <div class="row g-3">
                <x-input name="codigo" label="Código del Almacén" icon="fas fa-barcode" placeholder="Ej. ALM-01, BOD-01"
                    required maxlength="50" col="col-md-5" />

                <x-input name="nombre" label="Nombre del Almacén" icon="fas fa-warehouse"
                    placeholder="Ej. Almacén Central" required maxlength="150" col="col-md-7" />

                <x-input name="direccion" id="direccion" label="Dirección / Ubicación Física" icon="fas fa-map-marker-alt"
                    placeholder="Ej. Zona Industrial II, Galpón 4-A" maxlength="255" col="col-12" optionalText="Opcional" />
            </div>
        </form>
    </x-modal>
@endsection

@section('scripts')
    <script
        src="{{ asset('estilos/jsPropios/almacen.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/almacen.js')) ?: time() }}">
    </script>
@endsection
