@extends('Sistema.layouts.app')

@section('titulo', 'Panel Principal')
@section('subtitulo', 'Dashboard')

@section('contenido')
<div class="row g-4 mb-4">
    <!-- Quick Actions Banner -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">¡Bienvenido al Sistema POS!</h4>
                        <p class="mb-0 text-white-50">Gestiona tus ventas, inventario y caja en tiempo real.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-light rounded-pill px-4 fw-semibold text-primary shadow-sm" type="button">
                            <i class="fas fa-cash-register me-1"></i> Abrir POS
                        </button>
                        <button class="btn btn-outline-light rounded-pill px-3" type="button">
                            <i class="fas fa-plus me-1"></i> Nuevo Producto
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold small text-uppercase">Ventas de Hoy</span>
                    <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-dollar-sign fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <h3 class="fw-bold text-dark mb-0">$0.00</h3>
                    <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 small">Hoy</span>
                </div>
                <p class="text-muted small mb-0 mt-2"><i class="fas fa-clock me-1"></i> Actualizado en tiempo real</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold small text-uppercase">Tickets / Facturas</span>
                    <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-receipt fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <h3 class="fw-bold text-dark mb-0">0</h3>
                    <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 small">0 completadas</span>
                </div>
                <p class="text-muted small mb-0 mt-2"><i class="fas fa-check-circle me-1"></i> Transacciones emitidas</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold small text-uppercase">Productos</span>
                    <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-boxes fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <h3 class="fw-bold text-dark mb-0">0</h3>
                    <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1 small">En catálogo</span>
                </div>
                <p class="text-muted small mb-0 mt-2"><i class="fas fa-box-open me-1"></i> Control de inventario</p>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <span class="text-muted fw-semibold small text-uppercase">Clientes</span>
                    <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                        <i class="fas fa-user-friends fs-5"></i>
                    </div>
                </div>
                <div class="d-flex align-items-baseline justify-content-between">
                    <h3 class="fw-bold text-dark mb-0">0</h3>
                    <span class="badge bg-info-subtle text-info rounded-pill px-2 py-1 small">Registrados</span>
                </div>
                <p class="text-muted small mb-0 mt-2"><i class="fas fa-users me-1"></i> Directorio general</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Sales Chart -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-chart-area text-primary me-2"></i>Ventas de los Últimos 7 Días
                    </h5>
                    <p class="text-muted small mb-0">Resumen de ingresos generados por día</p>
                </div>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">Semanal</span>
            </div>
            <div class="card-body p-4">
                <div style="position: relative; height: 280px; width: 100%;">
                    <canvas id="ventasSemanaChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick POS & Cash Summary -->
    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold text-dark mb-1">
                    <i class="fas fa-wallet text-success me-2"></i>Estado de Caja
                </h5>
                <p class="text-muted small mb-0">Balance de la sesión actual</p>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-between">
                <div class="bg-light rounded-4 p-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Monto de Apertura:</span>
                        <span class="fw-bold text-dark">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Ventas en Efectivo:</span>
                        <span class="fw-bold text-success">$0.00</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Pagos Digitales:</span>
                        <span class="fw-bold text-primary">$0.00</span>
                    </div>
                    <hr class="my-2 opacity-25">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold text-dark">Total en Caja:</span>
                        <span class="fw-bold fs-5 text-dark">$0.00</span>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary rounded-pill py-2 fw-semibold shadow-sm" type="button">
                        <i class="fas fa-cash-register me-1"></i> Ir al Terminal POS
                    </button>
                    <button class="btn btn-outline-secondary rounded-pill py-2 small" type="button">
                        <i class="fas fa-calculator me-1"></i> Cuadre / Cierre de Caja
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table Card -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 pb-2 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-dark mb-1">
                        <i class="fas fa-history text-primary me-2"></i>Transacciones Recientes
                    </h5>
                    <p class="text-muted small mb-0">Últimas operaciones procesadas en el punto de venta</p>
                </div>
            </div>
            <div class="card-body px-4 pb-4 pt-2">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaVentasRecientes">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 rounded-start"># Ticket</th>
                                <th class="border-0">Cliente</th>
                                <th class="border-0">Método Pago</th>
                                <th class="border-0">Total</th>
                                <th class="border-0">Estado</th>
                                <th class="border-0">Fecha y Hora</th>
                                <th class="border-0 text-center rounded-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fs-3 d-block mb-2 text-secondary opacity-50"></i>
                                    No hay ventas registradas hoy.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Inicializar gráfico de ventas de la semana
        const ctx = document.getElementById('ventasSemanaChart');
        if (ctx) {
            const labels = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
            const dataVentas = [0, 0, 0, 0, 0, 0, 0];

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Ventas ($)',
                        data: dataVentas,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#4f46e5',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#1e293b',
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            padding: 10,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                color: '#94a3b8',
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: '#94a3b8'
                            }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection
