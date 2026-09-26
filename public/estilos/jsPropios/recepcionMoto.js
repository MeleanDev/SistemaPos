const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";
const urlCatalogos = urlCompleta + "/catalogos";
const urlDetalles = urlCompleta + "/";
const urlGuardar = urlCompleta;
const urlAnular = urlCompleta + "/";

let datatableRecepcionMotos = null;
let proveedoresLista = [];
let almacenesLista = [];
let productosLista = [];
let tasaOficialActual = 1.0000;
let tasaCompraActual = 1.0000;
let tasaVentaActual = 1.0000;
let tasaCambioActual = 1.0000;
let monedaSeleccionada = 'USD';
let modoItemActual = 'moto';
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
                    return `<span class="badge rounded-pill bg-primary text-white fw-bold px-3 py-1">${data || 0} Unids</span>`;
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
            productosLista = respuesta.data.productos || [];
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
        console.error(e);
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

    if (modoItemActual === 'moto') {
        recalcularPreciosLote();
    } else {
        recalcularPreciosProducto();
    }
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
    const $selectProdAlm = $('#prod_almacen_id').empty().append('<option value="">Seleccione almacén...</option>');
    almacenesLista.forEach((a, idx) => {
        const isSelected = idx === 0 ? 'selected' : '';
        $selectAlm.append(`<option value="${a.id}" ${isSelected}>${a.nombre} (${a.codigo})</option>`);
        $selectProdAlm.append(`<option value="${a.id}" ${isSelected}>${a.nombre} (${a.codigo})</option>`);
    });

    const $selectProd = $('#prod_select_id').empty().append('<option value="">Buscar o seleccionar producto...</option>');
    productosLista.forEach(p => {
        $selectProd.append(`<option value="${p.id}">[${p.codigo_interno}] ${p.nombre} (${p.categoria_nombre})</option>`);
    });
};

const cambiarModoItem = function (modo) {
    modoItemActual = modo;
    if (modo === 'moto') {
        $('#btnModoItemMoto').removeClass('btn-outline-primary').addClass('btn-primary shadow-xs');
        $('#btnModoItemProducto').removeClass('btn-success shadow-xs').addClass('btn-outline-secondary');
        $('#cardConstructorMoto').slideDown(200);
        $('#cardConstructorProducto').slideUp(200);
    } else {
        $('#btnModoItemProducto').removeClass('btn-outline-secondary').addClass('btn-success shadow-xs');
        $('#btnModoItemMoto').removeClass('btn-primary shadow-xs').addClass('btn-outline-primary');
        $('#cardConstructorProducto').slideDown(200);
        $('#cardConstructorMoto').slideUp(200);
    }
};
window.cambiarModoItem = cambiarModoItem;

const seleccionarProductoDeCatalogo = function () {
    const prodId = parseInt($('#prod_select_id').val()) || 0;
    const prod = productosLista.find(p => p.id === prodId);
    if (!prod) return;

    const esVes = monedaSeleccionada === 'VES';
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const tVenta = tasaVentaActual > 0 ? tasaVentaActual : 1.0;
    const tasaMenor = tCompra < tVenta;

    const costoVal = esVes
        ? (tasaMenor ? (prod.precio_costo_usd * tCompra) : (prod.precio_costo_usd * tVenta))
        : prod.precio_costo_usd;

    $('#prod_costo_unitario').val(costoVal > 0 ? costoVal.toFixed(4) : '');
    $('#prod_descuento').val('0.00');
    $('#prod_iva').val(prod.aplica_iva ? (prod.iva_porcentaje || 16) : 0);
    $('#prod_margen_detal').val(prod.ultimo_margen_detal || 30);
    $('#prod_margen_mayorista').val(prod.ultimo_margen_mayorista || 15);

    recalcularPreciosProducto();
};
window.seleccionarProductoDeCatalogo = seleccionarProductoDeCatalogo;

const recalcularPreciosProducto = function () {
    const costoInput = parseFloat($('#prod_costo_unitario').val()) || 0;
    const ivaPorcentaje = parseFloat($('#prod_iva').val()) || 0;
    const aplicaIva = ivaPorcentaje > 0;
    const res = window.CalculosCompra.calcularPreciosDesdeMargen({
        costo: costoInput,
        flete: 0,
        ivaPorcentaje: ivaPorcentaje,
        aplicaIva: aplicaIva,
        margenDetal: $('#prod_margen_detal').val(),
        margenMayorista: $('#prod_margen_mayorista').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    const esVes = monedaSeleccionada === 'VES';
    if (res.costoBaseUsd > 0 || res.costoBaseBs > 0) {
        if (!esVes) {
            $('#prod_precio_detal').val(res.precioDetalConIvaUsd.toFixed(4));
            $('#prod_precio_mayorista').val(res.precioMayoristaConIvaUsd.toFixed(4));
        } else {
            $('#prod_precio_detal').val(res.precioDetalConIvaBs.toFixed(4));
            $('#prod_precio_mayorista').val(res.precioMayoristaConIvaBs.toFixed(4));
        }
        $('#prod_detal_con_iva_badge').text(`$ ${res.precioDetalConIvaUsd.toFixed(2)} | Bs. ${res.precioDetalConIvaBs.toFixed(2)}`);
        $('#prod_detal_sin_iva').text(`$ ${res.precioDetalUsd.toFixed(2)} | Bs. ${res.precioDetalBs.toFixed(2)}`);
        $('#prod_mayorista_con_iva_badge').text(`$ ${res.precioMayoristaConIvaUsd.toFixed(2)} | Bs. ${res.precioMayoristaConIvaBs.toFixed(2)}`);
        $('#prod_mayorista_sin_iva').text(`$ ${res.precioMayoristaUsd.toFixed(2)} | Bs. ${res.precioMayoristaBs.toFixed(2)}`);
    } else {
        $('#prod_precio_detal').val('');
        $('#prod_precio_mayorista').val('');
        $('#prod_detal_con_iva_badge').text('$ 0.00 | Bs. 0.00');
        $('#prod_detal_sin_iva').text('$ 0.00 | Bs. 0.00');
        $('#prod_mayorista_con_iva_badge').text('$ 0.00 | Bs. 0.00');
        $('#prod_mayorista_sin_iva').text('$ 0.00 | Bs. 0.00');
    }
};
window.recalcularPreciosProducto = recalcularPreciosProducto;

const calcularPrecioDetalProducto = function () {
    recalcularPreciosProducto();
};
window.calcularPrecioDetalProducto = calcularPrecioDetalProducto;

const calcularMargenDetalProducto = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#prod_costo_unitario').val(),
        flete: 0,
        precio: $('#prod_precio_detal').val(),
        tipoPrecio: 'con_iva',
        ivaPorcentaje: $('#prod_iva').val(),
        aplicaIva: parseFloat($('#prod_iva').val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });
    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#prod_margen_detal').val(res.margen.toFixed(0));
        $('#prod_detal_con_iva_badge').text(`$ ${res.precioConIvaUsd.toFixed(2)} | Bs. ${res.precioConIvaBs.toFixed(2)}`);
        $('#prod_detal_sin_iva').text(`$ ${res.precioUsd.toFixed(2)} | Bs. ${res.precioBs.toFixed(2)}`);
    }
};
window.calcularMargenDetalProducto = calcularMargenDetalProducto;

