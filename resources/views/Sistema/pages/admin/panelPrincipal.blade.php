<style>
    .kpi-admin-card {
        border: 1px solid #e2e8f0;
        border-radius: 1.25rem;
        background: #ffffff;
        transition: all 0.25s ease;
    }

    .kpi-admin-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
        border-color: #cbd5e1;
    }

    .superadmin-banner {
        background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
        border-radius: 1.25rem;
        color: white;
        padding: 2.25rem 2rem;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        position: relative;
        overflow: hidden;
    }

    .superadmin-banner::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.2) 0%, rgba(99, 102, 241, 0) 70%);
        border-radius: 50%;
        pointer-events: none;
    }
</style>

<!-- Banner SuperAdmin -->
<div class="row mb-4">
    <div class="col-12">
        <div class="superadmin-banner d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-25 p-3 rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 60px; height: 60px;">
                    <i class="fas fa-chess-king fs-2 text-warning"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-1 h3 text-white">Centro de Comando Global SaaS</h2>
                    <p class="mb-0 text-white-50 small">Supervisión integral de plataformas, sedes y cuentas de laboratorio</p>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('empresa') }}" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="fas fa-plus me-2"></i> Nuevo Laboratorio
                </a>
                <a href="{{ route('administradores') }}" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold">
                    <i class="fas fa-user-plus me-2"></i> Nuevo Administrador
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Tarjetas KPI Globales -->
<div class="row g-3 mb-4">
    <!-- Total Empresas -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card kpi-admin-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Sedes</span>
                    <h3 class="fw-extrabold text-primary mb-0 font-monospace">{{ number_format($kpi['total_empresas'] ?? 0, 0, ',', '.') }}</h3>
                    <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill small px-2 mt-1">
                        <i class="fas fa-hospital-alt me-1"></i>SaaS
                    </span>
                </div>
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-building fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Empresas Activas -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card kpi-admin-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Sedes Activas</span>
                    <h3 class="fw-extrabold text-success mb-0 font-monospace">{{ number_format($kpi['empresas_activas'] ?? 0, 0, ',', '.') }}</h3>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill small px-2 mt-1">
                        <i class="fas fa-check-circle me-1"></i>Habilitadas
                    </span>
                </div>
                <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-check-circle fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Básico -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card kpi-admin-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Plan Básico</span>
                    <h3 class="fw-extrabold text-info mb-0 font-monospace">{{ number_format($kpi['empresas_basico'] ?? 0, 0, ',', '.') }}</h3>
                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill small px-2 mt-1">
                        <i class="fas fa-cube me-1"></i>Estándar
                    </span>
                </div>
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-cube fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Plan Premium -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card kpi-admin-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Plan Premium</span>
                    <h3 class="fw-extrabold text-warning mb-0 font-monospace">{{ number_format($kpi['empresas_premium'] ?? 0, 0, ',', '.') }}</h3>
                    <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill small px-2 mt-1">
                        <i class="fas fa-star me-1"></i>Ilimitado
                    </span>
                </div>
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-star fs-4"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Usuarios -->
    <div class="col-12 col-sm-6 col-xl">
        <div class="card kpi-admin-card h-100 shadow-sm p-3">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-uppercase text-muted fw-bold d-block mb-1" style="font-size: 0.7rem; letter-spacing: 0.5px;">Total Usuarios</span>
                    <h3 class="fw-extrabold text-dark mb-0 font-monospace">{{ number_format($kpi['total_usuarios'] ?? 0, 0, ',', '.') }}</h3>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill small px-2 mt-1">
                        <i class="fas fa-users me-1"></i>Cuentas
                    </span>
                </div>
                <div class="p-3 bg-secondary bg-opacity-10 text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fas fa-users fs-4"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de Últimas Empresas -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">
                        <i class="fas fa-hospital-alt text-primary me-2"></i>Nuevos Clientes SaaS Registrados
                    </h5>
                    <p class="text-muted small mb-0">Últimos laboratorios incorporados a la plataforma</p>
                </div>
                <a href="{{ route('empresa') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">
                    Ver Todas <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="text-center small text-muted text-uppercase">
                            <th class="border-0 rounded-start text-start ps-3">Laboratorio</th>
                            <th class="border-0 text-center">Identificación (RIF)</th>
                            <th class="border-0 text-center">Plan</th>
                            <th class="border-0 text-center rounded-end">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kpi['ultimas_empresas'] ?? [] as $empresa)
                            <tr>
                                <td class="ps-3 text-start">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $empresa->logo ?? 'https://ui-avatars.com/api/?name=' . urlencode($empresa->nombre) }}" class="rounded-circle border shadow-xs" width="38" height="38" alt="Logo">
                                        <span class="fw-bold text-dark">{{ $empresa->nombre }}</span>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border font-monospace">{{ $empresa->rif }}</span>
                                </td>
                                <td class="text-center">
                                    @if(strtolower($empresa->plan) === 'premium')
                                        <span class="badge bg-warning bg-opacity-10 text-warning border border-warning rounded-pill px-3 py-1 small fw-bold">
                                            <i class="fas fa-star me-1"></i>Premium
                                        </span>
                                    @else
                                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-3 py-1 small fw-bold">
                                            <i class="fas fa-cube me-1"></i>Básico
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if ($empresa->estado)
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success rounded-pill px-3 py-1 small fw-bold">
                                            <i class="fas fa-check-circle me-1"></i>Activa
                                        </span>
                                    @else
                                        <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-1 small fw-bold">
                                            <i class="fas fa-times-circle me-1"></i>Inactiva
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-2 opacity-25 d-block"></i>
                                    Aún no hay empresas registradas en el sistema.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

