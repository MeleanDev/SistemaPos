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
     * Monedas predeterminadas sugeridas por el sistema
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
        [
            'codigo' => 'EUR',
            'nombre' => 'Euro Europeo',
            'simbolo' => '€',
            'tasa_cambio' => 1.0000,
            'es_principal' => false,
            'estado' => false,
        ],
        [
            'codigo' => 'COP',
            'nombre' => 'Peso Colombiano',
            'simbolo' => 'COP$',
            'tasa_cambio' => 0.0100,
            'es_principal' => false,
            'estado' => false,
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
            ->orderBy('es_principal', 'desc')
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
                if (! $codigo) {
                    continue;
                }

                $moneda = EmpresaMoneda::where('empresa_id', $empresaId)
                    ->where('codigo', $codigo)
                    ->first();

                if ($moneda) {
                    $moneda->update([
                        'tasa_cambio' => $item['tasa_cambio'] ?? $moneda->tasa_cambio,
                        'estado' => filter_var($item['estado'] ?? true, FILTER_VALIDATE_BOOLEAN),
                        'ultima_actualizacion_tasa' => $ahora,
                    ]);
                }
            }

            return EmpresaMoneda::where('empresa_id', $empresaId)->get()->toArray();
        });
    }

    /**
     * Asegurar que existan monedas inicializadas para la empresa
     */
    private function asegurarMonedasInicializadas(int $empresaId): void
    {
        $conteo = EmpresaMoneda::where('empresa_id', $empresaId)->count();

        if ($conteo === 0) {
            foreach ($this->monedasPredeterminadas as $m) {
                EmpresaMoneda::create([
                    'empresa_id' => $empresaId,
                    'codigo' => $m['codigo'],
                    'nombre' => $m['nombre'],
                    'simbolo' => $m['simbolo'],
                    'tasa_cambio' => $m['tasa_cambio'],
                    'es_principal' => $m['es_principal'],
                    'estado' => $m['estado'],
                    'ultima_actualizacion_tasa' => now(),
                ]);
            }
        }
    }
}
