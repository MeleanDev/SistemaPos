<?php

use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Recepcion;
use App\Models\User;
use Spatie\Permission\Models\Permission;
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

    // Caso 2: Factura en VES (TC >= TV: Caso general)
    // Tasa Compra = 40 Bs/$, Tasa Venta = 38 Bs/$
    // Costo en Bs = 380 Bs. -> dividido entre TV (38) = 10 USD
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
    expect((float) $producto->precio_costo_bs)->toBe(380.0000); // 10 * 38
    expect((float) $producto->precio_detal_usd)->toBe(12.0000);
    expect((float) $producto->precio_detal_bs)->toBe(456.0000); // 12 * 38
});

test('recepcion en bolivares con tasa compra menor a tasa venta calcula costo con tasa compra y precio con tasa venta', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000003-3',
        'nombre' => 'Empresa Recepcion Tasas VES TC < TV',
        'razon_social' => 'Empresa Recepcion Tasas VES TC < TV C.A.',
        'direccion' => 'Calle 30',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-03',
        'nombre' => 'Almacén 3',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-66666666-6',
        'nombre' => 'Proveedor Express',
        'razon_social' => 'Proveedor Express C.A.',
        'direccion' => 'Norte',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-03',
        'nombre' => 'Snacks',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'SNACK-01',
        'nombre' => 'Papas Fritas',
        'unidad_medida' => 'UND',
        'aplica_iva' => false,
        'precio_costo_usd' => 1.00,
        'precio_costo_bs' => 38.00,
        'precio_detal_usd' => 1.20,
        'precio_detal_bs' => 48.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Caso Especial VES: TC < TV (TC = 38, TV = 40)
    // Costo en Bs = 380 Bs. -> dividido entre TC (38) = 10 USD
    // Margen detal = 20% -> Precio Detal USD = 10 * 1.20 = 12 USD
    // Precio Detal Bs = 12 * 40 = 480 Bs.
    $tasaCompra = 38.0000;
    $tasaVenta = 40.0000;
    $costoUsd = 10.0000;
    $precioDetalUsd = 12.0000;

    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-VES-003',
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
    expect((float) $producto->precio_costo_bs)->toBe(380.0000); // 10 * 38
    expect((float) $producto->precio_detal_usd)->toBe(12.0000);
    expect((float) $producto->precio_detal_bs)->toBe(480.0000); // 12 * 40
});

test('recepcion en dolares con tasa compra menor a tasa venta calcula costo en bs con tasa venta y precios directamente con tasa venta', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000004-4',
        'nombre' => 'Empresa Recepcion Tasas USD TC < TV',
        'razon_social' => 'Empresa Recepcion Tasas USD TC < TV C.A.',
        'direccion' => 'Calle 40',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-04',
        'nombre' => 'Almacén 4',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-55555555-4',
        'nombre' => 'Proveedor Import',
        'razon_social' => 'Proveedor Import C.A.',
        'direccion' => 'Puerto',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-04',
        'nombre' => 'Repuestos',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'REP-01',
        'nombre' => 'Filtro de Aceite',
        'unidad_medida' => 'UND',
        'aplica_iva' => false,
        'precio_costo_usd' => 5.00,
        'precio_costo_bs' => 200.00,
        'precio_detal_usd' => 7.00,
        'precio_detal_bs' => 280.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $tasaCompra = 38.0000;
    $tasaVenta = 40.0000;
    $costoUsd = 10.0000;
    $precioDetalUsd = 12.0000;

    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-USD-004',
        'moneda_documento' => 'USD',
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
    expect((float) $producto->precio_detal_bs)->toBe(480.0000); // 12 * 40
});

