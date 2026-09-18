const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";

$(document).ready(function () {
    const tabla = $("#datatable_historial").DataTable({
        ajax: {
            url: urlLista,
            data: function (d) {
                d.fecha_inicio = $("#filtro_fecha_inicio").val();
                d.fecha_fin = $("#filtro_fecha_fin").val();
                d.estado = $("#filtro_estado").val();
            },
        },
        responsive: true,
        processing: true,
        serverSide: true,
        order: [[0, "desc"]],
        columns: [
            {
                data: "fecha_dia",
                name: "created_at",
                render: function (data, type, row) {
                    return `
                        <span class="fw-bold text-dark d-block">${data}</span>
                        <span class="text-muted small">${row.fecha_hora}</span>
                    `;
                },
            },
            {
                data: "correlativo",
                name: "correlativo",
                render: function (data, type, row) {
                    return `
                        <span class="fw-bold text-primary d-block">Factura: ${data}</span>
                        <span class="text-secondary small">Orden: ${row.orden_codigo}</span>
                    `;
                },
            },
            {
                data: "paciente_nombre",
                name: "paciente_nombre",
                orderable: false,
                searchable: true,
                render: function (data, type, row) {
                    let infoAdicional = "-";
                    if (row.es_menor) {
                        infoAdicional = "Menor";
                    } else if (row.paciente_cedula) {
                        infoAdicional = "C.I: " + row.paciente_cedula;
                    } else {
                        infoAdicional = "Sin C.I.";
                    }
                    const badgeConvenio = row.convenio_nombre
                        ? `<span class="badge rounded-pill px-2 py-0.5 fw-bold d-inline-block mt-1" style="background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.72rem;"><i class="fas fa-handshake me-1"></i>${row.convenio_nombre}</span>`
                        : '';
                    return `
                        <span class="fw-bold text-dark d-block">${data}</span>
                        <span class="text-muted small">${infoAdicional}</span>
                        ${badgeConvenio}
                    `;
                },
            },
            {
                data: "total_usd",
                name: "total_usd",
                render: function (data) {
                    return `<span class="fw-bold">$${parseFloat(data).toFixed(2)}</span>`;
                },
            },
            {
                data: "pagado_usd",
                name: "pagado_usd",
                orderable: false,
                searchable: false,
                render: function (data) {
                    return `<span class="fw-bold text-success">$${parseFloat(data).toFixed(2)}</span>`;
                },
            },
            {
                data: null,
                name: "deuda",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    let deuda =
                        parseFloat(row.total_usd) - parseFloat(row.pagado_usd);
                    if (deuda < 0.01) deuda = 0;
                    let color = deuda > 0 ? "text-danger" : "text-success";
                    return `<span class="fw-bold ${color}">$${deuda.toFixed(2)}</span>`;
                },
            },
            {
                data: "estado",
                name: "estado",
                render: function (data) {
                    let badgeClass = "bg-secondary";
                    if (data === "Pagada") badgeClass = "bg-success";
                    if (data === "Pendiente")
                        badgeClass = "bg-warning text-dark";
                    if (data === "Anulada") badgeClass = "bg-danger";
                    return `<span class="badge rounded-pill ${badgeClass}">${data}</span>`;
                },
            },
            {
                data: null,
                name: "acciones",
                orderable: false,
                searchable: false,
                className: "text-end",
                render: function (data, type, row) {
                    let btnAbonar = "";
                    let btnAnular = "";
                    let btnAñadirExamen = "";
                    let deuda =
                        parseFloat(row.total_usd) - parseFloat(row.pagado_usd);

                    if (row.estado !== "Anulada" && window.canAnular) {
                        btnAnular = `<button class="btn btn-sm btn-outline-danger rounded-circle me-1" title="Anular Factura" onclick="abrirModalAnular(${row.id})">
                                        <i class="fas fa-ban"></i>
                                     </button>`;
                    }

                    if (row.estado === "Pendiente" && deuda > 0.005) {
                        btnAbonar = `<button class="btn btn-sm btn-outline-success rounded-circle me-1" title="Registrar Abono" onclick="abrirModalAbono(${row.id}, ${deuda}, ${row.tasa_cambio})">
                                        <i class="fas fa-hand-holding-usd"></i>
                                     </button>`;
                    }

                    let estadosBloqueados = [
                        "Validada",
                        "Entregada",
                        "Completada",
                    ];
                    if (
                        row.estado !== "Anulada" &&
                        row.orden &&
                        !estadosBloqueados.includes(row.orden.estado)
                    ) {
                        btnAñadirExamen = `<button class="btn btn-sm btn-outline-info rounded-circle me-1" title="Gestionar Exámenes (Añadir/Eliminar)" onclick="abrirModalAddExamen(${row.id})">
                                              <i class="fas fa-flask"></i>
                                           </button>`;
                    }

                    return `
                        ${btnAñadirExamen}
                        ${btnAbonar}
                        ${btnAnular}
                        <button class="btn btn-sm btn-outline-primary rounded-circle" title="Imprimir" onclick="imprimirFactura(${row.id})">
                            <i class="fas fa-print"></i>
                        </button>
                    `;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Factura, orden, paciente, cédula...",
            zeroRecords: "No se encontraron resultados",
            emptyTable: "Ningún dato disponible en esta tabla",
            lengthMenu: "Mostrar _MENU_ registros",
            info: "Mostrando _START_ al _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 al 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros)",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior",
            },
            sProcessing: "Cargando...",
        },
    });

    // Filtrado en tiempo real al cambiar inputs
    $("#filtro_fecha_inicio, #filtro_fecha_fin, #filtro_estado").on("change input", function () {
        tabla.ajax.reload();
    });

    window.filtrarRapidoHistorial = function (estado, btn) {
        $("#filtros-historial-rapido .btn").removeClass("active");
        $(btn).addClass("active");
        $("#filtro_estado").val(estado);
        tabla.ajax.reload();
    };

    window.limpiarFiltrosHistorial = function () {
        $("#filtro_fecha_inicio").val("");
        $("#filtro_fecha_fin").val("");
        $("#filtro_estado").val("");
        $("#filtros-historial-rapido .btn").removeClass("active");
        $("#filtros-historial-rapido .btn:first").addClass("active");
        tabla.ajax.reload();
    };

    // Lógica para el método de pago
    $("#abono_metodo_pago").on("change", function () {
        const metodo = $(this).val();
        if (
            ["Pago Movil", "Transf. (Bs)", "Zelle", "Binance", "Punto de Venta", "Biopago"].includes(metodo)
        ) {
            $("#contenedor_referencia").show();
            $("#abono_referencia").attr("required", true);
        } else {
            $("#contenedor_referencia").hide();
            $("#abono_referencia").removeAttr("required").val("");
        }
    });

    // Cálculos de conversión de moneda en tiempo real
    $("#abono_monto_usd").on("input", function () {
        let usd = parseFloat($(this).val()) || 0;
        let tasa = parseFloat($("#abono_tasa_cambio").val()) || 0;
        let deudaMax = parseFloat($("#abono_deuda_restante").val()) || 0;

        if (usd > deudaMax) {
            usd = deudaMax;
            $(this).val(usd.toFixed(2));
            notificacion.fire({
                icon: "warning",
                title: "Monto Máximo Alcanzado",
                text: "El abono no puede superar la deuda restante.",
            });
        }

        $("#abono_monto_bs").val((usd * tasa).toFixed(2));
    });

    // Enviar el formulario de abono
    $("#formularioAbono").on("submit", function (e) {
        e.preventDefault();

        let usd = parseFloat($("#abono_monto_usd").val()) || 0;
        let deudaMax = parseFloat($("#abono_deuda_restante").val()) || 0;

        if (usd > deudaMax) {
            notificacion.fire({
                icon: "error",
                title: "Error",
                text: "El abono no puede ser mayor que la deuda.",
            });
            return;
        }

        const facturaId = $("#abono_factura_id").val();
        const btn = $("#btnGuardarAbono");
        btn.prop("disabled", true).html(
            '<span class="spinner-border spinner-border-sm"></span> Procesando...',
        );

        $.ajax({
            url: urlCompleta + `/abonar/${facturaId}`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                monto_usd: $("#abono_monto_usd").val(),
                monto_bs: $("#abono_monto_bs").val(),
                metodo_pago: $("#abono_metodo_pago").val(),
                referencia: $("#abono_referencia").val(),
            },
            success: function (res) {
                if (res.success) {
                    notificacion.fire({
                        icon: "success",
                        title: "Éxito",
                        text: res.message,
                    });
                    $("#modalAbonar").modal("hide");
                    tabla.ajax.reload();
                } else {
                    notificacion.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message,
                    });
                }
            },
            error: function (xhr) {
                let msg =
                    xhr.responseJSON?.message || "Error al registrar abono";
                notificacion.fire({ icon: "error", title: "Error", text: msg });
            },
            complete: function () {
                btn.prop("disabled", false).html(
                    '<i class="fas fa-save"></i> Registrar Abono',
                );
            },
        });
    });

    // Enviar el formulario de anulación
    $("#formularioAnular").on("submit", function (e) {
        e.preventDefault();

        const facturaId = $("#anular_factura_id").val();
        const btn = $("#btnGuardarAnulacion");
        btn.prop("disabled", true).html(
            '<span class="spinner-border spinner-border-sm"></span> Procesando...',
        );

        $.ajax({
            url: urlCompleta + `/anular/${facturaId}`,
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr("content"),
                observacion: $("#anular_observacion").val(),
            },
            success: function (res) {
                if (res.success) {
                    notificacion.fire({
                        icon: "success",
                        title: "Anulada",
                        text: res.message,
                    });
                    $("#modalAnular").modal("hide");
                    tabla.ajax.reload();
                } else {
                    notificacion.fire({
                        icon: "error",
                        title: "Error",
                        text: res.message,
                    });
                }
            },
            error: function (xhr) {
                let msg =
                    xhr.responseJSON?.message || "Error al anular la factura";
                notificacion.fire({ icon: "error", title: "Error", text: msg });
            },
            complete: function () {
                btn.prop("disabled", false).html(
                    '<i class="fas fa-ban"></i> Confirmar Anulación',
                );
            },
        });
    });
});

