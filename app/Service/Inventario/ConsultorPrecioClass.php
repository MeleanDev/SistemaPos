<?php

namespace App\Service\Inventario;

use App\Models\EmpresaMoneda;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ConsultorPrecioClass
{
    /**
     * Obtener la tasa activa de la empresa en USD
     */
    public function obtenerTasaActiva(int $empresaId): float
    {
        $tasaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', 'USD')
            ->value('tasa_cambio');

        if (! $tasaUsd || (float) $tasaUsd <= 0) {
            $tasaUsd = EmpresaMoneda::where('empresa_id', $empresaId)
                ->where('es_principal', true)
                ->value('tasa_cambio');
        }

        return ((float) $tasaUsd > 0) ? (float) $tasaUsd : 1.0000;
    }

    /**
     * Datos iniciales para la vista del consultor de precios
     */
    public function datosIniciales(int $empresaId): array
    {
        $tasaUsd = $this->obtenerTasaActiva($empresaId);
        $productosIniciales = $this->buscar('', $empresaId, 24);

        return [
            'tasa_usd' => $tasaUsd,
            'fecha_consulta' => now()->format('d/m/Y h:i A'),
            'productos_iniciales' => $productosIniciales,
        ];
    }

    /**
     * Buscar productos, servicios y motos por término
     */
    public function buscar(string $termino, int $empresaId, int $limite = 30): Collection
    {
        $termino = trim($termino);
        $tasaUsd = $this->obtenerTasaActiva($empresaId);
        $resultados = collect();

        // 1. Búsqueda en Productos Físicos
        $queryProductos = Producto::with([
            'categoria:id,nombre',
            'codigosBarra:id,producto_id,codigo_barra,descripcion',
            'seriales' => fn ($q) => $q->where('estado', 'disponible'),
        ])
            ->where('empresa_id', $empresaId)
            ->where('estado', true);

        if ($termino !== '') {
            $queryProductos->where(function (Builder $q) use ($termino) {
                $q->where('codigo_interno', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%")
                    ->orWhereHas('codigosBarra', function (Builder $qb) use ($termino) {
                        $qb->where('codigo_barra', 'like', "%{$termino}%");
                    })
                    ->orWhereHas('seriales', function (Builder $qs) use ($termino) {
                        $qs->where('numero_serial', 'like', "%{$termino}%");
                    })
                    ->orWhereHas('categoria', function (Builder $qc) use ($termino) {
                        $qc->where('nombre', 'like', "%{$termino}%");
                    });
            });
        }

        $productos = $queryProductos->orderBy('nombre', 'asc')->limit($limite)->get();

        foreach ($productos as $p) {
            $detalBaseUsd = (float) $p->precio_detal_usd;
            if ($detalBaseUsd <= 0 && (float) $p->precio_detal_bs > 0 && $tasaUsd > 0) {
                $detalBaseUsd = round((float) $p->precio_detal_bs / $tasaUsd, 4);
            }

            $aplicaIva = (bool) $p->aplica_iva;
            $ivaPorcentaje = (float) ($p->iva_porcentaje ?? 16.00);
            $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;

            $detalConIvaUsd = round($detalBaseUsd * $factorIva, 2);
            $detalConIvaBs = round($detalConIvaUsd * $tasaUsd, 2);

            $codigos = $p->codigosBarra->pluck('codigo_barra')->filter()->values()->all();

            $resultados->push([
                'id' => $p->id,
                'tipo_item' => 'producto',
                'codigo_interno' => $p->codigo_interno ?: (string) $p->id,
                'nombre' => $p->nombre,
                'categoria' => $p->categoria?->nombre ?? 'General',
                'unidad_medida' => $p->unidad_medida ?? 'UND',
                'precio_usd_con_iva' => $detalConIvaUsd,
                'precio_bs_con_iva' => $detalConIvaBs,
                'tasa_aplicada' => $tasaUsd,
                'codigos_barra' => $codigos,
            ]);
        }

        // 2. Búsqueda en Servicios
        $queryServicios = Servicio::with('categoria:id,nombre')
            ->where('empresa_id', $empresaId)
            ->where('estado', true);

        if ($termino !== '') {
            $queryServicios->where(function (Builder $q) use ($termino) {
                $q->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('nombre', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%")
                    ->orWhereHas('categoria', function (Builder $qc) use ($termino) {
                        $qc->where('nombre', 'like', "%{$termino}%");
                    });
            });
        }

        $servicios = $queryServicios->orderBy('nombre', 'asc')->limit(10)->get();

        foreach ($servicios as $s) {
            $precioBaseUsd = (float) $s->precio_venta_usd;
            if ($precioBaseUsd <= 0 && (float) $s->precio_costo_bs > 0 && $tasaUsd > 0) {
                $precioBaseUsd = round((float) $s->precio_costo_bs / $tasaUsd, 4);
            }

            $aplicaIva = (bool) $s->aplica_iva;
            $ivaPorcentaje = (float) ($s->iva_porcentaje ?? 16.00);
            $factorIva = ($aplicaIva && $ivaPorcentaje > 0) ? (1 + ($ivaPorcentaje / 100)) : 1.00;

            $precioFinalUsd = round($precioBaseUsd * $factorIva, 2);
            $precioFinalBs = round($precioFinalUsd * $tasaUsd, 2);

            $resultados->push([
                'id' => $s->id,
                'tipo_item' => 'servicio',
                'codigo_interno' => $s->codigo ?: (string) $s->id,
                'nombre' => $s->nombre,
                'categoria' => $s->categoria?->nombre ?? 'Servicio',
                'unidad_medida' => 'SRV',
                'precio_usd_con_iva' => $precioFinalUsd,
                'precio_bs_con_iva' => $precioFinalBs,
                'tasa_aplicada' => $tasaUsd,
                'codigos_barra' => array_values(array_filter([(string) $s->codigo])),
            ]);
        }

        // 3. Búsqueda en Motos / Vehículos (si existen)
        $queryMotos = Moto::where('empresa_id', $empresaId)
            ->where('estado', 'disponible');

        if ($termino !== '') {
            $queryMotos->where(function (Builder $q) use ($termino) {
                $q->where('referencia', 'like', "%{$termino}%")
                    ->orWhere('marca', 'like', "%{$termino}%")
                    ->orWhere('modelo', 'like', "%{$termino}%")
                    ->orWhere('numero_niv', 'like', "%{$termino}%")
                    ->orWhere('numero_chasis', 'like', "%{$termino}%")
                    ->orWhere('numero_motor', 'like', "%{$termino}%")
                    ->orWhere('certificado_origen', 'like', "%{$termino}%")
                    ->orWhere('placa', 'like', "%{$termino}%");
            });
        }

        $motos = $queryMotos->orderBy('id', 'desc')->limit(10)->get();

        foreach ($motos as $m) {
            $detalConIvaUsd = (float) ($m->precio_detal_con_iva_usd > 0
                ? $m->precio_detal_con_iva_usd
                : round((float) $m->precio_detal_usd * 1.16, 2));
            $detalConIvaBs = round($detalConIvaUsd * $tasaUsd, 2);

            $codigos = array_values(array_filter([
                $m->referencia,
                $m->numero_niv,
                $m->numero_chasis,
                $m->numero_motor,
                $m->certificado_origen,
                $m->placa,
            ]));

            $resultados->push([
                'id' => $m->id,
                'tipo_item' => 'moto',
                'codigo_interno' => $m->referencia ?: $m->numero_niv,
                'nombre' => "{$m->marca} {$m->modelo} ({$m->anio}) - Color: {$m->color}".($m->referencia ? " [Ref: #{$m->referencia}]" : ''),
                'categoria' => 'Moto / Vehículo',
                'unidad_medida' => 'UND',
                'precio_usd_con_iva' => $detalConIvaUsd,
                'precio_bs_con_iva' => $detalConIvaBs,
                'tasa_aplicada' => $tasaUsd,
                'codigos_barra' => $codigos,
            ]);
        }

        // Si hay término exacto, priorizar la coincidencia exacta
        if ($termino !== '') {
            $termLower = mb_strtolower($termino);

            return $resultados->sortBy(function ($item) use ($termLower) {
                $codLower = mb_strtolower($item['codigo_interno']);
                $nomLower = mb_strtolower($item['nombre']);
                $barcodes = array_map('mb_strtolower', $item['codigos_barra']);

                if ($codLower === $termLower || in_array($termLower, $barcodes, true)) {
                    return 1;
                }
                if ($nomLower === $termLower) {
                    return 2;
                }
                if (str_starts_with($codLower, $termLower)) {
                    return 3;
                }
                if (str_starts_with($nomLower, $termLower)) {
                    return 4;
                }

                return 5;
            })->values();
        }

        return $resultados->values();
    }
}
