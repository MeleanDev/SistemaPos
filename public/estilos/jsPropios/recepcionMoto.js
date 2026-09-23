const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlCatalogos = urlCompleta + "/catalogos";
const urlDetalles = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlAnular = urlCompleta + "/";

let datatableRecepcionMotos = null;
let proveedoresLista = [];
let almacenesLista = [];
let tasaOficialActual = 1.0000;
let tasaCompraActual = 1.0000;
let tasaVentaActual = 1.0000;
let tasaCambioActual = 1.0000;
let monedaSeleccionada = 'USD';
let lotesAgregados = [];
let editandoLoteIndex = null;
let proximaReferenciaSugerida = '1';

$(document).ready(function () {
    crearSelect2({
        selector: '#proveedor_id',
        modalSelector: '#modalRecepcionMoto',
        placeholder: 'Seleccione un proveedor...',
    });

    inicializarTabla();
    cargarCatalogos();

    $('#lote_referencia').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    const hoy = new Date().toISOString().split('T')[0];
    $('#fecha_emision').val(hoy);
    $('#fecha_recepcion').val(hoy);

    $(document).on('keydown', '.input-serial-matrix', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const inputs = $('.input-serial-matrix');
            const currentIndex = inputs.index(this);
            if (currentIndex > -1 && currentIndex < inputs.length - 1) {
                inputs.eq(currentIndex + 1).focus().select();
            }
        }
    });

    generarMatrizSeriales();
});

const inicializarTabla = function () {
    datatableRecepcionMotos = crearDataTable({
        selector: "#datatable_recepcion_motos",
        url: urlLista,
        columns: [
            {
                data: 'codigo',
                name: 'codigo',
                render: function (data) {
                    return `<span class="badge rounded-pill bg-light text-dark border font-monospace fw-bold px-2 py-1"><i class="fas fa-hashtag me-1 text-primary"></i>${data}</span>`;
                }
            },
            {
                data: 'numero_documento',
                name: 'numero_documento',
                render: function (data, type, row) {
                    const tipo = (row.tipo_documento || 'factura').toUpperCase();
                    return `<div><strong class="text-dark">${data}</strong><br><small class="text-muted">${tipo}</small></div>`;
                }
            },
            {
                data: 'numero_control',
                name: 'numero_control',
                render: function (data) {
                    return data ? `<span class="font-monospace small text-muted">${data}</span>` : '<span class="text-muted fst-italic small">N/A</span>';
                }
            },
            {
                data: 'proveedor.nombre',
                name: 'proveedor.nombre',
                render: function (data, type, row) {
                    const rif = row.proveedor?.rif ? `<small class="text-muted font-monospace d-block">${row.proveedor.rif}</small>` : '';
                    return `<div><strong class="text-dark">${data || 'Sin proveedor'}</strong>${rif}</div>`;
                }
            },
            {
                data: 'almacen.nombre',
                name: 'almacen.nombre',
                render: function (data) {
                    return `<span class="badge rounded-pill bg-light text-secondary border px-2 py-1"><i class="fas fa-warehouse me-1"></i>${data || 'General'}</span>`;
                }
            },
            {
                data: 'total_unidades',
                name: 'total_unidades',
                className: 'text-center',
                render: function (data) {
                    return `<span class="badge rounded-pill bg-primary text-white fw-bold px-3 py-1"><i class="fas fa-motorcycle me-1"></i>${data || 0} Motos</span>`;
                }
            },
            {
                data: 'total_usd',
                name: 'total_usd',
                className: 'text-end',
                render: function (data, type, row) {
                    const totalUsd = parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const tasa = parseFloat(row.tasa_compra || row.tasa_cambio || 1);
                    const totalBs = (parseFloat(data || 0) * tasa).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `<div><strong class="text-success">$ ${totalUsd}</strong><br><small class="text-muted">Bs. ${totalBs}</small></div>`;
                }
            },
            {
                data: 'condicion_pago',
                name: 'condicion_pago',
                className: 'text-center',
                render: function (data, type, row) {
                    if (data === 'credito') {
                        return `<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Crédito (${row.dias_credito || 0}d)</span>`;
                    }
                    return `<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Contado</span>`;
                }
            },
            {
                data: 'estado',
                name: 'estado',
                className: 'text-center',
                render: function (data) {
                    if (data === 'procesada') {
                        return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-1"><i class="fas fa-check-circle me-1"></i>Procesada</span>';
                    } else if (data === 'anulada') {
                        return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle px-3 py-1"><i class="fas fa-ban me-1"></i>Anulada</span>';
                    }
                    return `<span class="badge rounded-pill bg-secondary px-3 py-1">${data}</span>`;
                }
            },
            {
                data: 'id',
                name: 'acciones',
                orderable: false,
                searchable: false,
                className: 'text-end',
                render: function (data, type, row) {
                    const anulada = row.estado === 'anulada';
                    return `
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2" onclick="verDetalle(${data})" title="Ver Detalles">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="/recepciones-motos/${data}/imprimir" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Imprimir Comprobante">
                                <i class="fas fa-print"></i>
                            </a>
                            ${!anulada ? `
                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill px-2" onclick="anularRecepcion(${data}, '${row.codigo}')" title="Anular Recepción">
                                    <i class="fas fa-ban"></i>
                                </button>
                            ` : ''}
                        </div>
                    `;
                }
            }
        ]
    });
};

