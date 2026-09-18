/**
 * Componente de Utilidades y Helpers para el Sistema POS
 */

/**
 * Desglosa una cédula/RIF en su tipo (V-, J-, etc.) y su número.
 * 
 * @param {string} cedulaCompleta
 * @returns {{ tipo: string, numero: string }}
 */
window.desglosarCedula = function (cedulaCompleta) {
    if (!cedulaCompleta) {
        return { tipo: "V-", numero: "" };
    }

    const prefijos = window.CONFIG_POS?.PREFIJOS_CEDULA || ["V-", "J-", "E-", "G-", "P-"];
    const prefijoEncontrado = prefijos.find((p) => cedulaCompleta.startsWith(p));

    if (prefijoEncontrado) {
        return {
            tipo: prefijoEncontrado,
            numero: cedulaCompleta.replace(prefijoEncontrado, ""),
        };
    }

    return { tipo: "V-", numero: cedulaCompleta };
};

/**
 * Desglosa un número telefónico en su código de país y su número local.
 * 
 * @param {string} telefonoCompleto
 * @returns {{ codigo: string, numero: string }}
 */
window.desglosarTelefono = function (telefonoCompleto) {
    if (!telefonoCompleto) {
        return { codigo: "+58", numero: "" };
    }

    const codigosPais = window.CONFIG_POS?.CODIGOS_PAIS?.map((c) => c.codigo) || [
        "+58", "+1", "+57", "+56", "+51", "+55", "+593", "+34", "+54", "+507", "+52"
    ];

    const codigoEncontrado = codigosPais.find((c) => telefonoCompleto.startsWith(c));

    if (codigoEncontrado) {
        return {
            codigo: codigoEncontrado,
            numero: telefonoCompleto.replace(codigoEncontrado, ""),
        };
    }

    return { codigo: "+58", numero: telefonoCompleto };
};

/**
 * Aplica restricciones en tiempo real para inputs de solo letras o solo números.
 */
window.aplicarRestriccionesInput = function () {
    $(document).on("keypress", ".solo-letras, #nombre, #apellido", function (e) {
        const key = e.keyCode || e.which;
        const teclado = String.fromCharCode(key).toLowerCase();
        const letras = " áéíóúabcdefghijklmnñopqrstuvwxyz";
        if (letras.indexOf(teclado) === -1 && key !== 8 && key !== 13) {
            return false;
        }
    });

    $(document).on("keypress", ".solo-numeros, #cedula_numero, #telefono_numero", function (e) {
        const key = e.keyCode || e.which;
        const teclado = String.fromCharCode(key);
        const numeros = "0123456789";
        if (numeros.indexOf(teclado) === -1 && key !== 8 && key !== 13) {
            return false;
        }
    });
};