const calcularPrecioMayoristaProducto = function () {
    recalcularPreciosProducto();
};
window.calcularPrecioMayoristaProducto = calcularPrecioMayoristaProducto;

const calcularMargenMayoristaProducto = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#prod_costo_unitario').val(),
        flete: 0,
        precio: $('#prod_precio_mayorista').val(),
        tipoPrecio: 'con_iva',
        ivaPorcentaje: $('#prod_iva').val(),
        aplicaIva: parseFloat($('#prod_iva').val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });
    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#prod_margen_mayorista').val(res.margen.toFixed(0));
        $('#prod_mayorista_con_iva_badge').text(`$ ${res.precioConIvaUsd.toFixed(2)} | Bs. ${res.precioConIvaBs.toFixed(2)}`);
        $('#prod_mayorista_sin_iva').text(`$ ${res.precioUsd.toFixed(2)} | Bs. ${res.precioUsd > 0 ? (res.precioUsd * tasaVentaActual).toFixed(2) : '0.00'}`);
    }
};
window.calcularMargenMayoristaProducto = calcularMargenMayoristaProducto;

const agregarProductoAFactura = function () {
    const prodId = parseInt($('#prod_select_id').val()) || 0;
    const almId = parseInt($('#prod_almacen_id').val()) || parseInt($('#almacen_id').val()) || 0;
    const cantidad = parseFloat($('#prod_cantidad').val()) || 0;
    const costoUnitarioInput = parseFloat($('#prod_costo_unitario').val()) || 0;

    if (prodId <= 0) return notificacion.fire({ icon: 'warning', title: 'Debes seleccionar un producto del catálogo.' });
    if (almId <= 0) return notificacion.fire({ icon: 'warning', title: 'Debes seleccionar el almacén de destino.' });
    if (cantidad <= 0) return notificacion.fire({ icon: 'warning', title: 'La cantidad debe ser mayor a cero.' });
    if (costoUnitarioInput <= 0) return notificacion.fire({ icon: 'warning', title: 'El costo unitario debe ser mayor a cero.' });

    const productoObj = productosLista.find(p => p.id === prodId);
    const nombreProd = productoObj ? productoObj.nombre : 'Producto';
    const codigoProd = productoObj ? productoObj.codigo_interno : 'PROD';

    const esVes = monedaSeleccionada === 'VES';
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const tVenta = tasaVentaActual > 0 ? tasaVentaActual : 1.0;
    const tasaMenor = tCompra < tVenta;

    const descPct = parseFloat($('#prod_descuento').val()) || 0;
    const ivaPct = parseFloat($('#prod_iva').val()) || 0;
    const aplicaIva = ivaPct > 0;
    const margenDetal = parseFloat($('#prod_margen_detal').val()) || 0;
    const margenMayorista = parseFloat($('#prod_margen_mayorista').val()) || 0;

    let costoUnitarioUsd = 0;
    let costoUnitarioBs = 0;

    if (!esVes) {
        costoUnitarioUsd = costoUnitarioInput;
        costoUnitarioBs = tasaMenor ? (costoUnitarioUsd * tVenta) : (costoUnitarioUsd * tCompra);
    } else {
        costoUnitarioBs = costoUnitarioInput;
        costoUnitarioUsd = tasaMenor ? (costoUnitarioBs / tCompra) : (costoUnitarioBs / tVenta);
    }

    const descUsd = costoUnitarioUsd * (descPct / 100);
    const costoNetoUsd = costoUnitarioUsd - descUsd;
    const subtotalUsd = costoNetoUsd * cantidad;
    const ivaUsd = aplicaIva ? (subtotalUsd * (ivaPct / 100)) : 0;
    const totalUsd = subtotalUsd + ivaUsd;

    const costoTotalProdUsd = costoNetoUsd + (aplicaIva ? (costoNetoUsd * (ivaPct / 100)) : 0);
    const sugeridoDetalConIvaUsd = !esVes
        ? (tasaMenor ? (costoTotalProdUsd * (1 + margenDetal / 100)) : ((costoTotalProdUsd * (1 + margenDetal / 100) * tCompra) / tVenta))
        : (costoTotalProdUsd * (1 + margenDetal / 100));
    const precioDetalConIvaUsd = !esVes
        ? (parseFloat($('#prod_precio_detal').val()) || sugeridoDetalConIvaUsd)
        : ((parseFloat($('#prod_precio_detal').val()) || (sugeridoDetalConIvaUsd * tVenta)) / tVenta);
    const precioDetalUsd = aplicaIva ? (precioDetalConIvaUsd / (1 + (ivaPct / 100))) : precioDetalConIvaUsd;
    const precioDetalBs = precioDetalUsd * tVenta;
    const precioDetalConIvaBs = precioDetalConIvaUsd * tVenta;

    const sugeridoMayorConIvaUsd = !esVes
        ? (tasaMenor ? (costoTotalProdUsd * (1 + margenMayorista / 100)) : ((costoTotalProdUsd * (1 + margenMayorista / 100) * tCompra) / tVenta))
        : (costoTotalProdUsd * (1 + margenMayorista / 100));
    const precioMayoristaConIvaUsd = !esVes
        ? (parseFloat($('#prod_precio_mayorista').val()) || sugeridoMayorConIvaUsd)
        : ((parseFloat($('#prod_precio_mayorista').val()) || (sugeridoMayorConIvaUsd * tVenta)) / tVenta);
    const precioMayoristaUsd = aplicaIva ? (precioMayoristaConIvaUsd / (1 + (ivaPct / 100))) : precioMayoristaConIvaUsd;
    const precioMayoristaBs = precioMayoristaUsd * tVenta;
    const precioMayoristaConIvaBs = precioMayoristaConIvaUsd * tVenta;

    const itemData = {
        tipo_item: 'producto',
        producto_id: prodId,
        almacen_id: almId,
        referencia: codigoProd,
        marca: productoObj?.categoria_nombre || 'General',
        modelo: nombreProd,
        anio: '',
        color: '',
        cilindrada: '',
        cantidad: cantidad,
        costo_unitario_usd: costoUnitarioUsd,
        costo_unitario_bs: costoUnitarioBs,
        flete_unitario_usd: 0,
        flete_unitario_bs: 0,
        costo_total_unitario_usd: costoNetoUsd,
        costo_total_unitario_bs: costoNetoUsd * tCompra,
        descuento_porcentaje: descPct,
        descuento_usd: descUsd,
        descuento_bs: descUsd * tCompra,
        aplica_iva: aplicaIva,
        iva_porcentaje: ivaPct,
        iva_monto_usd: ivaUsd / cantidad,
        iva_monto_bs: (ivaUsd * tCompra) / cantidad,
        margen_detal: margenDetal,
        precio_detal_usd: precioDetalUsd,
        precio_detal_bs: precioDetalBs,
        precio_detal_con_iva_usd: precioDetalConIvaUsd,
        precio_detal_con_iva_bs: precioDetalConIvaBs,
        margen_mayorista: margenMayorista,
        precio_mayorista_usd: precioMayoristaUsd,
        precio_mayorista_bs: precioMayoristaBs,
        precio_mayorista_con_iva_usd: precioMayoristaConIvaUsd,
        precio_mayorista_con_iva_bs: precioMayoristaConIvaBs,
        subtotal_usd: subtotalUsd,
        subtotal_bs: subtotalUsd * tCompra,
        iva_usd: ivaUsd,
        iva_bs: ivaUsd * tCompra,
        total_usd: totalUsd,
        total_bs: totalUsd * tCompra,
        seriales: []
    };

    if (editandoLoteIndex !== null && lotesAgregados[editandoLoteIndex]) {
        lotesAgregados[editandoLoteIndex] = itemData;
        notificacion.fire({ icon: 'success', title: 'Producto actualizado', timer: 1500, showConfirmButton: false });
    } else {
        lotesAgregados.push(itemData);
        notificacion.fire({ icon: 'success', title: 'Producto añadido a la Factura', timer: 1500, showConfirmButton: false });
    }

    cancelarEdicionProducto();
    renderizarLotesAgregados();
    recalcularTotalesGenerales();
};
window.agregarProductoAFactura = agregarProductoAFactura;

