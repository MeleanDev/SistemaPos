@extends('Sistema.impresiones.layouts.documento_base')

@section('titulo', 'Nota de Entrega / Factura ' . ($venta->codigo ?? '#' . $venta->id))

@section('tamano_papel_css', 'letter portrait')
@section('margen_pagina_css', '12mm 12mm 14mm 12mm')

@section('estilos_adicionales')
    .theme-color { color: {{ $colorPrimario }}; }
    .theme-bg { background-color: {{ $colorPrimario }}; color: #ffffff; }

    /* ESTILO COMERCIAL CLÁSICO (INSPIRADO EN TIENDAS REALES / MEGASTORE) */
    .header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    .header-table td {
        vertical-align: top;
    }

    .doc-meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
        margin-bottom: 12px;
    }
    .doc-meta-table td {
        padding: 2.5px 4px;
        vertical-align: top;
    }

    .table-items-clasica {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
        margin-bottom: 10px;
    }
    .table-items-clasica th {
        border-top: 1px solid #334155;
        border-bottom: 1px solid #334155;
        padding: 5px 4px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 8.5px;
        background-color: transparent;
    }
    .table-items-clasica td {
        padding: 5px 4px;
        border-bottom: 1px dotted #cbd5e1;
        vertical-align: middle;
    }

    .table-totals-clasica {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5px;
    }
    .table-totals-clasica td {
        padding: 2.5px 4px;
    }

    .stamp-entregado {
        display: inline-block;
        border: 2px dashed #0f172a;
        padding: 4px 14px;
        font-weight: 900;
        font-size: 13px;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin-top: 15px;
        color: #0f172a;
        opacity: 0.85;
    }

    .page-break {
        page-break-before: always;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px dashed #cbd5e1;
    }
@endsection

@section('botones_barra')
    <a href="{{ route('pos.descargar_pdf', $venta->id) }}" class="btn-action btn-pdf">
        📥 Descargar PDF
    </a>
    <a href="{{ route('pos.imprimir_ticket', $venta->id) }}" class="btn-action btn-ticket">
        🧾 Ver Ticket
    </a>
    @if($config->garantia_activa)
        <a href="{{ route('pos.imprimir_garantia', $venta->id) }}" class="btn-action btn-garantia">
            🛡️ Hoja de Garantía
        </a>
    @endif
@endsection

