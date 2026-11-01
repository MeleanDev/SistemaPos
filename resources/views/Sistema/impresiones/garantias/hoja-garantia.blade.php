@extends('Sistema.impresiones.layouts.documento_base')

@section('titulo', 'Póliza de Garantía ' . ($venta->codigo ?? '#' . $venta->id))

@section('tamano_papel_css', 'letter portrait')
@section('margen_pagina_css', '12mm 12mm 14mm 12mm')

@section('estilos_adicionales')
    .theme-color { color: {{ $colorPrimario }}; }
    .theme-bg { background-color: {{ $colorPrimario }}; color: #ffffff; }
    .theme-border { border-color: {{ $colorPrimario }} !important; }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .header-table td {
        vertical-align: middle;
    }

    .warranty-title-box {
        background-color: {{ $colorPrimario }};
        color: #ffffff;
        text-align: center;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: bold;
        font-size: 13px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 12px;
    }

    .box-info {
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 12px;
        margin-bottom: 12px;
        background-color: #ffffff;
    }
    .box-info table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .box-info td {
        padding: 2.5px 4px;
        vertical-align: top;
    }

    .table-items {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 9.5px;
    }
    .table-items th {
        background-color: #f1f5f9;
        color: #1e293b;
        border-top: 1.5px solid {{ $colorPrimario }};
        border-bottom: 1.5px solid {{ $colorPrimario }};
        padding: 6px 8px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 9px;
    }
    .table-items td {
        padding: 6px 8px;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }

    .clauses-container {
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        padding: 10px 14px;
        background-color: #ffffff;
        font-size: 9px;
        line-height: 1.45;
        color: #334155;
        margin-bottom: 16px;
        text-align: justify;
    }
    .clauses-container h6 {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        margin-bottom: 6px;
        color: #0f172a;
    }

    .signatures-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 24px;
        page-break-inside: avoid;
    }
    .signatures-table td {
        width: 45%;
        vertical-align: bottom;
        text-align: center;
        font-size: 9px;
    }
    .signature-line {
        border-top: 1.5px dashed #64748b;
        padding-top: 6px;
        margin-top: 40px;
    }
@endsection

@section('botones_barra')
    <a href="{{ route('pos.descargar_garantia_pdf', $venta->id) }}" class="btn-action btn-pdf">
        📥 Descargar PDF Garantía
    </a>
    <a href="{{ route('pos.imprimir_carta', $venta->id) }}" class="btn-action btn-print">
        📄 Ver Factura
    </a>
@endsection