const cargarCatalogos = async function () {
    try {
        const respuesta = await peticionAjax({
            url: urlCatalogos,
            method: "GET",
        });

        if (respuesta.success && respuesta.data) {
            proveedoresLista = respuesta.data.proveedores || [];
            almacenesLista = respuesta.data.almacenes || [];
            tasaOficialActual = parseFloat(respuesta.data.tasa_oficial) || 1.0000;
            tasaCompraActual = parseFloat(respuesta.data.tasa_compra || respuesta.data.tasa_oficial) || tasaOficialActual;
            tasaVentaActual = parseFloat(respuesta.data.tasa_venta || respuesta.data.tasa_oficial) || tasaOficialActual;
            tasaCambioActual = tasaVentaActual;

            if (respuesta.data.proxima_referencia) {
                proximaReferenciaSugerida = String(respuesta.data.proxima_referencia);
                if (!$('#lote_referencia').val()) {
                    $('#lote_referencia').val(proximaReferenciaSugerida);
                }
            }

            $('#tasa_cambio').val(tasaVentaActual.toFixed(4));
            $('#tasa_compra').val(tasaCompraActual.toFixed(4));
            $('#tasa_venta').val(tasaVentaActual.toFixed(4));
            $('#badgeTasaCambio').text(tasaOficialActual.toFixed(4));
            $('#badgeTasaCompraFase2').text(tasaCompraActual.toFixed(4));
            $('#badgeTasaVentaFase2').text(tasaVentaActual.toFixed(4));

            if (respuesta.data.codigo_sugerido) {
                $('#badgeCodigoRecepcion').html(`<i class="fas fa-hashtag me-1"></i>${respuesta.data.codigo_sugerido}`);
            }

            poblarSelects();
        }
    } catch (e) {
        console.error("Error al cargar catálogos:", e);
    }
};

const actualizarTasasDesdeInput = function () {
    const tCompra = parseFloat($('#tasa_compra').val()) || 0;
    const tVenta = parseFloat($('#tasa_venta').val()) || 0;

    if (tCompra > 0) {
        tasaCompraActual = tCompra;
        $('#badgeTasaCompraFase2').text(tasaCompraActual.toFixed(4));
    }
    if (tVenta > 0) {
        tasaVentaActual = tVenta;
        tasaCambioActual = tVenta;
        $('#tasa_cambio').val(tasaVentaActual.toFixed(4));
        $('#badgeTasaVentaFase2').text(tasaVentaActual.toFixed(4));
    }

    recalcularPreciosLote();
    recalcularTotalesGenerales();
};
window.actualizarTasasDesdeInput = actualizarTasasDesdeInput;

const restablecerTasaOficial = function (tipo) {
    if (tipo === 'compra') {
        $('#tasa_compra').val(tasaOficialActual.toFixed(4));
    } else if (tipo === 'venta') {
        $('#tasa_venta').val(tasaOficialActual.toFixed(4));
    }
    actualizarTasasDesdeInput();
};
window.restablecerTasaOficial = restablecerTasaOficial;

const poblarSelects = function () {
    const $selectProv = $('#proveedor_id');
    const valorSeleccionado = $selectProv.val();
    $selectProv.empty().append('<option value="">Seleccione un proveedor...</option>');
    proveedoresLista.forEach(p => {
        $selectProv.append(`<option value="${p.id}">[${p.rif}] ${p.nombre}</option>`);
    });

    if (valorSeleccionado) {
        establecerValorSelect2('#proveedor_id', valorSeleccionado);
    } else {
        $selectProv.trigger('change.select2');
    }

    const $selectAlm = $('#almacen_id').empty().append('<option value="">Seleccione almacén...</option>');
    almacenesLista.forEach((a, idx) => {
        const isSelected = idx === 0 ? 'selected' : '';
        $selectAlm.append(`<option value="${a.id}" ${isSelected}>${a.nombre} (${a.codigo})</option>`);
    });
};

const seleccionarMonedaDocumento = function (moneda) {
    monedaSeleccionada = moneda;
    $('#moneda_documento').val(moneda);

    if (moneda === 'USD') {
        $('#cardMonedaUsd').addClass('active-moneda');
        $('#cardMonedaVes').removeClass('active-moneda');
        $('#radio_usd').prop('checked', true);
        $('.label-simbolo-moneda-fac').text('$');
        $('#badgeMonedaFase2').html('<i class="fas fa-dollar-sign me-1"></i> Factura en $ USD').css({
            'background-color': '#dcfce7',
            'color': '#15803d',
            'border': '1px solid #bbf7d0'
        });
    } else {
        $('#cardMonedaVes').addClass('active-moneda');
        $('#cardMonedaUsd').removeClass('active-moneda');
        $('#radio_ves').prop('checked', true);
        $('.label-simbolo-moneda-fac').text('Bs.');
        $('#badgeMonedaFase2').html('<i class="fas fa-money-bill-wave me-1"></i> Factura en Bs. VES').css({
            'background-color': '#e0f2fe',
            'color': '#0369a1',
            'border': '1px solid #bae6fd'
        });
    }

    recalcularPreciosLote();
    recalcularTotalesGenerales();
};

const toggleCondicionPago = function () {
    const condicion = $('#condicion_pago').val();
    if (condicion === 'credito') {
        $('#contenedorDiasCredito').slideDown(200);
        calcularFechaVencimiento();
    } else {
        $('#contenedorDiasCredito').slideUp(200);
    }
};

const calcularFechaVencimiento = function () {
    const fechaEmision = $('#fecha_emision').val();
    const dias = parseInt($('#dias_credito').val()) || 0;
    const res = window.CalculosCompra.calcularFechaVencimientoCredito(fechaEmision, dias);
    $('#labelFechaVencimiento').text(`Vence: ${res.fechaFormateada}`);
};

const crear = function () {
    $('#formularioRecepcionMoto')[0].reset();
    limpiarSelect2('#proveedor_id');
    lotesAgregados = [];
    editandoLoteIndex = null;
    cancelarEdicionLote();
    renderizarLotesAgregados();
    volverAFase1();

    const hoy = new Date().toISOString().split('T')[0];
    $('#fecha_emision').val(hoy);
    $('#fecha_recepcion').val(hoy);
    $('#dias_credito').val(30);
    seleccionarMonedaDocumento('USD');
    cargarCatalogos();

    $('#modalRecepcionMoto').modal('show');
};

