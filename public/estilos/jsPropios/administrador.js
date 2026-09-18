
const soloLetras = (e) => {
    const key = e.keyCode || e.which;
    const teclado = String.fromCharCode(key).toLowerCase();
    const letras = " áéíóúabcdefghijklmnñopqrstuvwxyz";
    if (letras.indexOf(teclado) === -1 && key !== 8 && key !== 13) return false;
};

const soloNumeros = (e) => {
    const key = e.keyCode || e.which;
    const teclado = String.fromCharCode(key);
    const numeros = "0123456789";
    if (numeros.indexOf(teclado) === -1 && key !== 8 && key !== 13) return false;
};

const sinEspacios = (e) => {
    if (e.keyCode === 32) return false;
};

$(document).ready(function () {
    $('#nombre, #apellido').on('keypress', soloLetras);
    $('#telefono').on('keypress', soloNumeros);
    $('#name, #email, #password').on('keypress', sinEspacios);

    $('#telefono').on('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});

const consultar = (id) => {
    return $.ajax({
        url: urlDetalles + id,
        type: 'GET',
        dataType: 'json'
    });
};

crear = function () {
    isEditar = false;
    urlAccion = urlGuardar;
    $('#modalAdministrador').modal('show');
    $('#tituloModal').text('Nuevo Administrador');
    $('#colorModal').attr('class', 'modal-header modal-colored-header bg-primary');
    $('#formulario').trigger('reset');
    $('#formulario').find('input, select').prop('disabled', false);
    $('#guardarModal').prop('hidden', false);
};

editar = async function (id) {
    urlAccion = urlEditar + id;
    try {
        isEditar = true;
        const data = await consultar(id);
        $('#modalAdministrador').modal('show');
        $('#tituloModal').text('Editar Administrador: ' + data.name);
        $('#colorModal').attr('class', 'modal-header modal-colored-header bg-dark');
        $('#formulario').trigger('reset');
        $('#formulario').find('input, select').prop('disabled', false);
        $('#guardarModal').prop('hidden', false);

        $('#name').val(data.name);
        $('#email').val(data.email);
        $('#nombre').val(data.nombre);
        $('#apellido').val(data.apellido);
        $('#telefono').val(data.telefono);
    } catch (error) {
        notificacion.fire({ icon: 'error', title: 'Error', text: 'No se pudieron cargar los datos.' });
    }
};

eliminar = async function (id) {
    try {
        const data = await consultar(id);
        Swal.fire({
            title: '¿Estás seguro?',
            html: `Se eliminará al administrador <span class='text-danger'>${data.name}</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: urlEliminar + id,
                    type: "DELETE",
                    success: function (response) {
                        if (response.success) {
                            $('#datatable_administradores').DataTable().ajax.reload(null, false);
                            notificacion.fire({ icon: 'success', title: '¡Eliminado!', text: response.message });
                        }
                    }
                });
            }
        });
    } catch (error) {
        notificacion.fire({ icon: 'error', title: 'Error' });
    }
};

$('#formulario').on('submit', function (e) {
    e.preventDefault();

    if ($('#nombre').val().trim().length < 2 || $('#apellido').val().trim().length < 2) {
        return notificacion.fire({ icon: 'warning', title: 'Nombre o Apellido demasiado corto' });
    }

    if ($('#telefono').val().length < 7) {
        return notificacion.fire({ icon: 'warning', title: 'Teléfono incompleto' });
    }

    const email = $('#email').val().trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        return notificacion.fire({ icon: 'warning', title: 'Correo electrónico inválido' });
    }

    let formData = new FormData(this);
    if (isEditar) formData.append('_method', 'PUT');

    const btn = $('#guardarModal');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Procesando...');

    $.ajax({
        url: urlAccion,
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
            if (response.success) {
                $('#modalAdministrador').modal('hide');
                $('#datatable_administradores').DataTable().ajax.reload(null, false);
                notificacion.fire({ icon: 'success', title: isEditar ? '¡Información Editada!' : '¡Información Guardada!', text: response.message });
            } else {
                notificacion.fire({ icon: 'error', title: 'Error', text: response.message });
            }
        },
        error: function (xhr) {
            let msg = xhr.responseJSON?.message || 'Ocurrió un error';
            notificacion.fire({ icon: 'error', title: 'Error', text: msg });
        },
        complete: function () {
            btn.prop('disabled', false).text(isEditar ? 'Actualizar' : 'Guardar');
        }
    });
});