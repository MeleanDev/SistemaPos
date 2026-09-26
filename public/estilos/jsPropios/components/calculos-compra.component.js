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
        const fleteInput = this.normalizarNumero(opciones.flete);
        const ivaPorcentaje = this.normalizarNumero(opciones.ivaPorcentaje);
        const aplicaIva = opciones.aplicaIva !== undefined ? Boolean(opciones.aplicaIva) : (ivaPorcentaje > 0);
        const margenDetal = this.normalizarNumero(opciones.margenDetal);
        const margenMayorista = this.normalizarNumero(opciones.margenMayorista);
        const tCompra = this.normalizarNumero(opciones.tasaCompra) > 0 ? this.normalizarNumero(opciones.tasaCompra) : 1.0;
        const tVenta = this.normalizarNumero(opciones.tasaVenta) > 0 ? this.normalizarNumero(opciones.tasaVenta) : 1.0;
        const esVes = opciones.moneda === "VES";
        const tasaMenor = tCompra < tVenta;

        let costoBaseUsd = 0;
        let costoBaseBs = 0;
        let fleteUsd = 0;
        let fleteBs = 0;

        if (costoInput > 0) {
            if (!esVes) {
                costoBaseUsd = costoInput;
                costoBaseBs = tasaMenor ? (costoBaseUsd * tVenta) : (costoBaseUsd * tCompra);
                fleteUsd = fleteInput;
                fleteBs = tasaMenor ? (fleteUsd * tVenta) : (fleteUsd * tCompra);
            } else {
                costoBaseBs = costoInput;
                costoBaseUsd = tasaMenor ? (tCompra > 0 ? costoBaseBs / tCompra : 0) : (tVenta > 0 ? costoBaseBs / tVenta : 0);
                fleteBs = fleteInput;
                fleteUsd = tasaMenor ? (tCompra > 0 ? fleteBs / tCompra : 0) : (tVenta > 0 ? fleteBs / tVenta : 0);
            }
        }

        const ivaUnitarioUsd = (aplicaIva && ivaPorcentaje > 0) ? (costoBaseUsd * (ivaPorcentaje / 100)) : 0;
        const ivaUnitarioBs = (aplicaIva && ivaPorcentaje > 0) ? (costoBaseBs * (ivaPorcentaje / 100)) : 0;

        const costoTotalUsd = costoBaseUsd + ivaUnitarioUsd + fleteUsd;
        const costoTotalBs = costoBaseBs + ivaUnitarioBs + fleteBs;

        const costoSinIvaUsd = costoBaseUsd + fleteUsd;
        const costoSinIvaBs = costoBaseBs + fleteBs;

        let precioDetalConIvaUsd = 0;
        let precioMayoristaConIvaUsd = 0;
        let precioDetalSinIvaUsd = 0;
        let precioMayoristaSinIvaUsd = 0;

        if (costoTotalUsd > 0) {
            if (!esVes) {
                precioDetalConIvaUsd = tasaMenor
                    ? (costoTotalUsd * (1 + margenDetal / 100))
                    : ((costoTotalUsd * (1 + margenDetal / 100) * tCompra) / tVenta);

                precioMayoristaConIvaUsd = tasaMenor
                    ? (costoTotalUsd * (1 + margenMayorista / 100))
                    : ((costoTotalUsd * (1 + margenMayorista / 100) * tCompra) / tVenta);

                precioDetalSinIvaUsd = tasaMenor
                    ? (costoSinIvaUsd * (1 + margenDetal / 100))
                    : ((costoSinIvaUsd * (1 + margenDetal / 100) * tCompra) / tVenta);

                precioMayoristaSinIvaUsd = tasaMenor
                    ? (costoSinIvaUsd * (1 + margenMayorista / 100))
                    : ((costoSinIvaUsd * (1 + margenMayorista / 100) * tCompra) / tVenta);
            } else {
                precioDetalConIvaUsd = costoTotalUsd * (1 + margenDetal / 100);
                precioMayoristaConIvaUsd = costoTotalUsd * (1 + margenMayorista / 100);
                precioDetalSinIvaUsd = costoSinIvaUsd * (1 + margenDetal / 100);
                precioMayoristaSinIvaUsd = costoSinIvaUsd * (1 + margenMayorista / 100);
            }
        }

        const precioDetalConIvaBs = precioDetalConIvaUsd * tVenta;
        const precioMayoristaConIvaBs = precioMayoristaConIvaUsd * tVenta;
        const precioDetalSinIvaBs = precioDetalSinIvaUsd * tVenta;
        const precioMayoristaSinIvaBs = precioMayoristaSinIvaUsd * tVenta;

        return {
            costoBaseUsd: costoBaseUsd,
            costoBaseBs: costoBaseBs,
            fleteUsd: fleteUsd,
            fleteBs: fleteBs,
            ivaUnitarioUsd: ivaUnitarioUsd,
            ivaUnitarioBs: ivaUnitarioBs,
            costoTotalUsd: costoTotalUsd,
            costoTotalBs: costoTotalBs,
            costoSinIvaUsd: costoSinIvaUsd,
            costoSinIvaBs: costoSinIvaBs,
            precioDetalUsd: precioDetalSinIvaUsd,
            precioDetalBs: precioDetalSinIvaBs,
            precioDetalConIvaUsd: precioDetalConIvaUsd,
            precioDetalConIvaBs: precioDetalConIvaBs,
            precioMayoristaUsd: precioMayoristaSinIvaUsd,
            precioMayoristaBs: precioMayoristaSinIvaBs,
            precioMayoristaConIvaUsd: precioMayoristaConIvaUsd,
            precioMayoristaConIvaBs: precioMayoristaConIvaBs,
        };
    },

    calcularMargenDesdePrecio: function (opciones) {
        const costoInput = this.normalizarNumero(opciones.costo);
        const fleteInput = this.normalizarNumero(opciones.flete);
        const precioInput = this.normalizarNumero(opciones.precio);
        const tipoPrecio = opciones.tipoPrecio || 'con_iva';
        const tCompra = this.normalizarNumero(opciones.tasaCompra) > 0 ? this.normalizarNumero(opciones.tasaCompra) : 1.0;
        const tVenta = this.normalizarNumero(opciones.tasaVenta) > 0 ? this.normalizarNumero(opciones.tasaVenta) : 1.0;
        const esVes = opciones.moneda === "VES";
        const tasaMenor = tCompra < tVenta;
        const ivaPorcentaje = this.normalizarNumero(opciones.ivaPorcentaje);
        const aplicaIva = opciones.aplicaIva !== undefined ? Boolean(opciones.aplicaIva) : (ivaPorcentaje > 0);

        let costoBaseUsd = 0;
        let fleteUsd = 0;
        if (costoInput > 0) {
            if (!esVes) {
                costoBaseUsd = costoInput;
                fleteUsd = fleteInput;
            } else {
                costoBaseUsd = tasaMenor ? (tCompra > 0 ? costoInput / tCompra : 0) : (tVenta > 0 ? costoInput / tVenta : 0);
                fleteUsd = tasaMenor ? (tCompra > 0 ? fleteInput / tCompra : 0) : (tVenta > 0 ? fleteInput / tVenta : 0);
            }
        }
        const ivaUnitarioUsd = (aplicaIva && ivaPorcentaje > 0) ? (costoBaseUsd * (ivaPorcentaje / 100)) : 0;
        const costoTotalUsd = costoBaseUsd + ivaUnitarioUsd + fleteUsd;
        const costoSinIvaUsd = costoBaseUsd + fleteUsd;

        let precioSinIvaUsd = 0;
        let precioSinIvaBs = 0;
        let precioConIvaUsd = 0;
        let precioConIvaBs = 0;
        let nuevoMargen = 0;

        if (precioInput > 0 && costoTotalUsd > 0) {
            if (tipoPrecio === 'con_iva') {
                if (!esVes) {
                    precioConIvaUsd = precioInput;
                    precioConIvaBs = precioConIvaUsd * tVenta;
                    nuevoMargen = tasaMenor
                        ? (((precioConIvaUsd / costoTotalUsd) - 1) * 100)
                        : ((((precioConIvaUsd * tVenta) / (costoTotalUsd * tCompra)) - 1) * 100);
                    precioSinIvaUsd = tasaMenor
                        ? (costoSinIvaUsd * (1 + nuevoMargen / 100))
                        : ((costoSinIvaUsd * (1 + nuevoMargen / 100) * tCompra) / tVenta);
                    precioSinIvaBs = precioSinIvaUsd * tVenta;
                } else {
                    precioConIvaBs = precioInput;
                    precioConIvaUsd = tVenta > 0 ? precioConIvaBs / tVenta : 0;
                    nuevoMargen = (((precioConIvaUsd / costoTotalUsd) - 1) * 100);
                    precioSinIvaUsd = costoSinIvaUsd * (1 + nuevoMargen / 100);
                    precioSinIvaBs = precioSinIvaUsd * tVenta;
                }
            } else {
                if (!esVes) {
                    precioSinIvaUsd = precioInput;
                    precioSinIvaBs = precioSinIvaUsd * tVenta;
                    nuevoMargen = tasaMenor
                        ? (((precioSinIvaUsd / costoSinIvaUsd) - 1) * 100)
                        : ((((precioSinIvaUsd * tVenta) / (costoSinIvaUsd * tCompra)) - 1) * 100);
                    precioConIvaUsd = tasaMenor
                        ? (costoTotalUsd * (1 + nuevoMargen / 100))
                        : ((costoTotalUsd * (1 + nuevoMargen / 100) * tCompra) / tVenta);
                    precioConIvaBs = precioConIvaUsd * tVenta;
                } else {
                    precioSinIvaBs = precioInput;
                    precioSinIvaUsd = tVenta > 0 ? precioSinIvaBs / tVenta : 0;
                    nuevoMargen = (((precioSinIvaUsd / costoSinIvaUsd) - 1) * 100);
                    precioConIvaUsd = costoTotalUsd * (1 + nuevoMargen / 100);
                    precioConIvaBs = precioConIvaUsd * tVenta;
                }
            }
        }

        return {
            margen: nuevoMargen,
            precioUsd: precioSinIvaUsd,
            precioBs: precioSinIvaBs,
            precioConIvaUsd: precioConIvaUsd,
            precioConIvaBs: precioConIvaBs,
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