const avanzarAFase2 = function () {
    const numDoc = $('#numero_documento').val().trim();
    const provId = $('#proveedor_id').val();
    const almId = $('#almacen_id').val();
    const montoBruto = parseFloat($('#monto_bruto_input').val());

    if (!numDoc) {
        return notificacion.fire({ icon: 'warning', title: 'El número de factura o documento es obligatorio.' });
    }
    if (!provId) {
        return notificacion.fire({ icon: 'warning', title: 'Debes seleccionar el proveedor emisor.' });
    }
    if (!almId) {
        return notificacion.fire({ icon: 'warning', title: 'Debes seleccionar el almacén general de entrada.' });
    }
    if (isNaN(montoBruto) || montoBruto <= 0) {
        return notificacion.fire({ icon: 'warning', title: 'El Monto Bruto de la factura es obligatorio y debe ser mayor a cero.' });
    }

    const provTexto = $('#proveedor_id option:selected').text();
    const almTexto = $('#almacen_id option:selected').text();
    const simbolo = (monedaSeleccionada === 'VES') ? 'Bs.' : '$';
    $('#badgeDocFase2').html(`<i class="fas fa-file-invoice text-primary me-1"></i> Doc: <strong>${numDoc}</strong>`);
    $('#badgeProvFase2').html(`<i class="fas fa-truck text-primary me-1"></i> Prov: <strong>${provTexto}</strong>`);
    $('#badgeAlmFase2').html(`<i class="fas fa-warehouse text-secondary me-1"></i> Almacén: <strong>${almTexto}</strong>`);
    $('#badgeMontoBrutoFase2').html(`<i class="fas fa-receipt me-1"></i> Monto Fac: <strong>${simbolo} ${montoBruto.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</strong>`);

    $('#seccionFase1').slideUp(200);
    $('#seccionFase2').slideDown(250);

    $('#btnPaso1Stepper').removeClass('active').addClass('text-white-50');
    $('#btnPaso2Stepper').addClass('active').removeClass('text-white-50');

    $('#btnAvanzarFase2Modal').hide();
    $('#btnVolverFase1Modal').show();
    $('#btnProcesarRecepcionMoto').show();

    if (editandoLoteIndex === null && $('#tbodyMatrizSeriales tr').length === 0) {
        generarMatrizSeriales();
    }
    actualizarCuadreFactura();
};

const volverAFase1 = function () {
    $('#seccionFase2').slideUp(200);
    $('#seccionFase1').slideDown(250);

    $('#btnPaso2Stepper').removeClass('active').addClass('text-white-50');
    $('#btnPaso1Stepper').addClass('active').removeClass('text-white-50');

    $('#btnAvanzarFase2Modal').show();
    $('#btnVolverFase1Modal').hide();
    $('#btnProcesarRecepcionMoto').hide();
};

const generarMatrizSeriales = function (serialesExistentes = []) {
    const cant = parseInt($('#lote_cantidad').val()) || 1;
    const $tbody = $('#tbodyMatrizSeriales').empty();
    const almacenGeneralId = $('#almacen_id').val();

    $('#badgeCantidadSeriales').text(`${cant} ${cant === 1 ? 'Moto' : 'Motos'}`);

    let almacenesOptions = '';
    almacenesLista.forEach(a => {
        const isSelected = (a.id == almacenGeneralId) ? 'selected' : '';
        almacenesOptions += `<option value="${a.id}" ${isSelected}>${a.nombre}</option>`;
    });

    for (let i = 1; i <= cant; i++) {
        const s = serialesExistentes[i - 1] || {};
        const nivVal = s.numero_niv || '';
        const chasisVal = s.numero_chasis || '';
        const motorVal = s.numero_motor || '';
        const origenVal = s.certificado_origen || '';
        const placaVal = s.placa || '';
        const almIdVal = s.almacen_id || almacenGeneralId;

        let almOptionsRow = '';
        almacenesLista.forEach(a => {
            const isSelected = (a.id == almIdVal) ? 'selected' : '';
            almOptionsRow += `<option value="${a.id}" ${isSelected}>${a.nombre}</option>`;
        });

        $tbody.append(`
            <tr data-index="${i}">
                <td class="text-center fw-bold font-monospace bg-light">${i}</td>
                <td>
                    <input type="text" class="form-control form-control-sm input-serial-matrix serial-niv" placeholder="17 caracteres..." maxlength="50" value="${nivVal}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-serial-matrix serial-chasis" placeholder="N° Chasis..." maxlength="100" value="${chasisVal}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-serial-matrix serial-motor" placeholder="N° Motor..." maxlength="100" value="${motorVal}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-serial-matrix serial-origen" placeholder="Cert. Origen..." maxlength="100" value="${origenVal}" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-serial-matrix serial-placa" placeholder="Placa..." maxlength="50" value="${placaVal}">
                </td>
                <td>
                    <select class="form-select form-select-sm serial-almacen">
                        ${almOptionsRow}
                    </select>
                </td>
            </tr>
        `);
    }
};

const recalcularPreciosLote = function () {
    const esVes = monedaSeleccionada === 'VES';
    const costoIngresado = parseFloat($('#lote_costo_unitario').val()) || 0;
    const eq = window.CalculosCompra.calcularEquivalenteMoneda(costoIngresado, monedaSeleccionada, tasaCompraActual, tasaVentaActual);

    if (!esVes) {
        $('#lote_costo_equivalente').text(`Equiv: Bs. ${eq.bs.toFixed(4)}`);
    } else {
        $('#lote_costo_equivalente').text(`Equiv: $ ${eq.usd.toFixed(4)}`);
    }

    calcularPrecioDetalLote();
    calcularPrecioMayoristaLote();
};
window.recalcularPreciosLote = recalcularPreciosLote;

const calcularPrecioDetalLote = function () {
    const esVes = monedaSeleccionada === 'VES';
    const res = window.CalculosCompra.calcularPreciosDesdeMargen({
        costo: $('#lote_costo_unitario').val(),
        margenDetal: $('#lote_margen_detal').val(),
        margenMayorista: $('#lote_margen_mayorista').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.costoUsd > 0 || res.costoBs > 0) {
        if (!esVes) {
            $('#lote_precio_detal').val(res.precioDetalUsd.toFixed(4));
            $('#lote_detal_bs').text(`Bs. ${res.precioDetalBs.toFixed(4)}`);
        } else {
            $('#lote_precio_detal').val(res.precioDetalBs.toFixed(4));
            $('#lote_detal_bs').text(`$ ${res.precioDetalUsd.toFixed(4)}`);
        }
    } else {
        $('#lote_precio_detal').val('');
        $('#lote_detal_bs').text(esVes ? '$ 0.0000' : 'Bs. 0.0000');
    }
};
window.calcularPrecioDetalLote = calcularPrecioDetalLote;

const calcularMargenDetalLote = function () {
    const esVes = monedaSeleccionada === 'VES';
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#lote_costo_unitario').val(),
        precio: $('#lote_precio_detal').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#lote_margen_detal').val(res.margen.toFixed(0));
        if (!esVes) {
            $('#lote_detal_bs').text(`Bs. ${res.precioBs.toFixed(4)}`);
        } else {
            $('#lote_detal_bs').text(`$ ${res.precioUsd.toFixed(4)}`);
        }
    }
};
window.calcularMargenDetalLote = calcularMargenDetalLote;

