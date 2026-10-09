<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Service\Finanzas\ReporteClass;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteController extends Controller
{
    public function __construct(
        private ReporteClass $reporteClass
    ) {}

    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $catalogos = $this->reporteClass->catalogosFiltros($empresaId);

        return view('Sistema.pages.empresa.reportes', $catalogos);
    }

    /* =========================================================================
     * 1. REPORTE DE INGRESOS Y CAJA
     * ========================================================================= */

    public function ingresos(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'caja_id', 'metodo_pago_id']);
            $data = $this->reporteClass->reporteIngresos($empresaId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de ingresos: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfIngresos(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'caja_id', 'metodo_pago_id']);
        $data = $this->reporteClass->reporteIngresos($empresaId, $filtros);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-ingresos-pdf', [
                'empresa' => $empresa,
                'data' => $data,
            ])->setPaper('a4', 'portrait');

            $fileName = 'Reporte_Ingresos_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-ingresos-pdf', [
            'empresa' => $empresa,
            'data' => $data,
        ]);
    }

    public function excelIngresos(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'caja_id', 'metodo_pago_id']);
        $data = $this->reporteClass->reporteIngresos($empresaId, $filtros);

        $fileName = 'Reporte_Ingresos_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM

            // Resumen de Métodos de Pago
            fputcsv($output, ['REPORTE DE INGRESOS Y RECAUDACION POR METODO DE PAGO'], ';');
            fputcsv($output, ['Periodo:', $data['kpis']['periodo_texto']], ';');
            fputcsv($output, ['Total Facturado USD:', number_format($data['kpis']['total_facturado_usd'], 2), 'Total Facturado Bs:', number_format($data['kpis']['total_facturado_bs'], 2)], ';');
            fputcsv($output, ['Total Cobrado USD:', number_format($data['kpis']['total_pagado_usd'], 2), 'Total Cobrado Bs:', number_format($data['kpis']['total_pagado_bs'], 2)], ';');
            fputcsv($output, ['Por Cobrar USD:', number_format($data['kpis']['total_por_cobrar_usd'], 2)], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['--- DESGLOSE POR METODO DE PAGO ---'], ';');
            fputcsv($output, ['Metodo de Pago', 'Nro Transacciones', 'Monto USD', 'Monto Bs', '% Participacion'], ';');
            foreach ($data['desglose_metodos'] as $m) {
                fputcsv($output, [
                    $m['nombre'],
                    $m['transacciones_count'],
                    number_format($m['total_usd'], 2),
                    number_format($m['total_bs'], 2),
                    $m['porcentaje'].'%',
                ], ';');
            }

            fputcsv($output, [], ';');
            fputcsv($output, ['--- DETALLE DE TRANSACCIONES ---'], ';');
            fputcsv($output, ['Factura', 'Fecha', 'Hora', 'Cliente', 'Caja', 'Metodos', 'Total USD', 'Pagado USD', 'Pendiente USD'], ';');
            foreach ($data['transacciones'] as $t) {
                fputcsv($output, [
                    $t['codigo'],
                    $t['fecha'],
                    $t['hora'],
                    $t['cliente'],
                    $t['caja'],
                    $t['metodos'],
                    number_format($t['total_usd'], 2),
                    number_format($t['monto_pagado_usd'], 2),
                    number_format($t['saldo_pendiente_usd'], 2),
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /* =========================================================================
     * 2. REPORTE DE CREDITOS Y DEUDAS (CXC / CXP)
     * ========================================================================= */

    public function creditos(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $filtros = $request->only(['fecha_inicio', 'fecha_fin']);
            $data = $this->reporteClass->reporteCreditos($empresaId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de créditos y deudas: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfCreditos(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $filtros = $request->only(['fecha_inicio', 'fecha_fin']);
        $data = $this->reporteClass->reporteCreditos($empresaId, $filtros);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-creditos-pdf', [
                'empresa' => $empresa,
                'data' => $data,
            ])->setPaper('a4', 'portrait');

            $fileName = 'Reporte_Creditos_CXC_CXP_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-creditos-pdf', [
            'empresa' => $empresa,
            'data' => $data,
        ]);
    }

    public function excelCreditos(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $filtros = $request->only(['fecha_inicio', 'fecha_fin']);
        $data = $this->reporteClass->reporteCreditos($empresaId, $filtros);

        $fileName = 'Reporte_Creditos_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REPORTE DE CARTERA DE CREDITOS Y DEUDAS (CXC / CXP)'], ';');
            fputcsv($output, ['Total por Cobrar (Clientes) USD:', number_format($data['kpis']['total_cxc_usd'], 2), 'Bs:', number_format($data['kpis']['total_cxc_bs'], 2)], ';');
            fputcsv($output, ['Total por Pagar (Proveedores) USD:', number_format($data['kpis']['total_cxp_usd'], 2), 'Bs:', number_format($data['kpis']['total_cxp_bs'], 2)], ';');
            fputcsv($output, ['Abonos Cobrados Periodo USD:', number_format($data['kpis']['total_abonos_cxc_usd'], 2)], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['--- CUENTAS POR COBRAR PENDIENTES (CLIENTES) ---'], ';');
            fputcsv($output, ['Cliente', 'Documento', 'Telefono', 'Factura', 'Emision', 'Vencimiento', 'Mora/Dias', 'Total USD', 'Abonado USD', 'Saldo USD', 'Estado'], ';');
            foreach ($data['cxc_pendientes'] as $c) {
                fputcsv($output, [
                    $c['cliente_nombre'],
                    $c['cliente_cedula'],
                    $c['cliente_telefono'],
                    $c['factura_codigo'],
                    $c['fecha_emision'],
                    $c['fecha_vencimiento'],
                    $c['dias_mora'] > 0 ? "{$c['dias_mora']} d mora" : "Al día ({$c['dias_para_vencer']} d)",
                    number_format($c['monto_total_usd'], 2),
                    number_format($c['monto_pagado_usd'], 2),
                    number_format($c['saldo_pendiente_usd'], 2),
                    $c['estado'],
                ], ';');
            }

            fputcsv($output, [], ';');
            fputcsv($output, ['--- CUENTAS POR PAGAR PENDIENTES (PROVEEDORES) ---'], ';');
            fputcsv($output, ['Proveedor', 'RIF', 'Recepcion', 'Factura Prov', 'Emision', 'Vencimiento', 'Mora/Dias', 'Total USD', 'Abonado USD', 'Saldo USD', 'Estado'], ';');
            foreach ($data['cxp_pendientes'] as $p) {
                fputcsv($output, [
                    $p['proveedor_nombre'],
                    $p['proveedor_rif'],
                    $p['recepcion_codigo'],
                    $p['numero_factura'],
                    $p['fecha_emision'],
                    $p['fecha_vencimiento'],
                    $p['dias_mora'] > 0 ? "{$p['dias_mora']} d mora" : "Al día ({$p['dias_para_vencer']} d)",
                    number_format($p['monto_total_usd'], 2),
                    number_format($p['monto_pagado_usd'], 2),
                    number_format($p['saldo_pendiente_usd'], 2),
                    $p['estado'],
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /* =========================================================================
     * 3. REPORTE DE INVENTARIO Y VALORIZACION
     * ========================================================================= */

    public function inventario(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $filtros = $request->only(['almacen_id', 'categoria_id', 'bajo_stock']);
            $data = $this->reporteClass->reporteInventario($empresaId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de inventario: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfInventario(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $filtros = $request->only(['almacen_id', 'categoria_id', 'bajo_stock']);
        $data = $this->reporteClass->reporteInventario($empresaId, $filtros);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-inventario-pdf', [
                'empresa' => $empresa,
                'data' => $data,
            ])->setPaper('a4', 'landscape');

            $fileName = 'Reporte_Inventario_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-inventario-pdf', [
            'empresa' => $empresa,
            'data' => $data,
        ]);
    }

    public function excelInventario(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $filtros = $request->only(['almacen_id', 'categoria_id', 'bajo_stock']);
        $data = $this->reporteClass->reporteInventario($empresaId, $filtros);

        $fileName = 'Reporte_Inventario_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REPORTE DE INVENTARIO Y VALORIZACION'], ';');
            fputcsv($output, ['Total Items:', $data['kpis']['total_items'], 'Total Unidades:', $data['kpis']['total_unidades']], ';');
            fputcsv($output, ['Valor Costo USD:', number_format($data['kpis']['valor_costo_usd'], 2), 'Valor Venta USD:', number_format($data['kpis']['valor_venta_usd'], 2)], ';');
            fputcsv($output, ['Margen Proyectado USD:', number_format($data['kpis']['margen_proyectado_usd'], 2), 'Margen %:', $data['kpis']['margen_porcentaje'].'%'], ';');
            fputcsv($output, ['Alertas Bajo Stock:', $data['kpis']['conteo_bajo_stock']], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['Codigo', 'Producto', 'Categoria', 'Almacen', 'Stock Actual', 'Stock Minimo', 'Costo Unit USD', 'Precio Venta USD', 'Costo Total USD', 'Venta Total USD', 'Alerta'], ';');
            foreach ($data['inventario'] as $item) {
                fputcsv($output, [
                    $item['codigo_interno'],
                    $item['nombre'],
                    $item['categoria'],
                    $item['almacen'],
                    $item['stock_actual'],
                    $item['stock_minimo'],
                    number_format($item['costo_unitario_usd'], 2),
                    number_format($item['precio_venta_usd'], 2),
                    number_format($item['costo_subtotal_usd'], 2),
                    number_format($item['venta_subtotal_usd'], 2),
                    $item['es_bajo_stock'] ? 'BAJO STOCK' : 'OK',
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /* =========================================================================
     * 4. REPORTE DE RENTABILIDAD Y UTILIDAD BRUTA
     * ========================================================================= */

    public function rentabilidad(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'almacen_id']);
            $data = $this->reporteClass->reporteRentabilidad($empresaId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de rentabilidad: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfRentabilidad(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'almacen_id']);
        $data = $this->reporteClass->reporteRentabilidad($empresaId, $filtros);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-rentabilidad-pdf', [
                'empresa' => $empresa,
                'data' => $data,
            ])->setPaper('a4', 'portrait');

            $fileName = 'Reporte_Rentabilidad_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-rentabilidad-pdf', [
            'empresa' => $empresa,
            'data' => $data,
        ]);
    }

    public function excelRentabilidad(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $filtros = $request->only(['fecha_inicio', 'fecha_fin', 'almacen_id']);
        $data = $this->reporteClass->reporteRentabilidad($empresaId, $filtros);

        $fileName = 'Reporte_Rentabilidad_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REPORTE DE RENTABILIDAD Y GANANCIA BRUTA REAL'], ';');
            fputcsv($output, ['Periodo:', $data['kpis']['periodo_texto']], ';');
            fputcsv($output, ['Ingresos Totales USD:', number_format($data['kpis']['total_ingresos_usd'], 2), 'Bs:', number_format($data['kpis']['total_ingresos_bs'], 2)], ';');
            fputcsv($output, ['Costo de Ventas (COGS) USD:', number_format($data['kpis']['total_costo_usd'], 2), 'Bs:', number_format($data['kpis']['total_costo_bs'], 2)], ';');
            fputcsv($output, ['Ganancia Bruta USD:', number_format($data['kpis']['ganancia_bruta_usd'], 2), 'Bs:', number_format($data['kpis']['ganancia_bruta_bs'], 2)], ';');
            fputcsv($output, ['Margen Bruto Total:', number_format($data['kpis']['margen_bruto_porcentaje'], 1).'%'], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['--- TOP PRODUCTOS / SERVICIOS CON MAYOR RETORNO ---'], ';');
            fputcsv($output, ['Nombre', 'Tipo', 'Categoria', 'Cant Vendida', 'Ingreso Total USD', 'Costo Total USD', 'Ganancia Bruta USD', 'Margen %'], ';');
            foreach ($data['top_productos'] as $p) {
                fputcsv($output, [
                    $p['nombre'],
                    $p['tipo'],
                    $p['categoria'],
                    $p['cantidad_vendida'],
                    number_format($p['ingreso_total_usd'], 2),
                    number_format($p['costo_total_usd'], 2),
                    number_format($p['ganancia_bruta_usd'], 2),
                    $p['margen_porcentaje'].'%',
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /* =========================================================================
     * 5. REPORTE DE VENDEDORES Y COMISIONES (GENERADOR DIRECTO)
     * ========================================================================= */

    private function obtenerDatosVendedores(Request $request): array
    {
        $empresaId = $this->obtenerEmpresaId();

        $fechaInicio = $request->input('fecha_inicio') ?: date('Y-m-01');
        $fechaFin = $request->input('fecha_fin') ?: date('Y-m-d');
        $vendedorId = $request->input('vendedor_id');

        $queryVendedores = Vendedor::where('empresa_id', $empresaId);
        if ($vendedorId) {
            $queryVendedores->where('id', $vendedorId);
        }
        $vendedores = $queryVendedores->orderBy('nombre')->get();

        $resumen = [];
        $totalVentasUsd = 0;
        $totalVentasBs = 0;
        $totalComisionesUsd = 0;
        $totalComisionesBs = 0;
        $conteoFacturasTotal = 0;
        $topVendedorNombre = 'N/A';
        $maxVentasVendedor = 0;

        foreach ($vendedores as $v) {
            $ventas = Venta::where('empresa_id', $empresaId)
                ->where('vendedor_id', $v->id)
                ->where('estado', '!=', 'anulada')
                ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                ->get();

            $vVendidoUsd = (float) $ventas->sum('total_usd');
            $vVendidoBs = (float) $ventas->sum('total_bs');
            $vComisionUsd = (float) $ventas->sum('comision_monto_usd');
            $vComisionBs = (float) $ventas->sum('comision_monto_bs');
            $vConteo = $ventas->count();

            $totalVentasUsd += $vVendidoUsd;
            $totalVentasBs += $vVendidoBs;
            $totalComisionesUsd += $vComisionUsd;
            $totalComisionesBs += $vComisionBs;
            $conteoFacturasTotal += $vConteo;

            if ($vVendidoUsd > $maxVentasVendedor) {
                $maxVentasVendedor = $vVendidoUsd;
                $topVendedorNombre = "{$v->nombre} ($".number_format($vVendidoUsd, 2).')';
            }

            $resumen[] = [
                'id' => $v->id,
                'nombre' => $v->nombre,
                'documento' => $v->documento_completo,
                'comision_porcentaje' => (float) $v->comision_porcentaje,
                'conteo_ventas' => $vConteo,
                'total_vendido_usd' => $vVendidoUsd,
                'total_vendido_bs' => $vVendidoBs,
                'total_comision_usd' => $vComisionUsd,
                'total_comision_bs' => $vComisionBs,
            ];
        }

        return [
            'kpis' => [
                'total_ventas_usd' => $totalVentasUsd,
                'total_ventas_bs' => $totalVentasBs,
                'total_comisiones_usd' => $totalComisionesUsd,
                'total_comisiones_bs' => $totalComisionesBs,
                'conteo_facturas' => $conteoFacturasTotal,
                'top_vendedor' => $topVendedorNombre,
                'periodo_texto' => "Del {$fechaInicio} al {$fechaFin}",
            ],
            'resumen' => $resumen,
        ];
    }

    public function vendedores(Request $request): JsonResponse
    {
        try {
            $data = $this->obtenerDatosVendedores($request);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de vendedores: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfVendedores(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $data = $this->obtenerDatosVendedores($request);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-vendedores-pdf', [
                'empresa' => $empresa,
                'periodo_texto' => $data['kpis']['periodo_texto'],
                'kpis' => $data['kpis'],
                'resumen' => $data['resumen'],
            ])->setPaper('a4', 'portrait');

            $fileName = 'Reporte_Vendedores_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-vendedores-pdf', [
            'empresa' => $empresa,
            'periodo_texto' => $data['kpis']['periodo_texto'],
            'kpis' => $data['kpis'],
            'resumen' => $data['resumen'],
        ]);
    }

    public function excelVendedores(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $fechaInicio = $request->input('fecha_inicio') ?: date('Y-m-01');
        $fechaFin = $request->input('fecha_fin') ?: date('Y-m-d');
        $vendedorId = $request->input('vendedor_id');

        $queryVendedores = Vendedor::where('empresa_id', $empresaId);
        if ($vendedorId) {
            $queryVendedores->where('id', $vendedorId);
        }
        $vendedores = $queryVendedores->orderBy('nombre')->get();

        $fileName = 'Reporte_Vendedores_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($empresaId, $vendedores, $fechaInicio, $fechaFin) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REPORTE DE VENTAS Y COMISIONES POR VENDEDOR'], ';');
            fputcsv($output, ['Periodo:', "Del {$fechaInicio} al {$fechaFin}"], ';');
            fputcsv($output, [], ';');

            fputcsv($output, ['Vendedor', 'Documento', '% Comision', 'Facturas', 'Total Vendido USD', 'Total Vendido Bs', 'Comision Ganada USD', 'Comision Ganada Bs'], ';');
            foreach ($vendedores as $v) {
                $ventas = Venta::where('empresa_id', $empresaId)
                    ->where('vendedor_id', $v->id)
                    ->where('estado', '!=', 'anulada')
                    ->whereBetween('fecha_emision', [$fechaInicio, $fechaFin])
                    ->get();

                fputcsv($output, [
                    $v->nombre,
                    $v->documento_completo,
                    number_format((float) $v->comision_porcentaje, 2).'%',
                    $ventas->count(),
                    number_format((float) $ventas->sum('total_usd'), 2),
                    number_format((float) $ventas->sum('total_bs'), 2),
                    number_format((float) $ventas->sum('comision_monto_usd'), 2),
                    number_format((float) $ventas->sum('comision_monto_bs'), 2),
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /* =========================================================================
     * 6. REPORTE DE STOCK POR ALMACÉN (MATRIZ MULTIALMACÉN)
     * ========================================================================= */

    public function stockAlmacenes(Request $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $filtros = $request->only(['categoria_id', 'categoria_ids', 'producto_ids', 'solo_con_stock']);
            $data = $this->reporteClass->reporteStockAlmacenes($empresaId, $filtros);

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte de stock por almacén: '.$e->getMessage(),
            ], 500);
        }
    }

    public function pdfStockAlmacenes(Request $request): Response
    {
        $empresaId = $this->obtenerEmpresaId();
        $empresa = Empresa::findOrFail($empresaId);
        $filtros = $request->only(['categoria_id', 'categoria_ids', 'producto_ids', 'solo_con_stock']);
        $data = $this->reporteClass->reporteStockAlmacenes($empresaId, $filtros);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('Sistema.pdf.reporte-stock-almacenes-pdf', [
                'empresa' => $empresa,
                'data' => $data,
            ])->setPaper('a4', 'landscape');

            $fileName = 'Reporte_Stock_Almacenes_'.date('Ymd_His').'.pdf';

            return $request->boolean('descargar')
                ? $pdf->download($fileName)
                : $pdf->stream($fileName);
        }

        return response()->view('Sistema.pdf.reporte-stock-almacenes-pdf', [
            'empresa' => $empresa,
            'data' => $data,
        ]);
    }

    public function excelStockAlmacenes(Request $request): StreamedResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $filtros = $request->only(['categoria_id', 'categoria_ids', 'producto_ids', 'solo_con_stock']);
        $data = $this->reporteClass->reporteStockAlmacenes($empresaId, $filtros);

        $fileName = 'Reporte_Stock_Almacenes_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($data) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, ['REPORTE DE EXISTENCIAS POR ALMACEN (MATRIZ MULTIALMACEN)'], ';');
            fputcsv($output, [
                'Total Productos:', $data['kpis']['total_productos'],
                'Total Unidades:', number_format((float) $data['kpis']['total_unidades'], 2),
                'Total Almacenes:', $data['kpis']['total_almacenes'],
                'Productos Sin Stock:', $data['kpis']['productos_sin_stock'],
            ], ';');
            fputcsv($output, [], ';');

            $headers = ['Codigo', 'Producto', 'Categoria', 'U.M.'];
            foreach ($data['almacenes'] as $alm) {
                $headers[] = $alm['nombre'].' ('.$alm['codigo'].')';
            }
            $headers[] = 'Stock Total';
            fputcsv($output, $headers, ';');

            foreach ($data['items'] as $item) {
                $row = [
                    $item['codigo_interno'],
                    $item['nombre'],
                    $item['categoria'],
                    $item['unidad_medida'],
                ];
                foreach ($data['almacenes'] as $alm) {
                    $cant = $item['stocks_por_almacen'][$alm['id']] ?? 0;
                    $row[] = number_format((float) $cant, 2);
                }
                $row[] = number_format((float) $item['stock_total'], 2);
                fputcsv($output, $row, ';');
            }

            $totalesRow = ['TOTALES CONSOLIDADOS', '', '', ''];
            foreach ($data['almacenes'] as $alm) {
                $totAlm = $data['totales_por_almacen'][$alm['id']] ?? 0;
                $totalesRow[] = number_format((float) $totAlm, 2);
            }
            $totalesRow[] = number_format((float) $data['kpis']['total_unidades'], 2);
            fputcsv($output, $totalesRow, ';');

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }
}
