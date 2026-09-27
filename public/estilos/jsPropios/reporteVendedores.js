let dtFacturasVendedores = null;

$(document).ready(function () {
    const hoy = new Date();
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
    const hoyStr = hoy.toISOString().split('T')[0];

    $("#filtroFechaInicio").val(primerDiaMes);
    $("#filtroFechaFin").val(hoyStr);

    cargarKpis();
    cargarResumen();
    inicializarTablaFacturas();
});

function cargarKpis() {
    const params = {
        fecha_inicio: $("#filtroFechaInicio").val(),
        fecha_fin: $("#filtroFechaFin").val(),
        vendedor_id: $("#filtroVendedor").val(),
    };

    $.get(urlReporteKpis, params, function (res) {
        if (res.success && res.data) {
            const d = res.data;
            $("#kpiVentasUsd").text(`$ ${parseFloat(d.total_ventas_usd).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiVentasBs").text(`Bs. ${parseFloat(d.total_ventas_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiComisionesUsd").text(`$ ${parseFloat(d.total_comisiones_usd).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiComisionesBs").text(`Bs. ${parseFloat(d.total_comisiones_bs).toLocaleString('es-VE', { minimumFractionDigits: 2 })}`);
            $("#kpiConteoFacturas").text(d.conteo_facturas);
            $("#kpiTopVendedor").text(d.top_vendedor || '--');
        }
    });
};

function cargarResumen() {
    const params = {
        fecha_inicio: $("#filtroFechaInicio").val(),
        fecha_fin: $("#filtroFechaFin").val(),
    };

    $.get(urlReporteResumen, params, function (res) {
        const $tbody = $("#filasResumenVendedores");
        $tbody.empty();

        if (res.success && Array.isArray(res.data) && res.data.length > 0) {
            let totalVendidoUsd = 0;
            let totalVendidoBs = 0;
            let totalComisionUsd = 0;
            let totalComisionBs = 0;
            let totalFacturas = 0;

            res.data.forEach(v => {
                totalVendidoUsd += v.total_vendido_usd;
                totalVendidoBs += v.total_vendido_bs;
                totalComisionUsd += v.total_comision_usd;
                totalComisionBs += v.total_comision_bs;
                totalFacturas += v.conteo_ventas;

                $tbody.append(`
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-executive-sm bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">${v.nombre}</span>
                                    <span class="text-muted small">${v.documento}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border fw-bold">${parseFloat(v.comision_porcentaje).toFixed(2)}%</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">${v.conteo_ventas}</span>
                        </td>
                        <td class="text-end fw-bold text-dark">$ ${v.total_vendido_usd.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                        <td class="text-end text-muted small">Bs. ${v.total_vendido_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                        <td class="text-end fw-bold text-success">$ ${v.total_comision_usd.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                        <td class="text-end text-success-emphasis small">Bs. ${v.total_comision_bs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                    </tr>
                `);
            });

            $tbody.append(`
                <tr class="table-light fw-bold">
                    <td>TOTAL CONSOLIDADO</td>
                    <td class="text-center">--</td>
                    <td class="text-center">${totalFacturas}</td>
                    <td class="text-end text-dark">$ ${totalVendidoUsd.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end text-muted">Bs. ${totalVendidoBs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end text-success">$ ${totalComisionUsd.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                    <td class="text-end text-success">Bs. ${totalComisionBs.toLocaleString('es-VE', { minimumFractionDigits: 2 })}</td>
                </tr>
            `);
        } else {
            $tbody.append('<tr><td colspan="7" class="text-center text-muted py-4">No se encontraron vendedores registrados.</td></tr>');
        }
    });
};

function inicializarTablaFacturas() {
    crearDataTable({
        selector: "#datatable_facturas_vendedores",
        url: urlReporteLista,
        searchPlaceholder: "Factura, vendedor, cliente...",
        ajaxData: function (d) {
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
                    const control = row.numero_control ? `<small class="text-muted d-block font-monospace">Ctrl: ${row.numero_control}</small>` : '';
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
                    return `<span class="small font-monospace">${data} ${row.hora_emision || ''}</span>`;
                },
            },
            {
                data: "vendedor.nombre",
                name: "vendedor.nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    return row.vendedor ? `<strong class="text-primary">${row.vendedor.nombre}</strong>` : '<span class="text-muted">N/A</span>';
                },
            },
            {
                data: "cliente.nombre",
                name: "cliente.nombre",
                className: "text-start align-middle",
                render: function (data, type, row) {
                    if (!row.cliente) return 'Consumidor Final';
                    const nom = `${row.cliente.nombre || ''} ${row.cliente.apellido || ''}`.trim();
                    return `<span>${nom}</span><small class="text-muted d-block">${row.cliente.cedula || ''}</small>`;
                },
            },
            {
                data: "total_usd",
                name: "total_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toFixed(2);
                    const bs = parseFloat(row.total_bs || 0).toFixed(2);
                    return `
                        <div class="d-flex flex-column align-items-end">
                            <span class="fw-bold text-dark">$${usd}</span>
                            <span class="text-muted small" style="font-size: 0.75rem;">Bs. ${bs}</span>
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
                    return `<span class="badge bg-light text-dark border fw-bold">${val}%</span>`;
                },
            },
            {
                data: "comision_monto_usd",
                name: "comision_monto_usd",
                className: "text-end align-middle",
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toFixed(2);
                    const bs = parseFloat(row.comision_monto_bs || 0).toFixed(2);
                    return `
                        <div class="d-flex flex-column align-items-end">
                            <span class="fw-bold text-success">+$${usd}</span>
                            <span class="text-success-emphasis small" style="font-size: 0.75rem;">+Bs. ${bs}</span>
                        </div>
                    `;
                },
            },
        ],
    });
};

function recargarReportes() {
    cargarKpis();
    cargarResumen();
    if ($("#datatable_facturas_vendedores").length) {
        $("#datatable_facturas_vendedores").DataTable().ajax.reload(null, false);
    }
};

function limpiarFiltros() {
    const hoy = new Date();
    const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1).toISOString().split('T')[0];
    const hoyStr = hoy.toISOString().split('T')[0];

    $("#filtroFechaInicio").val(primerDiaMes);
    $("#filtroFechaFin").val(hoyStr);
    $("#filtroVendedor").val("");

    recargarReportes();
};
