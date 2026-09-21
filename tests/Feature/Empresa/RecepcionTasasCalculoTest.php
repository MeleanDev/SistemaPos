<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Recepcion;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('recepcion en dolares calcula precios aplicando formula con tasa compra y tasa venta', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000001-1',
        'nombre' => 'Empresa Recepcion Tasas USD',
        'razon_social' => 'Empresa Recepcion Tasas USD C.A.',
        'direccion' => 'Calle 10',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'es_principal' => false,
        'tasa_cambio' => 36.0000,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-01',
        'nombre' => 'Almacén Central',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-88888888-8',
        'nombre' => 'Distribuidora Mayorista',
        'razon_social' => 'Distribuidora Mayorista C.A.',
        'direccion' => 'Zona Industrial',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-01',
        'nombre' => 'Víveres',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'ARROZ-01',
        'nombre' => 'Arroz Blanco 1kg',
        'unidad_medida' => 'UND',
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'precio_costo_usd' => 1.0000,
        'precio_costo_bs' => 36.0000,
        'precio_detal_usd' => 1.3000,
        'precio_detal_bs' => 46.8000,
        'precio_mayorista_usd' => 1.1500,
        'precio_mayorista_bs' => 41.4000,
        'ultimo_margen_detal' => 30.00,
        'ultimo_margen_mayorista' => 15.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Caso 1: Factura en USD
    // Costo = 10 USD
    // Margen detal = 30% -> Base = 13 USD
    // Tasa Compra = 40 Bs / Tasa Venta = 36 Bs
    // Precio Detal USD esperado = (10 * 1.30 * 40) / 36 = 14.4444 USD
    // Precio Detal BS esperado = 14.4444 * 36 = 520.00 BS
    $tasaCompra = 40.0000;
    $tasaVenta = 36.0000;
    $costoUsd = 10.0000;
    $margenDetal = 30.00;
    $precioDetalUsdCalculado = round(($costoUsd * (1 + $margenDetal / 100) * $tasaCompra) / $tasaVenta, 4);

    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-USD-001',
        'moneda_documento' => 'USD',
        'fecha_emision' => now()->toDateString(),
        'fecha_recepcion' => now()->toDateString(),
        'condicion_pago' => 'contado',
        'tasa_compra' => $tasaCompra,
        'tasa_venta' => $tasaVenta,
        'tasa_cambio' => $tasaVenta,
        'monto_bruto_usd' => 100.00,
        'detalles' => [
            [
                'producto_id' => $producto->id,
                'almacen_id' => $almacen->id,
                'cantidad' => 10,
                'bultos' => 1,
                'unidades_por_bulto' => 10,
                'costo_bulto_usd' => 100.00,
                'costo_unitario_usd' => $costoUsd,
                'descuento_porcentaje' => 0,
                'aplica_iva' => false,
                'iva_porcentaje' => 0,
                'margen_detal_porcentaje' => $margenDetal,
                'precio_detal_usd' => $precioDetalUsdCalculado,
                'margen_mayorista_porcentaje' => 15.00,
                'precio_mayorista_usd' => round(($costoUsd * 1.15 * $tasaCompra) / $tasaVenta, 4),
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $recepcion = Recepcion::where('empresa_id', $empresa->id)->where('numero_documento', 'FACT-USD-001')->first();
    expect($recepcion)->not->toBeNull();
    expect((float) $recepcion->tasa_compra)->toBe(40.0000);
    expect((float) $recepcion->tasa_venta)->toBe(36.0000);

    $producto->refresh();
    expect((float) $producto->precio_costo_usd)->toBe(10.0000);
    expect((float) $producto->precio_costo_bs)->toBe(400.0000); // 10 * 40
    expect((float) $producto->precio_detal_usd)->toBe($precioDetalUsdCalculado);
    expect((float) $producto->precio_detal_bs)->toBe(round($precioDetalUsdCalculado * $tasaVenta, 4));
});

test('recepcion en bolivares convierte costo y calcula precios con tasas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000002-2',
        'nombre' => 'Empresa Recepcion Tasas VES',
        'razon_social' => 'Empresa Recepcion Tasas VES C.A.',
        'direccion' => 'Calle 20',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-02',
        'nombre' => 'Almacén 2',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-77777777-7',
        'nombre' => 'Distribuidora Local',
        'razon_social' => 'Distribuidora Local C.A.',
        'direccion' => 'Centro',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-02',
        'nombre' => 'Bebidas',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'JUGO-01',
        'nombre' => 'Jugo de Naranja 1L',
        'unidad_medida' => 'UND',
        'aplica_iva' => false,
        'precio_costo_usd' => 0.50,
        'precio_costo_bs' => 20.00,
        'precio_detal_usd' => 0.70,
        'precio_detal_bs' => 28.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Caso 2: Factura en VES
    // Costo en Bs = 400 Bs.
    // Tasa Compra = 40 Bs/$, Tasa Venta = 38 Bs/$
    // Costo USD = 400 / 40 = 10 USD
    // Margen detal = 20% -> Precio Detal USD = 10 * 1.20 = 12 USD
    // Precio Detal Bs = 12 * 38 = 456 Bs.
    $tasaCompra = 40.0000;
    $tasaVenta = 38.0000;
    $costoUsd = 10.0000;
    $precioDetalUsd = 12.0000;

    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-VES-002',
        'moneda_documento' => 'VES',
        'fecha_emision' => now()->toDateString(),
        'fecha_recepcion' => now()->toDateString(),
        'condicion_pago' => 'contado',
        'tasa_compra' => $tasaCompra,
        'tasa_venta' => $tasaVenta,
        'tasa_cambio' => $tasaVenta,
        'monto_bruto_usd' => 50.00,
        'detalles' => [
            [
                'producto_id' => $producto->id,
                'almacen_id' => $almacen->id,
                'cantidad' => 5,
                'bultos' => 1,
                'unidades_por_bulto' => 5,
                'costo_bulto_usd' => 50.00,
                'costo_unitario_usd' => $costoUsd,
                'descuento_porcentaje' => 0,
                'aplica_iva' => false,
                'margen_detal_porcentaje' => 20.00,
                'precio_detal_usd' => $precioDetalUsd,
                'margen_mayorista_porcentaje' => 10.00,
                'precio_mayorista_usd' => 11.0000,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $producto->refresh();
    expect((float) $producto->precio_costo_usd)->toBe(10.0000);
    expect((float) $producto->precio_costo_bs)->toBe(400.0000); // 10 * 40
    expect((float) $producto->precio_detal_usd)->toBe(12.0000);
    expect((float) $producto->precio_detal_bs)->toBe(456.0000); // 12 * 38
});
