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
        return { tipo: "V-", prefijo: "V-", numero: "" };
    }

    const prefijos = window.CONFIG_POS?.PREFIJOS_CEDULA || ["V-", "J-", "E-", "G-", "P-"];
    const prefijoEncontrado = prefijos.find((p) => cedulaCompleta.startsWith(p));

    if (prefijoEncontrado) {
        return {
            tipo: prefijoEncontrado,
            prefijo: prefijoEncontrado,
            numero: cedulaCompleta.replace(prefijoEncontrado, ""),
        };
    }

    return { tipo: "V-", prefijo: "V-", numero: cedulaCompleta };
};

/**
 * Desglosa un número telefónico en su código de país y su número local.
 * 
 * @param {string} telefonoCompleto
 * @returns {{ codigo: string, prefijo: string, numero: string }}
 */
window.desglosarTelefono = function (telefonoCompleto) {
    if (!telefonoCompleto) {
        return { codigo: "+58", prefijo: "+58", numero: "" };
    }

    const codigosPais = window.CONFIG_POS?.CODIGOS_PAIS?.map((c) => c.codigo) || [
        "+58", "+1", "+57", "+56", "+51", "+55", "+593", "+34", "+54", "+507", "+52"
    ];

    const codigoEncontrado = codigosPais.find((c) => telefonoCompleto.startsWith(c));

    if (codigoEncontrado) {
        return {
            codigo: codigoEncontrado,
            prefijo: codigoEncontrado,
            numero: telefonoCompleto.replace(codigoEncontrado, ""),
        };
    }

    return { codigo: "+58", prefijo: "+58", numero: telefonoCompleto };
};

window.aplicarRestriccionesInput = function () {
    $(document).on("keypress", ".solo-letras", function (e) {
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

/**
 * Formatea una fecha a estándar visual del sistema (DD/MM/YYYY).
 * Maneja cadenas ISO (ej. 2026-10-25T00:00:00Z), YYYY-MM-DD, objetos Date o timestamps.
 * 
 * @param {string|Date|number|null} fechaStr
 * @param {string} [placeholder="--"]
 * @returns {string}
 */
window.formatearFecha = function (fechaStr, placeholder = "--") {
    if (!fechaStr || fechaStr === "--" || fechaStr === "N/A") {
        return placeholder;
    }

    if (fechaStr instanceof Date) {
        if (isNaN(fechaStr.getTime())) return placeholder;
        const dia = String(fechaStr.getDate()).padStart(2, "0");
        const mes = String(fechaStr.getMonth() + 1).padStart(2, "0");
        const anio = fechaStr.getFullYear();
        return `${dia}/${mes}/${anio}`;
    }

    const str = String(fechaStr).trim();

    const matchIso = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (matchIso) {
        const [, anio, mes, dia] = matchIso;
        return `${dia}/${mes}/${anio}`;
    }

    const matchLatam = str.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/);
    if (matchLatam) {
        const dia = String(matchLatam[1]).padStart(2, "0");
        const mes = String(matchLatam[2]).padStart(2, "0");
        const anio = matchLatam[3];
        return `${dia}/${mes}/${anio}`;
    }

    const d = new Date(fechaStr);
    if (!isNaN(d.getTime())) {
        const dia = String(d.getDate()).padStart(2, "0");
        const mes = String(d.getMonth() + 1).padStart(2, "0");
        const anio = d.getFullYear();
        return `${dia}/${mes}/${anio}`;
    }

    return str;
};

/**
 * Formatea una hora a estándar visual del sistema (12 horas: hh:mm AM/PM).
 * 
 * @param {string|Date|null} horaStr
 * @param {string} [placeholder="--:--"]
 * @returns {string}
 */
window.formatearHora = function (horaStr, placeholder = "--:--") {
    if (!horaStr || horaStr === "--" || horaStr === "--:--") {
        return placeholder;
    }

    if (horaStr instanceof Date) {
        if (isNaN(horaStr.getTime())) return placeholder;
        let horas = horaStr.getHours();
        const minutos = String(horaStr.getMinutes()).padStart(2, "0");
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12 || 12;
        return `${String(horas).padStart(2, "0")}:${minutos} ${ampm}`;
    }

    const str = String(horaStr).trim();

    if (str.includes("T")) {
        const partes = str.split("T");
        if (partes[1]) {
            return window.formatearHora(partes[1].replace("Z", ""), placeholder);
        }
    }

    const matchAmPm = str.match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM|am|pm)$/i);
    if (matchAmPm) {
        const horas = String(matchAmPm[1]).padStart(2, "0");
        const minutos = matchAmPm[2];
        const ampm = matchAmPm[3].toUpperCase();
        return `${horas}:${minutos} ${ampm}`;
    }

    const match24 = str.match(/^(\d{1,2}):(\d{2})(?::(\d{2}))?/);
    if (match24) {
        let horas = parseInt(match24[1], 10);
        const minutos = match24[2];
        const ampm = horas >= 12 ? "PM" : "AM";
        horas = horas % 12 || 12;
        return `${String(horas).padStart(2, "0")}:${minutos} ${ampm}`;
    }

    return str;
};

/**
 * Formatea fecha y hora combinadas a estándar visual del sistema (DD/MM/YYYY hh:mm AM/PM).
 * 
 * @param {string|Date|null} fechaHoraStr
 * @param {string} [placeholder="--"]
 * @returns {string}
 */
window.formatearFechaHora = function (fechaHoraStr, placeholder = "--") {
    if (!fechaHoraStr || fechaHoraStr === "--") {
        return placeholder;
    }

    const fecha = window.formatearFecha(fechaHoraStr, "");
    if (!fecha) {
        return placeholder;
    }

    let hora = "";
    if (fechaHoraStr instanceof Date) {
        hora = window.formatearHora(fechaHoraStr, "");
    } else {
        const str = String(fechaHoraStr).trim();
        if (str.includes("T")) {
            hora = window.formatearHora(str.split("T")[1], "");
        } else if (str.includes(" ")) {
            hora = window.formatearHora(str.split(" ")[1], "");
        }
    }

    return hora ? `${fecha} ${hora}` : fecha;
};

/**
 * Formatea una fecha para asignación en inputs HTML5 (<input type="date">: YYYY-MM-DD).
 * 
 * @param {string|Date|null} fechaStr
 * @returns {string}
 */
window.formatearFechaParaInput = function (fechaStr) {
    if (!fechaStr) return "";

    if (fechaStr instanceof Date) {
        if (isNaN(fechaStr.getTime())) return "";
        const anio = fechaStr.getFullYear();
        const mes = String(fechaStr.getMonth() + 1).padStart(2, "0");
        const dia = String(fechaStr.getDate()).padStart(2, "0");
        return `${anio}-${mes}-${dia}`;
    }

    const str = String(fechaStr).trim();

    const matchIso = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (matchIso) {
        return `${matchIso[1]}-${matchIso[2]}-${matchIso[3]}`;
    }

    const matchLatam = str.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})/);
    if (matchLatam) {
        const dia = String(matchLatam[1]).padStart(2, "0");
        const mes = String(matchLatam[2]).padStart(2, "0");
        const anio = matchLatam[3];
        return `${anio}-${mes}-${dia}`;
    }

    return "";
};