@section('contenido_documento')

    @php
        $logoPath = null;
        if ($empresa->logo && file_exists(storage_path('app/public/' . $empresa->logo))) {
            $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents(storage_path('app/public/' . $empresa->logo)));
        }

        $diasGarantia = $config->garantia_dias_defecto ?: 30;
        $fechaVencimiento = \Carbon\Carbon::parse($venta->fecha_emision)->addDays($diasGarantia)->format('d/m/Y');
    @endphp

    {{-- CABECERA CORPORATIVA --}}
    <table class="header-table">
        <tr>
            <td style="width: 30%; text-align: left;">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $empresa->nombre }}" style="max-height: 58px; max-width: 170px; object-fit: contain;">
                @else
                    <h3 class="theme-color text-uppercase fw-bold" style="font-size: 15px; margin: 0;">{{ $empresa->nombre }}</h3>
                @endif
            </td>
            <td style="width: 70%; text-align: right;">
                <h4 class="fw-bold text-dark text-uppercase" style="font-size: 12px; margin: 0;">{{ $empresa->razon_social }}</h4>
                <div class="fw-bold font-mono" style="font-size: 9.5px; color: #334155;">RIF: {{ $empresa->rif }}</div>
                <div class="text-muted" style="font-size: 8.5px;">{{ $empresa->direccion }}</div>
                <div class="text-muted" style="font-size: 8.5px;">
                    @if($empresa->telefono) Telf: {{ $empresa->telefono }} @endif
                    @if($empresa->correo) | Email: {{ $empresa->correo }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- BANNER DE TÍTULO DE LA PÓLIZA --}}
    <div class="warranty-title-box">
        {{ $config->garantia_titulo ?: 'TÉRMINOS Y CONDICIONES GENERALES DE GARANTÍA' }}
    </div>

    {{-- DATOS DEL DOCUMENTO Y BENEFICIARIO --}}
    <div class="box-info">
        <table>
            <tr>
                <td style="width: 16%;" class="fw-bold text-muted">FACTURA N°:</td>
                <td style="width: 34%;" class="font-mono fw-bold theme-color" style="font-size: 11px;">{{ $venta->codigo }}</td>
                <td style="width: 20%;" class="fw-bold text-muted">FECHA EMISIÓN:</td>
                <td style="width: 30%;" class="font-mono">{{ \Carbon\Carbon::parse($venta->fecha_emision)->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="fw-bold text-muted">BENEFICIARIO:</td>
                <td class="fw-bold text-uppercase">{{ $venta->cliente->nombre ?? 'CLIENTE GENERAL' }} {{ $venta->cliente->apellido ?? '' }}</td>
                <td class="fw-bold text-muted">VIGENCIA HASTA:</td>
                <td class="font-mono fw-bold text-success">{{ $fechaVencimiento }} ({{ $diasGarantia }} días)</td>
            </tr>
            <tr>
                <td class="fw-bold text-muted">CÉDULA / RIF:</td>
                <td class="font-mono fw-bold">{{ $venta->cliente->cedula ?? 'V-00000000' }}</td>
                <td class="fw-bold text-muted">TELÉFONO:</td>
                <td class="font-mono text-muted">{{ $venta->cliente->telefono ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    {{-- SI LA VENTA TIENE MOTOS / VEHÍCULOS / RECOMENDACIONES TÉCNICAS --}}
    @php
        $motos = $venta->detalles->filter(fn($d) => $d->moto !== null);
    @endphp
    @if($motos->count() > 0)
        <div class="box-info" style="border-left: 3px solid {{ $colorPrimario }};">
            <span class="fw-bold font-mono text-dark" style="font-size: 9.5px; text-transform: uppercase;">
                IDENTIFICACIÓN TÉCNICA DEL VEHÍCULO / UNIDAD AMPARADA:
            </span>
            <table style="margin-top: 4px;">
                @foreach($motos as $itemMoto)
                    <tr>
                        <td style="width: 25%;" class="fw-bold">MODELO: {{ $itemMoto->moto->modelo ?? ($itemMoto->moto->marca ?? 'N/A') }}</td>
                        <td style="width: 25%;" class="font-mono"><strong>NIV:</strong> {{ $itemMoto->moto->numero_niv ?? ($itemMoto->moto->numero_chasis ?? '---') }}</td>
                        <td style="width: 25%;" class="font-mono"><strong>MOTOR:</strong> {{ $itemMoto->moto->numero_motor ?? '---' }}</td>
                        <td style="width: 25%;" class="font-mono"><strong>COLOR:</strong> {{ $itemMoto->moto->color ?? '---' }}</td>
                    </tr>
                @endforeach
            </table>
        </div>
    @endif

    {{-- LISTA DE ÍTEMS COBIJADOS --}}
    <table class="table-items">
        <thead>
            <tr>
                <th style="width: 15%;">CÓDIGO</th>
                <th style="width: 55%;">DESCRIPCIÓN DEL PRODUCTO / EQUIPO</th>
                <th class="text-center" style="width: 10%;">CANT</th>
                <th class="text-center" style="width: 20%;">COBERTURA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $detalle)
                @php
                    $nombre = $detalle->nombre_item ?? ($detalle->producto->nombre ?? ($detalle->moto->modelo ?? ($detalle->servicio->nombre ?? 'Ítem general')));
                    $codigo = $detalle->producto->codigo_interno ?? ($detalle->producto->codigo_barra ?? ($detalle->moto->referencia ?? ($detalle->servicio->codigo ?? '---')));
                    $marca = $detalle->producto->marca ?? ($detalle->moto->marca ?? null);
                @endphp
                <tr>
                    <td class="font-mono" style="font-size: 8.5px;">{{ $codigo }}</td>
                    <td>
                        <strong>{{ $nombre }}</strong>
                        @if($marca)
                            <span class="text-muted" style="font-size: 8.5px;">({{ $marca }})</span>
                        @endif
                        @if($detalle->serial_identificador)
                            <div class="font-mono text-muted" style="font-size: 8px;">
                                <strong>SERIAL / IMEI:</strong> {{ $detalle->serial_identificador }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center font-mono fw-bold">{{ $detalle->cantidad }}</td>
                    <td class="text-center font-mono text-success fw-bold">{{ $diasGarantia }} DÍAS CONTINUOS</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- CLÁUSULAS Y CONDICIONES GENERALES --}}
    <div class="clauses-container">
        <h6>Cláusulas y Disposiciones de Cobertura</h6>
        {!! nl2br(e($config->garantia_clausulas ?: "1. La presente garantía cubre defectos de fabricación bajo condiciones normales de uso.\n2. No cubre fallas causadas por variaciones de voltaje, negligencia, alteraciones de software, rotura de precintos de seguridad o manipulación por técnicos no autorizados.\n3. Para hacer efectiva la garantía es requisito indispensable presentar este certificado junto a la factura fiscal y empaques en óptimas condiciones.")) !!}
    </div>

    {{-- BLOQUE DE FIRMAS --}}
    @if($config->garantia_requiere_firma)
        <table class="signatures-table">
            <tr>
                <td>
                    <div class="signature-line">
                        <strong>POR LA EMPRESA / SERVICIO TÉCNICO</strong><br>
                        <span class="text-muted">{{ $empresa->nombre }}</span><br>
                        <span class="text-muted" style="font-size: 8px;">Firma Autorizada y Sello</span>
                    </div>
                </td>
                <td style="width: 10%;"></td>
                <td>
                    <div class="signature-line">
                        <strong>RECIBIDO EN CONFORMIDAD (CLIENTE)</strong><br>
                        <span class="text-muted">{{ $venta->cliente->nombre ?? 'CLIENTE' }} {{ $venta->cliente->apellido ?? '' }}</span><br>
                        <span class="font-mono text-muted" style="font-size: 8px;">C.I. / RIF: {{ $venta->cliente->cedula ?? '' }}</span>
                    </div>
                </td>
            </tr>
        </table>
    @endif

@endsection
