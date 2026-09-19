@extends('Sistema.layouts.app')

@section('titulo', '🏷️ Categorías de Productos')
@section('subtitulo', 'Catálogo y organización de categorías para segmentación de productos y servicios')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">Categorías</span>
@endsection

@section('acciones')
    <x-btn-action
        icon="fas fa-plus"
        text="Nueva Categoría"
        onclick="crear()"
    />
@endsection

@section('contenido')
    <x-datatable
        id="datatable_categorias"
        :headers="[
            'Categoría',
            'Código',
            'Descripción',
            'Fecha Registro',
            'Acciones',
        ]"
    />

    <x-modal
        id="modalCategoria"
        title="Nueva Categoría"
        subtitle="Completa la información de la categoría de productos"
        icon="fas fa-tags text-warning fs-5"
        size="modal-md"
        headerColor="bg-dark text-white"
        formId="formularioCategoria"
        submitText="Guardar"
    >
        <form id="formularioCategoria">
            @csrf
            <div class="row g-3">
                <x-input
                    name="codigo"
                    id="codigo"
                    label="Código de la Categoría"
                    icon="fas fa-barcode"
                    placeholder="Ej. CAT-01, LUB, ELECT"
                    required
                    maxlength="50"
                    col="col-12"
                />

                <x-input
                    name="nombre"
                    id="nombre"
                    label="Nombre de la Categoría"
                    icon="fas fa-tags"
                    placeholder="Ej. Lubricantes y Filtros, Ferretería..."
                    required
                    maxlength="150"
                    col="col-12"
                />

                <x-input
                    name="descripcion"
                    id="descripcion"
                    label="Descripción / Detalle"
                    icon="fas fa-align-left"
                    placeholder="Detalle o descripción de los productos que agrupa..."
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
        src="{{ asset('estilos/jsPropios/categoria.js') }}?v={{ @filemtime(public_path('estilos/jsPropios/categoria.js')) ?: time() }}">
    </script>
@endsection