const calcularPrecioMayoristaLote = function () {
    const esVes = monedaSeleccionada === 'VES';
    const res = window.CalculosCompra.calcularPreciosDesdeMargen({
        costo: $('#lote_costo_unitario').val(),
        margenDetal: $('#lote_margen_detal').val(),
        margenMayorista: $('#lote_margen_mayorista').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.costoUsd > 0 || res.costoBs > 0) {
        if (!esVes) {
            $('#lote_precio_mayorista').val(res.precioMayoristaUsd.toFixed(4));
            $('#lote_mayorista_bs').text(`Bs. ${res.precioMayoristaBs.toFixed(4)}`);
        } else {
            $('#lote_precio_mayorista').val(res.precioMayoristaBs.toFixed(4));
            $('#lote_mayorista_bs').text(`$ ${res.precioMayoristaUsd.toFixed(4)}`);
        }
    } else {
        $('#lote_precio_mayorista').val('');
        $('#lote_mayorista_bs').text(esVes ? '$ 0.0000' : 'Bs. 0.0000');
    }
};
window.calcularPrecioMayoristaLote = calcularPrecioMayoristaLote;

const calcularMargenMayoristaLote = function () {
    const esVes = monedaSeleccionada === 'VES';
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#lote_costo_unitario').val(),
        precio: $('#lote_precio_mayorista').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#lote_margen_mayorista').val(res.margen.toFixed(0));
        if (!esVes) {
            $('#lote_mayorista_bs').text(`Bs. ${res.precioBs.toFixed(4)}`);
        } else {
            $('#lote_mayorista_bs').text(`$ ${res.precioUsd.toFixed(4)}`);
        }
    }
};
window.calcularMargenMayoristaLote = calcularMargenMayoristaLote;
window.calcularMargenMayoristaLote = calcularMargenMayoristaLote;

const agregarLoteAFactura = function () {
    const referencia = $('#lote_referencia').val().trim();
    const marca = $('#lote_marca').val().trim();
    const modelo = $('#lote_modelo').val().trim();
    const anio = $('#lote_anio').val().trim();
    const color = $('#lote_color').val().trim();
    const cilindrada = $('#lote_cilindrada').val().trim();
    const cantidad = parseInt($('#lote_cantidad').val()) || 0;
    const costoUnitarioInput = parseFloat($('#lote_costo_unitario').val()) || 0;

    if (!referencia) return notificacion.fire({ icon: 'warning', title: 'La referencia o código SKU es obligatorio.' });
    if (!marca) return notificacion.fire({ icon: 'warning', title: 'La marca de la moto es obligatoria.' });
    if (!modelo) return notificacion.fire({ icon: 'warning', title: 'El modelo de la moto es obligatorio.' });
    if (!color) return notificacion.fire({ icon: 'warning', title: 'El color de la moto es obligatorio.' });
    if (cantidad <= 0) return notificacion.fire({ icon: 'warning', title: 'La cantidad debe ser al menos 1 moto.' });
    if (costoUnitarioInput <= 0) return notificacion.fire({ icon: 'warning', title: 'El costo unitario debe ser mayor a cero.' });

    const seriales = [];
    let faltanSeriales = false;

    $('#tbodyMatrizSeriales tr').each(function () {
        const niv = $(this).find('.serial-niv').val().trim().toUpperCase();
        const chasis = $(this).find('.serial-chasis').val().trim().toUpperCase();
        const motor = $(this).find('.serial-motor').val().trim().toUpperCase();
        const origen = $(this).find('.serial-origen').val().trim().toUpperCase();
        const placa = $(this).find('.serial-placa').val().trim().toUpperCase();
        const almacenId = $(this).find('.serial-almacen').val();

        if (!niv || !chasis || !motor || !origen) {
            faltanSeriales = true;
            return false;
        }

        seriales.push({
            numero_niv: niv,
            numero_chasis: chasis,
            numero_motor: motor,
            certificado_origen: origen,
            placa: placa,
            almacen_id: almacenId
        });
    });

    if (faltanSeriales || seriales.length !== cantidad) {
        return notificacion.fire({
            icon: 'warning',
            title: 'Seriales Incompletos',
            text: 'Debes completar todos los campos obligatorios (N.I.V., Chasis, Motor, Cert. Origen) para cada una de las motos.'
        });
    }

    const esVes = monedaSeleccionada === 'VES';
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const tVenta = tasaVentaActual > 0 ? tasaVentaActual : 1.0;
    const tasaMenor = tCompra < tVenta;

    let costoUnitarioUsd = 0;
    let costoUnitarioBs = 0;
    let precioDetalUsd = 0;
    let precioDetalBs = 0;
    let precioMayoristaUsd = 0;
    let precioMayoristaBs = 0;

    const descPct = parseFloat($('#lote_descuento').val()) || 0;
    const ivaPct = parseFloat($('#lote_iva').val()) || 0;
    const aplicaIva = ivaPct > 0;
    const margenDetal = parseFloat($('#lote_margen_detal').val()) || 0;
    const margenMayorista = parseFloat($('#lote_margen_mayorista').val()) || 0;

    if (!esVes) {
        costoUnitarioUsd = costoUnitarioInput;
        costoUnitarioBs = tasaMenor ? (costoUnitarioUsd * tVenta) : (costoUnitarioUsd * tCompra);
        const sugeridoDetalUsd = tasaMenor
            ? (costoUnitarioUsd * (1 + margenDetal / 100))
            : ((costoUnitarioUsd * (1 + margenDetal / 100) * tCompra) / tVenta);
        precioDetalUsd = parseFloat($('#lote_precio_detal').val()) || sugeridoDetalUsd;
        precioDetalBs = precioDetalUsd * tVenta;

        const sugeridoMayorUsd = tasaMenor
            ? (costoUnitarioUsd * (1 + margenMayorista / 100))
            : ((costoUnitarioUsd * (1 + margenMayorista / 100) * tCompra) / tVenta);
        precioMayoristaUsd = parseFloat($('#lote_precio_mayorista').val()) || sugeridoMayorUsd;
        precioMayorBs = precioMayoristaUsd * tVenta;
    } else {
        costoUnitarioBs = costoUnitarioInput;
        costoUnitarioUsd = tasaMenor ? (costoUnitarioBs / tCompra) : (costoUnitarioBs / tVenta);
        const sugeridoDetalUsd = costoUnitarioUsd * (1 + margenDetal / 100);
        precioDetalBs = parseFloat($('#lote_precio_detal').val()) || (sugeridoDetalUsd * tVenta);
        precioDetalUsd = precioDetalBs / tVenta;

        const sugeridoMayorUsd = costoUnitarioUsd * (1 + margenMayorista / 100);
        precioMayorBs = parseFloat($('#lote_precio_mayorista').val()) || (sugeridoMayorUsd * tVenta);
        precioMayoristaUsd = precioMayorBs / tVenta;
    }

    const costoNetoUsd = costoUnitarioUsd * (1 - (descPct / 100));
    const subtotalUsd = costoNetoUsd * cantidad;
    const ivaUsd = aplicaIva ? (subtotalUsd * (ivaPct / 100)) : 0;
    const totalUsd = subtotalUsd + ivaUsd;

    const loteData = {
        referencia: referencia,
        marca: marca,
        modelo: modelo,
        anio: anio,
        color: color,
        cilindrada: cilindrada,
        cantidad: cantidad,
        costo_unitario_usd: costoUnitarioUsd,
        costo_unitario_bs: costoUnitarioBs,
        descuento_porcentaje: descPct,
        aplica_iva: aplicaIva,
        iva_porcentaje: ivaPct,
        margen_detal: margenDetal,
        precio_detal_usd: precioDetalUsd,
        precio_detal_bs: precioDetalBs,
        margen_mayorista: margenMayorista,
        precio_mayorista_usd: precioMayoristaUsd,
        precio_mayorista_bs: precioMayoristaBs,
        subtotal_usd: subtotalUsd,
        subtotal_bs: subtotalUsd * tCompra,
        iva_usd: ivaUsd,
        iva_bs: ivaUsd * tCompra,
        total_usd: totalUsd,
        total_bs: totalUsd * tCompra,
        seriales: seriales
    };

    if (editandoLoteIndex !== null && lotesAgregados[editandoLoteIndex]) {
        lotesAgregados[editandoLoteIndex] = loteData;
        notificacion.fire({
            icon: 'success',
            title: 'Modelo actualizado',
            text: `Se actualizaron los datos y seriales de ${marca} ${modelo}.`,
            timer: 1500,
            showConfirmButton: false
        });
    } else {
        lotesAgregados.push(loteData);
        notificacion.fire({
            icon: 'success',
            title: 'Modelo añadido a la Factura',
            text: `Se agregaron ${cantidad} motos (${marca} ${modelo}). Puedes agregar otro modelo ahora.`,
            timer: 1700,
            showConfirmButton: false
        });
    }

    cancelarEdicionLote();
    renderizarLotesAgregados();
    recalcularTotalesGenerales();

    setTimeout(() => {
        $('#lote_referencia').focus();
    }, 200);
};

