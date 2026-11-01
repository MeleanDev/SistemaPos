<?php

namespace App\Service\Empresa;

use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConfiguracionEmpresaClass
{
    /**
     * Monedas secundarias operativas admitidas por el sistema bimonetario
     */
    private array $monedasPredeterminadas = [
        [
            'codigo' => 'USD',
            'nombre' => 'Dólar Estadounidense',
            'simbolo' => '$',
            'tasa_cambio' => 1.0000,
            'es_principal' => false,
            'estado' => true,
        ],
    ];

    /**
     * Obtener toda la configuración de la empresa activa
     */
    public function obtenerConfiguracion(int $empresaId): array
    {
        $empresa = Empresa::findOrFail($empresaId);

        // Asegurar que la empresa tenga sus monedas inicializadas
        $this->asegurarMonedasInicializadas($empresaId);

        $monedas = EmpresaMoneda::where('empresa_id', $empresaId)
            ->where('codigo', '!=', 'VES')
            ->orderBy('id', 'asc')
            ->get();

        return [
            'empresa' => $empresa,
            'monedas' => $monedas,
            'moneda_base' => config('pos.monedas.principal', [
                'codigo' => 'VES',
                'simbolo' => 'Bs.',
                'nombre' => 'Bolívares (VES)',
            ]),
        ];
    }

    /**
     * Actualizar datos generales de la empresa y logo
     */
    public function actualizarDatosEmpresa(array $datos, int $empresaId, ?UploadedFile $logo = null): Empresa
    {
        return DB::transaction(function () use ($datos, $empresaId, $logo) {
            $empresa = Empresa::findOrFail($empresaId);

            if ($logo) {
                // Eliminar logo anterior si existe
                if ($empresa->logo && Storage::disk('public')->exists($empresa->logo)) {
                    Storage::disk('public')->delete($empresa->logo);
                }
                $datos['logo'] = $logo->store('empresas', 'public');
            }

            if (array_key_exists('maneja_motos', $datos)) {
                $datos['maneja_motos'] = filter_var($datos['maneja_motos'], FILTER_VALIDATE_BOOLEAN);
            }
            if (array_key_exists('maneja_vendedores', $datos)) {
                $datos['maneja_vendedores'] = filter_var($datos['maneja_vendedores'], FILTER_VALIDATE_BOOLEAN);
            }

            $empresa->update($datos);

            return $empresa->fresh();
        });
    }

    /**
     * Actualizar tasas de cambio y estado de las monedas
     */
    public function actualizarTasasMonedas(array $monedas, int $empresaId): array
    {
        return DB::transaction(function () use ($monedas, $empresaId) {
            $ahora = now();

            foreach ($monedas as $item) {
                $codigo = $item['codigo'] ?? null;
                if (! $codigo || $codigo === 'VES') {
                    continue;
                }

                $moneda = EmpresaMoneda::where('empresa_id', $empresaId)
                    ->where('codigo', $codigo)
                    ->first();

                if ($moneda) {
                    $moneda->update([
                        'tasa_cambio' => $item['tasa_cambio'] ?? $moneda->tasa_cambio,
                        'es_principal' => false,
                        'estado' => filter_var($item['estado'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'ultima_actualizacion_tasa' => $ahora,
                    ]);
                }
            }

            return EmpresaMoneda::where('empresa_id', $empresaId)
                ->where('codigo', '!=', 'VES')
                ->get()
                ->toArray();
        });
    }

    /**
     * Asegurar que existan monedas inicializadas para la empresa
     */
    private function asegurarMonedasInicializadas(int $empresaId): void
    {
        $monedaVes = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'VES')->first();
        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();

        if ($monedaVes) {
            if ($monedaUsd && (float) $monedaUsd->tasa_cambio <= 1.0000 && (float) $monedaVes->tasa_cambio > 1.0000) {
                $monedaUsd->update([
                    'tasa_cambio' => $monedaVes->tasa_cambio,
                    'es_principal' => false,
                    'ultima_actualizacion_tasa' => $monedaVes->ultima_actualizacion_tasa ?? now(),
                ]);
            }
            $monedaVes->delete();
        }

        if ($monedaUsd && $monedaUsd->es_principal) {
            $monedaUsd->update(['es_principal' => false]);
        }

        EmpresaMoneda::where('empresa_id', $empresaId)
            ->whereIn('codigo', ['EUR', 'COP'])
            ->delete();

        foreach ($this->monedasPredeterminadas as $m) {
            $existe = EmpresaMoneda::where('empresa_id', $empresaId)
                ->where('codigo', $m['codigo'])
                ->exists();

            if (! $existe) {
                EmpresaMoneda::create([
                    'empresa_id' => $empresaId,
                    'codigo' => $m['codigo'],
                    'nombre' => $m['nombre'],
                    'simbolo' => $m['simbolo'],
                    'tasa_cambio' => $m['tasa_cambio'],
                    'es_principal' => false,
                    'estado' => $m['estado'],
                    'ultima_actualizacion_tasa' => now(),
                ]);
            }
        }
    }
}
