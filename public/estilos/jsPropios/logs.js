const urlCompleta = window.location.href;
const urlLista = urlCompleta + "/lista";

$(document).ready(function () {
    $("#datatable_logs").DataTable({
        ajax: {
            url: urlLista,
            data: function (d) {
                d.fecha = $('#filtro_fecha').val();
                d.modulo = $('#filtro_modulo').val();
                d.accion = $('#filtro_accion').val();
            }
        },
        responsive: true,
        processing: true,
        serverSide: true,
        lengthMenu: [
            [25, 50, 100],
            [25, 50, 100],
        ],
        order: [[0, 'desc']], // Ordenar por ID descendente (más recientes primero)
        columns: [
            {
                data: "fecha_hora",
                name: "created_at",
                className: "text-center",
                render: function (data) {
                    return `<span class="fw-semibold text-dark small"><i class="far fa-calendar-alt text-muted me-1"></i>${data}</span>`;
                }
            },
            {
                data: "usuario",
                name: "user.name",
                className: "text-center",
                render: function (data) {
                    return `<div class="d-inline-flex align-items-center gap-1 bg-light border px-2 py-1 rounded-pill small">
                        <i class="fas fa-user-circle text-primary"></i>
                        <span class="fw-bold text-dark">${data || 'Sistema'}</span>
                    </div>`;
                }
            },
            {
                data: "action",
                name: "action",
                className: "text-center",
                render: function (data) {
                    let badgeClass = 'bg-secondary bg-opacity-10 text-secondary border border-secondary';
                    let icon = 'fas fa-tag';

                    if (data === 'LOGIN') {
                        badgeClass = 'bg-success bg-opacity-10 text-success border border-success';
                        icon = 'fas fa-sign-in-alt';
                    } else if (data === 'LOGOUT') {
                        badgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning';
                        icon = 'fas fa-sign-out-alt';
                    } else if (data === 'CREAR') {
                        badgeClass = 'bg-success bg-opacity-10 text-success border border-success';
                        icon = 'fas fa-plus';
                    } else if (data === 'EDITAR' || data === 'PROCESAR') {
                        badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary';
                        icon = 'fas fa-edit';
                    } else if (data === 'ELIMINAR' || data === 'ANULAR') {
                        badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger';
                        icon = 'fas fa-trash-alt';
                    } else if (data === 'PAGO') {
                        badgeClass = 'bg-info bg-opacity-10 text-info border border-info';
                        icon = 'fas fa-credit-card';
                    }
                    
                    return `<span class="badge ${badgeClass} rounded-pill px-2 py-1 small fw-bold">
                        <i class="${icon} me-1"></i>${data}
                    </span>`;
                }
            },
            {
                data: "module",
                name: "module",
                className: "text-center",
                render: function (data) {
                    return `<span class="badge bg-light text-dark border rounded-pill px-2 py-1 small font-monospace">${data || '-'}</span>`;
                }
            },
            {
                data: "description",
                name: "description",
                className: "text-start text-muted small",
                render: function (data) {
                    return `<span class="text-secondary">${data || '-'}</span>`;
                }
            },
            {
                data: "ip_address",
                name: "ip_address",
                className: "text-center small text-muted",
                render: function (data) {
                    return data ? `<span class="badge bg-light text-muted font-monospace"><i class="fas fa-laptop me-1"></i>${data}</span>` : '<span class="text-muted">-</span>';
                }
            }
        ],
        language: {
            sProcessing: "Procesando...",
            sLengthMenu: "Mostrar _MENU_ registros",
            sZeroRecords: "No se encontraron resultados",
            sEmptyTable: "Ningún dato disponible en esta tabla",
            sInfo: "Mostrando _START_ al _END_ de _TOTAL_ registros",
            sInfoEmpty: "Mostrando 0 al 0 de 0 registros",
            sInfoFiltered: "(filtrado de un total de _MAX_ registros)",
            sSearch: "Buscar:",
            oPaginate: {
                sFirst: "Primero",
                sLast: "Último",
                sNext: "Siguiente",
                sPrevious: "Anterior",
            }
        },
    });

    // Auto-reload on filter change
    $('#filtro_fecha, #filtro_modulo, #filtro_accion').on('change', function() {
        $('#datatable_logs').DataTable().ajax.reload();
    });
});