const cancelarEdicionProducto = function () {
    editandoLoteIndex = null;
    $('#prod_select_id').val('');
    $('#prod_cantidad').val(1);
    $('#prod_costo_unitario').val('');
    $('#prod_descuento').val('0.00');
    $('#prod_iva').val('16');
    $('#prod_margen_detal').val('30');
    $('#prod_precio_detal').val('');
    $('#prod_margen_mayorista').val('15');
    $('#prod_precio_mayorista').val('');
    $('#prod_detal_con_iva_badge').text('$ 0.00 | Bs. 0.00');
    $('#prod_detal_sin_iva').text('$ 0.00 | Bs. 0.00');
    $('#prod_mayorista_con_iva_badge').text('$ 0.00 | Bs. 0.00');
    $('#prod_mayorista_sin_iva').text('$ 0.00 | Bs. 0.00');
    $('#badgeEstadoEdicionProducto').text('Nuevo Renglón de Producto');
    $('#btnAccionProductoTexto').text('Agregar este Producto a la Factura');
    $('#btnAccionProductoIcono').removeClass('fa-save').addClass('fa-plus-circle');
    $('#btnCancelarEdicionProducto').hide();
};
window.cancelarEdicionProducto = cancelarEdicionProducto;

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
    recalcularPreciosProducto();
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
    cancelarEdicionProducto();
    renderizarLotesAgregados();
    volverAFase1();

    const hoy = new Date().toISOString().split('T')[0];
    $('#fecha_emision').val(hoy);
    $('#fecha_recepcion').val(hoy);
    $('#dias_credito').val(30);
    $('#switchIncluirFleteFactura').prop('checked', false);
    seleccionarMonedaDocumento('USD');
    cambiarModoItem('moto');
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
    const fleteIngresado = parseFloat($('#lote_flete_unitario').val()) || 0;
    const ivaPorcentaje = parseFloat($('#lote_iva').val()) || 0;
    const aplicaIva = ivaPorcentaje > 0;

    const res = window.CalculosCompra.calcularPreciosDesdeMargen({
        costo: costoIngresado,
        flete: fleteIngresado,
        ivaPorcentaje: ivaPorcentaje,
        aplicaIva: aplicaIva,
        margenDetal: $('#lote_margen_detal').val(),
        margenMayorista: $('#lote_margen_mayorista').val(),
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (!esVes) {
        $('#lote_costo_equivalente').text(`Base: Bs. ${res.costoBaseBs.toFixed(4)}`);
        $('#lote_flete_bs').text(`Flete: Bs. ${res.fleteBs.toFixed(4)}`);
        $('#lote_precio_detal').val(res.costoTotalUsd > 0 ? res.precioDetalConIvaUsd.toFixed(4) : '');
        $('#lote_precio_mayorista').val(res.costoTotalUsd > 0 ? res.precioMayoristaConIvaUsd.toFixed(4) : '');
    } else {
        $('#lote_costo_equivalente').text(`Base: $ ${res.costoBaseUsd.toFixed(4)}`);
        $('#lote_flete_bs').text(`Flete: $ ${res.fleteUsd.toFixed(4)}`);
        $('#lote_precio_detal').val(res.costoTotalBs > 0 ? res.precioDetalConIvaBs.toFixed(4) : '');
        $('#lote_precio_mayorista').val(res.costoTotalBs > 0 ? res.precioMayoristaConIvaBs.toFixed(4) : '');
    }

    $('#lote_costo_total_usd').text(`$ ${res.costoTotalUsd.toFixed(4)}`);
    $('#lote_costo_total_bs').text(`Bs. ${res.costoTotalBs.toFixed(4)}`);
    $('#lote_detal_con_iva_badge').text(`$ ${res.precioDetalConIvaUsd.toFixed(2)} | Bs. ${res.precioDetalConIvaBs.toFixed(2)}`);
    $('#lote_detal_sin_iva').text(`$ ${res.precioDetalUsd.toFixed(2)} | Bs. ${res.precioDetalBs.toFixed(2)}`);
    $('#lote_mayorista_con_iva_badge').text(`$ ${res.precioMayoristaConIvaUsd.toFixed(2)} | Bs. ${res.precioMayoristaConIvaBs.toFixed(2)}`);
    $('#lote_mayorista_sin_iva').text(`$ ${res.precioMayoristaUsd.toFixed(2)} | Bs. ${res.precioMayoristaBs.toFixed(2)}`);
};
window.recalcularPreciosLote = recalcularPreciosLote;

const calcularPrecioDetalLote = function () {
    recalcularPreciosLote();
};
window.calcularPrecioDetalLote = calcularPrecioDetalLote;

const calcularMargenDetalLote = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#lote_costo_unitario').val(),
        flete: $('#lote_flete_unitario').val(),
        precio: $('#lote_precio_detal').val(),
        tipoPrecio: 'con_iva',
        ivaPorcentaje: $('#lote_iva').val(),
        aplicaIva: parseFloat($('#lote_iva').val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#lote_margen_detal').val(res.margen.toFixed(0));
        $('#lote_detal_con_iva_badge').text(`$ ${res.precioConIvaUsd.toFixed(2)} | Bs. ${res.precioConIvaBs.toFixed(2)}`);
        $('#lote_detal_sin_iva').text(`$ ${res.precioUsd.toFixed(2)} | Bs. ${res.precioBs.toFixed(2)}`);
    }
};
window.calcularMargenDetalLote = calcularMargenDetalLote;

