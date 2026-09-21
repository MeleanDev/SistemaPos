/**
 * Módulo de Cuentas por Pagar (CXP) - Frontend Controller
 * Estándar Ejecutivo UI/UX con Algoritmo FIFO y Pago Específico a Proveedores
 */

let catalogoMetodosPagoCxp = [];
let tasaCambioActivaCxp = 1.0;
let proveedorSeleccionadoActual = null;
let facturasPendientesActualesCxp = [];
let tablaCxp = null;

document.addEventListener('DOMContentLoaded', function () {
    cargarCatalogosCxp();
    inicializarTablaCxp();

    // Eventos de Formularios
    const formEspecifico = document.getElementById('formAbonarFacturaCxp');
    if (formEspecifico) {
        formEspecifico.addEventListener('submit', procesarAbonoEspecificoCxp);
    }

    const formGeneral = document.getElementById('formAbonoGeneralCxp');
    if (formGeneral) {
        formGeneral.addEventListener('submit', procesarAbonoGeneralCxp);
    }
});

/**
 * Cargar métodos de pago y tasa de cambio desde el servidor
 */
function cargarCatalogosCxp() {
    fetch('/cuentas-por-pagar/catalogos')
        .then(response => response.json())
        .then(res => {
            if (res.success) {
                catalogoMetodosPagoCxp = res.data.metodos_pago || [];
                tasaCambioActivaCxp = parseFloat(res.data.tasa_usd) || 1.0;
                poblarSelectsMetodosPagoCxp();
            }
        })
        .catch(err => console.error('Error al cargar catálogos CXP:', err));
}

function poblarSelectsMetodosPagoCxp() {
    const selects = ['abono_cxp_metodo_pago_id', 'gen_cxp_metodo_pago_id'];
    selects.forEach(id => {
        const select = document.getElementById(id);
        if (!select) return;
        select.innerHTML = '<option value="">Seleccione forma de pago...</option>';
        catalogoMetodosPagoCxp.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = `${m.nombre} (${m.tipo.toUpperCase()})`;
            select.appendChild(opt);
        });
    });
}

/**
 * Inicializar y cargar DataTables de proveedores con deudas
 */
function inicializarTablaCxp() {
    cargarResumenCxp();
}

function recargarTabla() {
    cargarResumenCxp();
}

function cargarResumenCxp() {
    fetch('/cuentas-por-pagar/lista')
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo cargar la información.', 'error');
                return;
            }

            const kpis = res.data.kpis;
            const proveedores = res.data.proveedores || [];

            // Actualizar KPIs
            document.getElementById('kpi_total_usd').textContent = `$${formatearNumero(kpis.total_por_pagar_usd)}`;
            document.getElementById('kpi_total_bs').textContent = `Bs. ${formatearNumero(kpis.total_por_pagar_bs)}`;
            document.getElementById('kpi_proveedores_count').textContent = kpis.proveedores_deudores_count;
            document.getElementById('kpi_facturas_count').textContent = kpis.facturas_pendientes_count;
            document.getElementById('kpi_vencidas_count').textContent = kpis.facturas_vencidas_count;
            document.getElementById('kpi_vencidas_monto').textContent = `$${formatearNumero(kpis.total_vencido_usd)} en mora`;

            if (kpis.tasa_cambio) {
                tasaCambioActivaCxp = parseFloat(kpis.tasa_cambio);
            }

            renderizarTablaProveedores(proveedores);
        })
        .catch(err => {
            console.error('Error al cargar resumen CXP:', err);
        });
}