window.abrirModalAbono = function (facturaId, deudaRestante, tasaCambio) {
    $.get("/ingreso/tasa-actual", function (res) {
        let tasaFinal = res.success ? res.tasa : tasaCambio;

        $("#abono_factura_id").val(facturaId);
        $("#abono_tasa_cambio").val(tasaFinal);
        $("#abono_deuda_restante").val(deudaRestante);
        $("#txtDeudaRestante").text("$" + deudaRestante.toFixed(2));

        $("#formularioAbono").trigger("reset");
        $("#contenedor_referencia").hide();
        $("#abono_referencia").removeAttr("required");

        $("#modalAbonar").modal("show");
    });
};

window.abrirModalAnular = function (facturaId) {
    $("#anular_factura_id").val(facturaId);
    $("#formularioAnular").trigger("reset");
    $("#modalAnular").modal("show");
};

window.imprimirFactura = function (id) {
    window.open(`/factura/${id}/imprimir`, "_blank");
};

let nuevoCarritoAdd = [];
let itemsActualesFactura = [];
let facturaConvenioId = null;

window.abrirModalAddExamen = function (facturaId) {
    $("#add_factura_id").val(facturaId);
    nuevoCarritoAdd = [];
    itemsActualesFactura = [];
    facturaConvenioId = null;
    renderTablaNuevosExamenes();
    cargarDetallesFacturaActual(facturaId);
    $("#modalAddExamen").modal("show");
};