test('empresa con usar_formula_costo_tc_menor activa aplica formula costo por tasa compra entre tasa venta en recepcion usd', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000005-5',
        'nombre' => 'Empresa Formula Especial TC Menor',
        'razon_social' => 'Empresa Formula Especial TC Menor C.A.',
        'direccion' => 'Calle Especial',
        'maneja_motos' => false,
        'usar_formula_costo_tc_menor' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-05',
        'nombre' => 'Almacén Especial',
        'es_principal' => true,
        'estado' => true,
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-55555555-5',
        'nombre' => 'Proveedor Especial',
        'razon_social' => 'Proveedor Especial C.A.',
        'direccion' => 'Zona Comercial',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-05',
        'nombre' => 'Ferretería',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'HERR-01',
        'nombre' => 'Taladro Percutor',
        'unidad_medida' => 'UND',
        'aplica_iva' => false,
        'precio_costo_usd' => 5.00,
        'precio_costo_bs' => 200.00,
        'precio_detal_usd' => 7.00,
        'precio_detal_bs' => 280.00,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // TC = 38, TV = 40 (TC < TV)
    // Costo ingresado = 10 USD
    // Formula Especial: Costo USD = (10 * 38) / 40 = 9.5000 USD
    // Costo Bs = 9.5 * 40 = 380.0000 Bs (equivale a 10 * 38)
    // Margen detal = 20% -> Precio Detal USD = 9.5 * 1.20 = 11.4000 USD
    // Precio Detal Bs = 11.4 * 40 = 456.0000 Bs
    $tasaCompra = 38.0000;
    $tasaVenta = 40.0000;
    $costoUsdIngresado = 10.0000;
    $precioDetalUsdEsperado = 11.4000;

    $payload = [
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'tipo_documento' => 'factura',
        'numero_documento' => 'FACT-USD-ESP-005',
        'moneda_documento' => 'USD',
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
                'costo_unitario_usd' => $costoUsdIngresado,
                'descuento_porcentaje' => 0,
                'aplica_iva' => false,
                'margen_detal_porcentaje' => 20.00,
                'precio_detal_usd' => $precioDetalUsdEsperado,
                'margen_mayorista_porcentaje' => 10.00,
                'precio_mayorista_usd' => 10.4500,
            ],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('recepcion'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $producto->refresh();
    expect((float) $producto->precio_costo_usd)->toBe(9.5000);
    expect((float) $producto->precio_costo_bs)->toBe(380.0000); // 9.5 * 40 = 380
    expect((float) $producto->precio_detal_usd)->toBe(11.4000);
    expect((float) $producto->precio_detal_bs)->toBe(456.0000); // 11.4 * 40 = 456
});

test('solo superadmin puede modificar usar_formula_costo_tc_menor en configuracion de empresa', function () {
    $empresa = Empresa::create([
        'rif' => 'J-70000006-6',
        'nombre' => 'Empresa Permisos Config',
        'razon_social' => 'Empresa Permisos Config C.A.',
        'direccion' => 'Calle Permisos',
        'maneja_motos' => false,
        'usar_formula_costo_tc_menor' => false,
        'estado' => true,
    ]);

    $superAdmin = User::factory()->create(['estado' => true]);
    $superAdmin->syncRoles('SuperAdmin');
    $superAdmin->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'configuracion.editar', 'guard_name' => 'web']);
    $adminNormal = User::factory()->create(['estado' => true]);
    $adminNormal->syncRoles('Administrador');
    $adminNormal->givePermissionTo('configuracion.editar');
    $adminNormal->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // 1. SuperAdmin actualiza la opción a true
    $resSuper = $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/configuracion/empresa', [
            'rif' => 'J-70000006-6',
            'nombre' => 'Empresa Permisos Config',
            'razon_social' => 'Empresa Permisos Config C.A.',
            'direccion' => 'Calle Permisos',
            'usar_formula_costo_tc_menor' => 1,
        ]);

    $resSuper->assertStatus(200);
    $empresa->refresh();
    expect($empresa->usar_formula_costo_tc_menor)->toBeTrue();

    // 2. Usuario no SuperAdmin intenta desactivar la opción a false
    $resNormal = $this->actingAs($adminNormal)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/configuracion/empresa', [
            'rif' => 'J-70000006-6',
            'nombre' => 'Empresa Permisos Config',
            'razon_social' => 'Empresa Permisos Config C.A.',
            'direccion' => 'Calle Permisos',
            'usar_formula_costo_tc_menor' => 0,
        ]);

    $resNormal->assertStatus(200);
    $empresa->refresh();
    // Debe continuar en true porque solo SuperAdmin puede alterarlo
    expect($empresa->usar_formula_costo_tc_menor)->toBeTrue();
});
