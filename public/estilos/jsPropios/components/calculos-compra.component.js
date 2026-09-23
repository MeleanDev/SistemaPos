window.CalculosCompra = {
    normalizarNumero: function (val) {
        if (typeof val === "number") return val;
        if (!val) return 0;
        const str = String(val).trim().replace(/\s/g, "");
        if (str.includes(",") && str.includes(".")) {
            return parseFloat(str.replace(/\./g, "").replace(",", ".")) || 0;
        }
        if (str.includes(",")) {
            return parseFloat(str.replace(",", ".")) || 0;
        }
        return parseFloat(str) || 0;
    },

    calcularPreciosDesdeMargen: function (opciones) {
        const costoInput = this.normalizarNumero(opciones.costo);
        const margenDetal = this.normalizarNumero(opciones.margenDetal);
        const margenMayorista = this.normalizarNumero(opciones.margenMayorista);
        const tCompra = this.normalizarNumero(opciones.tasaCompra) > 0 ? this.normalizarNumero(opciones.tasaCompra) : 1.0;
        const tVenta = this.normalizarNumero(opciones.tasaVenta) > 0 ? this.normalizarNumero(opciones.tasaVenta) : 1.0;
        const esVes = opciones.moneda === "VES";
        const tasaMenor = tCompra < tVenta;

        let costoUsd = 0;
        let costoBs = 0;
        let precioDetalUsd = 0;
        let precioDetalBs = 0;
        let precioMayoristaUsd = 0;
        let precioMayoristaBs = 0;

        if (costoInput > 0) {
            if (!esVes) {
                costoUsd = costoInput;
                costoBs = tasaMenor ? (costoUsd * tVenta) : (costoUsd * tCompra);
                precioDetalUsd = tasaMenor
                    ? (costoUsd * (1 + margenDetal / 100))
                    : ((costoUsd * (1 + margenDetal / 100) * tCompra) / tVenta);
                precioDetalBs = precioDetalUsd * tVenta;

                precioMayoristaUsd = tasaMenor
                    ? (costoUsd * (1 + margenMayorista / 100))
                    : ((costoUsd * (1 + margenMayorista / 100) * tCompra) / tVenta);
                precioMayoristaBs = precioMayoristaUsd * tVenta;
            } else {
                costoBs = costoInput;
                costoUsd = tasaMenor ? (tCompra > 0 ? costoBs / tCompra : 0) : (tVenta > 0 ? costoBs / tVenta : 0);
                precioDetalUsd = costoUsd * (1 + margenDetal / 100);
                precioDetalBs = precioDetalUsd * tVenta;

                precioMayoristaUsd = costoUsd * (1 + margenMayorista / 100);
                precioMayoristaBs = precioMayoristaUsd * tVenta;
            }
        }

        return {
            costoUsd: costoUsd,
            costoBs: costoBs,
            precioDetalUsd: precioDetalUsd,
            precioDetalBs: precioDetalBs,
            precioMayoristaUsd: precioMayoristaUsd,
            precioMayoristaBs: precioMayoristaBs,
        };
    },

    calcularMargenDesdePrecio: function (opciones) {
        const costoInput = this.normalizarNumero(opciones.costo);
        const precioInput = this.normalizarNumero(opciones.precio);
        const tCompra = this.normalizarNumero(opciones.tasaCompra) > 0 ? this.normalizarNumero(opciones.tasaCompra) : 1.0;
        const tVenta = this.normalizarNumero(opciones.tasaVenta) > 0 ? this.normalizarNumero(opciones.tasaVenta) : 1.0;
        const esVes = opciones.moneda === "VES";
        const tasaMenor = tCompra < tVenta;

        let nuevoMargen = 0;
        let precioUsd = 0;
        let precioBs = 0;

        if (costoInput > 0 && precioInput > 0) {
            if (!esVes) {
                precioUsd = precioInput;
                precioBs = precioUsd * tVenta;
                nuevoMargen = tasaMenor
                    ? (((precioUsd / costoInput) - 1) * 100)
                    : (((precioUsd * tVenta) / (costoInput * tCompra) - 1) * 100);
            } else {
                precioBs = precioInput;
                precioUsd = precioBs / tVenta;
                const costoUsd = tasaMenor ? (costoInput / tCompra) : (costoInput / tVenta);
                nuevoMargen = costoUsd > 0 ? (((precioUsd / costoUsd) - 1) * 100) : 0;
            }
        }

        return {
            margen: nuevoMargen,
            precioUsd: precioUsd,
            precioBs: precioBs,
        };
    },

    calcularEquivalenteMoneda: function (monto, moneda, tasaCompra, tasaVenta) {
        const num = this.normalizarNumero(monto);
        const tCompra = this.normalizarNumero(tasaCompra) > 0 ? this.normalizarNumero(tasaCompra) : 1.0;
        const tVenta = this.normalizarNumero(tasaVenta) > 0 ? this.normalizarNumero(tasaVenta) : 1.0;
        const tasaMenor = tCompra < tVenta;

        if (moneda === "VES") {
            const usd = tasaMenor ? (tCompra > 0 ? num / tCompra : 0) : (tVenta > 0 ? num / tVenta : 0);
            return {
                usd: usd,
                bs: num,
            };
        } else {
            const bs = tasaMenor ? (num * tVenta) : (num * tCompra);
            return {
                usd: num,
                bs: bs,
            };
        }
    },

    calcularFechaVencimientoCredito: function (fechaEmisionStr, diasCredito) {
        const dias = parseInt(diasCredito) || 0;
        if (!fechaEmisionStr) return { fechaISO: "", fechaFormateada: "--" };

        const partes = String(fechaEmisionStr).split("-");
        if (partes.length === 3) {
            const anio = parseInt(partes[0], 10);
            const mes = parseInt(partes[1], 10) - 1;
            const dia = parseInt(partes[2], 10);

            const fecha = new Date(Date.UTC(anio, mes, dia));
            fecha.setUTCDate(fecha.getUTCDate() + dias);

            const yyyy = fecha.getUTCFullYear();
            const mm = String(fecha.getUTCMonth() + 1).padStart(2, "0");
            const dd = String(fecha.getUTCDate()).padStart(2, "0");

            return {
                fechaISO: `${yyyy}-${mm}-${dd}`,
                fechaFormateada: `${dd}/${mm}/${yyyy}`,
            };
        }

        return { fechaISO: "", fechaFormateada: "--" };
    },
};
