@php
    $usuarioActual = Auth::user();
    $empresaActiva = $usuarioActual ? $usuarioActual->empresaActiva() : null;
    $empresasPermitidas = $usuarioActual ? $usuarioActual->obtenerEmpresasPermitidas() : collect();
@endphp

<header class="topbar" data-navbarbg="skin6">
    <nav class="navbar top-navbar navbar-expand-lg">
        <div class="navbar-header" data-logobg="skin6">
            <a class="nav-toggler waves-effect waves-light d-block d-lg-none" href="javascript:void(0)"><i
                    class="ti-menu ti-close"></i></a>
            <div class="navbar-brand">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center text-decoration-none">
                    <span class="badge bg-primary rounded-3 p-2 me-2 fs-5">
                        <i class="fas fa-cash-register text-white"></i>
                    </span>
                    <span class="fw-bold text-dark fs-4 tracking-tight">SISTEMA <span class="text-primary">POS</span></span>
                </a>
            </div>
            <a class="topbartoggler d-block d-lg-none waves-effect waves-light" href="javascript:void(0)"
                data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i
                    class="ti-more"></i></a>
        </div>
        <div class="navbar-collapse collapse" id="navbarSupportedContent">
            <!-- SELECTOR DE EMPRESA ACTIVA EN NAVBAR -->
            <ul class="navbar-nav float-left me-auto ms-3 ps-1">
                @if ($usuarioActual)
                    @if ($empresasPermitidas->count() > 1 || $usuarioActual->hasRole('SuperAdmin'))
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle btn btn-white bg-white border rounded-pill shadow-sm px-3 py-2 d-inline-flex align-items-center gap-2"
                                href="javascript:void(0)" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                style="font-size: 0.88rem; transition: all 0.2s ease;">
                                <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"
                                    style="width: 26px; height: 26px; font-size: 0.8rem;">
                                    <i class="fas fa-building"></i>
                                </div>
                                <div class="d-flex flex-column text-start">
                                    <span class="text-muted" style="font-size: 0.68rem; line-height: 1; text-transform: uppercase; letter-spacing: 0.05em;">Empresa Activa</span>
                                    <span class="fw-bold text-dark text-truncate" style="max-width: 200px; line-height: 1.2;">
                                        {{ $empresaActiva ? $empresaActiva->nombre : 'Seleccionar Empresa' }}
                                    </span>
                                </div>
                                <i class="fas fa-chevron-down text-muted ms-1 small"></i>
                            </a>

                            <div class="dropdown-menu dropdown-menu-left animated flipInY border-0 shadow-lg rounded-4 p-2" style="min-width: 260px;">
                                <div class="px-3 py-2 border-bottom mb-1">
                                    <span class="fw-bold text-dark small d-block">Cambiar de Empresa / Sede</span>
                                    <span class="text-muted" style="font-size: 0.72rem;">Selecciona la empresa con la que deseas operar</span>
                                </div>
                                <div style="max-height: 280px; overflow-y: auto;">
                                    @foreach ($empresasPermitidas as $empresa)
                                        <a class="dropdown-item rounded-pill px-3 py-2 d-flex align-items-center justify-content-between my-1 @if($empresaActiva && $empresaActiva->id === $empresa->id) active bg-primary text-white @endif"
                                            href="javascript:void(0)" onclick="cambiarEmpresaActiva({{ $empresa->id }})">
                                            <div class="d-flex align-items-center gap-2 text-truncate">
                                                <i class="fas fa-building @if($empresaActiva && $empresaActiva->id === $empresa->id) text-white @else text-primary @endif" style="font-size: 0.85rem;"></i>
                                                <div class="d-flex flex-column text-truncate">
                                                    <span class="fw-bold text-truncate" style="font-size: 0.85rem;">{{ $empresa->nombre }}</span>
                                                    <span class="@if($empresaActiva && $empresaActiva->id === $empresa->id) text-white-50 @else text-muted @endif" style="font-size: 0.72rem;">{{ $empresa->rif }}</span>
                                                </div>
                                            </div>
                                            @if($empresaActiva && $empresaActiva->id === $empresa->id)
                                                <i class="fas fa-check-circle ms-2"></i>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </li>
                    @elseif ($empresaActiva)
                        <li class="nav-item d-none d-md-block">
                            <span class="badge bg-white text-dark border px-3 py-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-2">
                                <i class="fas fa-building text-primary"></i>
                                <span class="fw-bold">{{ $empresaActiva->nombre }}</span>
                                <span class="text-muted small">({{ $empresaActiva->rif }})</span>
                            </span>
                        </li>
                    @endif
                @endif
            </ul>

            <ul class="navbar-nav float-end align-items-center">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="javascript:void(0)"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <span class="ms-2 d-none d-lg-inline-block">
                            <span class="text-muted small">Hola,</span>
                            <span class="text-dark fw-bold">
                                @if ($usuarioActual)
                                    {{ $usuarioActual->nombre_completo }}
                                @else
                                    Invitado
                                @endif
                            </span>
                            <i data-feather="chevron-down" class="svg-icon ms-1"></i>
                        </span>
                    </a>

                    <!-- Menú con bordes redondeados y sombra -->
                    <div
                        class="dropdown-menu dropdown-menu-end dropdown-menu-right user-dd animated flipInY border-0 shadow-lg rounded-4 p-2">

                        <div class="px-3 py-2 border-bottom mb-2">
                            <p class="mb-0 fw-bold text-dark">{{ $usuarioActual ? $usuarioActual->nombre_completo : 'Usuario' }}</p>
                            <small class="text-muted d-block">{{ $usuarioActual ? $usuarioActual->name : '' }}</small>
                            <span class="badge bg-primary text-white rounded-pill px-2 py-1 mt-1 small" style="font-size: 0.72rem;">
                                {{ $usuarioActual && $usuarioActual->roles->isNotEmpty() ? $usuarioActual->roles->first()->name : 'Sin Rol' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <a class="dropdown-item rounded-pill px-3 mt-1 text-danger" href="{{ route('logout') }}"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                <i data-feather="power" class="svg-icon me-2 ms-1"></i>
                                Cerrar Sesión
                            </a>
                        </form>
                    </div>
                </li>
            </ul>
        </div>
    </nav>
</header>

<script>
    function cambiarEmpresaActiva(empresaId) {
        $.ajax({
            url: '{{ route("cambiar_empresa") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                empresa_id: empresaId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: 'success',
                            title: response.message
                        });
                    }
                    setTimeout(() => {
                        window.location.reload();
                    }, 400);
                }
            },
            error: function(xhr) {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'No se pudo cambiar de empresa.'
                    });
                }
            }
        });
    }
</script>
