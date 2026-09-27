<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Kardex;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-313131311',
        'nombre' => 'Kardex Corp',
        'razon_social' => 'Kardex Corp C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $this->user = User::create([
        'name' => 'V-31313131',
        'nombre' => 'Admin',
        'apellido' => 'Kardex',
        'email' => 'admin@kardex.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->user->assignRole('Admin');
    $this->user->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->almacenOrigen = Almacen::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'ALM-01',
        'nombre' => 'Almacén Principal',
        'direccion' => 'Planta Baja',
        'estado' => true,
    ]);

    $this->almacenDestino = Almacen::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'ALM-02',
        'nombre' => 'Almacén Secundario',
        'direccion' => 'Piso 1',
        'estado' => true,
    ]);

    $this->categoria = Categoria::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'CAT-01',
        'nombre' => 'Repuestos y Partes',
        'estado' => true,
    ]);

    $this->producto = Producto::create([
        'empresa_id' => $this->empresa->id,
        'categoria_id' => $this->categoria->id,
        'codigo_interno' => 'REP-001',
        'nombre' => 'Batería 12V 7Ah',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 20.0000,
        'precio_costo_bs' => 800.0000,
        'precio_detal_usd' => 30.0000,
        'precio_detal_bs' => 1200.0000,
        'precio_mayorista_usd' => 25.0000,
        'precio_mayorista_bs' => 1000.0000,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'estado' => true,
    ]);

    // Asignar stock inicial de 50 en almacén origen y 10 en almacén destino
    ProductoStockAlmacen::create([
        'producto_id' => $this->producto->id,
        'almacen_id' => $this->almacenOrigen->id,
        'cantidad_actual' => 50.000,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $this->producto->id,
        'almacen_id' => $this->almacenDestino->id,
        'cantidad_actual' => 10.000,
    ]);
});

test('kardex index view loads correctly for authorized user', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get('/kardex');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.kardex');
});

test('kardex lista and catalogos endpoints return json data', function () {
    $responseLista = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->getJson('/kardex/lista');

    $responseLista->assertOk()
        ->assertJsonStructure(['data', 'recordsTotal']);

    $responseCatalogos = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->getJson('/kardex/catalogos');

    $responseCatalogos->assertOk()
        ->assertJsonStructure(['success', 'data' => ['almacenes', 'productos', 'tasa_usd']]);
});

