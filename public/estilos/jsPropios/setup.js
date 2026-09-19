/**
 * Asistente de Configuración Inicial (Setup Wizard)
 * Sistema POS Multisede — UI/UX Executive
 */

let pasoActual = 1;

/**
 * Alterna la selección visual de la tarjeta de método de pago
 * @param {HTMLElement} element 
 */
window.toggleMetodoCard = function (element) {
    const card = $(element);
    const checkbox = card.find(".check-metodo");
    const isChecked = checkbox.prop("checked");

    if (isChecked) {
        checkbox.prop("checked", false);
        card.removeClass("selected");
    } else {
        checkbox.prop("checked", true);
        card.addClass("selected");
    }
};

/**
 * Navega entre los pasos del asistente validando la información requerida
 * @param {number} nuevoPaso 
 */
window.irAlPaso = function (nuevoPaso) {
    if (nuevoPaso > pasoActual) {
        // Validar paso 1 antes de avanzar
        if (pasoActual === 1) {
            const nombre = $("#empresa_nombre").val().trim();
            const razonSocial = $("#empresa_razon_social").val().trim();
            const rifNum = $("#empresa_cedula_numero").val().trim();
            const direccion = $("#empresa_direccion").val().trim();

            if (!nombre || nombre.length < 2) {
                mostrarAlertaPaso("Por favor ingresa el nombre comercial de la empresa.");
                $("#empresa_nombre").focus();
                return;
            }

            if (!razonSocial || razonSocial.length < 2) {
                mostrarAlertaPaso("Por favor ingresa la razón social o legal de la empresa.");
                $("#empresa_razon_social").focus();
                return;
            }

            if (!rifNum || rifNum.length < 5) {
                mostrarAlertaPaso("Por favor ingresa un número de RIF / Identificación Fiscal válido.");
                $("#empresa_cedula_numero").focus();
                return;
            }

            if (!direccion || direccion.length < 5) {
                mostrarAlertaPaso("Por favor ingresa la dirección fiscal de la sede principal.");
                $("#empresa_direccion").focus();
                return;
            }
        }

        // Validar paso 2 antes de avanzar
        if (pasoActual === 2) {
            const cedulaNum = $("#admin_cedula_numero").val().trim();
            const email = $("#admin_email").val().trim();
            const nombre = $("#admin_nombre").val().trim();
            const apellido = $("#admin_apellido").val().trim();
            const password = $("#admin_password").val();
            const passwordConfirmation = $("#admin_password_confirmation").val();

            if (!cedulaNum || cedulaNum.length < 5) {
                mostrarAlertaPaso("Por favor ingresa la cédula de identidad del SuperAdministrador.");
                $("#admin_cedula_numero").focus();
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!email || !emailRegex.test(email)) {
                mostrarAlertaPaso("Por favor ingresa un correo electrónico válido.");
                $("#admin_email").focus();
                return;
            }

            if (!nombre || nombre.length < 2) {
                mostrarAlertaPaso("Por favor ingresa el nombre del SuperAdministrador.");
                $("#admin_nombre").focus();
                return;
            }

            if (!apellido || apellido.length < 2) {
                mostrarAlertaPaso("Por favor ingresa el apellido del SuperAdministrador.");
                $("#admin_apellido").focus();
                return;
            }

            if (!password || password.length < 8) {
                mostrarAlertaPaso("La contraseña de acceso debe tener un mínimo de 8 caracteres.");
                $("#admin_password").focus();
                return;
            }

            if (password !== passwordConfirmation) {
                mostrarAlertaPaso("La confirmación de la contraseña no coincide.");
                $("#admin_password_confirmation").focus();
                return;
            }
        }
    }

    // Cambiar panel visible
    $(".step-panel").removeClass("active");
    $(`#contenidoPaso${nuevoPaso}`).addClass("active");

    // Actualizar encabezado e indicador numérico
    $("#pasoActualTexto").text(nuevoPaso);
    pasoActual = nuevoPaso;

    // Actualizar barra de progreso conectora
    const progressPercent = nuevoPaso === 1 ? 0 : (nuevoPaso === 2 ? 50 : 100);
    $("#stepperProgressBar").css("width", `${progressPercent}%`);

    // Actualizar nodos del stepper
    for (let i = 1; i <= 3; i++) {
        const indicador = $(`#indicadorPaso${i}`);
        const circulo = $(`#circuloPaso${i}`);

        if (i < nuevoPaso) {
            indicador.removeClass("active").addClass("completed");
            circulo.html('<i class="fas fa-check"></i>');
        } else if (i === nuevoPaso) {
            indicador.addClass("active").removeClass("completed");
            circulo.text(i);
        } else {
            indicador.removeClass("active completed");
            circulo.text(i);
        }
    }

    window.scrollTo({ top: 0, behavior: "smooth" });
};

