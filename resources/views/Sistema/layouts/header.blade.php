@php
    $usuarioActual = Auth::user();
    $empresaActiva = $usuarioActual ? $usuarioActual->empresaActiva() : null;
    $empresasPermitidas = $usuarioActual ? $usuarioActual->obtenerEmpresasPermitidas() : collect();
@endphp

<header class="topbar" data-navbarbg="skin6">
    <nav class="navbar top-navbar navbar-expand p-0">
        <!-- SECCIÓN IZQUIERDA: MENÚ HAMBURGER & LOGO -->
        <div class="navbar-header d-flex align-items-center" data-logobg="skin6">
            <!-- Botón Hamburger Móvil (< 768px) -->
            <button type="button" class="btn btn-white bg-white border rounded-circle shadow-xs d-flex d-md-none align-items-center justify-content-center me-2 flex-shrink-0"
                onclick="toggleSidebarMenu()" title="Abrir Menú Lateral" style="width: 36px; height: 36px; min-width: 36px; color: #334155; padding: 0;">
                <i class="fas fa-bars" style="font-size: 0.95rem;"></i>
            </button>
            <div class="navbar-brand m-0 p-0">
                <a href="{{ route('dashboard') }}" class="logo-link d-inline-flex align-items-center text-decoration-none">
                    <img src="{{ asset('estilos/imgPropio/datavault-logo.jpg') }}" alt="DataVault System" class="logo-img rounded-circle shadow-xs flex-shrink-0" style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; object-fit: cover; border: 1.5px solid #0284c7;">
                    <span class="fw-bold text-dark fs-5 tracking-tight logo-text text-nowrap ms-2 d-none d-md-inline-block" style="letter-spacing: -0.02em;">DATAVAULT <span class="text-primary">SYSTEM</span></span>
                </a>
            </div>
        </div>

        <!-- SECCIÓN DERECHA: ACCIONES, SELECTOR EMPRESA, CONSULTOR Y PERFIL -->
        <div class="navbar-collapse d-flex align-items-center justify-content-between flex-grow-1" id="navbarSupportedContent">
            <!-- BOTÓN SIDEBAR ESCRITORIO + SELECTOR DE EMPRESA -->
            <div class="d-flex align-items-center ms-2 ms-md-3">
                <!-- Toggle Menú Escritorio (>= 768px) -->
                <button type="button" class="btn btn-white bg-white border rounded-circle shadow-xs d-none d-md-flex align-items-center justify-content-center me-3 flex-shrink-0" id="btnToggleSidebarGlobal" onclick="toggleSidebarMenu()" title="Expandir / Contraer Menú Lateral (Ctrl + B)" style="width: 38px; height: 38px; color: #334155; transition: all 0.2s ease;">
                    <i class="fas fa-bars" id="iconToggleSidebarGlobal" style="font-size: 1rem;"></i>
                </button>

                @if ($usuarioActual)
                    @if ($empresasPermitidas->count() > 1 || $usuarioActual->hasRole('SuperAdmin'))
                        <!-- Dropdown Selector de Empresa -->
                        <div class="dropdown">
                            <button class="btn btn-white bg-white border rounded-pill shadow-xs d-inline-flex align-items-center gap-2"
                                type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                style="height: 38px; padding: 4px 12px 4px 6px; border-color: #e2e8f0 !important; transition: all 0.2s ease;"
                                title="Cambiar Empresa / Sede">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                    style="width: 28px; height: 28px; background: #eff6ff; color: #2563eb; font-size: 0.82rem; border: 1px solid #dbeafe;">
                                    <i class="fas fa-building"></i>
                                </div>
                                <span class="fw-semibold text-dark text-truncate" style="font-size: 0.84rem; max-width: 180px;">
                                    {{ $empresaActiva ? $empresaActiva->nombre : 'Seleccionar Empresa' }}
                                </span>
                                <i class="fas fa-chevron-down text-muted" style="font-size: 0.65rem; margin-left: 2px;"></i>
                            </button>

                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-md-start animated flipInY border shadow-lg rounded-4 p-2" style="min-width: 270px; max-width: 90vw; border-color: #e2e8f0 !important; z-index: 1090;">
                                <div class="px-3 py-2 border-bottom mb-1">
                                    <span class="fw-bold text-dark small d-block">Cambiar Empresa / Sede</span>
                                    <span class="text-muted" style="font-size: 0.72rem;">Selecciona la empresa con la que deseas operar</span>
                                </div>
                                <div style="max-height: 280px; overflow-y: auto;">
                                    @foreach ($empresasPermitidas as $empresa)
                                        <a class="dropdown-item rounded-3 px-3 py-2 d-flex align-items-center justify-content-between my-1 @if($empresaActiva && $empresaActiva->id === $empresa->id) active bg-primary text-white @endif"
                                            href="javascript:void(0)" onclick="cambiarEmpresaActiva({{ $empresa->id }})" style="transition: all 0.15s ease;">
                                            <div class="d-flex align-items-center gap-2.5 text-truncate">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 @if($empresaActiva && $empresaActiva->id === $empresa->id) bg-white text-primary @else bg-primary-subtle text-primary @endif" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                    <i class="fas fa-building"></i>
                                                </div>
                                                <div class="d-flex flex-column text-truncate">
                                                    <span class="fw-bold text-truncate" style="font-size: 0.85rem;">{{ $empresa->nombre }}</span>
                                                    <span class="@if($empresaActiva && $empresaActiva->id === $empresa->id) text-white-50 @else text-muted @endif" style="font-size: 0.72rem;">{{ $empresa->rif }}</span>
                                                </div>
                                            </div>
                                            @if($empresaActiva && $empresaActiva->id === $empresa->id)
                                                <i class="fas fa-check-circle ms-2 text-white"></i>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @elseif ($empresaActiva)
                        <!-- Badge Informativo de Empresa Única -->
                        <div class="bg-white border rounded-pill shadow-xs d-inline-flex align-items-center gap-2"
                            style="height: 38px; padding: 4px 14px 4px 6px; border-color: #e2e8f0 !important;"
                            title="Empresa Activa">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width: 28px; height: 28px; background: #eff6ff; color: #2563eb; font-size: 0.82rem; border: 1px solid #dbeafe;">
                                <i class="fas fa-building"></i>
                            </div>
                            <span class="fw-semibold text-dark text-truncate" style="font-size: 0.84rem; max-width: 180px;">
                                {{ $empresaActiva->nombre }}
                            </span>
                        </div>
                    @endif
                @endif
            </div>

            <!-- BOTONES DERECHA: CONSULTOR & USUARIO -->
            <div class="d-flex align-items-center gap-1.5 gap-md-2 ms-auto pe-1">
                <!-- Consultor de Precios -->
                <a href="{{ route('consultor_precios') }}"
                    class="btn btn-white bg-white border rounded-pill shadow-xs d-inline-flex align-items-center gap-2 px-2.5 py-1.5 text-dark fw-semibold"
                    style="border-color: #e2e8f0 !important; font-size: 0.84rem; transition: all 0.2s ease;"
                    title="Consultor de Precios">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                        style="width: 28px; height: 28px; background: #f0fdf4; color: #16a34a; font-size: 0.78rem; border: 1px solid #bbf7d0;">
                        <i class="fas fa-barcode"></i>
                    </div>
                    <span class="d-none d-md-inline" style="font-size: 0.82rem; color: #1e293b;">Consultor</span>
                </a>

                <!-- Perfil de Usuario Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-link p-0 border-0 text-decoration-none d-flex align-items-center gap-2"
                        type="button" id="userProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="rounded-circle d-flex align-items-center justify-content-center shadow-xs flex-shrink-0"
                            style="width: 38px; height: 38px; min-width: 38px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #ffffff; font-weight: 700; font-size: 0.85rem; letter-spacing: 0.02em;">
                            {{ strtoupper(substr($usuarioActual->name ?? 'U', 0, 2)) }}
                        </div>
                        <div class="d-none d-xl-flex flex-column text-start">
                            <span style="font-size: 0.62rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; line-height: 1;">Usuario</span>
                            <span class="fw-bold text-dark text-truncate" style="max-width: 120px; font-size: 0.84rem; line-height: 1.2;">
                                {{ $usuarioActual ? $usuarioActual->nombre : 'Invitado' }}
                            </span>
                        </div>
                        <i class="fas fa-chevron-down text-muted small d-none d-xl-inline ms-0.5" style="font-size: 0.65rem;"></i>
                    </button>

                    <!-- Menú Desplegable de Usuario -->
                    <div class="dropdown-menu dropdown-menu-end animated flipInY shadow-lg rounded-4 p-2" aria-labelledby="userProfileDropdown"
                        style="min-width: 250px; max-width: 90vw; border: 1px solid #e2e8f0; z-index: 1090;">
                        <!-- Cabecera de Usuario -->
                        <div class="p-2.5 mb-1">
                            <div class="d-flex align-items-center gap-2.5 mb-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-xs"
                                    style="width: 38px; height: 38px; background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); color: #ffffff; font-weight: 700; font-size: 0.85rem;">
                                    {{ strtoupper(substr($usuarioActual->name ?? 'U', 0, 2)) }}
                                </div>
                                <div class="d-flex flex-column text-truncate">
                                    <span class="fw-bold text-dark text-truncate" style="font-size: 0.88rem; line-height: 1.2;">
                                        {{ $usuarioActual ? $usuarioActual->nombre_completo : 'Usuario' }}
                                    </span>
                                    <span class="text-muted text-truncate" style="font-size: 0.75rem; line-height: 1.2;">
                                        {{ $usuarioActual ? $usuarioActual->name : '' }}
                                    </span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                <span class="badge rounded-pill px-2 py-0.5" style="font-size: 0.68rem; font-weight: 600; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe;">
                                    <i class="fas fa-shield-halved me-1"></i>{{ $usuarioActual && $usuarioActual->roles->isNotEmpty() ? $usuarioActual->roles->first()->name : 'Sin Rol' }}
                                </span>
                                @if($empresaActiva)
                                    <span class="badge rounded-pill px-2 py-0.5 text-truncate" style="font-size: 0.68rem; font-weight: 600; background: #f8fafc; color: #475569; border: 1px solid #e2e8f0; max-width: 120px;">
                                        <i class="fas fa-building me-1"></i>{{ $empresaActiva->nombre }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="dropdown-divider my-1" style="border-color: #f1f5f9;"></div>

                        <!-- Botón Cerrar Sesión -->
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item rounded-3 px-3 py-2 text-danger d-flex align-items-center gap-2 fw-semibold w-100"
                                style="font-size: 0.84rem; transition: all 0.15s ease;">
                                <i class="fas fa-power-off"></i>
                                <span>Cerrar Sesión</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
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