function cargarDetallesFacturaActual(facturaId) {
    const tbody = $("#tabla_examenes_actuales tbody");
    tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted small"><span class="spinner-border spinner-border-sm me-2 text-primary"></span> Cargando exámenes actuales...</td></tr>');
    $("#badge_correlativo").text("");
    $("#badge_orden").text("");
    $("#resumen_total_usd").text("$0.00");
    $("#resumen_pagado_usd").text("$0.00");
    $("#resumen_deuda_usd").text("$0.00");
    $("#badge_count_actuales").text("0");

    $.ajax({
        url: urlCompleta + "/factura/" + facturaId + "/detalles",
        type: "GET",
        success: function (res) {
            if (res.success && res.data) {
                const data = res.data;
                itemsActualesFactura = data.items || [];
                facturaConvenioId = data.convenio_id || null;

                $("#badge_correlativo").html('<i class="fas fa-file-alt me-1"></i> Factura: ' + data.correlativo);
                $("#badge_orden").html('<i class="fas fa-clipboard-list me-1"></i> Orden: ' + (data.orden_codigo || 'N/A'));
                
                if (data.convenio_nombre) {
                    $("#txt_convenio_factura").text(data.convenio_nombre);
                    $("#badge_convenio_factura").removeClass("d-none").show();
                } else {
                    $("#txt_convenio_factura").text("Tarifa Estándar");
                    $("#badge_convenio_factura").removeClass("d-none").show();
                }

                $("#resumen_total_usd").text("$" + data.total_usd.toFixed(2));
                $("#resumen_pagado_usd").text("$" + data.pagado_usd.toFixed(2));
                $("#resumen_deuda_usd").text("$" + data.deuda_usd.toFixed(2));

                if (data.deuda_usd <= 0.005) {
                    $("#box_resumen_deuda")
                        .removeClass("bg-danger border-danger")
                        .addClass("bg-success bg-opacity-10 border-success border-opacity-10");
                    $("#resumen_deuda_usd")
                        .removeClass("text-danger")
                        .addClass("text-success")
                        .text("$0.00");
                } else {
                    $("#box_resumen_deuda")
                        .removeClass("bg-success border-success")
                        .addClass("bg-danger bg-opacity-10 border-danger border-opacity-10");
                    $("#resumen_deuda_usd")
                        .removeClass("text-success")
                        .addClass("text-danger");
                }

                $("#badge_count_actuales").text(itemsActualesFactura.length);

                renderTablaExamenesActuales(facturaId, itemsActualesFactura);
            }
        },
        error: function (err) {
            const msg = err.responseJSON ? err.responseJSON.message : "Error al cargar los exámenes actuales.";
            tbody.html(`<tr><td colspan="4" class="text-center text-danger small py-4">${msg}</td></tr>`);
        }
    });
}

