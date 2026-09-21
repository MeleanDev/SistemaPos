/**
 * Módulo de Cuentas por Cobrar (CXC) - Frontend Controller
 * Estándar Ejecutivo UI/UX con Algoritmo FIFO y Abono Específico
 */

let catalogoMetodosPago = [];
let tasaCambioActiva = 1.0;
let clienteSeleccionadoActual = null;
let facturasPendientesActuales = [];
let tablaCxc = null;

document.addEventListener('DOMContentLoaded', function () {
    cargarCatalogos();
    inicializarTabla();

    // Eventos de Formularios
    const formEspecifico = document.getElementById('formAbonarFactura');
    if (formEspecifico) {
        formEspecifico.addEventListener('submit', procesarAbonoEspecifico);
    }

    const formGeneral = document.getElementById('formAbonoGeneral');
    if (formGeneral) {
        formGeneral.addEventListener('submit', procesarAbonoGeneral);
    }
});

/**
 * Cargar métodos de pago y tasa de cambio desde el servidor
 */
function cargarCatalogos() {
    fetch('/cuentas-por-cobrar/catalogos')
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                catalogoMetodosPago = res.data.metodos_pago || [];
                tasaCambioActiva = parseFloat(res.data.tasa_usd) || 1.0;
                poblarSelectsMetodosPago();
            }
        })
        .catch(err => console.error('Error al cargar catálogos CXC:', err));
}

function poblarSelectsMetodosPago() {
    const selects = ['abono_metodo_pago_id', 'gen_metodo_pago_id'];
    selects.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione forma de pago...</option>';
        catalogoMetodosPago.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = `${m.nombre} (${m.tipo.toUpperCase()})`;
            select.appendChild(opt);
        });
    });
}

/**
 * Inicializar y cargar DataTables de clientes con deudas
 */
function inicializarTabla() {
    cargarResumen();
}

function recargarTabla() {
    cargarResumen();
}

function cargarResumen() {
    fetch('/cuentas-por-cobrar/lista')
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo cargar la información.', 'error');
                return;
            }

            const kpis = res.data.kpis;
            const clientes = res.data.clientes || [];

            // Actualizar KPIs
            document.getElementById('kpi_total_usd').textContent = `$${formatearNumero(kpis.total_por_cobrar_usd)}`;
            document.getElementById('kpi_total_bs').textContent = `Bs. ${formatearNumero(kpis.total_por_cobrar_bs)}`;
            document.getElementById('kpi_clientes_count').textContent = kpis.clientes_deudores_count;
            document.getElementById('kpi_facturas_count').textContent = kpis.facturas_pendientes_count;
            document.getElementById('kpi_vencidas_count').textContent = kpis.facturas_vencidas_count;
            document.getElementById('kpi_vencidas_monto').textContent = `$${formatearNumero(kpis.total_vencido_usd)} en mora`;

            if (kpis.tasa_cambio) {
                tasaCambioActiva = parseFloat(kpis.tasa_cambio);
            }

            renderizarTablaClientes(clientes);
        })
        .catch(err => {
            console.error('Error al cargar resumen CXC:', err);
        });
}

