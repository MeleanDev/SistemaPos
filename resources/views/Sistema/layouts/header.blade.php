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
            <ul class="navbar-nav float-left me-auto ms-3 ps-1">
                <li class="nav-item d-none d-md-block">
                    <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                        <i class="fas fa-store me-1 text-primary"></i> Punto de Venta Activo
                    </span>
                </li>
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
                                @if (Auth::check())
                                    {{ Auth::user()->name }}
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
                            <p class="mb-0 fw-bold text-dark">{{ Auth::check() ? Auth::user()->name : 'Usuario' }}</p>
                            <small class="text-muted">{{ Auth::check() ? Auth::user()->email : '' }}</small>
                        </div>

                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <!-- Ítem con hover estilo píldora y texto en rojo -->
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