function renderTablaExamenesActuales(facturaId, items) {
    const tbody = $("#tabla_examenes_actuales tbody");
    tbody.empty();

    if (!items || items.length === 0) {
        tbody.append('<tr><td colspan="4" class="text-center text-muted small py-4">No hay exámenes registrados en esta orden</td></tr>');
        return;
    }

    items.forEach((item) => {
        const iconBadge = item.tipo === "perfil"
            ? '<div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 34px; height: 34px;"><i class="fas fa-cubes"></i></div>'
            : '<div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 34px; height: 34px;"><i class="fas fa-flask"></i></div>';

        const estadoBadgeClass = item.estado_resultado === 'Completado' || item.estado_resultado === 'Validado'
            ? 'bg-success bg-opacity-10 text-success border border-success border-opacity-25'
            : (item.estado_resultado === 'Transcrito' ? 'bg-info bg-opacity-10 text-info border border-info border-opacity-25' : 'bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25');

        let btnEliminar = "";
        if (item.puede_eliminar) {
            btnEliminar = `<button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" title="Eliminar examen" onclick="eliminarExamenDeFactura(${facturaId}, ${item.id}, '${item.nombre.replace(/'/g, "\\'")}')">
                                <i class="fas fa-trash-alt"></i>
                           </button>`;
        } else {
            const razon = items.length <= 1
                ? 'No se puede eliminar el único examen restante de la orden'
                : 'El monto pagado superaría el total resultante';
            btnEliminar = `<button type="button" class="btn btn-sm btn-light text-muted rounded-circle opacity-50" style="width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" disabled title="${razon}">
                                <i class="fas fa-trash-alt"></i>
                           </button>`;
        }

        tbody.append(`
            <tr>
                <td class="ps-3 py-2">
                    <div class="d-flex align-items-center">
                        ${iconBadge}
                        <div>
                            <div class="fw-bold text-dark">${item.nombre}</div>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0" style="font-size: 0.7rem;">${item.tipo}</span>
                        </div>
                    </div>
                </td>
                <td class="text-end fw-bold text-dark pe-3">$${item.precio_usd.toFixed(2)}</td>
                <td class="text-center"><span class="badge ${estadoBadgeClass} rounded-pill px-3 py-1 fw-semibold">${item.estado_resultado}</span></td>
                <td class="text-center pe-3">${btnEliminar}</td>
            </tr>
        `);
    });
}

