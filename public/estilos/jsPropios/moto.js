const urlBase = window.location.origin + window.location.pathname.replace(/\/$/, "");
const urlLista = urlBase + "/lista";
const urlDetalles = urlBase + "/";
const urlActualizar = urlBase + "/actualizar/";

let datatableMotos = null;
let almacenesLista = [];

$(document).ready(function () {
    inicializarTabla();
    cargarAlmacenes();
});

const inicializarTabla = function () {
    datatableMotos = crearDataTable({
        selector: "#datatable_motos",
        url: urlLista,
        columns: [
            {
                data: 'modelo',
                name: 'modelo',
                render: function (data, type, row) {
                    return `
                        <div>
                            <strong class="text-dark fs-6">${row.marca} ${data}</strong>
                            <small class="text-muted d-block font-monospace">Ref: ${row.referencia} | ${row.cilindrada}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'anio',
                name: 'anio',
                className: 'text-center',
                render: function (data, type, row) {
                    return `
                        <div>
                            <span class="badge rounded-pill bg-light text-dark border">${data}</span>
                            <small class="text-muted d-block mt-1">${row.color}</small>
                        </div>
                    `;
                }
            },
            {
                data: 'numero_niv',
                name: 'numero_niv',
                render: function (data) {
                    return `<span class="badge rounded-pill bg-light text-dark border font-monospace fw-bold px-2 py-1"><i class="fas fa-fingerprint me-1 text-primary"></i>${data}</span>`;
                }
            },
            {
                data: 'numero_chasis',
                name: 'numero_chasis',
                className: 'font-monospace text-muted small',
                render: function (data) {
                    return data;
                }
            },
            {
                data: 'numero_motor',
                name: 'numero_motor',
                className: 'font-monospace text-muted small',
                render: function (data) {
                    return data;
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
                data: 'precio_detal_usd',
                name: 'precio_detal_usd',
                className: 'text-end',
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.precio_detal_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `<div><strong class="text-primary">$ ${usd}</strong><br><small class="text-muted font-monospace">Bs. ${bs}</small></div>`;
                }
            },
            {
                data: 'precio_mayorista_usd',
                name: 'precio_mayorista_usd',
                className: 'text-end',
                render: function (data, type, row) {
                    const usd = parseFloat(data || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const bs = parseFloat(row.precio_mayorista_bs || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    return `<div><strong style="color: #7e22ce;">$ ${usd}</strong><br><small class="text-muted font-monospace">Bs. ${bs}</small></div>`;
                }
            },
            {
                data: 'estado',
                name: 'estado',
                className: 'text-center',
                render: function (data) {
                    switch (data) {
                        case 'disponible':
                            return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="fas fa-check-circle me-1"></i>Disponible</span>';
                        case 'reservada':
                            return '<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis border border-warning-subtle px-2 py-1"><i class="fas fa-clock me-1"></i>Reservada</span>';
                        case 'vendida':
                            return '<span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-2 py-1"><i class="fas fa-file-invoice me-1"></i>Vendida</span>';
                        case 'en_mantenimiento':
                            return '<span class="badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="fas fa-wrench me-1"></i>Taller</span>';
                        default:
                            return `<span class="badge rounded-pill bg-light text-dark border">${data}</span>`;
                    }
                }
            },
            {
                data: 'id',
                name: 'id',
                className: 'text-end',
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    return `
                        <div class="d-flex justify-content-end gap-1">
                            <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-2" onclick="verFicha(${data})" title="Ver Ficha 360°">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-2" onclick="editarMoto(${data})" title="Modificar Vehículo">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    `;
                }
            }
        ]
    });
};

const cargarAlmacenes = async function () {
    try {
        const respuesta = await $.ajax({
            url: "/almacenes/lista",
            type: "GET",
            dataType: "json"
        });
        if (respuesta.data && Array.isArray(respuesta.data)) {
            almacenesLista = respuesta.data;
            const $select = $('#edit_almacen_id').empty();
            almacenesLista.forEach(a => {
                $select.append(`<option value="${a.id}">${a.nombre} (${a.codigo || 'ALM'})</option>`);
            });
        }
    } catch (e) {
        console.error("Error al cargar almacenes:", e);
    }
};

const verFicha = async function (id) {
    try {
        const respuesta = await $.ajax({
            url: urlDetalles + id,
            type: "GET",
            dataType: "json"
        });

        if (respuesta.success && respuesta.data) {
            const m = respuesta.data;
            $('#fichaMotoNivHeader').text(`NIV: ${m.numero_niv}`);

            let estadoBadge = '';
            if (m.estado === 'disponible') estadoBadge = '<span class="badge rounded-pill bg-success text-white px-3 py-1"><i class="fas fa-check-circle me-1"></i>Disponible para Venta</span>';
            else if (m.estado === 'reservada') estadoBadge = '<span class="badge rounded-pill bg-warning text-dark px-3 py-1"><i class="fas fa-clock me-1"></i>Reservada</span>';
            else if (m.estado === 'vendida') estadoBadge = '<span class="badge rounded-pill bg-primary text-white px-3 py-1"><i class="fas fa-file-invoice me-1"></i>Vendida</span>';
            else estadoBadge = `<span class="badge rounded-pill bg-secondary text-white px-3 py-1">${m.estado}</span>`;

            $('#contenidoFichaMoto').html(`
                <!-- ENCABEZADO DE LA FICHA -->
                <div class="card border rounded-4 p-4 bg-white mb-3 shadow-xs">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom pb-3 mb-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar-executive-md rounded-3 bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; font-size: 1.6rem;">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                            <div>
                                <h4 class="fw-bold text-dark mb-0">${m.marca} ${m.modelo}</h4>
                                <div class="d-flex align-items-center gap-2 text-muted small font-monospace">
                                    <span>Año: <strong>${m.anio}</strong></span>
                                    <span>•</span>
                                    <span>Color: <strong>${m.color}</strong></span>
                                    <span>•</span>
                                    <span>Cilindrada: <strong>${m.cilindrada}</strong></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            ${estadoBadge}
                        </div>
                    </div>

                    <!-- MATRIZ DE SERIALES LEGALES -->
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-fingerprint text-success me-2"></i> Identificadores Únicos y Trazabilidad Legal</h6>
                    <div class="row g-2 mb-3 font-mono small">
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">N.I.V. (VIN):</span>
                                <strong class="text-dark">${m.numero_niv}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">N° de Chasis:</span>
                                <strong class="text-dark">${m.numero_chasis}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">N° de Motor:</span>
                                <strong class="text-dark">${m.numero_motor}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">Certificado de Origen:</span>
                                <strong class="text-dark">${m.certificado_origen}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">Placa / Registro:</span>
                                <strong class="text-dark">${m.placa || 'En trámite / Sin placa'}</strong>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-2 border rounded-3 bg-light d-flex justify-content-between">
                                <span class="text-secondary fw-semibold">Ubicación / Almacén:</span>
                                <strong class="text-primary">${m.almacen?.nombre || 'N/A'}</strong>
                            </div>
                        </div>
                    </div>

                    <!-- PRECIOS Y COSTO -->
                    <h6 class="fw-bold text-dark mb-2"><i class="fas fa-coins text-warning me-2"></i> Estructura de Precios</h6>
                    <div class="row g-2 font-mono small">
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <span class="text-muted d-block mb-1">Costo de Compra:</span>
                                <h6 class="fw-bold text-dark mb-0">$ ${parseFloat(m.precio_costo_usd).toFixed(2)}</h6>
                                <small class="text-muted">Bs. ${parseFloat(m.precio_costo_bs).toFixed(2)}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <span class="text-primary fw-semibold d-block mb-1">Precio Detal:</span>
                                <h6 class="fw-bold text-primary mb-0">$ ${parseFloat(m.precio_detal_usd).toFixed(2)}</h6>
                                <small class="text-muted">Bs. ${parseFloat(m.precio_detal_bs).toFixed(2)}</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 border rounded-3 bg-light text-center">
                                <span class="fw-semibold d-block mb-1" style="color: #7e22ce;">Precio Mayorista:</span>
                                <h6 class="fw-bold mb-0" style="color: #7e22ce;">$ ${parseFloat(m.precio_mayorista_usd).toFixed(2)}</h6>
                                <small class="text-muted">Bs. ${parseFloat(m.precio_mayorista_bs).toFixed(2)}</small>
                            </div>
                        </div>
                    </div>

                    <!-- ORIGEN DE COMPRA -->
                    <div class="p-3 border rounded-3 bg-light mt-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block">Proveedor / Ensambladora:</small>
                                <strong class="text-dark">${m.proveedor?.nombre || 'N/A'} (${m.proveedor?.rif || ''})</strong>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Recepción de Origen:</small>
                                <span class="badge bg-white text-dark border font-monospace">${m.recepcion?.codigo || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `);

            $('#btnEditarDesdeFicha').attr('onclick', `editarMoto(${m.id})`);
            $('#modalFichaMoto').modal('show');
        }
    } catch (e) {
        notificacion.fire({ icon: 'error', title: 'Error al cargar la ficha del vehículo.' });
    }
};

const editarMoto = async function (id) {
    try {
        $('#modalFichaMoto').modal('hide');

        const respuesta = await $.ajax({
            url: urlDetalles + id,
            type: "GET",
            dataType: "json"
        });

        if (respuesta.success && respuesta.data) {
            const m = respuesta.data;
            $('#edit_moto_id').val(m.id);
            $('#subtituloEditarMoto').text(`${m.marca} ${m.modelo} - NIV: ${m.numero_niv}`);
            $('#edit_marca').val(m.marca);
            $('#edit_modelo').val(m.modelo);
            $('#edit_referencia').val(m.referencia || '');
            $('#edit_anio').val(m.anio);
            $('#edit_cilindrada').val(m.cilindrada || '');
            $('#edit_numero_niv').val(m.numero_niv);
            $('#edit_numero_chasis').val(m.numero_chasis);
            $('#edit_numero_motor').val(m.numero_motor);
            $('#edit_certificado_origen').val(m.certificado_origen || '');
            $('#edit_color').val(m.color || '');
            $('#edit_placa').val(m.placa || '');
            $('#edit_almacen_id').val(m.almacen_id);
            $('#edit_estado').val(m.estado);
            $('#edit_precio_costo_usd').val(parseFloat(m.precio_costo_usd || 0).toFixed(2));
            $('#edit_precio_detal_usd').val(parseFloat(m.precio_detal_usd || 0).toFixed(2));
            $('#edit_precio_mayorista_usd').val(parseFloat(m.precio_mayorista_usd || 0).toFixed(2));
            $('#edit_observaciones').val(m.observaciones || '');

            $('#modalEditarMoto').modal('show');
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: 'error', title: 'Error al cargar datos para edición.' });
        }
    }
};

const guardarEdicionMoto = async function () {
    const id = $('#edit_moto_id').val();
    const payload = {
        _token: $('meta[name="csrf-token"]').attr('content'),
        marca: $('#edit_marca').val().trim(),
        modelo: $('#edit_modelo').val().trim(),
        referencia: $('#edit_referencia').val().trim(),
        anio: parseInt($('#edit_anio').val()) || new Date().getFullYear(),
        cilindrada: $('#edit_cilindrada').val().trim(),
        numero_niv: $('#edit_numero_niv').val().trim(),
        numero_chasis: $('#edit_numero_chasis').val().trim(),
        numero_motor: $('#edit_numero_motor').val().trim(),
        certificado_origen: $('#edit_certificado_origen').val().trim(),
        color: $('#edit_color').val().trim(),
        placa: $('#edit_placa').val().trim(),
        almacen_id: $('#edit_almacen_id').val(),
        estado: $('#edit_estado').val(),
        precio_costo_usd: $('#edit_precio_costo_usd').val(),
        precio_detal_usd: $('#edit_precio_detal_usd').val(),
        precio_mayorista_usd: $('#edit_precio_mayorista_usd').val(),
        observaciones: $('#edit_observaciones').val().trim(),
    };

    try {
        const respuesta = await $.ajax({
            url: urlActualizar + id,
            type: "PUT",
            data: payload,
            dataType: "json",
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if (respuesta.success) {
            $('#modalEditarMoto').modal('hide');
            if (window.notificacion) {
                window.notificacion.fire({ icon: 'success', title: 'Vehículo Actualizado', text: respuesta.message });
            }
            if (datatableMotos) {
                datatableMotos.ajax.reload();
            }
        }
    } catch (e) {
        if (window.notificacion) {
            window.notificacion.fire({ icon: 'error', title: 'Error al actualizar moto', text: e.responseJSON?.message || 'Verifica los campos ingresados.' });
        }
    }
};
