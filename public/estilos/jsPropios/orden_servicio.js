$(document).ready(function () {
    iniciarDatatable();
});

function iniciarDatatable() {
    if ($.fn.DataTable.isDataTable("#datatable_ordenes")) {
        $("#datatable_ordenes").DataTable().destroy();
    }

    const tabla = $("#datatable_ordenes").DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        ajax: {
            url: "/ordenes-servicio/lista",
            data: function (d) {
                d.estado = $("#filtro_estado").val();
                d.fecha_inicio = $("#filtro_fecha_inicio").val();
                d.fecha_fin = $("#filtro_fecha_fin").val();
            },
        },
        order: [[0, "desc"]],
        columns: [
            {
                data: null,
                name: "codigo",
                render: (row) => {
                    const codigo = row.codigo || "-";
                    const correlativo = row.factura?.correlativo || "Sin Factura";

                    return `
                    <div class="ps-2">
                        <span class="fw-bold text-primary d-block h6 mb-1">${codigo}</span>
                        <span class="badge bg-light text-muted border rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                            <i class="fas fa-file-invoice me-1"></i>${correlativo}
                        </span>
                    </div>`;
                },
            },
            {
                data: "paciente",
                name: "paciente.cedula",
                render: function (data, type, row) {
                    let nombre = data
                        ? ((data.nombreUno || "") + " " + (data.apellidoUno || "")).trim()
                        : "Sin Paciente";
                    
                    let icon = (data && data.es_menor)
                        ? '<div class="bg-warning bg-opacity-10 text-dark rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 34px; height: 34px;"><i class="fas fa-child"></i></div>'
                        : '<div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex justify-content-center align-items-center me-2 shadow-xs" style="width: 34px; height: 34px;"><i class="fas fa-user"></i></div>';

                    let infoAdicional = "-";
                    if (data) {
                        if (data.es_menor) {
                            infoAdicional = '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-2 py-0" style="font-size: 0.7rem;">MENOR</span> ' + (data.codigo_registro || "");
                        } else if (data.cedula) {
                            infoAdicional = '<span class="text-muted small">C.I: ' + data.cedula + '</span>';
                        } else {
                            infoAdicional = '<span class="text-muted small">Sin C.I.</span>';
                        }
                    }

                    return `
                        <div class="d-flex align-items-center">
                            ${icon}
                            <div>
                                <div class="fw-bold text-dark">${nombre}</div>
                                <div>${infoAdicional}</div>
                            </div>
                        </div>
                    `;
                },
            },
            {
                data: "created_at",
                name: "created_at",
                className: "text-center",
                render: function (data) {
                    if (!data) return '<span class="text-muted">-</span>';
                    let date = new Date(data);
                    let fecha_str = date.toLocaleDateString();
                    let hora_str = date.toLocaleTimeString([], {
                        hour: "2-digit",
                        minute: "2-digit",
                    });
                    return `
                        <span class="fw-bold text-dark d-block">${fecha_str}</span>
                        <span class="text-muted small">${hora_str}</span>
                    `;
                },
            },
            {
                data: "estado",
                name: "estado",
                className: "text-center",
                render: function (data, type, row) {
                    if (data === "Completada" || data === "Entregada") {
                        return `<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-double me-1"></i>${data}</span>`;
                    } else if (data === "Validada") {
                        return `<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-check-circle me-1"></i>${data}</span>`;
                    } else if (data === "Transcrita") {
                        return `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-keyboard me-1"></i>${data}</span>`;
                    } else if (data === "En Proceso") {
                        return `<span class="badge bg-warning bg-opacity-10 text-dark border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-spinner me-1"></i>${data}</span>`;
                    } else {
                        return `<span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 rounded-pill px-3 py-1 fw-bold"><i class="fas fa-clock me-1"></i>Pendiente</span>`;
                    }
                },
            },
            {
                data: "id",
                name: "acciones",
                className: "text-end",
                orderable: false,
                searchable: false,
                render: function (data, type, row) {
                    let esFinalizada = ["Completada", "Validada", "Entregada"].includes(row.estado);
                    let text = esFinalizada ? "Ver / Editar" : (row.estado === "Transcrita" ? "Validar" : "Procesar");
                    let icon = esFinalizada ? "fa-eye" : (row.estado === "Transcrita" ? "fa-check-circle" : "fa-flask");
                    let btnClass = esFinalizada ? "btn-outline-secondary" : (row.estado === "Transcrita" ? "btn-info text-white" : "btn-primary");
                    return `
                        <div class="d-flex justify-content-end align-items-center gap-2 pe-2">
                            <a href="/ordenes-servicio/procesar/${data}" class="btn btn-sm ${btnClass} rounded-pill px-3 shadow-sm fw-bold">
                                <i class="fas ${icon} me-1"></i> ${text}
                            </a>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border rounded-circle shadow-none p-0 d-flex align-items-center justify-content-center" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 32px; height: 32px;" title="Más acciones">
                                    <i class="fas fa-ellipsis-v text-muted" style="font-size: 0.85rem;"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-4 p-2" style="font-size: 0.85rem;">
                                    <li>
                                        <a class="dropdown-item rounded-3 py-2" href="/ordenes-servicio/${data}/imprimir-resultados" target="_blank">
                                            <i class="fas fa-print me-2 text-primary"></i> Imprimir Todo
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-3 py-2" href="/ordenes-servicio/${data}/imprimir-resultados?solo_listos=1" target="_blank">
                                            <i class="fas fa-check-circle me-2 text-info"></i> Imprimir Solo Listos (Parcial)
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <button class="dropdown-item rounded-3 py-2" type="button" onclick="enviarCorreoLista(${data})">
                                            <i class="fas fa-envelope me-2 text-success"></i> Enviar Correo
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    `;
                },
            },
        ],
        language: {
            sSearch: "Buscar:",
            searchPlaceholder: "Nombre, apellido, cédula, código...",
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

    // Filtrado automático e instantáneo
    $('#filtro_estado, #filtro_fecha_inicio, #filtro_fecha_fin').off('change input').on('change input', function () {
        tabla.ajax.reload();
    });

    window.filtrarRapidoOrdenes = function (estado, btn) {
        $('#filtros-ordenes-rapido .btn').removeClass('active');
        $(btn).addClass('active');
        $('#filtro_estado').val(estado);
        tabla.ajax.reload();
    };

    window.limpiarFiltrosOrdenes = function () {
        $('#filtro_estado').val('');
        $('#filtro_fecha_inicio').val('');
        $('#filtro_fecha_fin').val('');
        $('#filtros-ordenes-rapido .btn').removeClass('active');
        $('#filtros-ordenes-rapido .btn:first').addClass('active');
        tabla.ajax.reload();
    };
}

window.enviarCorreoLista = function (id) {
    Swal.fire({
        title: "Enviar Resultados por Correo",
        text: "¿Deseas enviar solo los exámenes que ya están listos o el informe completo?",
        icon: "question",
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-check-circle me-1"></i> Solo Listos (Parcial)',
        denyButtonText: '<i class="fas fa-file-alt me-1"></i> Informe Completo',
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#10b981",
        denyButtonColor: "#0891b2",
    }).then((result) => {
        if (result.isConfirmed || result.isDenied) {
            let soloListos = result.isConfirmed ? 1 : 0;
            Swal.fire({
                title: "Enviando...",
                text: "Por favor espere mientras se procesa el envío",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            $.ajax({
                url: `/ordenes-servicio/${id}/enviar-correo`,
                type: "POST",
                data: { solo_listos: soloListos },
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                },
                success: function (response) {
                    if (response.success) {
                        Swal.fire({
                            icon: "success",
                            title: "Correo Encolado",
                            text: response.message,
                        });
                    } else {
                        Swal.fire({
                            icon: "error",
                            title: "Error",
                            text: response.message,
                        });
                    }
                },
                error: function (xhr) {
                    let mensajeError = "Ocurrió un problema al encolar el correo.";
                    if (xhr.responseJSON?.message) {
                        mensajeError = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: mensajeError,
                    });
                },
            });
        }
    });
};