window.eliminarExamenDeFactura = function (facturaId, detalleId, nombreExamen) {
    Swal.fire({
        title: "¿Eliminar examen?",
        html: `¿Estás seguro de eliminar el servicio <strong>${nombreExamen}</strong> de la factura y orden de servicio?<br><small class="text-muted">Se descontará automáticamente su valor del total de la factura.</small>`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: '<i class="fas fa-trash-alt me-1"></i> Sí, eliminar',
        cancelButtonText: "Cancelar",
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: urlCompleta + "/factura/" + facturaId + "/eliminar-examen/" + detalleId,
                type: "POST",
                data: {
                    _token: $('meta[name="csrf-token"]').attr("content"),
                },
                success: function (res) {
                    if (res.success) {
                        notificacion.fire({
                            icon: "success",
                            title: "Examen Eliminado",
                            text: res.message,
                        });
                        cargarDetallesFacturaActual(facturaId);
                        tabla.ajax.reload(null, false);
                    }
                },
                error: function (err) {
                    const msg = err.responseJSON ? err.responseJSON.message : "Error al eliminar el examen.";
                    notificacion.fire({ icon: "error", title: "Error", text: msg });
                },
            });
        }
    });
};

$("#buscador_servicios_historial").select2({
    dropdownParent: $("#modalAddExamen"),
    theme: "bootstrap-5",
    placeholder: "Escribe para buscar examen o perfil...",
    allowClear: true,
    ajax: {
        url: "/ingreso/buscar-servicio",
        dataType: "json",
        delay: 250,
        data: function (params) {
            return {
                q: params.term,
                convenio_id: facturaConvenioId,
            };
        },
        processResults: function (data) {
            return {
                results: $.map(data, function (item) {
                    return {
                        id: item.id,
                        text: item.nombre,
                        precio: item.precio,
                        tipo: item.tipo,
                    };
                }),
            };
        },
        cache: true,
    },
});

$("#buscador_servicios_historial").on("select2:select", function (e) {
    const data = e.params.data;

    // Verificar si ya está en los que se van a agregar
    const existeEnNuevos = nuevoCarritoAdd.find(
        (item) => item.id === data.id && item.tipo === data.tipo,
    );
    if (existeEnNuevos) {
        notificacion.fire({
            icon: "warning",
            title: "Ya en lista",
            text: "Este servicio ya está en la lista de nuevos exámenes a añadir.",
        });
        $(this).val(null).trigger("change");
        return;
    }

    // Verificar si ya está en los exámenes actuales
    const existeEnActuales = itemsActualesFactura.find(
        (item) => item.nombre.toLowerCase().trim() === data.text.toLowerCase().trim() && item.tipo === data.tipo,
    );
    if (existeEnActuales) {
        notificacion.fire({
            icon: "warning",
            title: "Ya registrado",
            text: "Este servicio ya se encuentra registrado actualmente en esta orden.",
        });
        $(this).val(null).trigger("change");
        return;
    }

    nuevoCarritoAdd.push({
        id: data.id,
        nombre: data.text,
        precio: parseFloat(data.precio),
        tipo: data.tipo,
    });
    renderTablaNuevosExamenes();

    $(this).val(null).trigger("change");
});