@section('contenido_documento')

    @php
        $logoPath = null;
        if ($empresa->logo && file_exists(storage_path('app/public/' . $empresa->logo))) {
            $logoPath = 'data:image/png;base64,' . base64_encode(file_get_contents(storage_path('app/public/' . $empresa->logo)));
        }
    @endphp

    {{-- CABECERA CORPORATIVA --}}
    <table class="header-table">
        <tr>
            <td style="width: 25%; text-align: left;">
                @if($logoPath)
                    <img src="{{ $logoPath }}" alt="{{ $empresa->nombre }}" style="max-height: 60px; max-width: 160px; object-fit: contain;">
                @else
                    <h2 class="theme-color text-uppercase fw-bold" style="font-size: 16px; margin: 0;">{{ $empresa->nombre }}</h2>
                @endif
            </td>
            <td style="width: 75%; text-align: center; padding-left: 10px;">
                <h3 class="fw-bold text-dark text-uppercase" style="font-size: 14px; margin: 0; letter-spacing: 1px;">{{ $empresa->razon_social ?: $empresa->nombre }}</h3>
                <div class="font-mono fw-bold" style="font-size: 9.5px; color: #1e293b; margin-top: 1px;">RIF: {{ $empresa->rif }}</div>
                <div class="text-muted" style="font-size: 8.5px;">{{ $empresa->direccion }}</div>
                <div class="text-muted font-mono" style="font-size: 8.5px;">
                    @if($empresa->telefono) WhatsApp / Telf: {{ $empresa->telefono }} @endif
                    @if($empresa->correo) | Email: {{ $empresa->correo }} @endif
                </div>
            </td>
        </tr>
    </table>

    {{-- SECCIÓN DATOS CLIENTE & DATOS DOCUMENTO (2 COLUMNAS) --}}
    <table class="doc-meta-table">
        <tr>
            {{-- COLUMNA IZQUIERDA: CLIENTE --}}
            <td style="width: 52%; border-right: 1px solid #e2e8f0; padding-right: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 32%;" class="fw-bold text-muted">Cliente:</td>
                        <td class="fw-bold text-uppercase">{{ $venta->cliente->nombre ?? 'CLIENTE GENERAL' }} {{ $venta->cliente->apellido ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">C.I. / RIF:</td>
                        <td class="font-mono fw-bold">{{ $venta->cliente->cedula ?? 'V-00000000' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Dirección:</td>
                        <td class="text-muted">{{ $venta->cliente->direccion ?? 'CIUDAD' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Teléfonos:</td>
                        <td class="font-mono text-muted">{{ $venta->cliente->telefono ?? 'Sin teléfono' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Correo:</td>
                        <td class="text-muted">{{ $venta->cliente->correo ?? '---' }}</td>
                    </tr>
                </table>
            </td>

            {{-- COLUMNA DERECHA: DOCUMENTO Y VENDEDOR --}}
            <td style="width: 48%; padding-left: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 42%;" class="fw-bold text-dark">NOTA / FACTURA #:</td>
                        <td class="font-mono fw-bold theme-color" style="font-size: 11px;">{{ $venta->codigo }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Fecha de emisión:</td>
                        <td class="font-mono">{{ \Carbon\Carbon::parse($venta->fecha_emision)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Condición de pago:</td>
                        <td class="font-mono fw-bold text-uppercase">{{ $venta->condicion_pago ?? 'CONTADO' }}</td>
                    </tr>
                    <tr>
                        <td class="fw-bold text-muted">Vendedor:</td>
                        <td class="text-uppercase">
                            {{ $venta->vendedor ? $venta->vendedor->nombre : ($venta->usuario ? (trim($venta->usuario->nombre . ' ' . $venta->usuario->apellido) ?: $venta->usuario->name) : 'SISTEMA') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- TABLA DE ÍTEMS ESTILO COMERCIAL CLÁSICO --}}
    <table class="table-items-clasica">
        <thead>
            <tr>
                <th class="text-center" style="width: 8%;">Cantidad</th>
                @if($config->mostrar_codigo_producto)
                    <th style="width: 16%;">Código</th>
                @endif
                <th style="width: {{ $config->mostrar_codigo_producto ? '42%' : '58%' }};">Descripción del producto</th>
                <th style="width: 12%;">Marca</th>
                <th class="text-center" style="width: 8%;">% I.V.A.</th>
                <th class="text-end" style="width: 14%;">Precio unit.</th>
                <th class="text-end" style="width: 16%;">Total neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->detalles as $item)
                @php
                    $nombre = $item->nombre_item ?? ($item->producto->nombre ?? ($item->moto->modelo ?? ($item->servicio->nombre ?? 'Ítem general')));
                    $codigo = $item->producto->codigo_interno ?? ($item->producto->codigo_barra ?? ($item->moto->referencia ?? ($item->servicio->codigo ?? '---')));
                    $marca = $item->producto->marca ?? ($item->moto->marca ?? '---');
                @endphp
                <tr>
                    <td class="text-center font-mono fw-bold">{{ $item->cantidad }}</td>
                    @if($config->mostrar_codigo_producto)
                        <td class="font-mono" style="font-size: 8px;">{{ $codigo }}</td>
                    @endif
                    <td>
                        {{ $nombre }}
                        @if($item->serial_identificador)
                            <div class="font-mono text-muted" style="font-size: 7.5px;">
                                <strong>SERIAL / IMEI:</strong> {{ $item->serial_identificador }}
                            </div>
                        @endif
                        @if($config->mostrar_motos_detalles && $item->moto)
                            <div class="font-mono text-muted" style="font-size: 7.5px;">
                                NIV: {{ $item->moto->numero_niv ?? ($item->moto->numero_chasis ?? '---') }} | MOTOR: {{ $item->moto->numero_motor ?? '---' }}
                                @if($item->moto->color) | COLOR: {{ $item->moto->color }} @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-muted" style="font-size: 8px;">{{ $marca }}</td>
                    <td class="text-center font-mono">{{ $venta->iva_usd > 0 ? '16,00' : '0,00' }}</td>
                    <td class="text-end font-mono">{{ number_format($item->precio_unitario_usd ?? 0, 2, '.', ',') }}</td>
                    <td class="text-end font-mono fw-bold">{{ number_format($item->subtotal_usd ?? 0, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- TOTALES Y OBSERVACIONES --}}
    <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-right: 15px;">
                <div style="font-size: 8.5px; color: #475569;">
                    <strong>Observaciones:</strong>
                    {{ $config->nota_pie_factura ?: 'Comprobante de entrega mercantil. Conserve su factura para cualquier trámite o reclamo de garantía.' }}
                </div>
                <div class="font-mono fw-bold text-muted mt-2" style="font-size: 8.5px;">
                    Total Ítems: {{ $venta->detalles->sum('cantidad') }}
                </div>

                @if($config->mostrar_equivalencia_bs)
                    <div style="margin-top: 8px; font-size: 9.5px; border-top: 1px dashed #cbd5e1; padding-top: 4px;">
                        <span class="text-muted">Total en Bolívares (Tasa Oficial):</span>
                        <strong class="font-mono text-dark">Bs. {{ number_format($venta->total_bs ?? 0, 2, ',', '.') }}</strong>
                    </div>
                @endif

                <div class="stamp-entregado">
                    ENTREGADO
                </div>
            </td>
            <td style="width: 45%; vertical-align: top;">
                <table class="table-totals-clasica">
                    <tr>
                        <td class="text-muted">Subtotal:</td>
                        <td class="text-end font-mono">${{ number_format($venta->subtotal_usd ?? 0, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Descuento:</td>
                        <td class="text-end font-mono">${{ number_format($venta->descuento_usd ?? 0, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Exento:</td>
                        <td class="text-end font-mono">${{ number_format($venta->iva_usd == 0 ? $venta->total_usd : 0, 2, '.', ',') }}</td>
                    </tr>
                    @if($venta->iva_usd > 0)
                    <tr>
                        <td class="text-muted">Base imponible (16%):</td>
                        <td class="text-end font-mono">${{ number_format($venta->subtotal_usd ?? 0, 2, '.', ',') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Impuesto (IVA 16%):</td>
                        <td class="text-end font-mono">${{ number_format($venta->iva_usd ?? 0, 2, '.', ',') }}</td>
                    </tr>
                    @endif
                    <tr style="border-top: 1.5px solid #0f172a; border-bottom: 1.5px solid #0f172a; font-weight: bold; font-size: 11px;">
                        <td>TOTAL:</td>
                        <td class="text-end font-mono" style="font-size: 13px;">${{ number_format($venta->total_usd ?? 0, 2, '.', ',') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

@endsection