const calcularPrecioMayoristaLote = function () {
    recalcularPreciosLote();
};
window.calcularPrecioMayoristaLote = calcularPrecioMayoristaLote;

const calcularMargenMayoristaLote = function () {
    const res = window.CalculosCompra.calcularMargenDesdePrecio({
        costo: $('#lote_costo_unitario').val(),
        flete: $('#lote_flete_unitario').val(),
        precio: $('#lote_precio_mayorista').val(),
        tipoPrecio: 'con_iva',
        ivaPorcentaje: $('#lote_iva').val(),
        aplicaIva: parseFloat($('#lote_iva').val()) > 0,
        tasaCompra: tasaCompraActual,
        tasaVenta: tasaVentaActual,
        moneda: monedaSeleccionada,
    });

    if (res.precioUsd > 0 || res.precioBs > 0) {
        $('#lote_margen_mayorista').val(res.margen.toFixed(0));
        $('#lote_mayorista_con_iva_badge').text(`$ ${res.precioConIvaUsd.toFixed(2)} | Bs. ${res.precioConIvaBs.toFixed(2)}`);
        $('#lote_mayorista_sin_iva').text(`$ ${res.precioUsd.toFixed(2)} | Bs. ${res.precioUsd > 0 ? (res.precioUsd * tasaVentaActual).toFixed(2) : '0.00'}`);
    }
};
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
    const fleteUnitarioInput = parseFloat($('#lote_flete_unitario').val()) || 0;

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
    let fleteUnitarioUsd = 0;
    let fleteUnitarioBs = 0;

    if (!esVes) {
        costoUnitarioUsd = costoUnitarioInput;
        costoUnitarioBs = tasaMenor ? (costoUnitarioUsd * tVenta) : (costoUnitarioUsd * tCompra);
        fleteUnitarioUsd = fleteUnitarioInput;
        fleteUnitarioBs = tasaMenor ? (fleteUnitarioUsd * tVenta) : (fleteUnitarioUsd * tCompra);
    } else {
        costoUnitarioBs = costoUnitarioInput;
        costoUnitarioUsd = tasaMenor ? (costoUnitarioBs / tCompra) : (costoUnitarioBs / tVenta);
        fleteUnitarioBs = fleteUnitarioInput;
        fleteUnitarioUsd = tasaMenor ? (fleteUnitarioBs / tCompra) : (fleteUnitarioBs / tVenta);
    }

    const descPct = parseFloat($('#lote_descuento').val()) || 0;
    const ivaPct = parseFloat($('#lote_iva').val()) || 0;
    const aplicaIva = ivaPct > 0;
    const margenDetal = parseFloat($('#lote_margen_detal').val()) || 0;
    const margenMayorista = parseFloat($('#lote_margen_mayorista').val()) || 0;

    const descUsd = costoUnitarioUsd * (descPct / 100);
    const costoNetoUsd = costoUnitarioUsd - descUsd;
    const ivaUnitarioUsd = aplicaIva ? (costoNetoUsd * (ivaPct / 100)) : 0;
    const costoTotalUnitarioUsd = costoNetoUsd + ivaUnitarioUsd + fleteUnitarioUsd;
    const costoTotalUnitarioBs = costoTotalUnitarioUsd * (tasaMenor ? tVenta : tCompra);

    const sugeridoDetalConIvaUsd = !esVes
        ? (tasaMenor ? (costoTotalUnitarioUsd * (1 + margenDetal / 100)) : ((costoTotalUnitarioUsd * (1 + margenDetal / 100) * tCompra) / tVenta))
        : (costoTotalUnitarioUsd * (1 + margenDetal / 100));
    const precioDetalConIvaUsd = !esVes
        ? (parseFloat($('#lote_precio_detal').val()) || sugeridoDetalConIvaUsd)
        : ((parseFloat($('#lote_precio_detal').val()) || (sugeridoDetalConIvaUsd * tVenta)) / tVenta);
    const precioDetalUsd = aplicaIva ? (precioDetalConIvaUsd / (1 + (ivaPct / 100))) : precioDetalConIvaUsd;
    const precioDetalBs = precioDetalUsd * tVenta;
    const precioDetalConIvaBs = precioDetalConIvaUsd * tVenta;

    const sugeridoMayorConIvaUsd = !esVes
        ? (tasaMenor ? (costoTotalUnitarioUsd * (1 + margenMayorista / 100)) : ((costoTotalUnitarioUsd * (1 + margenMayorista / 100) * tCompra) / tVenta))
        : (costoTotalUnitarioUsd * (1 + margenMayorista / 100));
    const precioMayoristaConIvaUsd = !esVes
        ? (parseFloat($('#lote_precio_mayorista').val()) || sugeridoMayorConIvaUsd)
        : ((parseFloat($('#lote_precio_mayorista').val()) || (sugeridoMayorConIvaUsd * tVenta)) / tVenta);
    const precioMayoristaUsd = aplicaIva ? (precioMayoristaConIvaUsd / (1 + (ivaPct / 100))) : precioMayoristaConIvaUsd;
    const precioMayoristaBs = precioMayoristaUsd * tVenta;
    const precioMayoristaConIvaBs = precioMayoristaConIvaUsd * tVenta;

    const subtotalRenglonUsd = costoNetoUsd * cantidad;
    const ivaRenglonUsd = ivaUnitarioUsd * cantidad;
    const totalRenglonUsd = subtotalRenglonUsd + ivaRenglonUsd;

    const loteData = {
        tipo_item: 'moto',
        producto_id: null,
        almacen_id: $('#almacen_id').val(),
        referencia: referencia,
        marca: marca,
        modelo: modelo,
        anio: anio,
        color: color,
        cilindrada: cilindrada,
        cantidad: cantidad,
        costo_unitario_usd: costoUnitarioUsd,
        costo_unitario_bs: costoUnitarioBs,
        flete_unitario_usd: fleteUnitarioUsd,
        flete_unitario_bs: fleteUnitarioBs,
        costo_total_unitario_usd: costoTotalUnitarioUsd,
        costo_total_unitario_bs: costoTotalUnitarioBs,
        descuento_porcentaje: descPct,
        descuento_usd: descUsd,
        descuento_bs: descUsd * tCompra,
        aplica_iva: aplicaIva,
        iva_porcentaje: ivaPct,
        iva_monto_usd: ivaUnitarioUsd,
        iva_monto_bs: ivaUnitarioUsd * tCompra,
        margen_detal: margenDetal,
        precio_detal_usd: precioDetalUsd,
        precio_detal_bs: precioDetalBs,
        precio_detal_con_iva_usd: precioDetalConIvaUsd,
        precio_detal_con_iva_bs: precioDetalConIvaBs,
        margen_mayorista: margenMayorista,
        precio_mayorista_usd: precioMayoristaUsd,
        precio_mayorista_bs: precioMayoristaBs,
        precio_mayorista_con_iva_usd: precioMayoristaConIvaUsd,
        precio_mayorista_con_iva_bs: precioMayoristaConIvaBs,
        subtotal_usd: subtotalRenglonUsd,
        subtotal_bs: subtotalRenglonUsd * tCompra,
        iva_usd: ivaRenglonUsd,
        iva_bs: ivaRenglonUsd * tCompra,
        total_usd: totalRenglonUsd,
        total_bs: totalRenglonUsd * tCompra,
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

    if (lote.tipo_item === 'producto') {
        cambiarModoItem('producto');
        $('#prod_select_id').val(lote.producto_id);
        $('#prod_almacen_id').val(lote.almacen_id);
        $('#prod_cantidad').val(lote.cantidad);

        const costoVal = esVes
            ? (tasaMenor ? lote.costo_unitario_usd * tCompra : lote.costo_unitario_usd * tVenta)
            : lote.costo_unitario_usd;
        $('#prod_costo_unitario').val(costoVal.toFixed(4));
        $('#prod_descuento').val(lote.descuento_porcentaje);
        $('#prod_iva').val(lote.iva_porcentaje);
        $('#prod_margen_detal').val(lote.margen_detal);
        $('#prod_precio_detal').val(esVes ? (lote.precio_detal_con_iva_bs || lote.precio_detal_con_iva_usd * tVenta).toFixed(4) : (lote.precio_detal_con_iva_usd || lote.precio_detal_usd).toFixed(4));
        $('#prod_margen_mayorista').val(lote.margen_mayorista);
        $('#prod_precio_mayorista').val(esVes ? (lote.precio_mayorista_con_iva_bs || lote.precio_mayorista_con_iva_usd * tVenta).toFixed(4) : (lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd).toFixed(4));

        const prodDetalConIvaUsd = lote.precio_detal_con_iva_usd || lote.precio_detal_usd;
        const prodDetalConIvaBs = lote.precio_detal_con_iva_bs || (prodDetalConIvaUsd * tVenta);
        const prodMayorConIvaUsd = lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd;
        const prodMayorConIvaBs = lote.precio_mayorista_con_iva_bs || (prodMayorConIvaUsd * tVenta);

        $('#prod_detal_con_iva_badge').text(`$ ${prodDetalConIvaUsd.toFixed(2)} | Bs. ${prodDetalConIvaBs.toFixed(2)}`);
        $('#prod_detal_sin_iva').text(`$ ${lote.precio_detal_usd.toFixed(2)} | Bs. ${lote.precio_detal_bs.toFixed(2)}`);
        $('#prod_mayorista_con_iva_badge').text(`$ ${prodMayorConIvaUsd.toFixed(2)} | Bs. ${prodMayorConIvaBs.toFixed(2)}`);
        $('#prod_mayorista_sin_iva').text(`$ ${lote.precio_mayorista_usd.toFixed(2)} | Bs. ${lote.precio_mayorista_bs.toFixed(2)}`);

        $('#badgeEstadoEdicionProducto').text(`Editando Renglón #${index + 1}`);
        $('#btnAccionProductoTexto').text('Guardar Cambios del Producto');
        $('#btnAccionProductoIcono').removeClass('fa-plus-circle').addClass('fa-save');
        $('#btnCancelarEdicionProducto').show();
        document.getElementById('cardConstructorProducto').scrollIntoView({ behavior: 'smooth', block: 'start' });
        return;
    }

    cambiarModoItem('moto');
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
    $('#lote_costo_unitario').val(costoVal.toFixed(4));

    const fleteVal = esVes
        ? (tasaMenor ? lote.flete_unitario_usd * tCompra : lote.flete_unitario_usd * tVenta)
        : lote.flete_unitario_usd;
    $('#lote_flete_unitario').val(fleteVal.toFixed(4));

    $('#lote_descuento').val(lote.descuento_porcentaje);
    $('#lote_iva').val(lote.iva_porcentaje);
    $('#lote_margen_detal').val(lote.margen_detal);
    
    const detalVal = esVes ? (lote.precio_detal_con_iva_bs || lote.precio_detal_con_iva_usd * tVenta) : (lote.precio_detal_con_iva_usd || lote.precio_detal_usd);
    $('#lote_precio_detal').val(detalVal.toFixed(4));
    
    $('#lote_margen_mayorista').val(lote.margen_mayorista);
    const mayorVal = esVes ? (lote.precio_mayorista_con_iva_bs || lote.precio_mayorista_con_iva_usd * tVenta) : (lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd);
    $('#lote_precio_mayorista').val(mayorVal.toFixed(4));

    const costoEquivBs = tasaMenor ? (lote.costo_unitario_usd * tVenta) : (lote.costo_unitario_usd * tCompra);
    const fleteEquivBs = tasaMenor ? (lote.flete_unitario_usd * tVenta) : (lote.flete_unitario_usd * tCompra);

    $('#lote_costo_equivalente').text(esVes ? `Base: $ ${lote.costo_unitario_usd.toFixed(4)}` : `Base: Bs. ${costoEquivBs.toFixed(4)}`);
    $('#lote_flete_bs').text(esVes ? `Flete: $ ${lote.flete_unitario_usd.toFixed(4)}` : `Flete: Bs. ${fleteEquivBs.toFixed(4)}`);
    $('#lote_costo_total_usd').text(`$ ${lote.costo_total_unitario_usd.toFixed(4)}`);
    $('#lote_costo_total_bs').text(`Bs. ${lote.costo_total_unitario_bs.toFixed(4)}`);

    const motoDetalConIvaUsd = lote.precio_detal_con_iva_usd || lote.precio_detal_usd;
    const motoDetalConIvaBs = lote.precio_detal_con_iva_bs || (motoDetalConIvaUsd * tVenta);
    const motoMayorConIvaUsd = lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd;
    const motoMayorConIvaBs = lote.precio_mayorista_con_iva_bs || (motoMayorConIvaUsd * tVenta);

    $('#lote_detal_con_iva_badge').text(`$ ${motoDetalConIvaUsd.toFixed(2)} | Bs. ${motoDetalConIvaBs.toFixed(2)}`);
    $('#lote_detal_sin_iva').text(`$ ${lote.precio_detal_usd.toFixed(2)} | Bs. ${lote.precio_detal_bs.toFixed(2)}`);
    $('#lote_mayorista_con_iva_badge').text(`$ ${motoMayorConIvaUsd.toFixed(2)} | Bs. ${motoMayorConIvaBs.toFixed(2)}`);
    $('#lote_mayorista_sin_iva').text(`$ ${lote.precio_mayorista_usd.toFixed(2)} | Bs. ${lote.precio_mayorista_bs.toFixed(2)}`);

    generarMatrizSeriales(lote.seriales || []);

    $('#badgeEstadoEdicionLote').removeClass('bg-primary-subtle text-primary border-primary-subtle')
        .addClass('bg-warning-subtle text-warning-emphasis border-warning-subtle')
        .text(`Editando Renglón #${index + 1}`);
    $('#btnAccionLoteTexto').text('Guardar Cambios del Modelo');
    $('#btnAccionLoteIcono').removeClass('fa-plus-circle').addClass('fa-save');
    $('#btnCancelarEdicionLote').show();

    document.getElementById('cardConstructorMoto').scrollIntoView({ behavior: 'smooth', block: 'start' });
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
    $('#lote_flete_unitario').val('0.0000');
    $('#lote_descuento').val('0.00');
    $('#lote_iva').val('16');
    $('#lote_margen_detal').val('25');
    $('#lote_precio_detal').val('');
    $('#lote_margen_mayorista').val('15');
    $('#lote_precio_mayorista').val('');
    $('#lote_costo_equivalente').text(monedaSeleccionada === 'VES' ? 'Base: $ 0.00' : 'Base: Bs. 0.00');
    $('#lote_flete_bs').text(monedaSeleccionada === 'VES' ? 'Flete: $ 0.00' : 'Flete: Bs. 0.00');
    $('#lote_costo_total_usd').text('$ 0.0000');
    $('#lote_costo_total_bs').text('Bs. 0.0000');
    $('#lote_detal_con_iva_badge').text('$ 0.00 | Bs. 0.00');
    $('#lote_detal_sin_iva').text('$ 0.00 | Bs. 0.00');
    $('#lote_mayorista_con_iva_badge').text('$ 0.00 | Bs. 0.00');
    $('#lote_mayorista_sin_iva').text('$ 0.00 | Bs. 0.00');

    $('#badgeEstadoEdicionLote').removeClass('bg-warning-subtle text-warning-emphasis border-warning-subtle')
        .addClass('bg-primary-subtle text-primary border-primary-subtle')
        .text('Nuevo Renglón de Moto');
    $('#btnAccionLoteTexto').text('Agregar este Modelo de Moto');
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
        cancelarEdicionProducto();
    }
    lotesAgregados.splice(index, 1);
    renderizarLotesAgregados();
    recalcularTotalesGenerales();
};

