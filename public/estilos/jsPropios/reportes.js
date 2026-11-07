let moduloActivoPrevia = 'ingresos';

const generarReporte = function (modulo, accion) {
    moduloActivoPrevia = modulo;
    let params = {};
    let urlPdf = '';
    let urlExcel = '';
    let urlJson = '';

    if (modulo === 'ingresos') {
        urlPdf = '/reportes/ingresos/pdf';
        urlExcel = '/reportes/ingresos/excel';
        urlJson = '/reportes/ingresos';
        params = {
            fecha_inicio: $('#ing_fecha_inicio').val() || '',
            fecha_fin: $('#ing_fecha_fin').val() || '',
            caja_id: $('#ing_caja_id').val() || '',
            metodo_pago_id: $('#ing_metodo_pago_id').val() || ''
        };
    } else if (modulo === 'creditos') {
        urlPdf = '/reportes/creditos/pdf';
        urlExcel = '/reportes/creditos/excel';
        urlJson = '/reportes/creditos';
        params = {
            fecha_inicio: $('#cred_fecha_inicio').val() || '',
            fecha_fin: $('#cred_fecha_fin').val() || ''
        };
    } else if (modulo === 'inventario') {
        urlPdf = '/reportes/inventario/pdf';
        urlExcel = '/reportes/inventario/excel';
        urlJson = '/reportes/inventario';
        params = {
            almacen_id: $('#inv_almacen_id').val() || '',
            categoria_id: $('#inv_categoria_id').val() || '',
            bajo_stock: $('#inv_bajo_stock').is(':checked') ? 1 : 0
        };
    } else if (modulo === 'rentabilidad') {
        urlPdf = '/reportes/rentabilidad/pdf';
        urlExcel = '/reportes/rentabilidad/excel';
        urlJson = '/reportes/rentabilidad';
        params = {
            fecha_inicio: $('#rent_fecha_inicio').val() || '',
            fecha_fin: $('#rent_fecha_fin').val() || '',
            almacen_id: $('#rent_almacen_id').val() || ''
        };
    } else if (modulo === 'vendedores') {
        urlPdf = '/reportes/vendedores/pdf';
        urlExcel = '/reportes/vendedores/excel';
        urlJson = '/reportes/vendedores/json';
        params = {
            fecha_inicio: $('#vend_fecha_inicio').val() || '',
            fecha_fin: $('#vend_fecha_fin').val() || '',
            vendedor_id: $('#vend_vendedor_id').val() || ''
        };
    } else if (modulo === 'stock_almacenes') {
        urlPdf = '/reportes/stock-almacenes/pdf';
        urlExcel = '/reportes/stock-almacenes/excel';
        urlJson = '/reportes/stock-almacenes';
        const selectedProducts = $('#stock_producto_ids').val() || [];
        const selectedCategories = $('#stock_categoria_ids').val() || [];
        params = {
            categoria_ids: Array.isArray(selectedCategories) ? selectedCategories.join(',') : (selectedCategories || ''),
            producto_ids: Array.isArray(selectedProducts) ? selectedProducts.join(',') : (selectedProducts || ''),
            solo_con_stock: $('#stock_solo_con_stock').is(':checked') ? 1 : 0
        };
    } else if (modulo === 'cuadre_diario') {
        urlPdf = '/reportes/cuadre-diario/pdf';
        urlExcel = '/reportes/cuadre-diario/excel';
        urlJson = '/reportes/cuadre-diario';
        params = {
            fecha_inicio: $('#cuadre_fecha_inicio').val() || '',
            fecha_fin: $('#cuadre_fecha_fin').val() || '',
            caja_id: $('#cuadre_caja_id').val() || ''
        };
    } else if (modulo === 'devoluciones_anulaciones') {
        urlPdf = '/reportes/devoluciones-anulaciones/pdf';
        urlExcel = '/reportes/devoluciones-anulaciones/excel';
        urlJson = '/reportes/devoluciones-anulaciones';
        params = {
            fecha_inicio: $('#devanul_fecha_inicio').val() || '',
            fecha_fin: $('#devanul_fecha_fin').val() || '',
            tipo: $('#devanul_tipo').val() || 'todos'
        };
    } else if (modulo === 'flujo_caja') {
        urlPdf = '/reportes/flujo-caja/pdf';
        urlExcel = '/reportes/flujo-caja/excel';
        urlJson = '/reportes/flujo-caja';
        params = {
            fecha_inicio: $('#flujo_fecha_inicio').val() || '',
            fecha_fin: $('#flujo_fecha_fin').val() || '',
            caja_id: $('#flujo_caja_id').val() || '',
            metodo_pago_id: $('#flujo_metodo_pago_id').val() || ''
        };
    } else if (modulo === 'libro_ventas') {
        urlPdf = '/reportes/libro-ventas/pdf';
        urlExcel = '/reportes/libro-ventas/excel';
        urlJson = '/reportes/libro-ventas';
        params = {
            fecha_inicio: $('#libro_fecha_inicio').val() || '',
            fecha_fin: $('#libro_fecha_fin').val() || ''
        };
    }

    const queryString = new URLSearchParams(params).toString();

    if (accion === 'previa') {
        cargarVistaPrevia(modulo, urlJson, params);
    } else if (accion === 'pdf_ver') {
        window.open(`${urlPdf}?${queryString}`, '_blank');
    } else if (accion === 'pdf_descargar') {
        window.location.href = `${urlPdf}?${queryString}&descargar=1`;
    } else if (accion === 'excel') {
        window.location.href = `${urlExcel}?${queryString}`;
    }
};

