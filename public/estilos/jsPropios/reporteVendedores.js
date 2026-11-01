let dtFacturasVendedores = null;
let graficoVendedoresInstancia = null;

$(document).ready(async function () {
    inicializarFechasPorDefecto();
    await cargarCatalogos();
    inicializarSelectores();
    cargarKpis();
    cargarResumen();
    inicializarTablaFacturas();
    configurarEventosTabs();
});

const cargarCatalogos = async function () {
    try {
        const res = await peticionAjax({ url: urlReporteCatalogos, method: "GET" });

        if (res && res.success && res.data) {
            const vendedores = res.data.vendedores || [];
            const $select = $("#filtroVendedor");

            vendedores.forEach(function (v) {
                const comision = parseFloat(v.comision_porcentaje || 0).toFixed(2);
                $select.append(new Option(`${v.nombre} (${comision}%)`, v.id));
            });

            $("#kpiAsesores").text(vendedores.length);
            $("#bannerConteoAsesores").text(vendedores.length);
            $("#bannerTasa").text(parseFloat(res.data.tasa_usd || 1).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error de consulta",
                text: "No se pudieron cargar los asesores.",
            });
        }
    }
};

const inicializarFechasPorDefecto = function () {
    const hoy = new Date();
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
    const hoyStr = hoy.toISOString().split('T')[0];

    $("#filtroFechaInicio").val(primerDiaMes);
    $("#filtroFechaFin").val(hoyStr);
};

const inicializarSelectores = function () {
    if (typeof crearSelect2 === "function") {
        crearSelect2({
            selector: "#filtroVendedor",
            placeholder: "-- Todos los Asesores --",
            allowClear: true,
        });

        $("#filtroVendedor").on("change", function () {
            recargarReportes();
        });
    }
};

const configurarEventosTabs = function () {
    $('button[data-bs-toggle="pill"]').on('shown.bs.tab', function () {
        if ($.fn.dataTable) {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }
    });
};

