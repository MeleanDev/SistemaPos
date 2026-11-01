$(document).ready(function () {
    let graficoVentasInstance = null;

    const formatearMoneda = function (monto, prefijo = '$') {
        const num = parseFloat(monto) || 0;
        return prefijo + num.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const formatearBs = function (monto) {
        const num = parseFloat(monto) || 0;
        return 'Bs. ' + num.toLocaleString('es-VE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    };

    const inicializarGraficoVentas = function (graficoData) {
        const canvas = document.getElementById('ventasSemanaChart');
        if (!canvas || !graficoData) {
            return;
        }

        const ctx = canvas.getContext('2d');
        if (graficoVentasInstance) {
            graficoVentasInstance.destroy();
        }

        const gradient = ctx.createLinearGradient(0, 0, 0, 300);
        gradient.addColorStop(0, 'rgba(79, 70, 229, 0.25)');
        gradient.addColorStop(1, 'rgba(79, 70, 229, 0.01)');

        graficoVentasInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: graficoData.labels || [],
                datasets: [{
                    label: 'Ventas ($)',
                    data: graficoData.totales_usd || [],
                    borderColor: '#4f46e5',
                    backgroundColor: gradient,
                    borderWidth: 3,
                    fill: true,
                    tension: 0.38,
                    pointBackgroundColor: '#4f46e5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2.5,
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointHoverBackgroundColor: '#4338ca',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        titleColor: '#f8fafc',
                        titleFont: { size: 13, weight: '600' },
                        bodyColor: '#cbd5e1',
                        bodyFont: { size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                const totalUsd = formatearMoneda(context.parsed.y);
                                const totalBs = graficoData.totales_bs && graficoData.totales_bs[index]
                                    ? formatearBs(graficoData.totales_bs[index])
                                    : '';
                                const cant = graficoData.cantidades && graficoData.cantidades[index]
                                    ? graficoData.cantidades[index]
                                    : 0;

                                return [
                                    'Total: ' + totalUsd + (totalBs ? ' (' + totalBs + ')' : ''),
                                    'Operaciones: ' + cant
                                ];
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f1f5f9',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 },
                            callback: function (val) {
                                return '$' + val;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: { size: 11 }
                        }
                    }
                }
            }
        });
    };

    const actualizarVistaDashboard = function (datos) {
        if (!datos) {
            return;
        }

        const kpis = datos.kpis || {};

        if (document.getElementById('kpiVentasHoyUsd') && kpis.ventas_hoy_usd !== undefined) {
            $('#kpiVentasHoyUsd').text(formatearMoneda(kpis.ventas_hoy_usd));
        }
        if (document.getElementById('kpiVentasHoyBs') && kpis.ventas_hoy_bs !== undefined) {
            $('#kpiVentasHoyBs').text(formatearBs(kpis.ventas_hoy_bs));
        }
        if (document.getElementById('kpiFacturasHoyCount') && kpis.facturas_hoy_count !== undefined) {
            $('#kpiFacturasHoyCount').text(kpis.facturas_hoy_count);
        }
        if (document.getElementById('kpiTicketPromedio') && kpis.ticket_promedio_usd !== undefined) {
            $('#kpiTicketPromedio').text(formatearMoneda(kpis.ticket_promedio_usd));
        }
        if (document.getElementById('kpiTotalProductos') && kpis.total_productos !== undefined) {
            $('#kpiTotalProductos').text(kpis.total_productos);
        }
        if (document.getElementById('kpiTotalServicios') && kpis.total_servicios !== undefined) {
            $('#kpiTotalServicios').text(kpis.total_servicios + ' servicios');
        }
        if (document.getElementById('kpiTotalClientes') && kpis.total_clientes !== undefined) {
            $('#kpiTotalClientes').text(kpis.total_clientes);
        }
        if (document.getElementById('kpiClientesNuevosMes') && kpis.clientes_nuevos_mes !== undefined) {
            $('#kpiClientesNuevosMes').text('+' + kpis.clientes_nuevos_mes + ' este mes');
        }
        if (document.getElementById('kpiCxcPendienteUsd') && kpis.cxc_pendiente_usd !== undefined) {
            $('#kpiCxcPendienteUsd').text(formatearMoneda(kpis.cxc_pendiente_usd));
        }
        if (document.getElementById('kpiCxcPendienteBs') && kpis.cxc_pendiente_bs !== undefined) {
            $('#kpiCxcPendienteBs').text(formatearBs(kpis.cxc_pendiente_bs));
        }
        if (document.getElementById('kpiCxpPendienteUsd') && kpis.cxp_pendiente_usd !== undefined) {
            $('#kpiCxpPendienteUsd').text(formatearMoneda(kpis.cxp_pendiente_usd));
        }
        if (document.getElementById('kpiCxpPendienteBs') && kpis.cxp_pendiente_bs !== undefined) {
            $('#kpiCxpPendienteBs').text(formatearBs(kpis.cxp_pendiente_bs));
        }

        if (datos.grafico_ventas) {
            inicializarGraficoVentas(datos.grafico_ventas);
        }

        if (datos.caja_estado && document.getElementById('contenedorEstadoCaja')) {
            const caja = datos.caja_estado;
            let htmlCaja = '';

            if (caja.tiene_turno_abierto) {
                htmlCaja = `
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="badge rounded-pill px-3 py-1.5 fw-semibold" style="background-color: #ecfdf5; color: #059669;">
                            <i class="fas fa-circle me-1" style="font-size: 0.55rem;"></i> Turno Abierto
                        </span>
                        <span class="small text-secondary font-monospace">${caja.fecha_apertura} ${caja.hora_apertura}</span>
                    </div>
                    <div class="mb-3">
                        <div class="fw-bold text-dark fs-5 mb-1 d-flex align-items-center gap-2">
                            <i class="fas fa-cash-register text-primary"></i> ${caja.caja_nombre}
                        </div>
                        <div class="text-secondary small d-flex align-items-center gap-1.5">
                            <i class="fas fa-user-shield text-info"></i> Cajero: <strong class="text-dark">${caja.cajero_nombre}</strong>
                        </div>
                    </div>
                    <div class="p-3 rounded-4 border mb-4 bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small">Monto Apertura:</span>
                            <span class="fw-semibold text-dark font-monospace">${formatearMoneda(caja.monto_apertura_usd)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small">Ventas Efectivo:</span>
                            <span class="fw-semibold text-emerald font-monospace" style="color: #059669;">${formatearMoneda(caja.ventas_efectivo_usd)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-secondary small">Pagos Digitales:</span>
                            <span class="fw-semibold text-primary font-monospace">${formatearMoneda(caja.pagos_digitales_usd)}</span>
                        </div>
                        <div class="border-top pt-2 mt-2 d-flex justify-content-between align-items-baseline">
                            <span class="fw-bold text-dark">Total en Caja:</span>
                            <span class="fw-bold fs-4 text-dark font-monospace">${formatearMoneda(caja.total_en_caja_usd)}</span>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="/pos" class="btn btn-primary rounded-pill py-2.5 fw-bold shadow-xs">
                            <i class="fas fa-cash-register me-1.5"></i> Ir al Terminal POS
                        </a>
                        <a href="/cajas" class="btn btn-outline-secondary rounded-pill py-2 small fw-semibold">
                            <i class="fas fa-vault me-1.5"></i> Gestión de Cajas
                        </a>
                    </div>
                `;
            } else {
                htmlCaja = `
                    <div class="text-center py-4">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; background-color: #f8fafc; color: #94a3b8;">
                            <i class="fas fa-cash-register fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1">Sin Turno de Caja Activo</h6>
                        <p class="text-secondary small mb-4 px-2">No se encuentra ninguna sesión de caja abierta en este momento.</p>
                        <a href="/cajas" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold shadow-xs">
                            <i class="fas fa-key me-1.5"></i> Aperturar Turno
                        </a>
                    </div>
                `;
            }

            $('#contenedorEstadoCaja').html(htmlCaja);
        }

        if (datos.transacciones_recientes && document.getElementById('tablaVentasRecientesBody')) {
            const transacciones = datos.transacciones_recientes;
            let htmlTabla = '';

            if (transacciones.length === 0) {
                htmlTabla = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-secondary">
                            <div class="py-2">
                                <i class="fas fa-receipt fs-1 text-secondary opacity-25 d-block mb-3"></i>
                                <span class="fw-semibold d-block text-dark">No hay transacciones registradas</span>
                                <span class="small text-secondary">Las ventas procesadas en el punto de venta aparecerán aquí en tiempo real.</span>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                transacciones.forEach(function (v) {
                    htmlTabla += `
                        <tr class="align-middle">
                            <td class="ps-3 py-3 font-monospace fw-bold text-dark">
                                ${v.codigo}
                            </td>
                            <td>
                                <span class="fw-semibold text-dark">${v.cliente}</span>
                            </td>
                            <td>
                                <span class="badge rounded-pill px-2.5 py-1 fw-semibold font-monospace" style="background-color: #f1f5f9; color: #475569;">
                                    ${v.metodos}
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark font-monospace">${formatearMoneda(v.total_usd)}</div>
                                <div class="text-secondary small font-monospace" style="font-size: 0.75rem;">${formatearBs(v.total_bs)}</div>
                            </td>
                            <td class="text-secondary small font-monospace">
                                ${v.fecha_hora}
                            </td>
                            <td class="text-end pe-3">
                                <a href="/pos/imprimir-ticket/${v.id}" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" title="Ver Ticket">
                                    <i class="fas fa-receipt"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
            }

            $('#tablaVentasRecientesBody').html(htmlTabla);
        }
    };

    const recargarDatosDashboard = function () {
        const $btn = $('#btnRefrescarDashboard');
        const $icono = $('#iconoRefrescar');

        $btn.prop('disabled', true);
        $icono.addClass('fa-spin');

        $.ajax({
            url: '/panel-principal/datos',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res && res.success && res.data) {
                    actualizarVistaDashboard(res.data);
                    if (window.notificacion) {
                        window.notificacion.fire({
                            icon: 'success',
                            title: 'Métricas actualizadas en tiempo real'
                        });
                    }
                }
            },
            error: function () {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: 'error',
                        title: 'No se pudieron actualizar los datos del dashboard'
                    });
                }
            },
            complete: function () {
                $btn.prop('disabled', false);
                $icono.removeClass('fa-spin');
            }
        });
    };

    $('#btnRefrescarDashboard').on('click', function (e) {
        e.preventDefault();
        recargarDatosDashboard();
    });

    const initDataEl = document.getElementById('dashboard-initial-data');
    if (initDataEl) {
        try {
            const initialData = JSON.parse(initDataEl.textContent);
            if (initialData && initialData.grafico_ventas) {
                inicializarGraficoVentas(initialData.grafico_ventas);
            }
        } catch (e) {
            console.error('Error al parsear datos iniciales del dashboard', e);
        }
    }
});
