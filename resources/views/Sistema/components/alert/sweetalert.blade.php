<script>
    /**
     * Configuración Global de AJAX y Manejo de Errores con SweetAlert2
     */
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            'Accept': 'application/json'
        }
    });

    $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
        $('.preloader').fadeOut('slow');

        if (jqxhr.status === 419 || jqxhr.status === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Sesión expirada',
                text: 'Tu sesión ha terminado por seguridad. Serás redirigido al inicio.',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            }).then(() => {
                window.location.reload();
            });
        } else if (jqxhr.status >= 500) {
            Swal.fire({
                icon: 'error',
                title: 'Error del Servidor',
                text: 'Ocurrió un problema inesperado en el servidor. Intenta nuevamente.',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4f46e5'
            });
        }
    });

    /**
     * Helper para diálogos de confirmación
     * Uso: confirmarAccion({ title: '¿Eliminar?', text: '...', icon: 'warning' }, callback)
     */
    window.confirmarAccion = function(opciones, callback) {
        const configDefault = {
            title: '¿Estás seguro?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-check me-1"></i> Sí, continuar',
            cancelButtonText: '<i class="fas fa-times me-1"></i> Cancelar',
            reverseButtons: true,
            customClass: {
                popup: 'rounded-4 border-0 shadow-lg',
                confirmButton: 'btn btn-danger rounded-pill px-4 me-2',
                cancelButton: 'btn btn-light rounded-pill px-4'
            },
            buttonsStyling: false
        };

        const config = Object.assign({}, configDefault, opciones);

        Swal.fire(config).then((result) => {
            if (result.isConfirmed && typeof callback === 'function') {
                callback();
            }
        });
    };

    /**
     * Helper para formatear y mostrar errores 422 de Laravel
     */
    window.mostrarErroresValidacion = function(xhr, titulo = 'Error de Validación') {
        let mensaje = 'Ocurrió un error en los datos enviados.';
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            mensaje = Object.values(xhr.responseJSON.errors)
                .map(err => Array.isArray(err) ? err.join('<br>') : err)
                .join('<br>');
        } else if (xhr.responseJSON && xhr.responseJSON.message) {
            mensaje = xhr.responseJSON.message;
        }

        Swal.fire({
            icon: 'error',
            title: titulo,
            html: `<div class="text-start small mb-0">${mensaje}</div>`,
            confirmButtonText: 'Entendido',
            confirmButtonColor: '#4f46e5',
            customClass: {
                popup: 'rounded-4 border-0 shadow-lg',
                confirmButton: 'btn btn-primary rounded-pill px-4'
            },
            buttonsStyling: false
        });
    };
</script>