test('positive inventory adjustment increases stock and creates kardex record', function () {
    $payload = [
        'almacen_id' => $this->almacenOrigen->id,
        'tipo_ajuste' => 'entrada',
        'motivo' => 'Sobrante de conteo físico mensual',
        'fecha' => '2026-09-26',
        'detalles' => [
            [
                'producto_id' => $this->producto->id,
                'cantidad' => 15.000,
                'costo_unitario_usd' => 20.0000,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/kardex/ajuste', $payload);

    $response->assertOk()
        ->assertJson(['success' => true]);

    // Verificar que el stock subió de 50 a 65
    $stockAlm = ProductoStockAlmacen::where('producto_id', $this->producto->id)
        ->where('almacen_id', $this->almacenOrigen->id)
        ->first();
    expect((float) $stockAlm->cantidad_actual)->toEqual(65.000);

    // Verificar registro en Kardex
    $kardex = Kardex::where('empresa_id', $this->empresa->id)
        ->where('producto_id', $this->producto->id)
        ->where('tipo_movimiento', 'ajuste_positivo')
        ->first();
    expect($kardex)->not->toBeNull()
        ->and((float) $kardex->cantidad)->toEqual(15.000)
        ->and((float) $kardex->stock_anterior)->toEqual(50.000)
        ->and((float) $kardex->stock_nuevo)->toEqual(65.000);
});

test('negative inventory adjustment decreases stock and validates sufficient stock', function () {
    // 1. Ajuste válido
    $payloadValido = [
        'almacen_id' => $this->almacenOrigen->id,
        'tipo_ajuste' => 'salida',
        'motivo' => 'Merma por rotura en depósito',
        'fecha' => '2026-09-26',
        'detalles' => [
            [
                'producto_id' => $this->producto->id,
                'cantidad' => 10.000,
                'costo_unitario_usd' => 20.0000,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/kardex/ajuste', $payloadValido);

    $response->assertOk()
        ->assertJson(['success' => true]);

    $stockAlm = ProductoStockAlmacen::where('producto_id', $this->producto->id)
        ->where('almacen_id', $this->almacenOrigen->id)
        ->first();
    expect((float) $stockAlm->cantidad_actual)->toEqual(40.000);

    // 2. Ajuste con stock insuficiente (solicita 100 teniendo 40)
    $payloadInvalido = [
        'almacen_id' => $this->almacenOrigen->id,
        'tipo_ajuste' => 'salida',
        'motivo' => 'Ajuste excesivo',
        'fecha' => '2026-09-26',
        'detalles' => [
            [
                'producto_id' => $this->producto->id,
                'cantidad' => 100.000,
                'costo_unitario_usd' => 20.0000,
            ],
        ],
    ];

    $responseError = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/kardex/ajuste', $payloadInvalido);

    $responseError->assertStatus(422)
        ->assertJsonValidationErrors(['detalles']);
});

test('warehouse transfer transfers stock from origin to destination and creates twin kardex records', function () {
    $payloadTraslado = [
        'almacen_origen_id' => $this->almacenOrigen->id,
        'almacen_destino_id' => $this->almacenDestino->id,
        'motivo' => 'Reabastecimiento de sucursal',
        'fecha' => '2026-09-26',
        'detalles' => [
            [
                'producto_id' => $this->producto->id,
                'cantidad' => 20.000,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/kardex/traslado', $payloadTraslado);

    $response->assertOk()
        ->assertJson(['success' => true]);

    // Verificar stock: Origen 50 -> 30, Destino 10 -> 30
    $stockOrigen = ProductoStockAlmacen::where('producto_id', $this->producto->id)
        ->where('almacen_id', $this->almacenOrigen->id)
        ->first();
    expect((float) $stockOrigen->cantidad_actual)->toEqual(30.000);

    $stockDestino = ProductoStockAlmacen::where('producto_id', $this->producto->id)
        ->where('almacen_id', $this->almacenDestino->id)
        ->first();
    expect((float) $stockDestino->cantidad_actual)->toEqual(30.000);

    // Verificar dos asientos de kardex: traslado_salida y traslado_entrada
    $kardexSalida = Kardex::where('empresa_id', $this->empresa->id)
        ->where('almacen_id', $this->almacenOrigen->id)
        ->where('tipo_movimiento', 'traslado_salida')
        ->first();
    expect($kardexSalida)->not->toBeNull()
        ->and((float) $kardexSalida->cantidad)->toEqual(20.000)
        ->and((float) $kardexSalida->stock_anterior)->toEqual(50.000)
        ->and((float) $kardexSalida->stock_nuevo)->toEqual(30.000);

    $kardexEntrada = Kardex::where('empresa_id', $this->empresa->id)
        ->where('almacen_id', $this->almacenDestino->id)
        ->where('tipo_movimiento', 'traslado_entrada')
        ->first();
    expect($kardexEntrada)->not->toBeNull()
        ->and((float) $kardexEntrada->cantidad)->toEqual(20.000)
        ->and((float) $kardexEntrada->stock_anterior)->toEqual(10.000)
        ->and((float) $kardexEntrada->stock_nuevo)->toEqual(30.000);
});

test('warehouse transfer fails when origin and destination are the same', function () {
    $payloadInvalido = [
        'almacen_origen_id' => $this->almacenOrigen->id,
        'almacen_destino_id' => $this->almacenOrigen->id,
        'motivo' => 'Traslado al mismo almacén',
        'fecha' => '2026-09-26',
        'detalles' => [
            [
                'producto_id' => $this->producto->id,
                'cantidad' => 5.000,
            ],
        ],
    ];

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/kardex/traslado', $payloadInvalido);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['almacen_destino_id']);
});

test('printable voucher view loads correctly', function () {
    $movimiento = MovimientoInventario::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'TRASF-00001',
        'tipo' => 'traslado',
        'almacen_origen_id' => $this->almacenOrigen->id,
        'almacen_destino_id' => $this->almacenDestino->id,
        'user_id' => $this->user->id,
        'motivo' => 'Guía de Despacho Interno',
        'fecha' => '2026-09-26',
        'total_items' => 1,
        'total_unidades' => 10.000,
    ]);

    $response = $this->actingAs($this->user)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get("/kardex/comprobante/{$movimiento->id}");

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.kardex-comprobante');
});