const cargarKpis = async function () {
    const params = {
        fecha_inicio: $("#filtroFechaInicio").val(),
        fecha_fin: $("#filtroFechaFin").val(),
        vendedor_id: $("#filtroVendedor").val(),
    };

    try {
        const res = await peticionAjax({
            url: urlReporteKpis,
            method: "GET",
            data: params,
        });

        if (res && res.success && res.data) {
            const d = res.data;
            $("#kpiVentasUsd").text(`$ ${parseFloat(d.total_ventas_usd || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiVentasBs").text(`Bs. ${parseFloat(d.total_ventas_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiComisionesUsd").text(`$ ${parseFloat(d.total_comisiones_usd || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiComisionesBs").text(`Bs. ${parseFloat(d.total_comisiones_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
            $("#kpiConteoFacturas").text(d.conteo_facturas || 0);
            $("#kpiTopVendedor").text(d.top_vendedor || '--');
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({
                icon: "error",
                title: "Error de consulta",
                text: "No se pudieron obtener los indicadores de ventas.",
            });
        }
    }
};

const cargarResumen = async function () {
    const params = {
        fecha_inicio: $("#filtroFechaInicio").val(),
        fecha_fin: $("#filtroFechaFin").val(),
        vendedor_id: $("#filtroVendedor").val(),
    };

    const $tbody = $("#filasResumenVendedores");
    $tbody.html('<tr><td colspan="7" class="text-center text-muted py-4"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Cargando consolidado...</td></tr>');

    try {
        const res = await peticionAjax({
            url: urlReporteResumen,
            method: "GET",
            data: params,
        });

        $tbody.empty();

        if (res && res.success && Array.isArray(res.data) && res.data.length > 0) {
            let totalVendidoUsd = 0;
            let totalVendidoBs = 0;
            let totalComisionUsd = 0;
            let totalComisionBs = 0;
            let totalFacturas = 0;

            const nombresGrafico = [];
            const ventasGrafico = [];
            const comisionesGrafico = [];

            res.data.forEach(function (v) {
                totalVendidoUsd += parseFloat(v.total_vendido_usd || 0);
                totalVendidoBs += parseFloat(v.total_vendido_bs || 0);
                totalComisionUsd += parseFloat(v.total_comision_usd || 0);
                totalComisionBs += parseFloat(v.total_comision_bs || 0);
                totalFacturas += parseInt(v.conteo_ventas || 0, 10);

                if (parseFloat(v.total_vendido_usd || 0) > 0 || parseFloat(v.total_comision_usd || 0) > 0) {
                    nombresGrafico.push(v.nombre);
                    ventasGrafico.push(parseFloat(v.total_vendido_usd || 0));
                    comisionesGrafico.push(parseFloat(v.total_comision_usd || 0));
                }

                const fila = `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="avatar-executive-sm rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; min-width: 36px;">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark" style="font-size: 0.90rem;">${v.nombre}</span>
                                    <span class="d-inline-flex align-items-center font-monospace text-secondary" style="font-size: 0.78rem;">
                                        <i class="fas fa-id-card me-1 text-primary"></i> ${v.documento}
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="small text-muted"><i class="fas fa-phone text-secondary me-1"></i>${v.telefono || 'Sin teléfono'}</span>
                                <span class="small text-muted"><i class="fas fa-envelope text-secondary me-1"></i>${v.correo || 'Sin correo'}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary rounded-pill font-monospace fw-bold px-2.5 py-1">
                                ${parseFloat(v.comision_porcentaje || 0).toFixed(2)}%
                            </span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill font-monospace fw-bold px-2.5 py-1">
                                ${v.conteo_ventas}
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-column align-items-end">
                                <span class="fw-bold text-dark font-monospace">$ ${parseFloat(v.total_vendido_usd || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                <span class="text-muted small font-monospace">Bs. ${parseFloat(v.total_vendido_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="d-flex flex-column align-items-end">
                                <span class="fw-bold text-success font-monospace">+$ ${parseFloat(v.total_comision_usd || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                <span class="text-success-emphasis small font-monospace">+Bs. ${parseFloat(v.total_comision_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 shadow-xs" onclick="filtrarPorVendedorEnTab(${v.id})" title="Ver facturas detalladas de este asesor">
                                <i class="fas fa-receipt me-1"></i>Facturas
                            </button>
                        </td>
                    </tr>
                `;

                $tbody.append(fila);
            });

            const filaTotal = `
                <tr class="fw-bold" style="background-color: #f8fafc; border-top: 2px solid #e2e8f0;">
                    <td><span class="fw-bold text-dark text-uppercase">TOTAL CONSOLIDADO</span></td>
                    <td class="text-muted">--</td>
                    <td class="text-center text-muted">--</td>
                    <td class="text-center">
                        <span class="badge bg-primary text-white rounded-pill font-monospace px-3 py-1">${totalFacturas}</span>
                    </td>
                    <td class="text-end font-monospace text-dark">
                        $ ${totalVendidoUsd.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        <div class="small text-muted font-normal">Bs. ${totalVendidoBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </td>
                    <td class="text-end font-monospace text-success">
                        +$ ${totalComisionUsd.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        <div class="small text-success font-normal">+Bs. ${totalComisionBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</div>
                    </td>
                    <td class="text-center">--</td>
                </tr>
            `;

            $tbody.append(filaTotal);
            renderizarGrafico(nombresGrafico, ventasGrafico, comisionesGrafico);
        } else {
            $tbody.append('<tr><td colspan="7" class="text-center text-muted py-4">No se encontraron ventas para los criterios seleccionados.</td></tr>');
            renderizarGrafico([], [], []);
        }
    } catch (e) {
        $tbody.html('<tr><td colspan="7" class="text-center text-danger py-4">Error al cargar la información consolidada.</td></tr>');
    }
};

const renderizarGrafico = function (etiquetas, datosVentas, datosComisiones) {
    const ctx = document.getElementById("graficoVendedores");
    const containerVacio = document.getElementById("graficoVacio");

    if (!ctx) {
        return;
    }

    if (!Array.isArray(etiquetas) || etiquetas.length === 0) {
        if (graficoVendedoresInstancia) {
            graficoVendedoresInstancia.destroy();
            graficoVendedoresInstancia = null;
        }
        if (containerVacio) {
            containerVacio.classList.remove("d-none");
        }
        return;
    }

    if (containerVacio) {
        containerVacio.classList.add("d-none");
    }

    if (graficoVendedoresInstancia) {
        graficoVendedoresInstancia.destroy();
    }

    graficoVendedoresInstancia = new Chart(ctx, {
        type: "bar",
        data: {
            labels: etiquetas,
            datasets: [
                {
                    label: "Ventas ($)",
                    data: datosVentas,
                    backgroundColor: "rgba(79, 70, 229, 0.85)",
                    borderColor: "rgb(79, 70, 229)",
                    borderWidth: 1,
                    borderRadius: 6,
                },
                {
                    label: "Comisiones ($)",
                    data: datosComisiones,
                    backgroundColor: "rgba(16, 185, 129, 0.85)",
                    borderColor: "rgb(16, 185, 129)",
                    borderWidth: 1,
                    borderRadius: 6,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: "index",
            },
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            let label = context.dataset.label || "";
                            if (label) {
                                label += ": ";
                            }
                            if (context.parsed.y !== null) {
                                label += "$ " + parseFloat(context.parsed.y).toLocaleString("es-VE", { minimumFractionDigits: 2 });
                            }
                            return label;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11,
                        },
                    },
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: "#f1f5f9",
                    },
                    ticks: {
                        callback: function (val) {
                            return "$ " + val.toLocaleString();
                        },
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11,
                        },
                    },
                },
            },
        },
    });
};

const inicializarTablaFacturas = function () {
    if (typeof crearDataTable !== "function") {
        return;
    }

    dtFacturasVendedores = crearDataTable({
        selector: "#datatable_facturas_vendedores",
        url: urlReporteLista,
        searchPlaceholder: "Factura, asesor, cliente...",
        dataExtra: function (d) {
            d.fecha_inicio = $("#filtroFechaInicio").val();
            d.fecha_fin = $("#filtroFechaFin").val();
            d.vendedor_id = $("#filtroVendedor").val();
        },
        columns: [
            {
                data: "codigo",
                name: "codigo",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    const control = row.numero_control ? `<small class="text-muted d-block font-monospace" style="font-size: 0.76rem;">Ctrl: ${row.numero_control}</small>` : '';
                    return `
                        <div class="d-flex flex-column">
                            <strong class="text-dark font-monospace">${data}</strong>
                            ${control}
                        </div>
                    `;
                },
            },
            {
                data: "fecha_emision",
                name: "fecha_emision",
                className: "text-center align-middle",
                render: function (data, type, row) {
                    return `<span class="small font-monospace text-secondary">${data} ${row.hora_emision || ''}</span>`;
                },
            },
            {
                data: "vendedor.nombre",
                name: "vendedor.nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    if (!row.vendedor) {
                        return '<span class="text-muted">Sin Asesor</span>';
                    }
                    return `
                        <div class="d-flex align-items-center gap-1.5">
                            <i class="fas fa-user-tie text-primary small"></i>
                            <strong class="text-dark">${row.vendedor.nombre}</strong>
                        </div>
                    `;
                },
            },
            {
                data: "cliente.nombre",
                name: "cliente.nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    if (!row.cliente) {
                        return '<span class="text-muted">Consumidor Final</span>';
                    }
                    const nom = `${row.cliente.nombre || ''} ${row.cliente.apellido || ''}`.trim();
                    const ced = row.cliente.cedula ? `<small class="d-inline-flex align-items-center font-monospace text-secondary" style="font-size: 0.76rem;"><i class="fas fa-id-card me-1 text-primary"></i> ${row.cliente.cedula}</small>` : '';
                    return `
                        <div class="d-flex flex-column">
                            <span class="text-dark fw-semibold">${nom}</span>
                            ${ced}
                        </div>
                    `;
                },
            },
            {
                data: "total_usd",
                name: "total_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.total_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <div class="d-flex flex-column align-items-end">
                            <span class="fw-bold text-dark font-monospace">$ ${usd}</span>
                            <span class="text-muted small font-monospace" style="font-size: 0.76rem;">Bs. ${bs}</span>
                        </div>
                    `;
                },
            },
            {
                data: "comision_porcentaje",
                name: "comision_porcentaje",
                className: "text-center align-middle",
                render: function (data) {
                    const val = parseFloat(data || 0).toFixed(2);
                    return `<span class="badge bg-primary-subtle text-primary rounded-pill font-monospace fw-bold px-2 py-1">${val}%</span>`;
                },
            },
            {
                data: "comision_monto_usd",
                name: "comision_monto_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.comision_monto_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `
                        <div class="d-flex flex-column align-items-end">
                            <span class="fw-bold text-success font-monospace">+$ ${usd}</span>
                            <span class="text-success-emphasis small font-monospace" style="font-size: 0.76rem;">+Bs. ${bs}</span>
                        </div>
                    `;
                },
            },
            {
                data: "id",
                name: "id",
                className: "text-center align-middle",
                orderable: false,
                searchable: false,
                render: function (data) {
                    return `
                        <div class="d-flex justify-content-center gap-1">
                            <button type="button" class="btn btn-outline-dark btn-sm rounded-pill px-2 py-1 shadow-xs" onclick="imprimirTicketFactura(${data})" title="Imprimir Ticket Térmico">
                                <i class="fas fa-receipt"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2 py-1 shadow-xs" onclick="imprimirCartaFactura(${data})" title="Ver Factura en PDF Carta">
                                <i class="fas fa-file-invoice"></i>
                            </button>
                        </div>
                    `;
                },
            },
        ],
    });
};

const recargarReportes = function () {
    cargarKpis();
    cargarResumen();
    if (dtFacturasVendedores) {
        dtFacturasVendedores.ajax.reload(null, false);
    }
};

const limpiarFiltros = function () {
    inicializarFechasPorDefecto();
    if (typeof limpiarSelect2 === "function") {
        limpiarSelect2("#filtroVendedor");
    } else {
        $("#filtroVendedor").val("").trigger("change");
    }

    $(".btn-periodo").removeClass("btn-primary active").addClass("btn-outline-secondary");
    $('.btn-periodo[data-periodo="mes"]').removeClass("btn-outline-secondary").addClass("btn-primary active");

    recargarReportes();
};

const establecerRangoRapido = function (periodo) {
    const hoy = new Date();
    let fechaInicio = "";
    let fechaFin = hoy.toISOString().split("T")[0];

    $(".btn-periodo").removeClass("btn-primary active").addClass("btn-outline-secondary");
    $(`.btn-periodo[data-periodo="${periodo}"]`).removeClass("btn-outline-secondary").addClass("btn-primary active");

    if (periodo === "hoy") {
        fechaInicio = fechaFin;
    } else if (periodo === "semana") {
        const diaSemana = hoy.getDay() === 0 ? 6 : hoy.getDay() - 1;
        const primerDiaSemana = new Date(hoy);
        primerDiaSemana.setDate(hoy.getDate() - diaSemana);
        fechaInicio = primerDiaSemana.toISOString().split("T")[0];
    } else if (periodo === "mes") {
        fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split("T")[0];
    } else if (periodo === "mes_anterior") {
        const primerDiaMesAnt = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
        const ultimoDiaMesAnt = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
        fechaInicio = primerDiaMesAnt.toISOString().split("T")[0];
        fechaFin = ultimoDiaMesAnt.toISOString().split("T")[0];
    } else if (periodo === "ano") {
        fechaInicio = new Date(hoy.getFullYear(), 0, 1).toISOString().split("T")[0];
    }

    $("#filtroFechaInicio").val(fechaInicio);
    $("#filtroFechaFin").val(fechaFin);

    recargarReportes();
};

const filtrarPorVendedorEnTab = function (vendedorId) {
    if (typeof establecerValorSelect2 === "function") {
        establecerValorSelect2("#filtroVendedor", vendedorId);
    } else {
        $("#filtroVendedor").val(vendedorId).trigger("change");
    }

    const triggerEl = document.getElementById("facturas-tab");
    if (triggerEl) {
        bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }

    recargarReportes();
};

const exportarReporte = function (tipo) {
    const fechaInicio = $("#filtroFechaInicio").val();
    const fechaFin = $("#filtroFechaFin").val();
    const vendedorId = $("#filtroVendedor").val();

    let baseUrl = "";
    if (tipo === "pdf_ver") {
        baseUrl = `${urlExportarPdf}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&vendedor_id=${vendedorId}`;
        window.open(baseUrl, "_blank");
    } else if (tipo === "pdf_descargar") {
        baseUrl = `${urlExportarPdf}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&vendedor_id=${vendedorId}&descargar=1`;
        window.location.href = baseUrl;
    } else if (tipo === "excel") {
        baseUrl = `${urlExportarExcel}?fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&vendedor_id=${vendedorId}`;
        window.location.href = baseUrl;
    }
};

const imprimirTicketFactura = function (id) {
    window.open(`${urlImprimirTicket}/${id}`, "_blank", "width=400,height=600");
};

const imprimirCartaFactura = function (id) {
    window.open(`${urlImprimirCarta}/${id}`, "_blank");
};
