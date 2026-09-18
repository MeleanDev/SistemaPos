<script>
    /**
     * Componente Global de Notificaciones Toast (SweetAlert2)
     * Uso: notificacion.fire({ icon: 'success'|'error'|'warning'|'info', title: 'Mensaje' })
     * Helper: mostrarToast('success', 'Operación exitosa')
     */
    const notificacion = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    window.mostrarToast = function(tipo, mensaje) {
        notificacion.fire({
            icon: tipo || 'info',
            title: mensaje || ''
        });
    };
</script>