const editarLote = function (index) {
    const lote = lotesAgregados[index];
    if (!lote) return;

    editandoLoteIndex = index;
    const esVes = monedaSeleccionada === 'VES';
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const tVenta = tasaVentaActual > 0 ? tasaVentaActual : 1.0;
    const tasaMenor = tCompra < tVenta;

    $('#lote_referencia').val(lote.referencia);
    $('#lote_marca').val(lote.marca);
    $('#lote_modelo').val(lote.modelo);
    $('#lote_anio').val(lote.anio);
    $('#lote_color').val(lote.color);
    $('#lote_cilindrada').val(lote.cilindrada);
    $('#lote_cantidad').val(lote.cantidad);

    const costoVal = esVes
        ? (tasaMenor ? lote.costo_unitario_usd * tCompra : lote.costo_unitario_usd * tVenta)
        : lote.costo_unitario_usd;
    $('#lote_costo_unitario').val(costoVal.toFixed(2));
    $('#lote_descuento').val(lote.descuento_porcentaje);
    $('#lote_iva').val(lote.iva_porcentaje);
    $('#lote_margen_detal').val(lote.margen_detal);
    
    const detalVal = esVes ? (lote.precio_detal_usd * tVenta) : lote.precio_detal_usd;
    $('#lote_precio_detal').val(detalVal.toFixed(2));
    
    $('#lote_margen_mayorista').val(lote.margen_mayorista);
    const mayorVal = esVes ? (lote.precio_mayorista_usd * tVenta) : lote.precio_mayorista_usd;
    $('#lote_precio_mayorista').val(mayorVal.toFixed(2));

    const costoEquivBs = tasaMenor ? (lote.costo_unitario_usd * tVenta) : (lote.costo_unitario_usd * tCompra);
    $('#lote_costo_equivalente').text(esVes ? `Equiv: $ ${lote.costo_unitario_usd.toFixed(2)}` : `Equiv: Bs. ${costoEquivBs.toFixed(2)}`);
    $('#lote_detal_bs').text(esVes ? `$ ${lote.precio_detal_usd.toFixed(2)}` : `Bs. ${(lote.precio_detal_usd * tVenta).toFixed(2)}`);
    $('#lote_mayorista_bs').text(esVes ? `$ ${lote.precio_mayorista_usd.toFixed(2)}` : `Bs. ${(lote.precio_mayorista_usd * tVenta).toFixed(2)}`);

    generarMatrizSeriales(lote.seriales || []);

    $('#tituloConstructorLote').html(`1. Modificando Renglón #${index + 1}: <span class="text-primary">${lote.marca} ${lote.modelo}</span>`);
    $('#badgeEstadoEdicionLote').removeClass('bg-primary-subtle text-primary border-primary-subtle')
        .addClass('bg-warning-subtle text-warning-emphasis border-warning-subtle')
        .text(`Editando Renglón #${index + 1}`);
    $('#btnAccionLoteTexto').text('Guardar Cambios del Modelo');
    $('#btnAccionLoteIcono').removeClass('fa-plus-circle').addClass('fa-save');
    $('#btnCancelarEdicionLote').show();

    document.getElementById('cardConstructorLote').scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const cancelarEdicionLote = function () {
    editandoLoteIndex = null;
    
    let maxRef = parseInt(proximaReferenciaSugerida) || 1;
    lotesAgregados.forEach((l) => {
        const r = parseInt(l.referencia);
        if (!isNaN(r) && r >= maxRef) {
            maxRef = r + 1;
        }
    });
    $('#lote_referencia').val(maxRef);
    $('#lote_marca').val('');
    $('#lote_modelo').val('');
    $('#lote_color').val('');
    $('#lote_cantidad').val(1);
    $('#lote_costo_unitario').val('');
    $('#lote_descuento').val('0.00');
    $('#lote_iva').val('16');
    $('#lote_margen_detal').val('25');
    $('#lote_precio_detal').val('');
    $('#lote_margen_mayorista').val('15');
    $('#lote_precio_mayorista').val('');
    $('#lote_costo_equivalente').text(monedaSeleccionada === 'VES' ? 'Equiv: $ 0.00' : 'Equiv: Bs. 0.00');
    $('#lote_detal_bs').text(monedaSeleccionada === 'VES' ? '$ 0.00' : 'Bs. 0.00');
    $('#lote_mayorista_bs').text(monedaSeleccionada === 'VES' ? '$ 0.00' : 'Bs. 0.00');

    $('#tituloConstructorLote').text('1. Configurar Modelo / Lote de Motos');
    $('#badgeEstadoEdicionLote').removeClass('bg-warning-subtle text-warning-emphasis border-warning-subtle')
        .addClass('bg-primary-subtle text-primary border-primary-subtle')
        .text('Nuevo Renglón');
    $('#btnAccionLoteTexto').text('Agregar este Modelo a la Factura');
    $('#btnAccionLoteIcono').removeClass('fa-save').addClass('fa-plus-circle');
    $('#btnCancelarEdicionLote').hide();

    generarMatrizSeriales();
};

const verSerialesLote = function (index) {
    const lote = lotesAgregados[index];
    if (!lote) return;

    $('#modalVerSerialesTitulo').html(`Seriales de <strong>${lote.marca} ${lote.modelo}</strong> (${lote.cantidad} Motos)`);

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-white align-middle mb-0 font-monospace" style="font-size: 0.8rem;">
                <thead class="table-light">
                    <tr>
                        <th class="text-center" style="width: 35px;">#</th>
                        <th>N.I.V.</th>
                        <th>Chasis</th>
                        <th>Motor</th>
                        <th>Cert. Origen</th>
                        <th>Placa</th>
                    </tr>
                </thead>
                <tbody>
    `;

    (lote.seriales || []).forEach((s, idx) => {
        html += `
            <tr>
                <td class="text-center fw-bold bg-light">${idx + 1}</td>
                <td><span class="badge bg-light text-dark border">${s.numero_niv}</span></td>
                <td>${s.numero_chasis}</td>
                <td>${s.numero_motor}</td>
                <td><span class="badge bg-success-subtle text-success border border-success-subtle">${s.certificado_origen}</span></td>
                <td>${s.placa || '<span class="text-muted fst-italic">N/A</span>'}</td>
            </tr>
        `;
    });

    html += `
                </tbody>
            </table>
        </div>
    `;

    $('#modalVerSerialesCuerpo').html(html);
    $('#modalVerSerialesLote').modal('show');
};

const eliminarLote = function (index) {
    if (editandoLoteIndex === index) {
        cancelarEdicionLote();
    }
    lotesAgregados.splice(index, 1);
    renderizarLotesAgregados();
    recalcularTotalesGenerales();
};

const renderizarLotesAgregados = function () {
    const $tbody = $('#tbodyRecepcionMotoDetalles').empty();
    let totalMotos = 0;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;

    if (lotesAgregados.length === 0) {
        $tbody.html(`
            <tr id="filaSinLotes">
                <td colspan="11" class="text-center py-4 text-muted">
                    <i class="fas fa-motorcycle fs-3 d-block mb-2 opacity-50"></i>
                    No has agregado ningún modelo de motos a esta factura.
                </td>
            </tr>
        `);
        $('#contadorLotesMotos').text('0 Modelos (0 Motos)');
        actualizarCuadreFactura();
        return;
    }

    lotesAgregados.forEach((lote, idx) => {
        totalMotos += lote.cantidad;
        const costoUsd = lote.costo_unitario_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const costoBs = (lote.costo_unitario_usd * tCompra).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const totalLoteUsd = lote.total_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const totalLoteBs = (lote.total_usd * tCompra).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const detalUsd = lote.precio_detal_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const mayorUsd = lote.precio_mayorista_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        $tbody.append(`
            <tr>
                <td class="text-center font-monospace fw-bold">${idx + 1}</td>
                <td>
                    <strong class="text-dark">${lote.marca} ${lote.modelo}</strong>
                    <small class="text-muted d-block font-monospace">Ref: ${lote.referencia} | ${lote.cilindrada}</small>
                </td>
                <td>
                    <span class="badge rounded-pill bg-light text-secondary border">${lote.anio}</span>
                    <small class="d-block text-muted">${lote.color}</small>
                </td>
                <td class="text-center">
                    <span class="badge rounded-pill bg-primary text-white fw-bold px-2 py-1">${lote.cantidad} unids</span>
                </td>
                <td class="text-end font-monospace">
                    <span class="fw-semibold text-dark">$ ${costoUsd}</span>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Bs. ${costoBs}</small>
                </td>
                <td class="text-center">
                    <span class="badge rounded-pill ${lote.aplica_iva ? 'bg-secondary text-white' : 'bg-light text-muted border'}">${lote.iva_porcentaje}%</span>
                </td>
                <td class="text-end font-monospace text-primary fw-bold">$ ${detalUsd}</td>
                <td class="text-end font-monospace" style="color: #7e22ce;">$ ${mayorUsd}</td>
                <td class="text-end font-monospace">
                    <strong class="text-success">$ ${totalLoteUsd}</strong>
                    <small class="text-muted d-block" style="font-size: 0.72rem;">Bs. ${totalLoteBs}</small>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2 py-1 font-monospace" onclick="verSerialesLote(${idx})" title="Ver Seriales de este Modelo" style="font-size: 0.74rem;">
                        <i class="fas fa-fingerprint me-1"></i> ${lote.seriales?.length || 0} Ser.
                    </button>
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle p-1" onclick="editarLote(${idx})" title="Editar Modelo y Seriales">
                            <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1" onclick="eliminarLote(${idx})" title="Eliminar Modelo">
                            <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });

    $('#contadorLotesMotos').text(`${lotesAgregados.length} ${lotesAgregados.length === 1 ? 'Modelo' : 'Modelos'} (${totalMotos} Motos)`);
    actualizarCuadreFactura();
};