function mostrarAlertaPaso(mensaje) {
    if (window.notificacion) {
        window.notificacion.fire({
            icon: "warning",
            title: mensaje,
        });
    } else if (typeof Swal !== "undefined") {
        Swal.fire({
            icon: "warning",
            title: "Atención",
            text: mensaje,
            confirmButtonColor: "#4f46e5",
            customClass: {
                popup: "rounded-4 shadow-lg border-0",
                confirmButton: "rounded-pill px-4",
            },
        });
    } else {
        alert(mensaje);
    }
}

$(document).ready(function () {
    // 1. Preview de Logo en tiempo real
    $("#empresa_logo").on("change", function (e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                mostrarAlertaPaso("El logo excede el límite máximo de 2MB.");
                $(this).val("");
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                $("#logoPreviewImg").attr("src", event.target.result).removeClass("d-none");
                $("#placeholderLogoIcon").addClass("d-none");
            };
            reader.readAsDataURL(file);
        } else {
            $("#logoPreviewImg").attr("src", "").addClass("d-none");
            $("#placeholderLogoIcon").removeClass("d-none");
        }
    });

    // 2. Envío AJAX del formulario
    $("#formularioSetup").on("submit", function (e) {
        e.preventDefault();

        // Validar que al menos un método de pago esté activo
        const metodosSeleccionados = $("input[name='metodos_pago[]']:checked").length;
        if (metodosSeleccionados === 0) {
            mostrarAlertaPaso("Debes seleccionar al menos un método de pago inicial.");
            return;
        }

        const btn = $("#btnFinalizarSetup");
        const textoOriginal = btn.html();
        btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span> Configurando Sistema...');

        const formData = new FormData(this);

        $.ajax({
            url: "/configuracion-inicial",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            dataType: "json",
            success: function (response) {
                if (response.success) {
                    Swal.fire({
                        icon: "success",
                        title: "¡Configuración Exitosa!",
                        text: response.message || "Sistema inicializado correctamente. Redirigiendo...",
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        allowOutsideClick: false,
                        customClass: {
                            popup: "rounded-4 shadow-lg border-0",
                        },
                    }).then(() => {
                        window.location.href = response.redirect || "/panel-principal";
                    });
                } else {
                    btn.prop("disabled", false).html(textoOriginal);
                    Swal.fire({
                        icon: "error",
                        title: "Error en la Configuración",
                        text: response.message || "No se pudo completar la instalación inicial.",
                        confirmButtonColor: "#4f46e5",
                        customClass: {
                            popup: "rounded-4 shadow-lg border-0",
                            confirmButton: "rounded-pill px-4",
                        },
                    });
                }
            },
            error: function (xhr) {
                btn.prop("disabled", false).html(textoOriginal);

                if (typeof window.mostrarErroresValidacion === "function") {
                    window.mostrarErroresValidacion(xhr, "Verifica los datos");
                } else if (xhr.responseJSON && xhr.responseJSON.errors) {
                    const errores = Object.values(xhr.responseJSON.errors).flat().join("<br>");
                    Swal.fire({
                        icon: "error",
                        title: "Verifica los campos requeridos",
                        html: errores,
                        confirmButtonColor: "#4f46e5",
                        customClass: {
                            popup: "rounded-4 shadow-lg border-0",
                            confirmButton: "rounded-pill px-4",
                        },
                    });
                } else {
                    Swal.fire({
                        icon: "error",
                        title: "Error Inesperado",
                        text: xhr.responseJSON?.message || "Ocurrió un error en el servidor.",
                        confirmButtonColor: "#4f46e5",
                        customClass: {
                            popup: "rounded-4 shadow-lg border-0",
                            confirmButton: "rounded-pill px-4",
                        },
                    });
                }
            },
        });
    });
});
