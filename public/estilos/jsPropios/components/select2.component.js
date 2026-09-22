window.crearSelect2 = function ({
    selector,
    modalSelector = null,
    placeholder = "Seleccione una opción...",
    allowClear = true,
    width = "100%",
    dropdownParent = null,
    minimumResultsForSearch = 0,
}) {
    const $elemento = $(selector);
    if (!$elemento.length) {
        return null;
    }

    if ($elemento.hasClass("select2-hidden-accessible")) {
        $elemento.select2("destroy");
    }

    let parentContainer = dropdownParent;
    if (!parentContainer && modalSelector) {
        parentContainer = $(modalSelector);
    }

    const config = {
        theme: "bootstrap-5",
        placeholder: placeholder,
        allowClear: allowClear,
        width: width,
        minimumResultsForSearch: minimumResultsForSearch,
        language: {
            noResults: function () {
                return "No se encontraron resultados";
            },
            searching: function () {
                return "Buscando...";
            },
            inputTooShort: function (args) {
                const remaining = args.minimum - args.input.length;
                return "Por favor ingresa " + remaining + " o más caracteres";
            },
        },
    };

    if (parentContainer && parentContainer.length) {
        config.dropdownParent = parentContainer;
    }

    return $elemento.select2(config);
};

window.destruirSelect2 = function (selector) {
    const $elemento = $(selector);
    if ($elemento.length && $elemento.hasClass("select2-hidden-accessible")) {
        $elemento.select2("destroy");
    }
};

window.limpiarSelect2 = function (selector) {
    const $elemento = $(selector);
    if ($elemento.length) {
        $elemento.val("").trigger("change");
    }
};

window.establecerValorSelect2 = function (selector, valor) {
    const $elemento = $(selector);
    if ($elemento.length) {
        $elemento.val(valor || "").trigger("change");
    }
};
