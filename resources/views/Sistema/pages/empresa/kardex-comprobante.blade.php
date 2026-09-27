<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprobante de Movimiento {{ $movimiento->codigo }} - {{ $movimiento->empresa->nombre ?? 'Sistema POS' }}</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            font-size: 12px;
            margin: 0;
            padding: 20px 0;
        }

        .font-mono {
            font-family: 'JetBrains Mono', monospace;
        }

        .hoja-impresion {
            background: #ffffff;
            width: 216mm;
            min-height: 279mm;
            margin: 0 auto;
            padding: 16mm;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            box-sizing: border-box;
        }

        .barra-acciones {
            width: 216mm;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .barra-acciones {
                display: none !important;
            }
            .hoja-impresion {
                width: 100%;
                margin: 0;
                padding: 10mm;
                box-shadow: none;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>

    <div class="barra-acciones">
        <a href="{{ route('kardex') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 fw-bold">
            <i class="fas fa-arrow-left me-1"></i> Volver a Movimientos
        </a>
        <button type="button" class="btn btn-sm btn-primary rounded-pill px-4 py-1.5 fw-bold shadow-sm" onclick="window.print()">
            <i class="fas fa-print me-1"></i> Imprimir Comprobante
        </button>
    </div>

    <div class="hoja-impresion">
        
        <!-- ENCABEZADO DE LA EMPRESA Y COMPROBANTE -->
        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">
            <div>
                <h4 class="fw-bold mb-1 text-dark">{{ $movimiento->empresa->nombre ?? 'MI EMPRESA' }}</h4>
                <div class="text-muted small">
                    <span><strong>RIF:</strong> {{ $movimiento->empresa->rif ?? 'J-00000000-0' }}</span><br>
                    <span>{{ $movimiento->empresa->direccion ?? 'Dirección Fiscal' }}</span><br>
                    <span><strong>Teléfono:</strong> {{ $movimiento->empresa->telefono ?? '--' }}</span>
                </div>
            </div>
            <div class="text-end">
                <div class="badge bg-dark text-white rounded-pill px-3 py-1.5 font-mono fw-bold fs-6 mb-1">
                    {{ $movimiento->codigo }}
                </div>
                <div class="text-primary fw-bold" style="font-size: 0.95rem;">
                    @if($movimiento->tipo === 'traslado')
                        GUÍA DE TRASLADO ENTRE ALMACENES
                    @elseif($movimiento->tipo === 'ajuste_entrada')
                        ACTA DE AJUSTE DE INVENTARIO (ENTRADA)
                    @else
                        ACTA DE AJUSTE DE INVENTARIO (SALIDA)
                    @endif
                </div>
                <small class="text-muted font-mono">Fecha: {{ $movimiento->fecha ? $movimiento->fecha->format('d/m/Y') : date('d/m/Y') }}</small>
            </div>
        </div>

        <!-- DETALLES DE LA OPERACIÓN -->
        <div class="card border rounded-3 p-3 bg-light mb-3">
            <div class="row g-2">
                @if($movimiento->tipo === 'traslado')
                    <div class="col-6">
                        <small class="text-muted d-block">Almacén Origen:</small>
                        <strong class="text-dark">{{ $movimiento->almacenOrigen?->nombre ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Almacén Destino:</small>
                        <strong class="text-dark">{{ $movimiento->almacenDestino?->nombre ?? 'N/A' }}</strong>
                    </div>
                @else
                    <div class="col-6">
                        <small class="text-muted d-block">Almacén de Aplicación:</small>
                        <strong class="text-dark">{{ $movimiento->almacenOrigen?->nombre ?? $movimiento->almacenDestino?->nombre ?? 'N/A' }}</strong>
                    </div>
                    <div class="col-6">
                        <small class="text-muted d-block">Tipo de Operación:</small>
                        <strong class="{{ $movimiento->tipo === 'ajuste_entrada' ? 'text-success' : 'text-danger' }}">
                            {{ $movimiento->tipo === 'ajuste_entrada' ? 'Ajuste Positivo (+ Ingreso)' : 'Ajuste Negativo (- Salida/Merma)' }}
                        </strong>
                    </div>
                @endif

                <div class="col-6">
                    <small class="text-muted d-block">Motivo / Justificación:</small>
                    <span class="text-dark fw-semibold">{{ $movimiento->motivo }}</span>
                </div>
                <div class="col-6">
                    <small class="text-muted d-block">Responsable / Operador:</small>
                    <span class="text-dark">{{ $movimiento->usuario?->name ?? 'Sistema' }}</span>
                </div>

                @if($movimiento->observaciones)
                    <div class="col-12 mt-2 pt-2 border-top">
                        <small class="text-muted d-block">Observaciones:</small>
                        <span class="fst-italic text-secondary">{{ $movimiento->observaciones }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- TABLA DE ARTÍCULOS -->
        <table class="table table-bordered table-sm align-middle mb-4">
            <thead class="table-light font-mono" style="font-size: 0.75rem;">
                <tr>
                    <th class="text-center" style="width: 35px;">#</th>
                    <th>Producto / Código</th>
                    <th class="text-center" style="width: 80px;">Unidad</th>
                    <th class="text-center" style="width: 100px;">Cantidad</th>
                    <th class="text-end" style="width: 110px;">Costo Ref. ($)</th>
                </tr>
            </thead>
            <tbody>
                @php $totalUnids = 0; @endphp
                @foreach($movimiento->kardex->unique('producto_id') as $idx => $k)
                    @php $totalUnids += $k->cantidad; @endphp
                    <tr>
                        <td class="text-center font-mono">{{ $idx + 1 }}</td>
                        <td>
                            <strong class="text-dark">{{ $k->producto?->nombre ?? 'Producto' }}</strong>
                            <small class="text-muted d-block font-mono">SKU: {{ $k->producto?->codigo_interno ?? '--' }}</small>
                        </td>
                        <td class="text-center font-mono small">{{ $k->producto?->unidad_medida ?? 'UND' }}</td>
                        <td class="text-center font-mono fw-bold">{{ number_format($k->cantidad, 2, ',', '.') }}</td>
                        <td class="text-end font-mono">$ {{ number_format($k->costo_unitario_usd, 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light font-mono">
                <tr>
                    <td colspan="3" class="text-end fw-bold">Total Unidades:</td>
                    <td class="text-center fw-bold">{{ number_format($totalUnids, 2, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <!-- SECCIÓN DE FIRMAS -->
        <div class="row mt-5 pt-4 text-center" style="margin-top: 60px;">
            <div class="col-4">
                <div class="border-top border-dark pt-2 mx-3">
                    <small class="fw-bold d-block">Entregado / Despachado por</small>
                    <small class="text-muted">Firma y Cédula</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-2 mx-3">
                    <small class="fw-bold d-block">Transportado / Autorizado por</small>
                    <small class="text-muted">Firma y Cédula</small>
                </div>
            </div>
            <div class="col-4">
                <div class="border-top border-dark pt-2 mx-3">
                    <small class="fw-bold d-block">Recibido / Verificado por</small>
                    <small class="text-muted">Firma y Cédula</small>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
