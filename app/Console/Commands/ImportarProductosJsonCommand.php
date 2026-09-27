<?php

namespace App\Console\Commands;

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\ProductoCodigoBarra;
use App\Models\ProductoStockAlmacen;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportarProductosJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:importar-productos-json
                            {path=C:/Users/Kenne/Downloads/salida.json : Ruta absoluta del archivo JSON}
                            {--empresa=1 : ID de la Empresa destino}
                            {--limite= : Cantidad máxima de registros a importar (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa un catálogo masivo de productos desde un archivo JSON a la base de datos para pruebas';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path');
        $empresaId = (int) $this->option('empresa');
        $limite = $this->option('limite') ? (int) $this->option('limite') : null;

        $this->info("Iniciando importación desde: {$path}");

        if (! file_exists($path)) {
            $this->error("El archivo no existe en la ruta: {$path}");

            return self::FAILURE;
        }

        $empresa = Empresa::find($empresaId);
        if (! $empresa) {
            $this->error("No se encontró la empresa con ID: {$empresaId}");

            return self::FAILURE;
        }

        $monedaUsd = EmpresaMoneda::where('empresa_id', $empresaId)->where('codigo', 'USD')->first();
        $tasaUsd = $monedaUsd ? (float) $monedaUsd->tasa_cambio : 50.0000;
        if ($tasaUsd <= 0) {
            $tasaUsd = 50.0000;
        }

        $almacenPrincipal = Almacen::where('empresa_id', $empresaId)->orderBy('id')->first();
        if (! $almacenPrincipal) {
            $almacenPrincipal = Almacen::create([
                'empresa_id' => $empresaId,
                'codigo' => 'ALM-01',
                'nombre' => 'Almacén Principal',
                'es_principal' => true,
                'estado' => true,
            ]);
        }

        $this->info("Empresa: {$empresa->nombre} (ID: {$empresaId}) | Tasa USD: {$tasaUsd} | Almacén: {$almacenPrincipal->nombre} (ID: {$almacenPrincipal->id})");

        $rawJson = file_get_contents($path);
        $items = json_decode($rawJson, true);

        if (! is_array($items)) {
            $this->error('Error al decodificar el archivo JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        $totalItems = count($items);
        $this->info("Total de artículos encontrados en JSON: {$totalItems}");

        if ($limite && $limite > 0 && $limite < $totalItems) {
            $items = array_slice($items, 0, $limite);
            $this->warn("Se limitará la importación a los primeros {$limite} artículos.");
        }

        // Cachear o crear categorías por departamento
        $categoriaCache = [];
        $categoriaGeneral = Categoria::firstOrCreate([
            'empresa_id' => $empresaId,
            'codigo' => 'CAT-GEN',
        ], [
            'nombre' => 'General / Varios',
            'estado' => true,
        ]);

        $categoriaCache['default'] = $categoriaGeneral->id;

        $categoriasExistentes = Categoria::where('empresa_id', $empresaId)->get();
        foreach ($categoriasExistentes as $c) {
            $categoriaCache[$c->codigo] = $c->id;
        }

        $chunkSize = 500;
        $chunks = array_chunk($items, $chunkSize);
        $totalChunks = count($chunks);

        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        $insertados = 0;
        $actualizados = 0;
        $errores = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::transaction(function () use (
                $chunk,
                $empresaId,
                $tasaUsd,
                $almacenPrincipal,
                &$categoriaCache,
                &$insertados,
                &$actualizados,
                &$errores,
                $bar
            ) {
                foreach ($chunk as $it) {
                    try {
                        $codigo = trim((string) ($it['CODIGO'] ?? ''));
                        $nombre = trim((string) ($it['DESCRIP'] ?? ''));

                        if (empty($codigo) || empty($nombre)) {
                            $bar->advance();

                            continue;
                        }

                        // Resolver Categoría
                        $depto = trim((string) ($it['DEPTO'] ?? ''));
                        $categoriaId = $categoriaCache['default'];

                        if (! empty($depto)) {
                            $catCodigo = 'DEP-'.$depto;
                            if (isset($categoriaCache[$catCodigo])) {
                                $categoriaId = $categoriaCache[$catCodigo];
                            } else {
                                $nuevaCat = Categoria::create([
                                    'empresa_id' => $empresaId,
                                    'codigo' => $catCodigo,
                                    'nombre' => 'Departamento '.$depto,
                                    'estado' => true,
                                ]);
                                $categoriaCache[$catCodigo] = $nuevaCat->id;
                                $categoriaId = $nuevaCat->id;
                            }
                        }

                        // Calcular Precios y Costos
                        $costoUsd = (float) ($it['DCOSTO'] ?? 0);
                        $costoBs = (float) ($it['COSTO'] ?? 0);
                        $detalUsd = (float) ($it['DPVPDET1'] ?? 0);
                        $detalBs = (float) ($it['PVPDET1'] ?? 0);
                        $mayorUsd = (float) ($it['DPVPVEN1'] ?? 0);
                        $mayorBs = (float) ($it['PVPVEN1'] ?? 0);

                        if ($detalUsd <= 0 && $detalBs > 0) {
                            $detalUsd = round($detalBs / $tasaUsd, 4);
                        }
                        if ($detalUsd <= 0 && $costoUsd > 0) {
                            $detalUsd = round($costoUsd * 1.30, 4);
                        }
                        if ($detalUsd <= 0) {
                            $detalUsd = 1.0000;
                        }
                        if ($detalBs <= 0) {
                            $detalBs = round($detalUsd * $tasaUsd, 4);
                        }

                        if ($mayorUsd <= 0 && $mayorBs > 0) {
                            $mayorUsd = round($mayorBs / $tasaUsd, 4);
                        }
                        if ($mayorUsd <= 0) {
                            $mayorUsd = round($detalUsd * 0.90, 4);
                        }
                        if ($mayorBs <= 0) {
                            $mayorBs = round($mayorUsd * $tasaUsd, 4);
                        }

                        if ($costoUsd <= 0 && $costoBs > 0) {
                            $costoUsd = round($costoBs / $tasaUsd, 4);
                        }
                        if ($costoUsd <= 0) {
                            $costoUsd = round($detalUsd * 0.70, 4);
                        }
                        if ($costoBs <= 0) {
                            $costoBs = round($costoUsd * $tasaUsd, 4);
                        }

                        $ivaPorc = (float) ($it['IVA'] ?? 0);
                        $aplicaIva = ($ivaPorc > 0);
                        if (! $aplicaIva) {
                            $ivaPorc = 0.00;
                        }

                        $unidad = trim((string) ($it['MEDIDA'] ?? ''));
                        if (empty($unidad)) {
                            $unidad = 'UND';
                        }

                        $productoData = [
                            'empresa_id' => $empresaId,
                            'categoria_id' => $categoriaId,
                            'tipo' => 'producto',
                            'codigo_interno' => $codigo,
                            'nombre' => $nombre,
                            'descripcion' => trim((string) ($it['DESCOR'] ?? '')) ?: null,
                            'unidad_medida' => strtoupper(substr($unidad, 0, 10)),
                            'stock_minimo' => (float) ($it['MINIMO'] ?? 0),
                            'stock_maximo' => (float) ($it['MAXIMO'] ?? 0),
                            'precio_costo_usd' => $costoUsd,
                            'precio_costo_bs' => $costoBs,
                            'precio_detal_usd' => $detalUsd,
                            'precio_detal_bs' => $detalBs,
                            'precio_mayorista_usd' => $mayorUsd,
                            'precio_mayorista_bs' => $mayorBs,
                            'ultimo_margen_detal' => (float) ($it['UTD1'] ?? 0),
                            'ultimo_margen_mayorista' => (float) ($it['UTM1'] ?? 0),
                            'tasa_cambio' => $tasaUsd,
                            'aplica_iva' => $aplicaIva,
                            'iva_porcentaje' => $ivaPorc,
                            'aplica_igtf' => false,
                            'igtf_porcentaje' => 3.00,
                            'estado' => ($it['ESTADO'] ?? 'A') === 'A',
                        ];

                        $producto = Producto::where('empresa_id', $empresaId)
                            ->where('codigo_interno', $codigo)
                            ->first();

                        if ($producto) {
                            $producto->update($productoData);
                            $actualizados++;
                        } else {
                            $producto = Producto::create($productoData);
                            $insertados++;
                        }

                        // Stock en Almacén
                        $stockExistente = (float) ($it['EXIST_D1'] ?? 0);
                        ProductoStockAlmacen::updateOrCreate([
                            'producto_id' => $producto->id,
                            'almacen_id' => $almacenPrincipal->id,
                        ], [
                            'cantidad_actual' => max(0, $stockExistente),
                            'cantidad_reservada' => 0,
                            'ubicacion_pasillo' => trim((string) ($it['UBICACION'] ?? '')) ?: null,
                        ]);

                        // Código de Barras (si tiene)
                        $codbar = trim((string) ($it['CODBAR'] ?? ''));
                        if (! empty($codbar)) {
                            ProductoCodigoBarra::updateOrCreate([
                                'producto_id' => $producto->id,
                                'codigo_barra' => $codbar,
                            ], [
                                'empresa_id' => $empresaId,
                                'descripcion' => 'Principal',
                                'es_principal' => true,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        $errores++;
                    }

                    $bar->advance();
                }
            });
        }

        $bar->finish();
        $this->newLine(2);

        $this->info(' Importación completada con éxito:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Nuevos Productos Insertados', $insertados],
                ['Productos Existentes Actualizados', $actualizados],
                ['Errores u Omitidos', $errores],
                ['Total Procesados', $insertados + $actualizados + $errores],
            ]
        );

        return self::SUCCESS;
    }
}
