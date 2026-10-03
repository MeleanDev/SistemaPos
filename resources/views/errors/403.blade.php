@extends('Sistema.layouts.app')

@section('titulo', '🚫 Acceso Denegado')
@section('subtitulo', 'No dispones de los permisos necesarios para acceder a esta sección')

@section('rutas')
    <a href="{{ route('dashboard') }}">Sistema</a>
    <span class="breadcrumb-separator"><i class="fas fa-chevron-right"></i></span>
    <span class="active">403 Acceso Denegado</span>
@endsection

@section('contenido')
    <div class="row justify-content-center py-5">
        <div class="col-12 col-md-8 col-lg-6 text-center">
            <div class="card border-0 shadow-sm rounded-4 p-5 bg-white">
                <div class="avatar-executive-lg mx-auto mb-4 bg-danger-subtle text-danger d-flex align-items-center justify-content-center rounded-circle" style="width: 90px; height: 90px; font-size: 2.5rem;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">Acceso Restringido</h3>
                <p class="text-muted mb-4">
                    Tu usuario no cuenta con los permisos necesarios para visualizar o gestionar este módulo. Si crees que se trata de un error, solicita la autorización correspondiente al administrador del sistema.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-gradient-primary rounded-pill px-4 py-2 fw-semibold">
                        <i class="fas fa-home me-1"></i> Ir al Panel Principal
                    </a>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold" onclick="window.history.back()">
                        <i class="fas fa-arrow-left me-1"></i> Volver Atrás
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