function renderizarTablaClientes(clientes) {
    if ($.fn.DataTable.isDataTable('#tabla_cxc_clientes')) {
        $('#tabla_cxc_clientes').DataTable().destroy();
    }

    const tbody = document.querySelector('#tabla_cxc_clientes tbody');
    tbody.innerHTML = '';

    clientes.forEach(c => {
        let alertaBadge = '';
        if (c.estado_alerta === 'vencida') {
            alertaBadge = '<span class="badge bg-danger rounded-pill px-3 py-1 fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Vencida</span>';
        } else if (c.estado_alerta === 'por_vencer') {
            alertaBadge = '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><i class="fas fa-clock me-1"></i> Por Vencer</span>';
        } else {
            alertaBadge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i> Al Día</span>';
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">${c.cliente_nombre}</div>
                        <span class="badge bg-light text-muted border rounded-pill px-2 py-0 small">${c.cliente_cedula}</span>
                        <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-2 py-0 small">${c.cliente_tipo.toUpperCase()}</span>
                    </div>
                </div>
            </td>
            <td><i class="fas fa-phone-alt text-muted me-1 small"></i> ${c.cliente_telefono}</td>
            <td class="text-center">
                <span class="badge bg-warning bg-opacity-25 text-dark fw-bold rounded-pill px-3 py-1 fs-6">
                    ${c.total_facturas_pendientes}
                </span>
            </td>
            <td>
                <div class="fw-semibold text-dark">${c.factura_mas_antigua_fecha}</div>
                <small class="text-muted">${c.dias_antiguedad} día(s) transcurridos</small>
            </td>
            <td>${alertaBadge}</td>
            <td class="text-end fw-bold text-danger fs-6">$${formatearNumero(c.total_deuda_usd)}</td>
            <td class="text-end fw-bold text-dark">Bs. ${formatearNumero(c.total_deuda_bs)}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-none fw-bold" onclick="abrirDetalleCliente(${c.cliente_id})" title="Ver facturas del cliente">
                        <i class="fas fa-eye me-1"></i> Ver Facturas
                    </button>
                    <button class="btn btn-sm btn-success rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonoGeneral(${c.cliente_id})" title="Abono General FIFO">
                        <i class="fas fa-coins me-1"></i> Abono Rápido
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    tablaCxc = $('#tabla_cxc_clientes').DataTable({
        responsive: true,
        order: [[5, 'desc']], // Ordenar por mayor deuda en USD
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
    });
}

/**
 * Abrir Modal Nivel 2: Explorador de Facturas del Cliente
 */
function abrirDetalleCliente(clienteId) {
    fetch(`/cuentas-por-cobrar/cliente/${clienteId}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo obtener el estado de cuenta.', 'error');
                return;
            }

            const data = res.data;
            clienteSeleccionadoActual = data.cliente;
            facturasPendientesActuales = data.facturas_pendientes || [];

            // Llenar Cabecera y KPIs
            document.getElementById('det_cliente_nombre').textContent = data.cliente.nombre;
            document.getElementById('det_cliente_cedula').textContent = data.cliente.cedula;
            document.getElementById('det_cliente_contacto').textContent = `Teléfono: ${data.cliente.telefono} | Correo: ${data.cliente.correo} | Límite: $${formatearNumero(data.cliente.limite_credito)}`;
            document.getElementById('det_deuda_total_usd').textContent = `$${formatearNumero(data.cliente.total_deuda_usd)}`;
            document.getElementById('det_deuda_total_bs').textContent = `Bs. ${formatearNumero(data.cliente.total_deuda_bs)}`;
            document.getElementById('det_facturas_pendientes_count').textContent = facturasPendientesActuales.length;
            document.getElementById('det_limite_credito').textContent = `$${formatearNumero(data.cliente.limite_credito)}`;
            document.getElementById('det_dias_credito').textContent = `${data.cliente.dias_credito} días de plazo`;
            document.getElementById('det_tasa_bcv').textContent = `${formatearNumero(data.tasa_cambio, 2)} Bs/$`;

            document.getElementById('badge_count_pendientes').textContent = facturasPendientesActuales.length;
            document.getElementById('badge_count_pagadas').textContent = (data.facturas_pagadas || []).length;

            // Configurar botón de Abono General
            const btnAbonoGen = document.getElementById('btn_abono_general_cliente');
            btnAbonoGen.onclick = () => {
                const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalleCliente'));
                if (modalDetalle) modalDetalle.hide();
                abrirModalAbonoGeneral(clienteId);
            };

            // Renderizar Facturas Pendientes
            renderizarFacturasPendientes(facturasPendientesActuales);

            // Renderizar Facturas Pagadas
            renderizarFacturasPagadas(data.facturas_pagadas || []);

            const modal = new bootstrap.Modal(document.getElementById('modalDetalleCliente'));
            modal.show();
        })
        .catch(err => {
            console.error('Error al abrir detalle:', err);
            Swal.fire('Error', 'No se pudo cargar el detalle del cliente.', 'error');
        });
}

function renderizarFacturasPendientes(facturas) {
    const tbody = document.getElementById('tbody_facturas_pendientes');
    tbody.innerHTML = '';

    if (facturas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success fs-4 d-block mb-2"></i>El cliente no tiene facturas pendientes.</td></tr>';
        return;
    }

    facturas.forEach(f => {
        let badgeMora = '';
        if (f.es_vencida) {
            badgeMora = `<span class="badge bg-danger rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-exclamation-triangle me-1"></i> ${Math.abs(f.dias_vencimiento)}d vencida</span>`;
        } else if (f.dias_vencimiento <= 3) {
            badgeMora = `<span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-clock me-1"></i> Vence en ${f.dias_vencimiento}d</span>`;
        } else {
            badgeMora = `<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small fw-bold"><i class="fas fa-calendar-check me-1"></i> ${f.dias_vencimiento}d restantes</span>`;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4">
                <div class="fw-bold text-dark">${f.numero_factura}</div>
                <small class="text-muted">Venta: ${f.venta_codigo}</small>
            </td>
            <td><i class="far fa-calendar-alt text-muted me-1"></i> ${f.fecha_emision}</td>
            <td><i class="far fa-calendar-times text-muted me-1"></i> ${f.fecha_vencimiento}</td>
            <td>${badgeMora}</td>
            <td class="text-end fw-semibold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
            <td class="text-end fw-semibold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
            <td class="text-end fw-bold text-danger fs-6">
                <div>$${formatearNumero(f.saldo_pendiente_usd)}</div>
                <small class="text-muted">Bs. ${formatearNumero(f.saldo_pendiente_bs)}</small>
            </td>
            <td class="text-center pe-4">
                <div class="d-flex justify-content-center gap-1">
                    <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonoFactura(${f.id}, '${f.numero_factura}', ${f.saldo_pendiente_usd}, ${f.saldo_pendiente_bs})">
                        <i class="fas fa-hand-holding-usd me-1"></i> Abonar
                    </button>
                    ${f.abonos_count > 0 ? `
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick='abrirHistorialAbonosFactura(${JSON.stringify(f.abonos)}, "${f.numero_factura}")' title="Ver historial de abonos">
                            <i class="fas fa-history"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarFacturasPagadas(facturas) {
    const tbody = document.getElementById('tbody_facturas_pagadas');
    tbody.innerHTML = '';

    if (facturas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No hay facturas pagadas en el historial reciente.</td></tr>';
        return;
    }

    facturas.forEach(f => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="ps-4">
                <div class="fw-bold text-dark">${f.numero_factura}</div>
                <small class="text-muted">Venta: ${f.venta_codigo}</small>
            </td>
            <td>${f.fecha_emision}</td>
            <td>${f.fecha_vencimiento}</td>
            <td class="text-end fw-semibold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
            <td class="text-end fw-semibold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
            <td class="text-center">
                <span class="badge bg-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-double me-1"></i> 100% Pagada</span>
            </td>
            <td class="text-center pe-4">
                ${f.abonos && f.abonos.length > 0 ? `
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick='abrirHistorialAbonosFactura(${JSON.stringify(f.abonos)}, "${f.numero_factura}")' title="Ver abonos">
                        <i class="fas fa-history"></i>
                    </button>
                ` : '<span class="text-muted small">-</span>'}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Abrir Modal Operativo 1: Abonar a Factura Específica
 */
let saldoFacturaActualUsd = 0;
let saldoFacturaActualBs = 0;

function abrirModalAbonoFactura(cuentaId, numeroFactura, saldoUsd, saldoBs) {
    saldoFacturaActualUsd = parseFloat(saldoUsd);
    saldoFacturaActualBs = parseFloat(saldoBs);

    document.getElementById('abono_cuenta_id').value = cuentaId;
    document.getElementById('abono_tasa_cambio').value = tasaCambioActiva;
    document.getElementById('abono_factura_label').textContent = `Factura #${numeroFactura}`;
    document.getElementById('abono_saldo_label').textContent = `$${formatearNumero(saldoFacturaActualUsd)}`;
    document.getElementById('abono_saldo_bs_label').textContent = `Bs. ${formatearNumero(saldoFacturaActualBs)}`;

    document.getElementById('abono_monto').value = '';
    document.getElementById('abono_moneda').value = 'USD';
    document.getElementById('abono_moneda_sym').textContent = '$';
    document.getElementById('abono_referencia').value = '';
    document.getElementById('abono_observaciones').value = '';
    document.getElementById('abono_equivalente_label').textContent = `Equivalente: Bs. 0.00`;

    poblarSelectsMetodosPago();

    const modal = new bootstrap.Modal(document.getElementById('modalAbonarFactura'));
    modal.show();
}

function recalcularAbonoEspecifico() {
    const moneda = document.getElementById('abono_moneda').value;
    const monto = parseFloat(document.getElementById('abono_monto').value) || 0;
    const sym = document.getElementById('abono_moneda_sym');
    const eqLabel = document.getElementById('abono_equivalente_label');

    if (moneda === 'USD') {
        sym.textContent = '$';
        const bs = round(monto * tasaCambioActiva, 2);
        eqLabel.textContent = `Equivalente: Bs. ${formatearNumero(bs)}`;
    } else {
        sym.textContent = 'Bs';
        const usd = tasaCambioActiva > 0 ? round(monto / tasaCambioActiva, 2) : 0;
        eqLabel.textContent = `Equivalente: $${formatearNumero(usd)}`;
    }
}

function pagarTotalidadFactura() {
    const moneda = document.getElementById('abono_moneda').value;
    if (moneda === 'USD') {
        document.getElementById('abono_monto').value = saldoFacturaActualUsd.toFixed(2);
    } else {
        document.getElementById('abono_monto').value = (saldoFacturaActualUsd * tasaCambioActiva).toFixed(2);
    }
    recalcularAbonoEspecifico();
}

/**
 * Procesar formulario de Abono Específico
 */
function procesarAbonoEspecifico(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_guardar_abono_especifico');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

    const formData = new FormData(document.getElementById('formAbonarFactura'));

    fetch('/cuentas-por-cobrar/abonar-factura', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo registrar el abono.', 'error');
                return;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAbonarFactura'));
            if (modal) modal.hide();

            Swal.fire({
                title: '¡Abono Registrado!',
                text: res.message,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#059669',
            }).then((result) => {
                if (result.isConfirmed && res.data.abono_id) {
                    abrirVentanaTicket(`/cuentas-por-cobrar/ticket/${res.data.abono_id}`);
                }
            });

            // Recargar datos
            cargarResumen();
            if (clienteSeleccionadoActual) {
                abrirDetalleCliente(clienteSeleccionadoActual.id);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            Swal.fire('Error', 'Ocurrió un fallo en el servidor.', 'error');
        });
}

/**
 * Abrir Modal Operativo 2: Abono General FIFO
 */
function abrirModalAbonoGeneral(clienteId) {
    fetch(`/cuentas-por-cobrar/cliente/${clienteId}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo consultar el cliente.', 'error');
                return;
            }

            clienteSeleccionadoActual = res.data.cliente;
            facturasPendientesActuales = res.data.facturas_pendientes || [];

            if (facturasPendientesActuales.length === 0) {
                Swal.fire('Aviso', 'Este cliente no tiene facturas con saldo pendiente.', 'info');
                return;
            }

            document.getElementById('gen_cliente_id').value = clienteSeleccionadoActual.id;
            document.getElementById('gen_tasa_cambio').value = tasaCambioActiva;
            document.getElementById('gen_cliente_label').textContent = `${clienteSeleccionadoActual.nombre} (${clienteSeleccionadoActual.cedula})`;
            document.getElementById('gen_deuda_total_label').textContent = `$${formatearNumero(clienteSeleccionadoActual.total_deuda_usd)}`;
            document.getElementById('gen_deuda_total_bs_label').textContent = `Bs. ${formatearNumero(clienteSeleccionadoActual.total_deuda_bs)}`;

            document.getElementById('gen_monto').value = '';
            document.getElementById('gen_moneda').value = 'USD';
            document.getElementById('gen_moneda_sym').textContent = '$';
            document.getElementById('gen_referencia').value = '';
            document.getElementById('gen_observaciones').value = '';
            document.getElementById('gen_equivalente_label').textContent = `Equivalente: Bs. 0.00`;

            poblarSelectsMetodosPago();
            recalcularAbonoGeneral();

            const modal = new bootstrap.Modal(document.getElementById('modalAbonoGeneral'));
            modal.show();
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire('Error', 'No se pudo abrir el abono general.', 'error');
        });
}

function recalcularAbonoGeneral() {
    const moneda = document.getElementById('gen_moneda').value;
    const monto = parseFloat(document.getElementById('gen_monto').value) || 0;
    const sym = document.getElementById('gen_moneda_sym');
    const eqLabel = document.getElementById('gen_equivalente_label');

    let montoUsd = 0;
    if (moneda === 'USD') {
        sym.textContent = '$';
        montoUsd = monto;
        const bs = round(monto * tasaCambioActiva, 2);
        eqLabel.textContent = `Equivalente: Bs. ${formatearNumero(bs)}`;
    } else {
        sym.textContent = 'Bs';
        montoUsd = tasaCambioActiva > 0 ? round(monto / tasaCambioActiva, 2) : 0;
        eqLabel.textContent = `Equivalente: $${formatearNumero(montoUsd)}`;
    }

    // Simular Cascada FIFO en la tabla de previsualización
    simularCascadaFifo(montoUsd);
}

function simularCascadaFifo(montoTotalUsd) {
    const tbody = document.getElementById('tbody_preview_fifo');
    const badgeResumen = document.getElementById('gen_preview_resumen');
    tbody.innerHTML = '';

    if (montoTotalUsd <= 0 || facturasPendientesActuales.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Ingresa un monto para ver la cascada de amortización</td></tr>';
        badgeResumen.textContent = '0 facturas cubiertas';
        return;
    }

    let restanteUsd = montoTotalUsd;
    let cubiertasCompletas = 0;
    let facturasAfectadasCount = 0;

    facturasPendientesActuales.forEach(f => {
        if (restanteUsd <= 0.001) return;

        const saldoActual = parseFloat(f.saldo_pendiente_usd);
        const aplicar = Math.min(restanteUsd, saldoActual);
        const nuevoSaldo = Math.max(0, saldoActual - aplicar);
        const quedaPagada = nuevoSaldo <= 0.001;

        if (quedaPagada) cubiertasCompletas++;
        facturasAfectadasCount++;

        const tr = document.createElement('tr');
        tr.className = quedaPagada ? 'table-success bg-opacity-25' : 'table-warning bg-opacity-25';
        tr.innerHTML = `
            <td class="fw-bold">${f.numero_factura}</td>
            <td>${f.fecha_emision}</td>
            <td class="text-end">$${formatearNumero(saldoActual)}</td>
            <td class="text-end fw-bold text-success">+$${formatearNumero(aplicar)}</td>
            <td class="text-end fw-bold ${quedaPagada ? 'text-success' : 'text-danger'}">$${formatearNumero(nuevoSaldo)}</td>
            <td class="text-center">
                ${quedaPagada
                    ? '<span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i> Quedará Pagada (100%)</span>'
                    : '<span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-adjust me-1"></i> Abono Parcial</span>'}
            </td>
        `;
        tbody.appendChild(tr);

        restanteUsd -= aplicar;
    });

    badgeResumen.textContent = `${facturasAfectadasCount} factura(s) impactadas (${cubiertasCompletas} al 100%)`;
}

function pagarDeudaTotalGeneral() {
    if (!clienteSeleccionadoActual) return;
    const moneda = document.getElementById('gen_moneda').value;
    const deudaUsd = parseFloat(clienteSeleccionadoActual.total_deuda_usd);

    if (moneda === 'USD') {
        document.getElementById('gen_monto').value = deudaUsd.toFixed(2);
    } else {
        document.getElementById('gen_monto').value = (deudaUsd * tasaCambioActiva).toFixed(2);
    }
    recalcularAbonoGeneral();
}

/**
 * Procesar formulario de Abono General FIFO
 */
function procesarAbonoGeneral(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_guardar_abono_general');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando FIFO...';

    const formData = new FormData(document.getElementById('formAbonoGeneral'));

    fetch('/cuentas-por-cobrar/abonar-general', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
        },
        body: formData,
    })
        .then(response => response.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = originalText;

            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo procesar el abono general.', 'error');
                return;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAbonoGeneral'));
            if (modal) modal.hide();

            Swal.fire({
                title: '¡Abono FIFO Aplicado!',
                text: res.message,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#059669',
            }).then((result) => {
                if (result.isConfirmed && res.data.primer_abono_id) {
                    abrirVentanaTicket(`/cuentas-por-cobrar/ticket/${res.data.primer_abono_id}`);
                }
            });

            // Recargar
            cargarResumen();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            Swal.fire('Error', 'Ocurrió un fallo al procesar el abono general.', 'error');
        });
}

/**
 * Abrir Modal Informativo de Historial de Abonos de una Factura
 */
function abrirHistorialAbonosFactura(abonos, numeroFactura) {
    document.getElementById('hist_modal_title').textContent = `Historial de Abonos - Factura #${numeroFactura}`;
    const tbody = document.getElementById('tbody_historial_abonos');
    tbody.innerHTML = '';

    if (!abonos || abonos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No hay abonos registrados para esta factura.</td></tr>';
    } else {
        abonos.forEach(a => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><i class="far fa-calendar-alt text-muted me-1"></i> ${a.fecha}</td>
                <td><span class="badge bg-light text-dark border rounded-pill px-2 py-1">${a.metodo_pago}</span></td>
                <td><small class="text-muted">${a.referencia || 'N/A'}</small></td>
                <td class="text-end fw-bold text-success">+$${formatearNumero(a.monto_usd)}</td>
                <td class="text-end text-dark">Bs. ${formatearNumero(a.monto_bs)}</td>
                <td><small class="text-muted"><i class="fas fa-user-edit me-1"></i> ${a.usuario || 'Sistema'}</small></td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-dark rounded-pill px-2" onclick="abrirVentanaTicket('/cuentas-por-cobrar/ticket/${a.id}')" title="Imprimir Ticket">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('modalHistorialAbonos'));
    modal.show();
}

/**
 * Abrir ventana de impresión térmica de comprobante
 */
function abrirVentanaTicket(url) {
    const w = 420;
    const h = 600;
    const left = (screen.width / 2) - (w / 2);
    const top = (screen.height / 2) - (h / 2);
    window.open(url, 'TicketAbonoCXC', `width=${w},height=${h},top=${top},left=${left},scrollbars=yes,status=no`);
}

/**
 * Utilidades numéricas
 */
function formatearNumero(valor, decimales = 2) {
    const num = parseFloat(valor) || 0;
    return num.toLocaleString('es-VE', {
        minimumFractionDigits: decimales,
        maximumFractionDigits: decimales,
    });
}

function round(valor, decimales) {
    return Number(Math.round(valor + 'e' + decimales) + 'e-' + decimales);
}