function renderizarTablaProveedores(proveedores) {
    if ($.fn.DataTable.isDataTable('#tabla_cxp_proveedores')) {
        $('#tabla_cxp_proveedores').DataTable().destroy();
    }

    const tbody = document.querySelector('#tabla_cxp_proveedores tbody');
    tbody.innerHTML = '';

    proveedores.forEach(p => {
        let alertaBadge = '';
        if (p.estado_alerta === 'vencida') {
            alertaBadge = '<span class="badge bg-danger rounded-pill px-3 py-1 fw-bold"><i class="fas fa-exclamation-circle me-1"></i> Vencida</span>';
        } else if (p.estado_alerta === 'por_vencer') {
            alertaBadge = '<span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold"><i class="fas fa-clock me-1"></i> Por Vencer</span>';
        } else {
            alertaBadge = '<span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i> Al Día</span>';
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-light text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 38px; height: 38px;">
                        <i class="fas fa-truck-moving"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">${p.proveedor_nombre}</div>
                        <span class="badge bg-light text-muted border rounded-pill px-2 py-0 small">${p.proveedor_rif}</span>
                    </div>
                </div>
            </td>
            <td>
                <div><i class="fas fa-user-circle text-muted me-1 small"></i> ${p.proveedor_contacto}</div>
                <small class="text-muted"><i class="fas fa-phone-alt me-1"></i> ${p.proveedor_telefono}</small>
            </td>
            <td class="text-center">
                <span class="badge bg-warning bg-opacity-25 text-dark fw-bold rounded-pill px-3 py-1 fs-6">
                    ${p.total_facturas_pendientes}
                </span>
            </td>
            <td>
                <div class="fw-semibold text-dark">${p.factura_mas_antigua_fecha}</div>
                <small class="text-muted">${p.dias_antiguedad} día(s) transcurridos</small>
            </td>
            <td>${alertaBadge}</td>
            <td class="text-end fw-bold text-danger fs-6">$${formatearNumero(p.total_deuda_usd)}</td>
            <td class="text-end fw-bold text-dark">Bs. ${formatearNumero(p.total_deuda_bs)}</td>
            <td class="text-center">
                <div class="d-flex justify-content-center gap-2">
                    <button class="btn btn-sm btn-outline-info rounded-pill px-3 shadow-none fw-bold" onclick="abrirDetalleProveedor(${p.proveedor_id})" title="Ver facturas pendientes">
                        <i class="fas fa-eye me-1"></i> Ver Facturas
                    </button>
                    <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonoGeneralCxp(${p.proveedor_id})" title="Abono General FIFO">
                        <i class="fas fa-coins me-1"></i> Pagar Rápido
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });

    tablaCxp = $('#tabla_cxp_proveedores').DataTable({
        responsive: true,
        order: [[5, 'desc']], // Ordenar por mayor deuda en USD
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 10,
    });
}

/**
 * Abrir Modal Nivel 2: Explorador de Facturas de Proveedor
 */
function abrirDetalleProveedor(proveedorId) {
    fetch(`/cuentas-por-pagar/proveedor/${proveedorId}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo obtener el estado de cuenta.', 'error');
                return;
            }

            const data = res.data;
            proveedorSeleccionadoActual = data.proveedor;
            facturasPendientesActualesCxp = data.facturas_pendientes || [];

            // Llenar Cabecera y KPIs
            document.getElementById('det_proveedor_nombre').textContent = data.proveedor.nombre;
            document.getElementById('det_proveedor_rif').textContent = data.proveedor.rif;
            document.getElementById('det_proveedor_contacto').textContent = `Contacto: ${data.proveedor.nombre_contacto} | Teléfono: ${data.proveedor.telefono} | Correo: ${data.proveedor.correo}`;
            document.getElementById('det_deuda_total_usd').textContent = `$${formatearNumero(data.proveedor.total_deuda_usd)}`;
            document.getElementById('det_deuda_total_bs').textContent = `Bs. ${formatearNumero(data.proveedor.total_deuda_bs)}`;
            document.getElementById('det_facturas_pendientes_count').textContent = facturasPendientesActualesCxp.length;
            document.getElementById('det_tasa_bcv').textContent = `${formatearNumero(data.tasa_cambio, 2)} Bs/$`;

            document.getElementById('badge_count_cxp_pendientes').textContent = facturasPendientesActualesCxp.length;
            document.getElementById('badge_count_cxp_pagadas').textContent = (data.facturas_pagadas || []).length;

            // Configurar botón de Abono General
            const btnAbonoGen = document.getElementById('btn_abono_general_proveedor');
            btnAbonoGen.onclick = () => {
                const modalDetalle = bootstrap.Modal.getInstance(document.getElementById('modalDetalleProveedor'));
                if (modalDetalle) modalDetalle.hide();
                abrirModalAbonoGeneralCxp(proveedorId);
            };

            // Renderizar Facturas Pendientes
            renderizarFacturasPendientesCxp(facturasPendientesActualesCxp);

            // Renderizar Facturas Pagadas
            renderizarFacturasPagadasCxp(data.facturas_pagadas || []);

            const modal = new bootstrap.Modal(document.getElementById('modalDetalleProveedor'));
            modal.show();
        })
        .catch(err => {
            console.error('Error al abrir detalle proveedor:', err);
            Swal.fire('Error', 'No se pudo cargar el detalle del proveedor.', 'error');
        });
}

function renderizarFacturasPendientesCxp(facturas) {
    const tbody = document.getElementById('tbody_cxp_pendientes');
    tbody.innerHTML = '';

    if (facturas.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-check-circle text-success fs-4 d-block mb-2"></i>No hay facturas pendientes con este proveedor.</td></tr>';
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
                <small class="text-muted">Recepción: ${f.recepcion_codigo}</small>
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
                    <button class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-bold" onclick="abrirModalAbonoFacturaCxp(${f.id}, '${f.numero_factura}', ${f.saldo_pendiente_usd}, ${f.saldo_pendiente_bs})">
                        <i class="fas fa-hand-holding-usd me-1"></i> Pagar
                    </button>
                    ${f.abonos_count > 0 ? `
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick='abrirHistorialAbonosFacturaCxp(${JSON.stringify(f.abonos)}, "${f.numero_factura}")' title="Ver historial de pagos">
                            <i class="fas fa-history"></i>
                        </button>
                    ` : ''}
                </div>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

function renderizarFacturasPagadasCxp(facturas) {
    const tbody = document.getElementById('tbody_cxp_pagadas');
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
                <small class="text-muted">Recepción: ${f.recepcion_codigo}</small>
            </td>
            <td>${f.fecha_emision}</td>
            <td>${f.fecha_vencimiento}</td>
            <td class="text-end fw-semibold text-dark">$${formatearNumero(f.monto_total_usd)}</td>
            <td class="text-end fw-semibold text-success">$${formatearNumero(f.monto_pagado_usd)}</td>
            <td class="text-center">
                <span class="badge bg-success rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-double me-1"></i> 100% Saldada</span>
            </td>
            <td class="text-center pe-4">
                ${f.abonos && f.abonos.length > 0 ? `
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-2" onclick='abrirHistorialAbonosFacturaCxp(${JSON.stringify(f.abonos)}, "${f.numero_factura}")' title="Ver pagos">
                        <i class="fas fa-history"></i>
                    </button>
                ` : '<span class="text-muted small">-</span>'}
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Abrir Modal Operativo 1: Abonar a Factura Específica de Proveedor
 */
let saldoFacturaCxpActualUsd = 0;
let saldoFacturaCxpActualBs = 0;

function abrirModalAbonoFacturaCxp(cuentaId, numeroFactura, saldoUsd, saldoBs) {
    saldoFacturaCxpActualUsd = parseFloat(saldoUsd);
    saldoFacturaCxpActualBs = parseFloat(saldoBs);

    document.getElementById('abono_cxp_cuenta_id').value = cuentaId;
    document.getElementById('abono_cxp_tasa_cambio').value = tasaCambioActivaCxp;
    document.getElementById('abono_cxp_factura_label').textContent = `Factura #${numeroFactura}`;
    document.getElementById('abono_cxp_saldo_label').textContent = `$${formatearNumero(saldoFacturaCxpActualUsd)}`;
    document.getElementById('abono_cxp_saldo_bs_label').textContent = `Bs. ${formatearNumero(saldoFacturaCxpActualBs)}`;

    document.getElementById('abono_cxp_monto').value = '';
    document.getElementById('abono_cxp_moneda').value = 'USD';
    document.getElementById('abono_cxp_moneda_sym').textContent = '$';
    document.getElementById('abono_cxp_referencia').value = '';
    document.getElementById('abono_cxp_observaciones').value = '';
    document.getElementById('abono_cxp_equivalente_label').textContent = `Equivalente: Bs. 0.00`;

    poblarSelectsMetodosPagoCxp();

    const modal = new bootstrap.Modal(document.getElementById('modalAbonarFacturaCxp'));
    modal.show();
}

function recalcularAbonoEspecificoCxp() {
    const moneda = document.getElementById('abono_cxp_moneda').value;
    const monto = parseFloat(document.getElementById('abono_cxp_monto').value) || 0;
    const sym = document.getElementById('abono_cxp_moneda_sym');
    const eqLabel = document.getElementById('abono_cxp_equivalente_label');

    if (moneda === 'USD') {
        sym.textContent = '$';
        const bs = round(monto * tasaCambioActivaCxp, 2);
        eqLabel.textContent = `Equivalente: Bs. ${formatearNumero(bs)}`;
    } else {
        sym.textContent = 'Bs';
        const usd = tasaCambioActivaCxp > 0 ? round(monto / tasaCambioActivaCxp, 2) : 0;
        eqLabel.textContent = `Equivalente: $${formatearNumero(usd)}`;
    }
}

function pagarTotalidadFacturaCxp() {
    const moneda = document.getElementById('abono_cxp_moneda').value;
    if (moneda === 'USD') {
        document.getElementById('abono_cxp_monto').value = saldoFacturaCxpActualUsd.toFixed(2);
    } else {
        document.getElementById('abono_cxp_monto').value = (saldoFacturaCxpActualUsd * tasaCambioActivaCxp).toFixed(2);
    }
    recalcularAbonoEspecificoCxp();
}

/**
 * Procesar formulario de Pago Específico a Proveedor
 */
function procesarAbonoEspecificoCxp(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_guardar_abono_especifico_cxp');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';

    const formData = new FormData(document.getElementById('formAbonarFacturaCxp'));

    fetch('/cuentas-por-pagar/abonar-factura', {
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
                Swal.fire('Error', res.message || 'No se pudo registrar el pago.', 'error');
                return;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAbonarFacturaCxp'));
            if (modal) modal.hide();

            Swal.fire({
                title: '¡Pago Registrado!',
                text: res.message,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#2563eb',
            }).then((result) => {
                if (result.isConfirmed && res.data.abono_id) {
                    abrirVentanaTicketCxp(`/cuentas-por-pagar/ticket/${res.data.abono_id}`);
                }
            });

            // Recargar datos
            cargarResumenCxp();
            if (proveedorSeleccionadoActual) {
                abrirDetalleProveedor(proveedorSeleccionadoActual.id);
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
 * Abrir Modal Operativo 2: Abono General FIFO a Proveedor
 */
function abrirModalAbonoGeneralCxp(proveedorId) {
    fetch(`/cuentas-por-pagar/proveedor/${proveedorId}`)
        .then(response => response.json())
        .then(res => {
            if (!res.success) {
                Swal.fire('Error', res.message || 'No se pudo consultar el proveedor.', 'error');
                return;
            }

            proveedorSeleccionadoActual = res.data.proveedor;
            facturasPendientesActualesCxp = res.data.facturas_pendientes || [];

            if (facturasPendientesActualesCxp.length === 0) {
                Swal.fire('Aviso', 'Este proveedor no tiene facturas con saldo pendiente.', 'info');
                return;
            }

            document.getElementById('gen_cxp_proveedor_id').value = proveedorSeleccionadoActual.id;
            document.getElementById('gen_cxp_tasa_cambio').value = tasaCambioActivaCxp;
            document.getElementById('gen_cxp_proveedor_label').textContent = `${proveedorSeleccionadoActual.nombre} (${proveedorSeleccionadoActual.rif})`;
            document.getElementById('gen_cxp_deuda_total_label').textContent = `$${formatearNumero(proveedorSeleccionadoActual.total_deuda_usd)}`;
            document.getElementById('gen_cxp_deuda_total_bs_label').textContent = `Bs. ${formatearNumero(proveedorSeleccionadoActual.total_deuda_bs)}`;

            document.getElementById('gen_cxp_monto').value = '';
            document.getElementById('gen_cxp_moneda').value = 'USD';
            document.getElementById('gen_cxp_moneda_sym').textContent = '$';
            document.getElementById('gen_cxp_referencia').value = '';
            document.getElementById('gen_cxp_observaciones').value = '';
            document.getElementById('gen_cxp_equivalente_label').textContent = `Equivalente: Bs. 0.00`;

            poblarSelectsMetodosPagoCxp();
            recalcularAbonoGeneralCxp();

            const modal = new bootstrap.Modal(document.getElementById('modalAbonoGeneralCxp'));
            modal.show();
        })
        .catch(err => {
            console.error('Error:', err);
            Swal.fire('Error', 'No se pudo abrir el pago general a proveedor.', 'error');
        });
}

function recalcularAbonoGeneralCxp() {
    const moneda = document.getElementById('gen_cxp_moneda').value;
    const monto = parseFloat(document.getElementById('gen_cxp_monto').value) || 0;
    const sym = document.getElementById('gen_cxp_moneda_sym');
    const eqLabel = document.getElementById('gen_cxp_equivalente_label');

    let montoUsd = 0;
    if (moneda === 'USD') {
        sym.textContent = '$';
        montoUsd = monto;
        const bs = round(monto * tasaCambioActivaCxp, 2);
        eqLabel.textContent = `Equivalente: Bs. ${formatearNumero(bs)}`;
    } else {
        sym.textContent = 'Bs';
        montoUsd = tasaCambioActivaCxp > 0 ? round(monto / tasaCambioActivaCxp, 2) : 0;
        eqLabel.textContent = `Equivalente: $${formatearNumero(montoUsd)}`;
    }

    // Simular Cascada FIFO en la tabla de previsualización
    simularCascadaFifoCxp(montoUsd);
}

function simularCascadaFifoCxp(montoTotalUsd) {
    const tbody = document.getElementById('tbody_cxp_preview_fifo');
    const badgeResumen = document.getElementById('gen_cxp_preview_resumen');
    tbody.innerHTML = '';

    if (montoTotalUsd <= 0 || facturasPendientesActualesCxp.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Ingresa un monto para ver la cascada de amortización</td></tr>';
        badgeResumen.textContent = '0 facturas cubiertas';
        return;
    }

    let restanteUsd = montoTotalUsd;
    let cubiertasCompletas = 0;
    let facturasAfectadasCount = 0;

    facturasPendientesActualesCxp.forEach(f => {
        if (restanteUsd <= 0.001) return;

        const saldoActual = parseFloat(f.saldo_pendiente_usd);
        const aplicar = Math.min(restanteUsd, saldoActual);
        const nuevoSaldo = Math.max(0, saldoActual - aplicar);
        const quedaPagada = nuevoSaldo <= 0.001;

        if (quedaPagada) cubiertasCompletas++;
        facturasAfectadasCount++;

        const tr = document.createElement('tr');
        tr.className = quedaPagada ? 'table-primary bg-opacity-25' : 'table-warning bg-opacity-25';
        tr.innerHTML = `
            <td class="fw-bold">${f.numero_factura}</td>
            <td>${f.fecha_emision}</td>
            <td class="text-end">$${formatearNumero(saldoActual)}</td>
            <td class="text-end fw-bold text-primary">+$${formatearNumero(aplicar)}</td>
            <td class="text-end fw-bold ${quedaPagada ? 'text-success' : 'text-danger'}">$${formatearNumero(nuevoSaldo)}</td>
            <td class="text-center">
                ${quedaPagada
                    ? '<span class="badge bg-success rounded-pill px-2 py-1"><i class="fas fa-check me-1"></i> Quedará Saldada (100%)</span>'
                    : '<span class="badge bg-warning text-dark rounded-pill px-2 py-1"><i class="fas fa-adjust me-1"></i> Abono Parcial</span>'}
            </td>
        `;
        tbody.appendChild(tr);

        restanteUsd -= aplicar;
    });

    badgeResumen.textContent = `${facturasAfectadasCount} factura(s) impactadas (${cubiertasCompletas} al 100%)`;
}

function pagarDeudaTotalGeneralCxp() {
    if (!proveedorSeleccionadoActual) return;
    const moneda = document.getElementById('gen_cxp_moneda').value;
    const deudaUsd = parseFloat(proveedorSeleccionadoActual.total_deuda_usd);

    if (moneda === 'USD') {
        document.getElementById('gen_cxp_monto').value = deudaUsd.toFixed(2);
    } else {
        document.getElementById('gen_cxp_monto').value = (deudaUsd * tasaCambioActivaCxp).toFixed(2);
    }
    recalcularAbonoGeneralCxp();
}

/**
 * Procesar formulario de Abono General FIFO a Proveedor
 */
function procesarAbonoGeneralCxp(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_guardar_abono_general_cxp');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando FIFO...';

    const formData = new FormData(document.getElementById('formAbonoGeneralCxp'));

    fetch('/cuentas-por-pagar/abonar-general', {
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
                Swal.fire('Error', res.message || 'No se pudo procesar el pago general.', 'error');
                return;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('modalAbonoGeneralCxp'));
            if (modal) modal.hide();

            Swal.fire({
                title: '¡Pago FIFO Aplicado!',
                text: res.message,
                icon: 'success',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                cancelButtonText: 'Cerrar',
                confirmButtonColor: '#2563eb',
            }).then((result) => {
                if (result.isConfirmed && res.data.primer_abono_id) {
                    abrirVentanaTicketCxp(`/cuentas-por-pagar/ticket/${res.data.primer_abono_id}`);
                }
            });

            // Recargar
            cargarResumenCxp();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalText;
            console.error('Error:', err);
            Swal.fire('Error', 'Ocurrió un fallo al procesar el pago general.', 'error');
        });
}

/**
 * Abrir Modal Informativo de Historial de Abonos de una Factura de Proveedor
 */
function abrirHistorialAbonosFacturaCxp(abonos, numeroFactura) {
    document.getElementById('hist_cxp_modal_title').textContent = `Historial de Pagos - Factura #${numeroFactura}`;
    const tbody = document.getElementById('tbody_cxp_historial_abonos');
    tbody.innerHTML = '';

    if (!abonos || abonos.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No hay pagos registrados para esta factura.</td></tr>';
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
                    <button class="btn btn-sm btn-outline-dark rounded-pill px-2" onclick="abrirVentanaTicketCxp('/cuentas-por-pagar/ticket/${a.id}')" title="Imprimir Ticket">
                        <i class="fas fa-print"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const modal = new bootstrap.Modal(document.getElementById('modalHistorialAbonosCxp'));
    modal.show();
}

/**
 * Abrir ventana de impresión térmica de comprobante CXP
 */
function abrirVentanaTicketCxp(url) {
    const w = 420;
    const h = 600;
    const left = (screen.width / 2) - (w / 2);
    const top = (screen.height / 2) - (h / 2);
    window.open(url, 'TicketAbonoCXP', `width=${w},height=${h},top=${top},left=${left},scrollbars=yes,status=no`);
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