function renderTablaNuevosExamenes() {
    const tbody = $("#tabla_nuevos_examenes tbody");
    const tfoot = $("#tfoot_nuevos");
    tbody.empty();

    if (nuevoCarritoAdd.length === 0) {
        tbody.append(
            '<tr id="fila_vacia_add"><td colspan="3" class="text-center text-muted small py-4"><i class="fas fa-cart-plus fa-2x text-muted opacity-50 mb-2 d-block"></i><span>Busca y selecciona exámenes arriba para añadirlos</span></td></tr>',
        );
        tfoot.hide();
        $("#btnGuardarAddExamen")
            .prop("disabled", true)
            .html('<i class="fas fa-plus me-1"></i> Guardar y Añadir');
        $("#total_nuevo_usd").text("$0.00");
        return;
    }

    tfoot.show();
    $("#btnGuardarAddExamen").prop("disabled", false);
    let total = 0;

    nuevoCarritoAdd.forEach((item, index) => {
        total += item.precio;
        const iconBadge =
            item.tipo === "perfil"
                ? '<div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 30px; height: 30px;"><i class="fas fa-cubes" style="font-size: 0.8rem;"></i></div>'
                : '<div class="bg-info bg-opacity-10 text-info rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 30px; height: 30px;"><i class="fas fa-flask" style="font-size: 0.8rem;"></i></div>';

        tbody.append(`
            <tr>
                <td class="ps-3 py-2">
                    <div class="d-flex align-items-center">
                        ${iconBadge}
                        <div>
                            <div class="fw-bold text-dark">${item.nombre}</div>
                            <span class="badge bg-light text-muted border rounded-pill px-2 py-0" style="font-size: 0.7rem;">${item.tipo}</span>
                        </div>
                    </div>
                </td>
                <td class="text-end fw-bold text-success pe-3">$${item.precio.toFixed(2)}</td>
                <td class="text-center pe-3">
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-circle shadow-sm" style="width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center;" onclick="removerExamenNuevo(${index})" title="Quitar de la lista">
                        <i class="fas fa-times"></i>
                    </button>
                </td>
            </tr>
        `);
    });

    $("#total_nuevo_usd").text("$" + total.toFixed(2));
}

window.removerExamenNuevo = function (index) {
    nuevoCarritoAdd.splice(index, 1);
    renderTablaNuevosExamenes();
};

$("#formularioAddExamen").on("submit", function (e) {
    e.preventDefault();

    if (nuevoCarritoAdd.length === 0) return;

    const facturaId = $("#add_factura_id").val();
    const btn = $("#btnGuardarAddExamen");
    btn.prop("disabled", true).html(
        '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...',
    );

    $.ajax({
        url: urlCompleta + "/factura/" + facturaId + "/add-examenes",
        type: "POST",
        data: {
            carrito: nuevoCarritoAdd,
        },
        success: function (res) {
            if (res.success) {
                $("#modalAddExamen").modal("hide");
                notificacion.fire({
                    icon: "success",
                    title: "Exámenes Añadidos",
                    text: res.message,
                });
                tabla.ajax.reload(null, false);
                window.open("/factura/" + facturaId + "/imprimir", "_blank");
            }
        },
        error: function (err) {
            const msg = err.responseJSON
                ? err.responseJSON.message
                : "Error interno.";
            notificacion.fire({ icon: "error", title: "Error", text: msg });
        },
        complete: function () {
            btn.prop("disabled", false).html(
                '<i class="fas fa-plus me-1"></i> Guardar y Añadir',
            );
        },
    });
});
