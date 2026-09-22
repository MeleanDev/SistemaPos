<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura {{ $venta->codigo }} - {{ $venta->empresa->nombre }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        body {
            background-color: #f1f5f9;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.35;
        }
        .page-container {
            max-width: 210mm;
            margin: 20px auto;
            background: #ffffff;
            padding: 24px 28px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .no-print {
            max-width: 210mm;
            margin: 15px auto 5px auto;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-print { background-color: #1e40af; color: #ffffff; }
        .btn-print:hover { background-color: #1d4ed8; }
        .btn-ticket { background-color: #059669; color: #ffffff; }
        .btn-ticket:hover { background-color: #047857; }
        .btn-close { background-color: #64748b; color: #ffffff; }
        .btn-close:hover { background-color: #475569; }

        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .fw-bold { font-weight: bold; }
        .text-uppercase { text-transform: uppercase; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .text-danger { color: #dc2626; }
        .text-muted { color: #475569; }
        .text-primary { color: #1e3a8a; }

        /* HEADER */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .company-info {
            width: 58%;
        }
        .company-title {
            font-size: 20px;
            font-weight: 900;
            color: #1e3a8a;
            letter-spacing: -0.5px;
            line-height: 1.1;
            margin-bottom: 4px;
        }
        .company-rif {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .company-address {
            font-size: 10px;
            color: #334155;
            line-height: 1.25;
        }

        .fiscal-control {
            width: 40%;
            text-align: right;
        }
        .fiscal-badge {
            font-size: 11px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 2px;
        }
        .control-number {
            font-size: 16px;
            font-weight: 900;
            color: #dc2626;
            letter-spacing: 1px;
            font-family: 'Courier New', Courier, monospace;
        }
        .invoice-number-box {
            margin-top: 6px;
            font-size: 14px;
            font-weight: 900;
            color: #0f172a;
        }

        /* CLIENT & DOC DETAILS GRID */
        .meta-section {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 12px;
            background-color: #f8fafc;
        }
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
            font-size: 10.5px;
        }
        .meta-row:last-child {
            margin-bottom: 0;
        }
        .meta-label {
            font-weight: 700;
            color: #1e293b;
        }
        .meta-val {
            color: #0f172a;
        }

        /* TABLE OF ITEMS */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .items-table th {
            background-color: #f1f5f9;
            border-top: 1.5px solid #0f172a;
            border-bottom: 1.5px solid #0f172a;
            padding: 6px 8px;
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            color: #0f172a;
        }
        .items-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            font-size: 10.5px;
        }
        .items-table tr:nth-child(even) {
            background-color: #fafafa;
        }
        .item-spec-box {
            font-size: 9.5px;
            color: #334155;
            margin-top: 2px;
            line-height: 1.25;
            background: #ffffff;
            padding: 3px 6px;
            border-left: 2px solid #3b82f6;
            border-radius: 2px;
        }

        /* BREAKDOWN TOTALS SECTION */
        .breakdown-container {
            display: flex;
            justify-content: space-between;
            border: 1.5px solid #0f172a;
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 10px;
            background-color: #ffffff;
        }
        .breakdown-column {
            width: 48%;
        }
        .breakdown-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 10.5px;
        }
        .breakdown-row.highlight {
            font-weight: 800;
            font-size: 11.5px;
            border-top: 1px solid #94a3b8;
            margin-top: 3px;
            padding-top: 3px;
        }
        .breakdown-row.total-final {
            font-weight: 900;
            font-size: 12.5px;
            color: #0f172a;
            border-top: 1.5px solid #0f172a;
            margin-top: 4px;
            padding-top: 4px;
        }

        /* RATE & LEGAL FOOTER */
        .legal-notice-box {
            font-size: 9px;
            color: #334155;
            line-height: 1.3;
            margin-bottom: 12px;
            padding: 6px 8px;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            background-color: #f8fafc;
        }
        .signatures-container {
            display: flex;
            justify-content: space-around;
            margin-top: 24px;
            margin-bottom: 12px;
            padding-top: 10px;
        }
        .sign-box {
            width: 40%;
            text-align: center;
            border-top: 1px solid #000000;
            padding-top: 4px;
            font-size: 10px;
            font-weight: 700;
        }
        .bottom-tax-legend {
            text-align: center;
            font-size: 8.5px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .page-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

@php
    $montoExentoUsd = 0;
    $montoExentoBs = 0;
    $baseImponibleUsd = 0;
    $baseImponibleBs = 0;

    foreach ($venta->detalles as $det) {
        $aplicaIva = (bool) $det->aplica_iva;
        $ivaPct = (float) $det->iva_porcentaje;

        if ($aplicaIva && $ivaPct > 0) {
            $baseImponibleUsd += (float) $det->subtotal_usd;
            $baseImponibleBs += (float) $det->subtotal_bs;
        } else {
            $montoExentoUsd += (float) $det->subtotal_usd;
            $montoExentoBs += (float) $det->subtotal_bs;
        }
    }

    $subtotalUsd = (float) $venta->subtotal_neto_usd;
    $subtotalBs = (float) $venta->subtotal_neto_bs;
    $ivaUsd = (float) $venta->iva_monto_usd;
    $ivaBs = (float) $venta->iva_monto_bs;
    $totalNetoUsd = $subtotalUsd + $ivaUsd;
    $totalNetoBs = $subtotalBs + $ivaBs;

    $baseIgtfUsd = (float) ($venta->igtf_monto_usd > 0 ? ($venta->igtf_monto_usd / 0.03) : 0);
    $baseIgtfBs = (float) ($venta->igtf_monto_bs > 0 ? ($venta->igtf_monto_bs / 0.03) : 0);
    $igtfUsd = (float) ($venta->igtf_monto_usd ?? 0);
    $igtfBs = (float) ($venta->igtf_monto_bs ?? 0);

    $totalPagarUsd = (float) $venta->total_usd;
    $totalPagarBs = (float) $venta->total_bs;

    $diasCredito = $venta->condicion_pago === 'credito' ? 15 : 0;
    $fechaEmision = \Carbon\Carbon::parse($venta->fecha_emision);
    $fechaVencimiento = $fechaEmision->copy()->addDays($diasCredito);

    $tieneVehiculo = $venta->detalles->contains(fn($d) => $d->tipo_item === 'moto');
@endphp

    <!-- BARRA DE ACCIONES SUPERIOR (SOLO VISIBLE EN PANTALLA) -->
    <div class="no-print">
        <div>
            <span class="fw-bold" style="font-size: 14px; color: #1e293b;">📄 Vista Previa: Factura Carta (Forma Libre)</span>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="btn-action btn-print" onclick="window.print()">
                🖨️ Imprimir Hoja Blanca
            </button>
            <a href="{{ route('pos.imprimir_ticket', $venta->id) }}" class="btn-action btn-ticket">
                🧾 Ver Formato Ticket
            </a>
            <button type="button" class="btn-action btn-close" onclick="window.close()">
                ✖️ Cerrar
            </button>
        </div>
    </div>

    <!-- DOCUMENTO FACTURA CARTA -->
    <div class="page-container">

        <!-- ENCABEZADO FISCAL -->
        <div class="invoice-header">
            <div class="company-info">
                <div class="company-title text-uppercase">{{ $venta->empresa->nombre }}</div>
                @if($venta->empresa->razon_social && $venta->empresa->razon_social !== $venta->empresa->nombre)
                    <div style="font-size: 11px; font-weight: bold; color: #334155; margin-bottom: 2px;">{{ $venta->empresa->razon_social }}</div>
                @endif
                <div class="company-rif">RIF.: {{ $venta->empresa->rif }}</div>
                <div class="company-address">
                    {{ $venta->empresa->direccion ?? 'DIRECCIÓN FISCAL NO ESPECIFICADA' }}
                    @if($venta->empresa->telefono)
                        <br>Teléfono: {{ $venta->empresa->telefono }}
                    @endif
                    @if($venta->empresa->correo)
                        • Correo: {{ $venta->empresa->correo }}
                    @endif
                </div>
            </div>

            <div class="fiscal-control">
                <div class="fiscal-badge">Forma Libre</div>
                <div class="control-number">N° CONTROL 00- <span style="font-size: 17px;">{{ str_pad($venta->numero_control ?? $venta->id, 8, '0', STR_PAD_LEFT) }}</span></div>
                <div class="invoice-number-box">
                    @if($tieneVehiculo)
                        <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; margin-right: 4px;">.VEHICULO</span>
                    @endif
                    FACTURA Nro. <span class="font-mono text-primary">{{ $venta->codigo }}</span>
                </div>
            </div>
        </div>

        <!-- DATOS DEL CLIENTE Y METADATOS DE FACTURACIÓN -->
        <div class="meta-section">
            <div class="meta-row">
                <div>
                    <span class="meta-label">Condición de Pago:</span>
                    <span class="meta-val fw-bold text-uppercase">{{ $venta->condicion_pago === 'credito' ? 'CRÉDITO' : 'CONTADO' }}</span>
                </div>
                <div>
                    <span class="meta-label">Emisión:</span>
                    <span class="meta-val font-mono fw-bold">{{ $fechaEmision->format('d-m-Y') }} {{ $venta->hora_emision }}</span>
                </div>
                <div>
                    <span class="meta-label">Vencimiento:</span>
                    <span class="meta-val font-mono fw-bold">{{ $fechaVencimiento->format('d-m-Y') }}</span>
                </div>
            </div>

            <div class="meta-row" style="border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                <div style="width: 70%;">
                    <span class="meta-label">Nombre(s) y Apellido(s) o Razón Social:</span>
                    <span class="meta-val fw-bold text-uppercase">{{ $venta->cliente->nombre_completo }}</span>
                </div>
                <div style="width: 30%; text-align: right;">
                    <span class="meta-label">CI / RIF:</span>
                    <span class="meta-val font-mono fw-bold">{{ $venta->cliente->cedula ?? '--' }}</span>
                </div>
            </div>

            <div class="meta-row">
                <div style="width: 100%;">
                    <span class="meta-label">Domicilio Fiscal:</span>
                    <span class="meta-val">{{ $venta->cliente->direccion ?? 'DOMICILIO FISCAL NO REGISTRADO' }}</span>
                </div>
            </div>

            <div class="meta-row" style="border-top: 1px dashed #cbd5e1; padding-top: 4px; margin-top: 4px;">
                <div>
                    <span class="meta-label">Teléfonos:</span>
                    <span class="meta-val font-mono">{{ $venta->cliente->telefono ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="meta-label">Correo Electrónico:</span>
                    <span class="meta-val">{{ $venta->cliente->correo ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="meta-label">ASESOR:</span>
                    <span class="meta-val fw-bold text-uppercase">{{ $venta->usuario?->name ?? 'CAJERO PRINCIPAL' }}</span>
                </div>
            </div>
        </div>

        <!-- TABLA DE DETALLES -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 14%;" class="text-start">Código</th>
                    <th style="width: 48%;" class="text-start">Descripción</th>
                    <th style="width: 10%;" class="text-center">Cant.</th>
                    <th style="width: 14%;" class="text-end">P. Unitario (BS)</th>
                    <th style="width: 14%;" class="text-end">Total (BS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($venta->detalles as $idx => $det)
                    @php
                        $nombreItem = $det->nombre_item ?? $det->producto?->nombre ?? ($det->moto ? "{$det->moto->marca} {$det->moto->modelo}" : null) ?? $det->servicio?->nombre ?? 'Artículo';
                        $codigoItem = $det->serial_identificador ?? $det->producto?->codigo_interno ?? $det->moto?->numero_niv ?? $det->servicio?->codigo ?? str_pad($idx + 1, 2, '0', STR_PAD_LEFT);
                        $cant = (float) $det->cantidad;
                        $precioUnitBs = (float) $det->precio_unitario_bs;
                        $totalRenglonBs = (float) $det->subtotal_bs;
                    @endphp
                    <tr>
                        <td class="font-mono">{{ $codigoItem }}</td>
                        <td>
                            <strong class="text-uppercase">{{ $nombreItem }}</strong>
                            
                            @if($det->tipo_item === 'moto' && $det->moto)
                                <div class="item-spec-box font-mono">
                                    Marca: <strong>{{ $det->moto->marca }}</strong> • Modelo: <strong>{{ $det->moto->modelo }}</strong>
                                    @if($det->moto->placa) • Placa: <strong>{{ $det->moto->placa }}</strong> @endif
                                    • Serial N.I.V.: <strong>{{ $det->serial_identificador ?? $det->moto->numero_niv }}</strong>
                                    <br>
                                    @if($det->moto->numero_motor) Motor: <strong>{{ $det->moto->numero_motor }}</strong> • @endif
                                    @if($det->moto->numero_chasis) Chasis: <strong>{{ $det->moto->numero_chasis }}</strong> • @endif
                                    Tipo: <strong>MOTOCICLETA</strong>
                                    <br>
                                    @if($det->moto->anio) Año: <strong>{{ $det->moto->anio }}</strong> • @endif
                                    @if($det->moto->color) Color: <strong>{{ $det->moto->color }}</strong> • @endif
                                    @if($det->moto->cilindrada) Cilindrada: <strong>{{ $det->moto->cilindrada }} cc</strong> • @endif
                                    @if($det->moto->certificado_origen) Cert. Origen: <strong>{{ $det->moto->certificado_origen }}</strong> @endif
                                </div>
                            @elseif($det->aplica_iva)
                                <span style="font-size: 8.5px; color: #475569; font-weight: bold;">(IVA {{ (float)$det->iva_porcentaje }}%)</span>
                            @else
                                <span style="font-size: 8.5px; color: #475569; font-weight: bold;">(EXENTO)</span>
                            @endif
                        </td>
                        <td class="text-center font-mono fw-bold">{{ number_format($cant, 2, ',', '.') }}</td>
                        <td class="text-end font-mono">{{ number_format($precioUnitBs, 2, ',', '.') }}</td>
                        <td class="text-end font-mono fw-bold">{{ number_format($totalRenglonBs, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- DESGLOSE DE MONTOS EN DÓLARES Y BOLÍVARES (DUAL CURRENCY) -->
        <div class="breakdown-container">
            <!-- COLUMNA USD -->
            <div class="breakdown-column font-mono" style="border-right: 1px dashed #cbd5e1; padding-right: 14px;">
                <div class="breakdown-row">
                    <span>Subtotal USD:</span>
                    <span>{{ number_format($subtotalUsd, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>Monto Exento USD:</span>
                    <span>{{ number_format($montoExentoUsd, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>Base Imponible IVA (16,00%) USD:</span>
                    <span>{{ number_format($baseImponibleUsd, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>IVA 16,00% USD:</span>
                    <span>{{ number_format($ivaUsd, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row highlight">
                    <span>Total Neto Factura USD:</span>
                    <span>{{ number_format($totalNetoUsd, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row">
                    <span>Base Imponible IGTF (3%) USD:</span>
                    <span>{{ number_format($baseIgtfUsd, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>IGTF 3% USD:</span>
                    <span>{{ number_format($igtfUsd, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row total-final">
                    <span>Monto Total a Pagar USD:</span>
                    <span>$ {{ number_format($totalPagarUsd, 2, ',', '.') }}</span>
                </div>
            </div>

            <!-- COLUMNA BS -->
            <div class="breakdown-column font-mono" style="padding-left: 14px;">
                <div class="breakdown-row">
                    <span>Subtotal BS:</span>
                    <span>{{ number_format($subtotalBs, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>Monto Exento BS:</span>
                    <span>{{ number_format($montoExentoBs, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>Base Imponible IVA (16,00%) BS:</span>
                    <span>{{ number_format($baseImponibleBs, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>IVA 16,00% BS:</span>
                    <span>{{ number_format($ivaBs, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row highlight">
                    <span>Total Neto Factura BS:</span>
                    <span>{{ number_format($totalNetoBs, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row">
                    <span>Base Imponible IGTF (3%) BS:</span>
                    <span>{{ number_format($baseIgtfBs, 2, ',', '.') }}</span>
                </div>
                <div class="breakdown-row">
                    <span>IGTF 3% BS:</span>
                    <span>{{ number_format($igtfBs, 2, ',', '.') }}</span>
                </div>

                <div class="breakdown-row total-final">
                    <span>Monto Total a Pagar BS:</span>
                    <span>Bs. {{ number_format($totalPagarBs, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- TASA DE CAMBIO BCV Y NORMATIVA IGTF -->
        <div class="legal-notice-box font-mono">
            <div style="font-weight: bold; margin-bottom: 2px;">
                Tasa de cambio aplicada 1 BS/USD = {{ number_format($venta->tasa_cambio, 4, ',', '.') }} según la tasa del BCV al {{ $fechaEmision->format('d-m-Y') }}, PROV Nro. 0071
            </div>
            <div>
                GACETA OFICIAL NRO 6687, ART 6 providencia 000013, SE FIJA UNA ALÍCUOTA DEL 3% PARA LOS PAGOS REALIZADOS A SUJETOS PASIVOS ESPECIALES EN MONEDA DIFERENTE A LA DE CURSO LEGAL EN EL PAÍS.
            </div>
        </div>

        <!-- FIRMAS -->
        <div class="signatures-container">
            <div class="sign-box">
                Por la Empresa
            </div>
            <div class="sign-box">
                Cliente
            </div>
        </div>

        <!-- BASE LEGAL SENIAT -->
        <div class="legal-notice-box" style="margin-bottom: 6px; font-size: 8.5px;">
            A los efectos previstos en el art.13 de la PA-0071 Nro. SNAT\2011\0071 se expresan los montos de la factura en Bs. Considerando el tipo de cambio establecido por el BCV de Bs/US$ segun Res.#19-05-01 (GAC.OFIC.NRO.41.624 02-05-2019)
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="bottom-tax-legend">
            ESTA FACTURA VA SIN TACHADURAS NI ENMENDADURAS
        </div>

    </div>

</body>
</html>