const renderizarLotesAgregados = function () {
    const $tbody = $('#tbodyRecepcionMotoDetalles').empty();
    let totalUnidades = 0;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;

    if (lotesAgregados.length === 0) {
        $tbody.html(`
            <tr id="filaSinLotes">
                <td colspan="13" class="text-center py-4 text-muted">
                    <i class="fas fa-motorcycle fs-3 d-block mb-2 opacity-50"></i>
                    No has agregado ningún renglón (moto o producto) a esta factura.
                </td>
            </tr>
        `);
        $('#contadorLotesMotos').text('0 Renglones (0 Unidades)');
        actualizarCuadreFactura();
        return;
    }

    lotesAgregados.forEach((lote, idx) => {
        totalUnidades += lote.cantidad;
        const costoUsd = lote.costo_unitario_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const fleteUsd = (lote.flete_unitario_usd || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const costoTotalUsd = (lote.costo_total_unitario_usd || lote.costo_unitario_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const subtotalUsd = lote.subtotal_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const detalSinIva = lote.precio_detal_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const detalConIvaUsd = (lote.precio_detal_con_iva_usd || lote.precio_detal_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const detalConIvaBs = ((lote.precio_detal_con_iva_usd || lote.precio_detal_usd) * tasaVentaActual).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const mayorSinIva = lote.precio_mayorista_usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const mayorConIvaUsd = (lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const mayorConIvaBs = ((lote.precio_mayorista_con_iva_usd || lote.precio_mayorista_usd) * tasaVentaActual).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        const esMoto = (lote.tipo_item === 'moto');
        const badgeTipo = esMoto
            ? '<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-0.5"><i class="fas fa-motorcycle me-1"></i>Moto</span>'
            : '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-0.5"><i class="fas fa-box me-1"></i>Prod</span>';

        $tbody.append(`
            <tr>
                <td class="text-center font-monospace fw-bold">${idx + 1}</td>
                <td class="text-center">${badgeTipo}</td>
                <td>
                    <strong class="text-dark">${lote.marca} ${lote.modelo}</strong>
                    <small class="text-muted d-block font-monospace">Ref: ${lote.referencia}${lote.cilindrada ? ' | ' + lote.cilindrada : ''}${lote.color ? ' | ' + lote.color : ''}</small>
                </td>
                <td class="text-center">
                    <span class="badge rounded-pill bg-dark text-white fw-bold px-2 py-1">${lote.cantidad} unids</span>
                </td>
                <td class="text-end font-monospace">
                    <span class="fw-semibold text-dark">$ ${costoUsd}</span>
                </td>
                <td class="text-end font-monospace text-warning">
                    ${esMoto ? '$ ' + fleteUsd : '<span class="text-muted">--</span>'}
                </td>
                <td class="text-end font-monospace">
                    <span class="fw-bold text-dark">$ ${costoTotalUsd}</span>
                </td>
                <td class="text-center">
                    <span class="badge rounded-pill ${lote.aplica_iva ? 'bg-secondary text-white' : 'bg-light text-muted border'}">${lote.iva_porcentaje}%</span>
                </td>
                <td class="text-end font-monospace" style="font-size: 0.78rem;">
                    <span class="text-muted small">Sin: $ ${detalSinIva}</span><br>
                    <span class="badge rounded-pill px-2 py-0.5 font-monospace fw-bold" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.74rem;">$ ${detalConIvaUsd} | Bs. ${detalConIvaBs}</span>
                </td>
                <td class="text-end font-monospace" style="font-size: 0.78rem;">
                    <span class="text-muted small">Sin: $ ${mayorSinIva}</span><br>
                    <span class="badge rounded-pill px-2 py-0.5 font-monospace fw-bold" style="background-color: #faf5ff; color: #6b21a8; border: 1px solid #d8b4fe; font-size: 0.74rem;">$ ${mayorConIvaUsd} | Bs. ${mayorConIvaBs}</span>
                </td>
                <td class="text-end font-monospace">
                    <strong class="text-success">$ ${subtotalUsd}</strong>
                </td>
                <td class="text-center">
                    ${esMoto ? `
                        <button type="button" class="btn btn-outline-success btn-sm rounded-pill px-2 py-1 font-monospace" onclick="verSerialesLote(${idx})" title="Ver Seriales" style="font-size: 0.72rem;">
                            <i class="fas fa-fingerprint me-1"></i> ${lote.seriales?.length || 0}
                        </button>
                    ` : '<span class="text-muted small">N/A</span>'}
                </td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-circle p-1" onclick="editarLote(${idx})" title="Editar Renglón">
                            <i class="fas fa-edit" style="font-size: 0.75rem;"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle p-1" onclick="eliminarLote(${idx})" title="Eliminar Renglón">
                            <i class="fas fa-trash-alt" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `);
    });

    $('#contadorLotesMotos').text(`${lotesAgregados.length} Renglones (${totalUnidades} Unidades)`);
    actualizarCuadreFactura();
};

const recalcularTotalesGenerales = function () {
    let baseImponibleUsd = 0;
    let exentoUsd = 0;
    let ivaUsd = 0;
    let fleteTotalUsd = 0;
    let ivaPctGeneral = 16.00;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;

    lotesAgregados.forEach(l => {
        if (l.aplica_iva && l.iva_porcentaje > 0) {
            baseImponibleUsd += l.subtotal_usd;
            ivaUsd += l.iva_usd;
            ivaPctGeneral = l.iva_porcentaje;
        } else {
            exentoUsd += l.subtotal_usd;
        }
        if (l.tipo_item === 'moto') {
            fleteTotalUsd += ((l.flete_unitario_usd || 0) * l.cantidad);
        }
    });

    const descGlobalPct = parseFloat($('#descuento_global_porcentaje').val()) || 0;
    const subtotalBruto = baseImponibleUsd + exentoUsd;
    const descGlobalUsd = subtotalBruto * (descGlobalPct / 100);

    let baseFinalUsd = baseImponibleUsd;
    let exentoFinalUsd = exentoUsd;

    if (descGlobalPct > 0) {
        baseFinalUsd = baseImponibleUsd * (1 - (descGlobalPct / 100));
        exentoFinalUsd = exentoUsd * (1 - (descGlobalPct / 100));
        ivaUsd = baseFinalUsd * (ivaPctGeneral / 100);
    }

    const incluirFlete = $('#switchIncluirFleteFactura').is(':checked');
    const subtotalNetoUsd = baseFinalUsd + exentoFinalUsd;
    const totalFacturaUsd = subtotalNetoUsd + ivaUsd + (incluirFlete ? fleteTotalUsd : 0);

    const baseFinalBs = baseFinalUsd * tCompra;
    const descGlobalBs = descGlobalUsd * tCompra;
    const exentoFinalBs = exentoFinalUsd * tCompra;
    const ivaBs = ivaUsd * tCompra;
    const fleteTotalBs = fleteTotalUsd * tCompra;
    const totalFacturaBs = totalFacturaUsd * tCompra;

    $('#resumenBaseImponible').text(`$ ${baseFinalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${baseFinalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenDescuentoUsd').text(`-$ ${descGlobalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | -Bs. ${descGlobalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenExento').text(`$ ${exentoFinalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${exentoFinalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#labelResumenIva').text(`IVA (${ivaPctGeneral}%):`);
    $('#resumenIvaUsd').text(`$ ${ivaUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${ivaBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenFleteTotal').text(`$ ${fleteTotalUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} | Bs. ${fleteTotalBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenTotalUsd').text(`$ ${totalFacturaUsd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
    $('#resumenTotalBs').text(`Bs. ${totalFacturaBs.toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);

    actualizarCuadreFactura();
};

const actualizarCuadreFactura = function () {
    const montoFacturaInput = parseFloat($('#monto_bruto_input').val()) || 0;
    const tCompra = tasaCompraActual > 0 ? tasaCompraActual : 1.0;
    const montoFacturaUsd = (monedaSeleccionada === 'VES') ? (montoFacturaInput / tCompra) : montoFacturaInput;

    const incluirFlete = $('#switchIncluirFleteFactura').is(':checked');
    let sumaRenglonesUsd = 0;
    let fleteTotalUsd = 0;

    lotesAgregados.forEach(l => {
        sumaRenglonesUsd += l.subtotal_usd + l.iva_usd;
        if (l.tipo_item === 'moto') {
            fleteTotalUsd += ((l.flete_unitario_usd || 0) * l.cantidad);
        }
    });

    if (incluirFlete) {
        sumaRenglonesUsd += fleteTotalUsd;
    }

    const $badgeCuadre = $('#badgeEstadoCuadreFactura');

    if (lotesAgregados.length === 0) {
        $badgeCuadre.html('<i class="fas fa-scale-balanced me-1"></i> Sin renglones cargados')
            .css({ 'background-color': '#f8fafc', 'color': '#64748b', 'border': '1px solid #e2e8f0' });
        return;
    }

    const diferencia = Math.abs(montoFacturaUsd - sumaRenglonesUsd);

    if (diferencia < 0.05) {
        $badgeCuadre.html('<i class="fas fa-check-circle me-1"></i> Factura Cuadrada ($ ' + sumaRenglonesUsd.toFixed(2) + ')')
            .css({ 'background-color': '#dcfce7', 'color': '#15803d', 'border': '1px solid #86efac' });
    } else if (sumaRenglonesUsd < montoFacturaUsd) {
        const falta = (montoFacturaUsd - sumaRenglonesUsd).toFixed(2);
        $badgeCuadre.html(`<i class="fas fa-clock me-1"></i> Faltan $ ${falta} por cargar`)
            .css({ 'background-color': '#fef3c7', 'color': '#b45309', 'border': '1px solid #fde68a' });
    } else {
        const sobra = (sumaRenglonesUsd - montoFacturaUsd).toFixed(2);
        $badgeCuadre.html(`<i class="fas fa-exclamation-triangle me-1"></i> Excede por $ ${sobra}`)
            .css({ 'background-color': '#fee2e2', 'color': '#b91c1c', 'border': '1px solid #fca5a5' });
    }
};

const procesarRecepcionMoto = async function () {
    if (lotesAgregados.length === 0) {
        return notificacion.fire({
            icon: 'warning',
            title: 'Sin Renglones Registrados',
            text: 'Debes agregar al menos una moto o producto a la recepción.'
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
        incluir_flete_en_factura: $('#switchIncluirFleteFactura').is(':checked') ? 1 : 0,
        observaciones: $('#observaciones').val(),
        detalles: lotesAgregados
    };

    const confirm = await Swal.fire({
        title: '¿Procesar Recepción?',
        text: `Se registrarán ${lotesAgregados.reduce((a, b) => a + b.cantidad, 0)} unidades (motos y productos) y se ingresarán al inventario.`,
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
        text: 'Registrando lotes, seriales y actualizando inventario...',
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
                if (d.tipo_item === 'moto' && d.motos && d.motos.length > 0) {
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
                }

                const tituloRenglon = d.tipo_item === 'moto'
                    ? `${idx + 1}. [MOTO] ${d.marca} ${d.modelo} (${d.anio} - ${d.color})`
                    : `${idx + 1}. [PRODUCTO] ${d.producto?.nombre || d.modelo || 'Producto General'}`;

                tablaDetalles += `
                    <div class="card border rounded-4 p-3 mb-3 bg-white shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold text-dark mb-0">${tituloRenglon}</h6>
                            <span class="badge ${d.tipo_item === 'moto' ? 'bg-primary' : 'bg-success'} rounded-pill px-3 py-1">${d.cantidad} Unidades</span>
                        </div>
                        <div class="row g-2 font-monospace small text-muted mb-2">
                            <div class="col-md-3">Costo Unit: $ ${parseFloat(d.costo_unitario_usd).toFixed(2)}</div>
                            <div class="col-md-3">Flete Unit: $ ${parseFloat(d.flete_unitario_usd || 0).toFixed(2)}</div>
                            <div class="col-md-3">PVP Detal: $ ${parseFloat(d.precio_detal_usd).toFixed(2)}</div>
                            <div class="col-md-3 text-end fw-bold text-success">Total Renglón: $ ${parseFloat(d.total_usd).toFixed(2)}</div>
                        </div>
                        ${serialesHtml ? `
                            <div class="mt-2">
                                <small class="fw-bold text-dark d-block mb-1"><i class="fas fa-fingerprint text-success me-1"></i> Seriales Registrados:</small>
                                ${serialesHtml}
                            </div>
                        ` : ''}
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

                <h6 class="fw-bold text-dark mb-2"><i class="fas fa-boxes-stacked text-primary me-2"></i> Renglones y Unidades Recibidas</h6>
                ${tablaDetalles}

                <div class="card border-0 rounded-4 p-3 text-white text-end font-monospace" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
                    <div class="fs-5 fw-bold text-warning">TOTAL FACTURA: $ ${parseFloat(r.total_usd).toFixed(2)}</div>
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
        text: "Se revertirán las motos y productos del inventario si no han sido vendidos.",
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
