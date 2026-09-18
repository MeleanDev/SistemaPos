/**
 * Componente de Formularios y Acciones CRUD para el Sistema POS
 * Maneja el envío de datos mediante FormData, spinners, SweetAlert2 y recarga de tablas
 */

/**
 * Procesa el envío AJAX de un formulario (Crear / Actualizar) usando FormData.
 * 
 * @param {Object} opciones
 * @param {HTMLFormElement|string} opciones.form - Formulario DOM o selector
 * @param {string} opciones.url - URL del endpoint (ej. urlAccion)
 * @param {boolean} [opciones.isEditar=false] - Indica si es actualización (agrega _method: 'PUT')
 * @param {string} [opciones.modalSelector] - Selector del modal para cerrarlo tras éxito (ej. '#modalCliente')
 * @param {string} [opciones.tablaSelector] - Selector del DataTable para recargarlo tras éxito
 * @param {string|jQuery} [opciones.btnSubmit] - Botón de submit para manejar spinner
 * @param {string} [opciones.textoGuardarOriginal] - Texto original del botón de submit
 * @param {Function} [opciones.antesDeEnviar] - Hook callback(formData) para modificar datos antes del envío
 * @param {Function} [opciones.onSuccess] - Callback opcional al completar con éxito
 * @param {Function} [opciones.onError] - Callback opcional al fallar
 */
window.enviarFormulario = function (opciones) {
    const formElement = typeof opciones.form === "string" ? $(opciones.form)[0] : opciones.form;
    if (!formElement) {
        console.error("enviarFormulario: El formulario especificado no existe.");
        return;
    }

    const formData = new FormData(formElement);

    if (opciones.isEditar) {
        formData.append("_method", "PUT");
    }

    // Ejecutar hook de personalización si existe
    if (typeof opciones.antesDeEnviar === "function") {
        opciones.antesDeEnviar(formData);
    }

    const btn = opciones.btnSubmit ? $(opciones.btnSubmit) : null;
    const textoOriginal = opciones.textoGuardarOriginal || (btn ? btn.html() : "Guardar");

    if (btn && btn.length) {
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1"></span> Procesando...');
    }

    $.ajax({
        url: opciones.url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
            if (response.success) {
                if (opciones.modalSelector) {
                    $(opciones.modalSelector).modal("hide");
                }

                if (opciones.tablaSelector) {
                    window.recargarDataTable(opciones.tablaSelector);
                }

                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "success",
                        title: response.message || "Operación realizada exitosamente",
                    });
                }

                if (typeof opciones.onSuccess === "function") {
                    opciones.onSuccess(response);
                }
            } else {
                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "error",
                        title: "Error",
                        text: response.message || "Ocurrió un error inesperado",
                    });
                }

                if (typeof opciones.onError === "function") {
                    opciones.onError(response);
                }
            }
        },
        error: function (xhr) {
            if (typeof window.mostrarErroresValidacion === "function") {
                window.mostrarErroresValidacion(xhr, "Error de Validación");
            }

            if (typeof opciones.onError === "function") {
                opciones.onError(xhr);
            }
        },
        complete: function () {
            if (btn && btn.length) {
                btn.prop("disabled", false).html(textoOriginal);
            }
        },
    });
};

/**
 * Cambia el estado (Activo / Inactivo) o elimina un registro con confirmación SweetAlert2.
 * 
 * @param {Object} opciones
 * @param {string} opciones.url - URL base de eliminación (ej. /clientes/)
 * @param {number|string} opciones.id - ID del registro
 * @param {string} [opciones.nombre='este registro'] - Nombre representativo del registro
 * @param {string} [opciones.tablaSelector] - Selector de DataTable a recargar
 * @param {string} [opciones.titulo] - Título personalizado de confirmación
 * @param {string} [opciones.mensaje] - Mensaje personalizado
 * @param {string} [opciones.confirmButtonText] - Texto del botón de confirmación
 * @param {Function} [opciones.onSuccess] - Callback al completar con éxito
 */
window.cambiarEstadoRegistro = function (opciones) {
    const urlLimpia = opciones.url.endsWith("/") ? opciones.url : `${opciones.url}/`;
    const nombre = opciones.nombre || "este registro";

    const configAlerta = {
        title: opciones.titulo || "¿Cambiar estado del registro?",
        text: opciones.mensaje || `Se modificará el estado de ${nombre}.`,
        icon: "question",
        confirmButtonText: opciones.confirmButtonText || '<i class="fas fa-sync-alt me-1"></i> Sí, cambiar estado',
        confirmButtonColor: "#4f46e5",
    };

    window.confirmarAccion(configAlerta, function () {
        $.ajax({
            url: `${urlLimpia}${opciones.id}`,
            type: "DELETE",
            dataType: "json",
            success: function (res) {
                if (opciones.tablaSelector) {
                    window.recargarDataTable(opciones.tablaSelector);
                }

                if (window.notificacion) {
                    window.notificacion.fire({
                        icon: "success",
                        title: res.message || "Estado actualizado correctamente",
                    });
                }

                if (typeof opciones.onSuccess === "function") {
                    opciones.onSuccess(res);
                }
            },
            error: function (xhr) {
                if (typeof window.mostrarErroresValidacion === "function") {
                    window.mostrarErroresValidacion(xhr, "Error al actualizar estado");
                }
            },
        });
    });
};