const recalcularTotalesGenerales = function () {
    let subtotalUsd = 0;
    let ivaUsd = 0;
    let totalUsd = 0;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;

    lotesAgregados.forEach(l => {
        subtotalUsd += l.subtotal_usd;
        ivaUsd += l.iva_usd;
        totalUsd += l.total_usd;
    });

    const descGlobalPct = parseFloat($('#descuento_global_porcentaje').val()) || 0;
    const descGlobalUsd = subtotalUsd * (descGlobalPct / 100);
    const granTotalUsd = Math.max(0, totalUsd - descGlobalUsd);
    const granTotalBs = granTotalUsd * tCompra;

    $('#resumenSubtotalUsd').text(`$ ${subtotalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenIvaUsd').text(`$ ${ivaUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenDescuentoUsd').text(`-$ ${descGlobalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenTotalUsd').text(`$ ${granTotalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenTotalBs').text(`Bs. ${granTotalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);

    actualizarCuadreFactura();
};

const actualizarCuadreFactura = function () {
    const montoFacturaInput = parseFloat($('#monto_bruto_input').val()) || 0;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const montoFacturaUsd = (monedaSeleccionada === 'VES') ? (montoFacturaInput / tCompra) : montoFacturaInput;

    let sumaModelosUsd = 0;
    lotesAgregados.forEach(l => {
        sumaModelosUsd += l.total_usd;
    });

    const $badgeCuadre = $('#badgeEstadoCuadreFactura');

    if (lotesAgregados.length === 0) {
        $badgeCuadre.html('<i class="fas fa-scale-balanced me-1"></i> Sin renglones cargados')
            .css({ 'background-color': '#f8fafc', 'color': '#64748b', 'border': '1px solid #e2e8f0' });
        return;
    }

    const diferencia = Math.abs(montoFacturaUsd - sumaModelosUsd);

    if (diferencia < 0.05) {
        $badgeCuadre.html('<i class="fas fa-check-circle me-1"></i> Factura Cuadrada ($ ' + sumaModelosUsd.toFixed(2) + ')')
            .css({ 'background-color': '#dcfce7', 'color': '#15803d', 'border': '1px solid #86efac' });
    } else if (sumaModelosUsd < montoFacturaUsd) {
        const falta = (montoFacturaUsd - sumaModelosUsd).toFixed(2);
        $badgeCuadre.html(`<i class="fas fa-clock me-1"></i> Faltan $ ${falta} por cargar`)
            .css({ 'background-color': '#fef3c7', 'color': '#b45309', 'border': '1px solid #fde68a' });
    } else {
        const sobra = (sumaModelosUsd - montoFacturaUsd).toFixed(2);
        $badgeCuadre.html(`<i class="fas fa-exclamation-triangle me-1"></i> Excede por $ ${sobra}`)
            .css({ 'background-color': '#fee2e2', 'color': '#b91c1c', 'border': '1px solid #fca5a5' });
    }
};

const procesarRecepcionMoto = async function () {
    if (lotesAgregados.length === 0) {
        return notificacion.fire({
            icon: 'warning',
            title: 'Sin Motos Registradas',
            text: 'Debes agregar al menos un lote de motos con sus respectivos seriales a la recepción.'
        });
    }

    const payload = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        almacen_id: $('#almacen_id').val(),
        proveedor_id: $('#proveedor_id').val(),
        tipo_documento: $('#tipo_documento').val(),
        numero_documento: $('#numero_documento').val().trim(),
        numero_control: $('#numero_control').val().trim(),
        moneda_documento: monedaSeleccionada,
        tasa_cambio: tasaVentaActual,
        tasa_compra: tasaCompraActual,
        tasa_venta: tasaVentaActual,
        fecha_emision: $('#fecha_emision').val(),
        fecha_recepcion: $('#fecha_recepcion').val(),
        condicion_pago: $('#condicion_pago').val(),
        dias_credito: $('#dias_credito').val(),
        monto_bruto_usd: $('#monto_bruto_input').val(),
        descuento_global_porcentaje: $('#descuento_global_porcentaje').val(),
        observaciones: $('#observaciones').val(),
        detalles: lotesAgregados
    };

    const confirm = await Swal.fire({
        title: '¿Procesar Recepción de Motos?',
        text: `Se registrarán ${lotesAgregados.reduce((a, b) => a + b.cantidad, 0)} motos con sus seriales únicos y se ingresarán al inventario.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-check-double me-1"></i> Sí, procesar ingreso',
        cancelButtonText: 'Revisar datos'
    });

    if (!confirm.isConfirmed) return;

    Swal.fire({
        title: 'Procesando recepción...',
        text: 'Registrando seriales únicos y actualizando inventario...',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading()
    });

    try {
        const respuesta = await $.ajax({
            url: urlGuardar,
            type: "POST",
            data: JSON.stringify(payload),
            contentType: "application/json",
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if (respuesta.success) {
            $('#modalRecepcionMoto').modal('hide');
            Swal.fire({
                icon: 'success',
                title: '¡Recepción Procesada!',
                text: respuesta.message,
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-print me-1"></i> Imprimir Comprobante',
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed && respuesta.data?.id) {
                    window.open(`/recepciones-motos/${respuesta.data.id}/imprimir`, '_blank');
                }
            });
            datatableRecepcionMotos.ajax.reload();
        }
    } catch (error) {
        let msg = 'Ocurrió un error al procesar la recepción.';
        if (error.responseJSON?.message) {
            msg = error.responseJSON.message;
        } else if (error.responseJSON?.errors) {
            msg = Object.values(error.responseJSON.errors).flat().join('<br>');
        }
        Swal.fire({
            icon: 'error',
            title: 'Error de Validación',
            html: msg
        });
    }
};

const verDetalle = async function (id) {
    try {
        const respuesta = await $.ajax({
            url: urlDetalles + id,
            type: "GET",
            dataType: "json"
        });

        if (respuesta.success && respuesta.data) {
            const r = respuesta.data;
            $('#detalleModalCodigo').text(r.codigo);
            $('#btnImprimirDetalle').attr('href', `/recepciones-motos/${r.id}/imprimir`);

            let tablaDetalles = '';
            (r.detalles || []).forEach((d, idx) => {
                let serialesHtml = '';
                (d.motos || []).forEach(m => {
                    serialesHtml += `
                        <div class="p-2 border rounded-3 bg-light mb-1 font-monospace small">
                            <strong>NIV:</strong> ${m.numero_niv} | 
                            <strong>Chasis:</strong> ${m.numero_chasis} | 
                            <strong>Motor:</strong> ${m.numero_motor} | 
                            <strong>Cert. Origen:</strong> ${m.certificado_origen}
                            ${m.placa ? ` | <strong>Placa:</strong> ${m.placa}` : ''}
                        </div>
                    `;
                });

                tablaDetalles += `
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">${idx + 1}. ${d.marca} ${d.modelo} (${d.anio} - ${d.color})</h6>
                            <span class="badge bg-primary rounded-pill px-3 py-1">${d.cantidad} Motos</span>
                        </div>
                        <div class="row g-2 font-monospace small text-muted mb-2">
                            <div class="col-md-3">Costo Unit: $ ${parseFloat(d.costo_unitario_usd).toFixed(2)}</div>
                            <div class="col-md-3">Precio Detal: $ ${parseFloat(d.precio_detal_usd).toFixed(2)}</div>
                            <div class="col-md-3">Precio Mayor: $ ${parseFloat(d.precio_mayorista_usd).toFixed(2)}</div>
                            <div class="col-md-3 text-end fw-bold text-success">Total Lote: $ ${parseFloat(d.total_usd).toFixed(2)}</div>
                        </div>
                        <div class="mt-2">
                            <small class="fw-bold text-dark d-block mb-1"><i class="fas fa-fingerprint text-success me-1"></i> Seriales Registrados:</small>
                            ${serialesHtml}
                        </div>
                    </div>
                `;
            });

            $('#contenidoDetalleRecepcionMoto').html(`
                <div class="card border rounded-4 p-3 bg-white mb-3 shadow-xs">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <small class="text-muted d-block">Proveedor:</small>
                            <strong class="text-dark">${r.proveedor?.nombre || 'N/A'} (${r.proveedor?.rif || ''})</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Factura / Documento:</small>
                            <strong class="text-dark">${r.numero_documento}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Fecha de Emisión:</small>
                            <strong class="text-dark">${r.fecha_emision}</strong>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted d-block">Condición / Tasas:</small>
                            <span class="badge bg-light text-dark border">${r.condicion_pago.toUpperCase()}</span>
                            <small class="font-monospace text-muted d-block">T. Compra: ${parseFloat(r.tasa_compra || r.tasa_cambio).toFixed(4)} Bs. | T. Venta: ${parseFloat(r.tasa_venta || r.tasa_cambio).toFixed(4)} Bs.</small>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-motorcycle text-primary me-2"></i> Lotes y Unidades Recibidas</h6>
                ${tablaDetalles}

                <div class="card border-0 rounded-4 p-3 text-white text-end font-monospace" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <div class="fs-5 fw-bold text-warning">TOTAL COMPRA: $ ${parseFloat(r.total_usd).toFixed(2)}</div>
                    <div class="text-white-50">Equivalente: Bs. ${parseFloat(r.total_bs).toFixed(2)}</div>
                </div>
            `);

            $('#modalDetalleRecepcionMoto').modal('show');
        }
    } catch (error) {
        notificacion.fire({ icon: 'error', title: 'Error al cargar detalles de la recepción.' });
    }
};

const anularRecepcion = async function (id, codigo) {
    const confirm = await Swal.fire({
        title: `¿Anular Recepción ${codigo}?`,
        text: "Se revertirán las motos del inventario si no han sido vendidas.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-ban me-1"></i> Sí, anular',
        cancelButtonText: 'Cancelar'
    });

    if (!confirm.isConfirmed) return;

    try {
        const respuesta = await $.ajax({
            url: `${urlAnular}${id}/anular`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: "json"
        });

        if (respuesta.success) {
            notificacion.fire({ icon: 'success', title: 'Recepción Anulada', text: respuesta.message });
            datatableRecepcionMotos.ajax.reload();
        }
    } catch (error) {
        notificacion.fire({
            icon: 'error',
            title: 'No se pudo anular',
            text: error.responseJSON?.message || 'Error al anular la recepción.'
        });
    }
};

const abrirModalRapidoProveedor = function () {
    $('#formularioRapidoProveedor')[0].reset();
    $('#modalRapidoProveedor').modal('show');
};

const guardarRapidoProveedor = async function () {
    const formData = new FormData($('#formularioRapidoProveedor')[0]);
    const rifCompleto = $('#formularioRapidoProveedor select[name="tipo_cedula"]').val() + $('#formularioRapidoProveedor input[name="cedula_numero"]').val().trim();
    formData.set('rif', rifCompleto);

    const telNum = $('#formularioRapidoProveedor input[name="telefono_numero"]').val().trim();
    if (telNum) {
        formData.set('telefono', $('#formularioRapidoProveedor select[name="codigo_pais"]').val() + telNum);
    }

    try {
        const respuesta = await $.ajax({
            url: "/proveedores",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if (respuesta.success && respuesta.data) {
            $('#modalRapidoProveedor').modal('hide');
            notificacion.fire({ icon: 'success', title: 'Proveedor creado exitosamente.' });
            await cargarCatalogos();
            establecerValorSelect2('#proveedor_id', respuesta.data.id);
        }
    } catch (error) {
        notificacion.fire({
            icon: 'error',
            title: 'Error al registrar proveedor',
            text: error.responseJSON?.message || 'Verifica los campos ingresados.'
        });
    }
};
