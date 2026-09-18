<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura #{{ $factura->correlativo }}</title>
    <style>
        @page {
            margin-top: 30px;
            margin-bottom: 30px;
            margin-left: 45px;
            margin-right: 45px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .watermark {
            position: absolute;
            top: 25%;
            left: 20%;
            width: 60%;
            opacity: 0.05;
            z-index: -1;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 12px;
        }
        .header td {
            vertical-align: middle;
        }
        .logo-container {
            width: 40%;
        }
        .logo {
            max-width: 200px;
            max-height: 80px;
        }
        .company-info {
            width: 60%;
            text-align: right;
            font-size: 11px;
            line-height: 1.4;
            color: #4b5563;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .document-info {
            width: 100%;
            margin-bottom: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px;
        }
        .document-info table {
            width: 100%;
            font-size: 12px;
        }
        .document-info th {
            text-align: left;
            color: #64748b;
            padding-bottom: 4px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .document-info td {
            color: #0f172a;
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #2563eb;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            color: #1e293b;
        }
        .items-table th.right, .items-table td.right {
            text-align: right;
        }
        .totals-container {
            width: 100%;
            margin-top: 15px;
        }
        .totals-table {
            width: 350px;
            float: right;
            border-collapse: collapse;
        }
        .totals-table th, .totals-table td {
            padding: 8px 10px;
            text-align: right;
        }
        .totals-table th {
            color: #475569;
            font-size: 11px;
        }
        .totals-table tr.grand-total td, .totals-table tr.grand-total th {
            background-color: #eff6ff;
            color: #1d4ed8;
            font-weight: bold;
            font-size: 15px;
            border-top: 2px solid #bfdbfe;
        }
        .totals-table tr.bs-total td, .totals-table tr.bs-total th {
            background-color: #f8fafc;
            font-weight: bold;
            font-size: 13px;
            color: #334155;
        }
        .clear {
            clear: both;
        }
        .payments-table {
            width: 100%;
            margin-top: 5px;
            border-collapse: collapse;
        }
        .payments-table th {
            background-color: #f1f5f9;
            color: #475569;
            padding: 8px;
            text-align: left;
            font-size: 10px;
        }
        .payments-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .footer {
            margin-top: 35px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 11px;
            color: white;
            background-color: {{ $factura->estado === 'Pagada' ? '#10b981' : ($factura->estado === 'Por Pagar' || $factura->estado === 'Pendiente' ? '#f59e0b' : '#ef4444') }};
        }
    </style>
</head>
<body>

    @if(file_exists(public_path('estilos/imgPropio/logo.png')))
        <img src="{{ public_path('estilos/imgPropio/logo.png') }}" class="watermark" alt="Watermark">
    @endif

    <table class="header">
        <tr>
            <td class="logo-container">
                @if($factura->empresa->logo)
                    @if(Str::startsWith($factura->empresa->logo, 'http'))
                        <img src="{{ $factura->empresa->logo }}" class="logo" alt="Logo">
                    @elseif(file_exists(public_path('storage/' . $factura->empresa->logo)))
                        <img src="{{ public_path('storage/' . $factura->empresa->logo) }}" class="logo" alt="Logo">
                    @endif
                @else
                    <h1 style="color: #1e3a8a; margin:0;">{{ $factura->empresa->nombre }}</h1>
                @endif
            </td>
            <td class="company-info">
                <div class="title">{{ $factura->empresa->nombre }}</div>
                <div>RIF: {{ $factura->empresa->rif }}</div>
                <div>{{ $factura->empresa->direccion }}</div>
                <div>Telf: {{ $factura->empresa->telefonoUno }} @if($factura->empresa->telefonoDos) / {{ $factura->empresa->telefonoDos }} @endif</div>
                <div>Email: {{ $factura->empresa->correo }}</div>
            </td>
        </tr>
    </table>

    <div class="document-info">
        <table>
            <tr>
                <th>CLIENTE</th>
                <th>DOCUMENTO</th>
                @if($factura->orden && $factura->orden->codigo)
                    <th style="color: #1e3a8a; font-weight: bold;">N° ORDEN</th>
                @endif
                <th>FECHA EMISIÓN</th>
                <th style="text-align: right">ESTADO</th>
            </tr>
            <tr>
                <td>{{ $factura->paciente->nombreUno }} {{ $factura->paciente->apellidoUno }}</td>
                <td>{{ $factura->paciente->cedula ?? 'N/A' }}</td>
                @if($factura->orden && $factura->orden->codigo)
                    <td style="color: #1e3a8a; font-size: 15px; font-weight: 800; letter-spacing: 0.5px;">{{ $factura->orden->codigo }}</td>
                @endif
                <td>{{ $factura->created_at->format('d/m/Y h:i A') }}</td>
                <td style="text-align: right">
                    <span class="status-badge">{{ mb_strtoupper($factura->estado) }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table style="width: 100%; margin-top: 10px; margin-bottom: 12px;">
        <tr>
            <td style="font-size: 16px; color: #1e3a8a; text-align: left; width: 50%;">
                <strong>FACTURA N° {{ $factura->correlativo }}</strong>
            </td>
            <td style="font-size: 15px; color: #b91c1c; text-align: right; width: 50%;">
                <strong>NRO. CONTROL: {{ $factura->numero_control_formateado }}</strong>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Cant.</th>
                <th style="width: 55%;">Descripción de Servicios / Exámenes</th>
                <th class="right" style="width: 17%;">Precio Unitario</th>
                <th class="right" style="width: 18%;">Total USD</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->orden->detalles as $detalle)
            <tr>
                <td>1</td>
                <td>
                    <strong>
                        @if($detalle->perfil_id && $detalle->perfil)
                            {{ $detalle->perfil->nombre }}
                        @elseif($detalle->examen_id && $detalle->examen)
                            {{ $detalle->examen->nombre }}
                        @elseif($detalle->inventario_id && $detalle->inventario)
                            {{ $detalle->inventario->nombre }}
                        @else
                            Ítem médico / Servicio
                        @endif
                    </strong>
                </td>
                <td class="right">${{ number_format($detalle->precio_registrado_usd, 2) }}</td>
                <td class="right">${{ number_format($detalle->precio_registrado_usd, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-container">
        <table class="totals-table">
            <tr>
                <th>SUBTOTAL:</th>
                <td>${{ number_format($factura->subtotal_exento_usd + $factura->base_imponible_usd, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <th>TOTAL A PAGAR:</th>
                <td>${{ number_format($factura->total_usd, 2) }}</td>
            </tr>
            <tr class="bs-total">
                <th>TOTAL BS:</th>
                <td>Bs. {{ number_format($factura->total_bs, 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 10px; color: #64748b; text-align: right; padding-top: 2px;">
                    Tasa de Cambio BCV Referencial: Bs. {{ number_format($factura->tasa_cambio, 2) }}
                </td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    @if($factura->pagos->count() > 0)
    <div style="margin-top: 25px;">
        <h4 style="color: #1e3a8a; border-bottom: 2px solid #2563eb; padding-bottom: 5px; font-size: 12px; margin-bottom: 0;">HISTORIAL DE PAGOS REGISTRADOS</h4>
        <table class="payments-table">
            <thead>
                <tr>
                    <th>FECHA Y HORA</th>
                    <th>MÉTODO DE PAGO</th>
                    <th>NRO. REFERENCIA</th>
                    <th style="text-align: right;">MONTO ABONADO</th>
                </tr>
            </thead>
            <tbody>
                @foreach($factura->pagos as $pago)
                <tr>
                    <td>{{ $pago->created_at->format('d/m/Y h:i A') }}</td>
                    <td>{{ $pago->metodo_pago }}</td>
                    <td>{{ $pago->referencia ?? 'N/A' }}</td>
                    <td style="text-align: right; font-weight: bold; color: #059669;">${{ number_format($pago->monto_usd, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p style="margin:0 0 4px 0;">Este documento es un comprobante de servicio médico. Gracias por preferir nuestros servicios.</p>
        <p style="margin:0;"><strong>{{ $factura->empresa->nombre }}</strong> | Tecnología provista por DataBioSystem</p>
    </div>

</body>
</html>