const exportarDesdePrevia = function (accion) {
    generarReporte(moduloActivoPrevia, accion);
};

const cargarVistaPrevia = async function (modulo, urlJson, params) {
    if (window.Swal) {
        Swal.fire({
            title: 'Consultando Informe...',
            text: 'Procesando auditoría y métricas en tiempo real',
            allowOutsideClick: false,
            allowEscapeKey: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    }

    try {
        const res = await peticionAjax({
            url: urlJson,
            method: 'GET',
            data: params
        });

        if (window.Swal) {
            Swal.close();
        }

        if (res && res.success && res.data) {
            renderizarVistaPrevia(modulo, res.data);
            const modalEl = document.getElementById('modalVistaPrevia');
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
                modalInstance.show();
            }
        } else {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Sin datos',
                    text: 'No se encontraron registros para los filtros seleccionados.'
                });
            }
        }
    } catch (error) {
        if (window.Swal) {
            Swal.close();
            Swal.fire({
                icon: 'error',
                title: 'Error de Consulta',
                text: 'Ocurrió un error al obtener la vista previa del reporte.'
            });
        }
    }
};

const renderizarVistaPrevia = function (modulo, data) {
    const kpisEl = $('#contenedorKpisPrevia');
    const tablaEl = $('#contenedorTablaPrevia');
    const badgeModuloEl = $('#previaBadgeModulo');
    const periodoEl = $('#previaPeriodoTexto');
    const tituloTablaEl = $('#previaTituloTabla');
    const conteoEl = $('#previaConteoRegistros');
    const tituloModalEl = $('#modalVistaPreviaTitulo');

    kpisEl.empty();
    tablaEl.empty();

    if (modulo === 'ingresos') {
        tituloModalEl.text('Informe de Ingresos & Métodos de Pago');
        badgeModuloEl.text('INGRESOS & CAJA');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-credit-card text-primary me-2"></i>Recaudación por Forma de Pago');
        conteoEl.text(`${data.desglose_metodos?.length || 0} métodos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Venta Neta</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-file-invoice-dollar fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_neto_usd ?? data.kpis?.total_facturado_usd)}</h3>
                    <div class="text-primary font-monospace fw-semibold mt-1" style="font-size: 0.78rem;">Bruto: $${formatearNumero(data.kpis?.total_bruto_usd ?? data.kpis?.total_facturado_usd)} • Dev: -$${formatearNumero(data.kpis?.total_devoluciones_usd)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Recaudado en Caja</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-cash-register fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_pagado_usd)}</h3>
                    <div class="text-success font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_pagado_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Ventas Cerradas</span>
                        <div class="rounded-3 bg-info bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-receipt fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">${data.kpis?.conteo_ventas || 0}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Ticket Prom: <strong class="font-monospace text-dark">$${formatearNumero(data.kpis?.ticket_promedio_usd)}</strong></div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Por Cobrar (Créditos)</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-hand-holding-dollar fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-0 font-monospace" style="color: #d97706 !important;">$${formatearNumero(data.kpis?.total_por_cobrar_usd)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Vueltos: <strong class="font-monospace text-dark">$${formatearNumero(data.kpis?.total_vuelto_usd)}</strong></div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Método de Pago</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Transacciones</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Monto ($ USD)</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Monto (Bs. VES)</th>
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">% Participación</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.desglose_metodos && data.desglose_metodos.length > 0) {
            data.desglose_metodos.forEach(function (m) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5 fw-bold text-dark">${m.nombre}</td>
                        <td class="text-center py-2.5 font-monospace">${m.transacciones_count}</td>
                        <td class="text-end py-2.5 fw-bold text-success">$${formatearNumero(m.total_usd)}</td>
                        <td class="text-end py-2.5">Bs. ${formatearNumero(m.total_bs)}</td>
                        <td class="text-end pe-3 py-2.5 fw-bold text-dark">${m.porcentaje}%</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="fas fa-inbox fs-3 mb-2 d-block opacity-50"></i>
                        No hay pagos registrados para los filtros seleccionados
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);

    } else if (modulo === 'creditos') {
        tituloModalEl.text('Informe de Cartera de Créditos (CXC / CXP)');
        badgeModuloEl.text('CRÉDITOS & CARTERA');
        periodoEl.text(data.kpis?.periodo_texto || ('Cartera al día • Tasa: ' + formatearNumero(data.kpis?.tasa_cambio) + ' Bs/$'));
        tituloTablaEl.html('<i class="fas fa-file-invoice-dollar text-danger me-2"></i>Cuentas por Cobrar Pendientes');
        conteoEl.text(`${data.cxc_pendientes?.length || 0} cuentas`);

        kpisEl.html(`
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Por Cobrar (Clientes)</span>
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-clock-rotate-left fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-0 font-monospace">$${formatearNumero(data.kpis?.total_cxc_usd)}</h3>
                    <div class="text-danger font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_cxc_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Por Pagar (Proveedores)</span>
                        <div class="rounded-3 bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-building-columns fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_cxp_usd)}</h3>
                    <div class="text-muted font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_cxp_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Abonos Cobrados Período</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-money-bill-trend-up fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_abonos_cxc_usd)}</h3>
                    <div class="text-success font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_abonos_cxc_bs)}</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Cliente</th>
                        <th class="py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Factura</th>
                        <th class="py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Vencimiento</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Estado / Mora</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Total Factura</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Abonado</th>
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Saldo Pendiente</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.cxc_pendientes && data.cxc_pendientes.length > 0) {
            data.cxc_pendientes.forEach(function (c) {
                const moraBadge = c.dias_mora > 0
                    ? `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger px-2 py-1">${c.dias_mora}d mora</span>`
                    : `<span class="badge bg-success bg-opacity-10 text-success border border-success px-2 py-1">Al día (${c.dias_para_vencer}d)</span>`;

                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5"><strong class="text-dark">${c.cliente_nombre}</strong><br><small class="text-muted">${c.cliente_cedula || 'S/D'}</small></td>
                        <td class="py-2.5">${c.factura_codigo}</td>
                        <td class="py-2.5">${c.fecha_vencimiento}</td>
                        <td class="text-center py-2.5">${moraBadge}</td>
                        <td class="text-end py-2.5">$${formatearNumero(c.monto_total_usd)}</td>
                        <td class="text-end py-2.5 text-success">$${formatearNumero(c.monto_pagado_usd)}</td>
                        <td class="text-end pe-3 py-2.5 fw-bold text-danger">$${formatearNumero(c.saldo_pendiente_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fs-3 text-success mb-2 d-block opacity-75"></i>
                        No hay cuentas pendientes por cobrar en este momento
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);

    } else if (modulo === 'inventario') {
        tituloModalEl.text('Informe de Inventario & Valorización');
        badgeModuloEl.text('INVENTARIO & VALORIZACIÓN');
        periodoEl.text('Existencias al Día • Tasa: ' + formatearNumero(data.kpis?.tasa_cambio) + ' Bs/$');
        tituloTablaEl.html('<i class="fas fa-boxes-stacked text-info me-2"></i>Existencias y Stock Físico');
        conteoEl.text(`${data.inventario?.length || 0} artículos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Artículos / Unidades</span>
                        <div class="rounded-3 bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-boxes-stacked fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">${data.kpis?.total_items || 0}</h3>
                    <div class="text-muted font-monospace mt-1" style="font-size: 0.8rem;">${formatearNumero(data.kpis?.total_unidades, 0)} unidades físicas</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Valor a Costo Base</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-tags fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">$${formatearNumero(data.kpis?.valor_costo_usd)}</h3>
                    <div class="text-primary font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.valor_costo_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Valor Venta Proyectada</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-circle-dollar-to-slot fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.valor_venta_usd)}</h3>
                    <div class="text-success font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.valor_venta_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Margen Proyectado</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-chart-line fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-0 font-monospace" style="color: #d97706 !important;">$${formatearNumero(data.kpis?.margen_proyectado_usd)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Rentabilidad: <strong class="text-dark">${data.kpis?.margen_porcentaje}%</strong></div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Código / Producto</th>
                        <th class="py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Almacén</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Stock</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Costo Unit</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Precio Venta</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Costo Total</th>
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Venta Total</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.inventario && data.inventario.length > 0) {
            data.inventario.forEach(function (i) {
                const alertaBadge = i.es_bajo_stock ? '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger ms-1">Crítico</span>' : '';
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5"><strong class="text-dark">${i.nombre}</strong><br><small class="text-muted">${i.codigo_interno} • ${i.categoria}</small></td>
                        <td class="py-2.5">${i.almacen}</td>
                        <td class="text-center py-2.5 fw-bold ${i.es_bajo_stock ? 'text-danger' : 'text-dark'}">${i.stock_actual} ${alertaBadge}</td>
                        <td class="text-end py-2.5">$${formatearNumero(i.costo_unitario_usd)}</td>
                        <td class="text-end py-2.5 text-primary fw-semibold">$${formatearNumero(i.precio_venta_usd)}</td>
                        <td class="text-end py-2.5">$${formatearNumero(i.costo_subtotal_usd)}</td>
                        <td class="text-end pe-3 py-2.5 fw-bold text-success">$${formatearNumero(i.venta_subtotal_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-box-open fs-3 mb-2 d-block opacity-50"></i>
                        No hay productos registrados en este almacén o categoría
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);

    } else if (modulo === 'rentabilidad') {
        tituloModalEl.text('Informe de Rentabilidad & Utilidad Bruta');
        badgeModuloEl.text('RENTABILIDAD & UTILIDAD');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-chart-pie text-success me-2"></i>Top 20 Productos / Servicios Más Rentables');
        conteoEl.text(`${data.top_productos?.length || 0} ítems`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Ingresos por Ventas</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-arrow-trend-up fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">$${formatearNumero(data.kpis?.total_ingresos_usd)}</h3>
                    <div class="text-primary font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_ingresos_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Costo de Ventas (COGS)</span>
                        <div class="rounded-3 bg-dark bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-calculator fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_costo_usd)}</h3>
                    <div class="text-muted font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_costo_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Ganancia Bruta Real</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-sack-dollar fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.ganancia_bruta_usd)}</h3>
                    <div class="text-success font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.ganancia_bruta_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Margen Bruto</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-percent fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-warning mb-0 font-monospace" style="color: #d97706 !important;">${formatearNumero(data.kpis?.margen_bruto_porcentaje, 1)}%</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Rentabilidad global</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Ítem / Categoría</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Tipo</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Vendidos</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Ingreso Total</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Costo Total</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Ganancia Bruta</th>
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Margen %</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.top_productos && data.top_productos.length > 0) {
            data.top_productos.forEach(function (p) {
                const margenColor = p.margen_porcentaje >= 30 ? 'text-success' : (p.margen_porcentaje >= 15 ? 'text-primary' : 'text-danger');
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5"><strong class="text-dark">${p.nombre}</strong><br><small class="text-muted">${p.categoria}</small></td>
                        <td class="text-center py-2.5"><span class="badge bg-white text-dark border">${p.tipo}</span></td>
                        <td class="text-center py-2.5 fw-bold">${p.cantidad_vendida}</td>
                        <td class="text-end py-2.5">$${formatearNumero(p.ingreso_total_usd)}</td>
                        <td class="text-end py-2.5 text-muted">$${formatearNumero(p.costo_total_usd)}</td>
                        <td class="text-end py-2.5 fw-bold text-success">$${formatearNumero(p.ganancia_bruta_usd)}</td>
                        <td class="text-end pe-3 py-2.5 fw-bold ${margenColor}">${formatearNumero(p.margen_porcentaje, 1)}%</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-chart-simple fs-3 mb-2 d-block opacity-50"></i>
                        No hay ventas registradas en el período seleccionado
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);

    } else if (modulo === 'vendedores') {
        tituloModalEl.text('Informe de Vendedores & Comisiones');
        badgeModuloEl.text('VENDEDORES & ASESORES');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-user-tie text-warning me-2"></i>Rendimiento y Comisiones por Asesor');
        conteoEl.text(`${data.resumen?.length || 0} vendedores`);

        kpisEl.html(`
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Ventas Asesoradas</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-users-viewfinder fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">$${formatearNumero(data.kpis?.total_ventas_usd)}</h3>
                    <div class="text-primary font-monospace fw-semibold mt-1" style="font-size: 0.8rem;">Bs. ${formatearNumero(data.kpis?.total_ventas_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Comisiones Generadas</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-award fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_comisiones_usd)}</h3>
                    <div class="text-muted font-monospace fw-semibold mt-1" style="font-size: 0.76rem;">Pagada: <span class="text-success">$${formatearNumero(data.kpis?.total_comisiones_pagadas_usd)}</span> • Pend: <span class="text-warning">$${formatearNumero(data.kpis?.total_comisiones_pendientes_usd)}</span></div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Asesor Líder</span>
                        <div class="rounded-3 bg-warning bg-opacity-10 text-dark d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-trophy fs-6"></i>
                        </div>
                    </div>
                    <h4 class="fw-bold text-dark mb-0 font-monospace" style="font-size: 1.05rem;">${data.kpis?.top_vendedor || 'N/A'}</h4>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">${data.kpis?.conteo_facturas || 0} facturas procesadas</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Vendedor</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">% Comisión</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Facturas</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Total Vendido ($)</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Total Vendido (Bs)</th>
                        <th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Comisión ($)</th>
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Comisión (Bs)</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.resumen && data.resumen.length > 0) {
            data.resumen.forEach(function (v) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5"><strong class="text-dark">${v.nombre}</strong><br><small class="text-muted">${v.documento || 'S/D'}</small></td>
                        <td class="text-center py-2.5"><span class="badge bg-white text-dark border">${formatearNumero(v.comision_porcentaje, 2)}%</span></td>
                        <td class="text-center py-2.5 fw-bold">${v.conteo_ventas}</td>
                        <td class="text-end py-2.5 fw-bold">$${formatearNumero(v.total_vendido_usd)}</td>
                        <td class="text-end py-2.5">Bs. ${formatearNumero(v.total_vendido_bs)}</td>
                        <td class="text-end py-2.5 fw-bold text-success">$${formatearNumero(v.total_comision_usd)}</td>
                        <td class="text-end pe-3 py-2.5 text-success">Bs. ${formatearNumero(v.total_comision_bs)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="fas fa-user-xmark fs-3 mb-2 d-block opacity-50"></i>
                        No hay ventas registradas por asesores en el período seleccionado
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    } else if (modulo === 'stock_almacenes') {
        tituloModalEl.text('Informe de Existencias por Almacén');
        badgeModuloEl.text('MULTIALMACÉN & STOCK');
        periodoEl.text(`Corte al ${new Date().toLocaleDateString('es-VE')}`);
        tituloTablaEl.html('<i class="fas fa-warehouse text-primary me-2"></i>Matriz de Stock Físico por Almacén');
        conteoEl.text(`${data.items?.length || 0} productos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Productos</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-boxes-stacked fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">${formatearNumero(data.kpis?.total_productos, 0)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Artículos consultados</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Existencia Total</span>
                        <div class="rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-cubes fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success mb-0 font-monospace">${formatearNumero(data.kpis?.total_unidades)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Unidades físicas</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Almacenes</span>
                        <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-warehouse fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">${formatearNumero(data.kpis?.total_almacenes, 0)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Ubicaciones activas</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-muted fw-bold small text-uppercase" style="letter-spacing: 0.5px; font-size: 0.72rem;">Sin Existencia</span>
                        <div class="rounded-3 bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-circle-exclamation fs-6"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-danger mb-0 font-monospace">${formatearNumero(data.kpis?.productos_sin_stock, 0)}</h3>
                    <div class="text-muted mt-1" style="font-size: 0.78rem;">Productos en 0</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Código</th>
                        <th class="py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Producto</th>
                        <th class="py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Categoría</th>
                        <th class="text-center py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">U.M.</th>
        `;

        if (data.almacenes && data.almacenes.length > 0) {
            data.almacenes.forEach(function (alm) {
                htmlTabla += `<th class="text-end py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">${alm.nombre}</th>`;
            });
        }

        htmlTabla += `
                        <th class="text-end pe-3 py-2.5 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Stock Total</th>
                    </tr>
                </thead>
                <tbody>
        `;

        if (data.items && data.items.length > 0) {
            data.items.forEach(function (item) {
                const stockTotalClass = item.stock_total > 0 ? 'text-success' : 'text-danger';
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2.5 fw-bold text-dark">${item.codigo_interno}</td>
                        <td class="py-2.5"><strong class="text-dark">${item.nombre}</strong></td>
                        <td class="py-2.5 text-muted">${item.categoria}</td>
                        <td class="text-center py-2.5"><span class="badge bg-white text-dark border">${item.unidad_medida}</span></td>
                `;

                if (data.almacenes && data.almacenes.length > 0) {
                    data.almacenes.forEach(function (alm) {
                        const stockVal = (item.stocks_por_almacen && item.stocks_por_almacen[alm.id] !== undefined) ? item.stocks_por_almacen[alm.id] : 0;
                        const stockTextClass = stockVal > 0 ? 'fw-bold text-dark' : 'text-muted';
                        htmlTabla += `<td class="text-end py-2.5 ${stockTextClass}">${formatearNumero(stockVal)}</td>`;
                    });
                }

                htmlTabla += `
                        <td class="text-end pe-3 py-2.5 fw-bold ${stockTotalClass}">${formatearNumero(item.stock_total)}</td>
                    </tr>
                `;
            });

            htmlTabla += `
                <tr class="fw-bold bg-white border-top border-2">
                    <td colspan="4" class="ps-3 py-2.5 text-uppercase text-dark">TOTALES CONSOLIDADOS:</td>
            `;

            if (data.almacenes && data.almacenes.length > 0) {
                data.almacenes.forEach(function (alm) {
                    const totalAlm = (data.totales_por_almacen && data.totales_por_almacen[alm.id] !== undefined) ? data.totales_por_almacen[alm.id] : 0;
                    htmlTabla += `<td class="text-end py-2.5 text-dark">${formatearNumero(totalAlm)}</td>`;
                });
            }

            htmlTabla += `
                    <td class="text-end pe-3 py-2.5 text-success">${formatearNumero(data.kpis?.total_unidades)}</td>
                </tr>
            `;
        } else {
            const colspan = 5 + (data.almacenes ? data.almacenes.length : 0);
            htmlTabla += `
                <tr>
                    <td colspan="${colspan}" class="text-center py-4 text-muted">
                        <i class="fas fa-boxes-stacked fs-3 mb-2 d-block opacity-50"></i>
                        No hay existencias que coincidan con los filtros seleccionados
                    </td>
                </tr>
            `;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    } else if (modulo === 'cuadre_diario') {
        tituloModalEl.text('Informe de Cuadre Diario de Cajas & Turnos');
        badgeModuloEl.text('CUADRE & ARQUEO');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-balance-scale text-primary me-2"></i>Turnos y Cierres Auditados');
        conteoEl.text(`${data.turnos?.length || 0} turnos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Ventas Netas</span>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_ventas_neto_usd)}</h3>
                    <div class="text-muted small mt-1">Bruto: $${formatearNumero(data.kpis?.total_ventas_bruto_usd)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Efectivo Esperado</span>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_esperado_usd)}</h3>
                    <div class="text-muted small mt-1">Apertura: $${formatearNumero(data.kpis?.total_apertura_usd)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Efectivo Contado</span>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">$${formatearNumero(data.kpis?.total_contado_usd)}</h3>
                    <div class="text-muted small mt-1">Vueltos: $${formatearNumero(data.kpis?.total_vueltos_usd)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Diferencia Neta</span>
                    <h3 class="fw-bold ${data.kpis?.total_diferencia_usd < 0 ? 'text-danger' : (data.kpis?.total_diferencia_usd > 0 ? 'text-success' : 'text-dark')} mb-0 font-monospace">$${formatearNumero(data.kpis?.total_diferencia_usd)}</h3>
                    <div class="text-muted small mt-1">${data.kpis?.total_diferencia_usd < 0 ? 'Faltante de Caja' : (data.kpis?.total_diferencia_usd > 0 ? 'Sobrante de Caja' : 'Caja Cuadrada')}</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Turno</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Caja / Cajero</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Apertura</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Cierre</th>
                        <th class="text-center py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Estado</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Venta Neta</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Esperado</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Contado</th>
                        <th class="text-end pe-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Diferencia</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.turnos && data.turnos.length > 0) {
            data.turnos.forEach(function (t) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2 fw-bold">#${t.turno_id}</td>
                        <td class="py-2"><strong>${t.caja_nombre}</strong><br><span class="text-muted" style="font-size: 0.72rem;">${t.cajero_nombre}</span></td>
                        <td class="py-2">${t.fecha_apertura} ${t.hora_apertura}</td>
                        <td class="py-2">${t.fecha_cierre} ${t.hora_cierre}</td>
                        <td class="text-center py-2"><span class="badge ${t.estado === 'cerrada' ? 'bg-success' : 'bg-warning text-dark'} rounded-pill px-2 py-0.5">${t.estado.toUpperCase()}</span></td>
                        <td class="text-end py-2 fw-bold text-success">$${formatearNumero(t.ventas_neto_usd)}</td>
                        <td class="text-end py-2 fw-bold">$${formatearNumero(t.esperado_usd)}</td>
                        <td class="text-end py-2">$${formatearNumero(t.contado_usd)}</td>
                        <td class="text-end pe-3 py-2 fw-bold ${t.diferencia_usd < 0 ? 'text-danger' : (t.diferencia_usd > 0 ? 'text-success' : '')}">$${formatearNumero(t.diferencia_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `<tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-cash-register fs-3 mb-2 d-block opacity-50"></i>No hay turnos registrados</td></tr>`;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    } else if (modulo === 'devoluciones_anulaciones') {
        tituloModalEl.text('Informe de Devoluciones y Anulaciones');
        badgeModuloEl.text('AUDITORÍA');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-ban text-danger me-2"></i>Eventos Revertidos');
        conteoEl.text(`${data.items?.length || 0} eventos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Devoluciones</span>
                    <h3 class="fw-bold text-warning mb-0 font-monospace">$${formatearNumero(data.kpis?.total_devoluciones_usd)}</h3>
                    <div class="text-muted small mt-1">${data.kpis?.conteo_devoluciones || 0} devoluciones</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Anulaciones</span>
                    <h3 class="fw-bold text-danger mb-0 font-monospace">$${formatearNumero(data.kpis?.total_anuladas_usd)}</h3>
                    <div class="text-muted small mt-1">${data.kpis?.conteo_anulaciones || 0} facturas anuladas</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Total Revertido ($ USD)</span>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_general_usd)}</h3>
                    <div class="text-muted small mt-1">${data.kpis?.conteo_total || 0} eventos totales</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Total Revertido (Bs.)</span>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">Bs. ${formatearNumero(data.kpis?.total_general_bs)}</h3>
                    <div class="text-muted small mt-1">Tasa: ${formatearNumero(data.kpis?.tasa_cambio)} Bs/$</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Tipo</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Comprobante</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Factura Orig.</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Fecha / Hora</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Usuario</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Cliente</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Motivo</th>
                        <th class="text-end pe-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Monto Revertido</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.items && data.items.length > 0) {
            data.items.forEach(function (i) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2"><span class="badge ${i.tipo === 'Anulación' ? 'bg-danger' : 'bg-warning text-dark'} rounded-pill px-2 py-0.5">${i.tipo}</span></td>
                        <td class="py-2 fw-bold">${i.codigo}</td>
                        <td class="py-2">${i.factura_codigo}</td>
                        <td class="py-2">${i.fecha} ${i.hora}</td>
                        <td class="py-2">${i.usuario}</td>
                        <td class="py-2">${i.cliente}</td>
                        <td class="py-2">${i.motivo}</td>
                        <td class="text-end pe-3 py-2 fw-bold text-danger">-$${formatearNumero(i.monto_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `<tr><td colspan="8" class="text-center py-4 text-muted"><i class="fas fa-ban fs-3 mb-2 d-block opacity-50"></i>No hay devoluciones ni anulaciones registradas</td></tr>`;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    } else if (modulo === 'flujo_caja') {
        tituloModalEl.text('Informe de Flujo Real de Caja');
        badgeModuloEl.text('FLUJO DE CAJA');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-credit-card text-success me-2"></i>Flujo por Método de Pago');
        conteoEl.text(`${data.desglose_metodos?.length || 0} métodos`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Total Entradas</span>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_entradas_usd)}</h3>
                    <div class="text-success small mt-1">Bs. ${formatearNumero(data.kpis?.total_entradas_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Total Salidas</span>
                    <h3 class="fw-bold text-danger mb-0 font-monospace">$${formatearNumero(data.kpis?.total_salidas_usd)}</h3>
                    <div class="text-danger small mt-1">Vueltos + Reembolsos</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Flujo Neto Real</span>
                    <h3 class="fw-bold ${data.kpis?.flujo_neto_usd >= 0 ? 'text-success' : 'text-danger'} mb-0 font-monospace">$${formatearNumero(data.kpis?.flujo_neto_usd)}</h3>
                    <div class="text-muted small mt-1">Bs. ${formatearNumero(data.kpis?.flujo_neto_bs)}</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Método de Pago</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Entradas ($)</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Entradas (Bs.)</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Salidas ($)</th>
                        <th class="text-end pe-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Flujo Neto ($)</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.desglose_metodos && data.desglose_metodos.length > 0) {
            data.desglose_metodos.forEach(function (m) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2 fw-bold">${m.nombre}</td>
                        <td class="text-end py-2 text-success fw-bold">$${formatearNumero(m.entradas_usd)}</td>
                        <td class="text-end py-2">Bs. ${formatearNumero(m.entradas_bs)}</td>
                        <td class="text-end py-2 text-danger">-$${formatearNumero(m.salidas_usd)}</td>
                        <td class="text-end pe-3 py-2 fw-bold ${m.neto_usd >= 0 ? 'text-success' : 'text-danger'}">$${formatearNumero(m.neto_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `<tr><td colspan="5" class="text-center py-4 text-muted"><i class="fas fa-money-bill-transfer fs-3 mb-2 d-block opacity-50"></i>Sin movimientos</td></tr>`;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    } else if (modulo === 'libro_ventas') {
        tituloModalEl.text('Libro de Ventas Fiscal (Sin IGTF)');
        badgeModuloEl.text('LIBRO FISCAL');
        periodoEl.text(data.kpis?.periodo_texto || 'Período Seleccionado');
        tituloTablaEl.html('<i class="fas fa-book-bookmark text-dark me-2"></i>Facturas y Notas de Crédito');
        conteoEl.text(`${data.items?.length || 0} registros`);

        kpisEl.html(`
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Total Facturado</span>
                    <h3 class="fw-bold text-dark mb-0 font-monospace">$${formatearNumero(data.kpis?.total_facturado_usd)}</h3>
                    <div class="text-primary small mt-1">Bs. ${formatearNumero(data.kpis?.total_facturado_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Base Imponible Gravada</span>
                    <h3 class="fw-bold text-primary mb-0 font-monospace">$${formatearNumero(data.kpis?.total_base_imponible_usd)}</h3>
                    <div class="text-muted small mt-1">Bs. ${formatearNumero(data.kpis?.total_base_imponible_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">IVA Causado (16%)</span>
                    <h3 class="fw-bold text-success mb-0 font-monospace">$${formatearNumero(data.kpis?.total_iva_usd)}</h3>
                    <div class="text-success small mt-1">Bs. ${formatearNumero(data.kpis?.total_iva_bs)}</div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card border rounded-4 p-3 bg-white shadow-xs h-100">
                    <span class="text-muted fw-bold small text-uppercase" style="font-size: 0.72rem;">Ventas Exentas</span>
                    <h3 class="fw-bold text-secondary mb-0 font-monospace">$${formatearNumero(data.kpis?.total_exento_usd)}</h3>
                    <div class="text-muted small mt-1">Bs. ${formatearNumero(data.kpis?.total_exento_bs)}</div>
                </div>
            </div>
        `);

        let htmlTabla = `
            <table class="table table-hover align-middle mb-0 font-monospace small">
                <thead style="background-color: #0f172a;">
                    <tr>
                        <th class="ps-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Fecha</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Tipo Doc.</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Factura / Control</th>
                        <th class="py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Cliente / RIF</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Total ($)</th>
                        <th class="text-end py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">Base Imp. ($)</th>
                        <th class="text-end pe-3 py-2 text-white fw-bold text-uppercase" style="font-size: 0.72rem;">IVA ($)</th>
                    </tr>
                </thead>
                <tbody>
        `;
        if (data.items && data.items.length > 0) {
            data.items.forEach(function (i) {
                htmlTabla += `
                    <tr class="border-bottom">
                        <td class="ps-3 py-2">${i.fecha}</td>
                        <td class="py-2 fw-bold ${i.tipo_doc.includes('Nota') ? 'text-danger' : ''}">${i.tipo_doc}</td>
                        <td class="py-2"><strong>${i.codigo}</strong><br><span class="text-muted" style="font-size: 0.72rem;">Ctrl: ${i.numero_control}</span></td>
                        <td class="py-2">${i.cliente_nombre}<br><span class="text-muted" style="font-size: 0.72rem;">${i.cliente_rif}</span></td>
                        <td class="text-end py-2 fw-bold ${i.total_usd < 0 ? 'text-danger' : ''}">$${formatearNumero(i.total_usd)}</td>
                        <td class="text-end py-2 ${i.base_imponible_usd < 0 ? 'text-danger' : ''}">$${formatearNumero(i.base_imponible_usd)}</td>
                        <td class="text-end pe-3 py-2 fw-bold ${i.iva_usd < 0 ? 'text-danger' : 'text-success'}">$${formatearNumero(i.iva_usd)}</td>
                    </tr>
                `;
            });
        } else {
            htmlTabla += `<tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-book-bookmark fs-3 mb-2 d-block opacity-50"></i>No hay registros fiscales</td></tr>`;
        }
        htmlTabla += '</tbody></table>';
        tablaEl.html(htmlTabla);
    }
};

const formatearNumero = function (val, dec = 2) {
    const num = parseFloat(val) || 0;
    return num.toLocaleString('es-VE', {
        minimumFractionDigits: dec,
        maximumFractionDigits: dec
    });
};

const limpiarSelectorModerno = function (selector) {
    const el = $(selector);
    if (el.length) {
        el.val(null).trigger('change');
    }
};

$(document).ready(function () {
    if ($.fn.select2) {
        $('#stock_producto_ids').select2({
            placeholder: 'Buscar productos por código o nombre...',
            allowClear: false,
            width: '100%'
        }).on('change', function () {
            const val = $(this).val();
            const count = Array.isArray(val) ? val.length : 0;
            $('#badgeContadorProductos').text(count > 0 ? `${count} seleccionados` : 'Todos');
        });

        $('#stock_categoria_ids').select2({
            placeholder: 'Todas las categorías...',
            allowClear: false,
            width: '100%'
        }).on('change', function () {
            const val = $(this).val();
            const count = Array.isArray(val) ? val.length : 0;
            $('#badgeContadorCategorias').text(count > 0 ? `${count} seleccionadas` : 'Todas');
        });
    }
});
