<?php

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\Producto;
use App\Models\ProductoCodigoBarra;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('un producto puede tener multiples codigos qr o de barra', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000001-1',
        'nombre' => 'Empresa QR Test',
        'razon_social' => 'Empresa QR Test C.A.',
        'direccion' => 'Calle Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-01',
        'nombre' => 'Alimentos',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $payload = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'HARINA-001',
        'nombre' => 'Harina Pan 1kg',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'precio_costo_usd' => 1.00,
        'precio_detal_usd' => 1.50,
        'codigos_barra' => [
            ['codigo' => '7591234567890', 'descripcion' => 'Código EAN'],
            ['codigo' => 'QR-HARINA-PAN-01', 'descripcion' => 'QR Empaque'],
            ['codigo' => 'QR-HARINA-PAN-BULTO', 'descripcion' => 'QR Bulto x20'],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $producto = Producto::where('empresa_id', $empresa->id)->where('codigo_interno', 'HARINA-001')->first();
    expect($producto)->not->toBeNull();

    $this->assertDatabaseHas('producto_codigos_barra', [
        'producto_id' => $producto->id,
        'empresa_id' => $empresa->id,
        'codigo_barra' => '7591234567890',
    ]);

    $this->assertDatabaseHas('producto_codigos_barra', [
        'producto_id' => $producto->id,
        'empresa_id' => $empresa->id,
        'codigo_barra' => 'QR-HARINA-PAN-01',
    ]);

    $this->assertDatabaseHas('producto_codigos_barra', [
        'producto_id' => $producto->id,
        'empresa_id' => $empresa->id,
        'codigo_barra' => 'QR-HARINA-PAN-BULTO',
    ]);

    expect($producto->codigosBarra()->count())->toBe(3);
});

test('dos productos en la misma empresa no pueden compartir el mismo codigo qr o barra', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000002-2',
        'nombre' => 'Empresa QR Unique Test',
        'razon_social' => 'Empresa QR Unique Test C.A.',
        'direccion' => 'Av. Bolívar',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-02',
        'nombre' => 'Bebidas',
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Producto 1 con QR-BEBIDA-01
    $prod1 = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'REFRESCO-001',
        'nombre' => 'Coca Cola 2L',
        'unidad_medida' => 'UND',
        'tipo' => 'producto',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    ProductoCodigoBarra::create([
        'producto_id' => $prod1->id,
        'empresa_id' => $empresa->id,
        'codigo_barra' => 'QR-BEBIDA-01',
        'descripcion' => 'QR Botella',
        'es_principal' => false,
    ]);

    // Intentar crear Producto 2 con el mismo QR-BEBIDA-01
    $payload = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PEPSI-001',
        'nombre' => 'Pepsi 2L',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'codigos_barra' => [
            ['codigo' => 'QR-BEBIDA-01', 'descripcion' => 'QR Duplicado'],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['codigos_barra.0.codigo']);
});

test('dos empresas distintas pueden tener el mismo codigo qr sin conflicto', function () {
    $empresaA = Empresa::create([
        'rif' => 'J-10000003-3',
        'nombre' => 'Empresa A',
        'razon_social' => 'Empresa A C.A.',
        'direccion' => 'Sector Norte',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $empresaB = Empresa::create([
        'rif' => 'J-10000004-4',
        'nombre' => 'Empresa B',
        'razon_social' => 'Empresa B C.A.',
        'direccion' => 'Sector Sur',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $catA = Categoria::create(['empresa_id' => $empresaA->id, 'codigo' => 'CAT-A', 'nombre' => 'Cat A', 'estado' => true]);
    $catB = Categoria::create(['empresa_id' => $empresaB->id, 'codigo' => 'CAT-B', 'nombre' => 'Cat B', 'estado' => true]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresaA->id, ['es_predeterminada' => true, 'estado' => true]);
    $user->empresas()->attach($empresaB->id, ['es_predeterminada' => false, 'estado' => true]);

    // Crear en Empresa A con QR "QR-UNIVERSAL-100"
    $prodA = Producto::create([
        'empresa_id' => $empresaA->id,
        'categoria_id' => $catA->id,
        'codigo_interno' => 'PROD-A',
        'nombre' => 'Producto Empresa A',
        'unidad_medida' => 'UND',
        'tipo' => 'producto',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);
    ProductoCodigoBarra::create([
        'producto_id' => $prodA->id,
        'empresa_id' => $empresaA->id,
        'codigo_barra' => 'QR-UNIVERSAL-100',
        'descripcion' => 'QR General',
        'es_principal' => false,
    ]);

    // Crear en Empresa B con el mismo QR "QR-UNIVERSAL-100"
    $payloadB = [
        'tipo' => 'producto',
        'categoria_id' => $catB->id,
        'codigo_interno' => 'PROD-B',
        'nombre' => 'Producto Empresa B',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'codigos_barra' => [
            ['codigo' => 'QR-UNIVERSAL-100', 'descripcion' => 'QR General'],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresaB->id])
        ->postJson(route('producto'), $payloadB);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('producto_codigos_barra', [
        'empresa_id' => $empresaB->id,
        'codigo_barra' => 'QR-UNIVERSAL-100',
    ]);
});

test('rechaza codigos duplicados dentro del mismo array al crear o editar', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000005-5',
        'nombre' => 'Empresa Distinct Test',
        'razon_social' => 'Empresa Distinct Test C.A.',
        'direccion' => 'Av. Central',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create(['empresa_id' => $empresa->id, 'codigo' => 'CAT-DIST', 'nombre' => 'Cat Distinct', 'estado' => true]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $payload = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-DIST-01',
        'nombre' => 'Producto Con QRs Repetidos En Array',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'codigos_barra' => [
            ['codigo' => 'QR-REPETIDO-MISMO-PROD', 'descripcion' => 'QR 1'],
            ['codigo' => 'QR-REPETIDO-MISMO-PROD', 'descripcion' => 'QR 2'],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['codigos_barra.0.codigo', 'codigos_barra.1.codigo']);
});

test('al actualizar un producto se puede mantener sus propios codigos qr sin error de duplicado', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000006-6',
        'nombre' => 'Empresa Update QR Test',
        'razon_social' => 'Empresa Update QR Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create(['empresa_id' => $empresa->id, 'codigo' => 'CAT-UPD', 'nombre' => 'Cat Update', 'estado' => true]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $prod = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-UPD-01',
        'nombre' => 'Producto Para Actualizar',
        'unidad_medida' => 'UND',
        'tipo' => 'producto',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    ProductoCodigoBarra::create([
        'producto_id' => $prod->id,
        'empresa_id' => $empresa->id,
        'codigo_barra' => 'QR-MIO-101',
        'descripcion' => 'Mi QR',
        'es_principal' => false,
    ]);

    $payload = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-UPD-01',
        'nombre' => 'Producto Para Actualizar Editado',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'codigos_barra' => [
            ['codigo' => 'QR-MIO-101', 'descripcion' => 'Mi QR Actualizado'],
            ['codigo' => 'QR-MIO-102', 'descripcion' => 'Segundo QR Nuevo'],
        ],
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->putJson("/productos/actualizar/{$prod->id}", $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($prod->codigosBarra()->count())->toBe(2);
});

test('al crear un producto sin codigo_interno se le asigna un correlativo numerico autoincremental', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000007-7',
        'nombre' => 'Empresa SKU Test',
        'razon_social' => 'Empresa SKU Test C.A.',
        'direccion' => 'Av. SKU',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create(['empresa_id' => $empresa->id, 'codigo' => 'CAT-SKU', 'nombre' => 'Cat SKU', 'estado' => true]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Crear Producto 1 sin codigo_interno
    $payload1 = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'nombre' => 'Producto 1 Auto SKU',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
    ];

    $response1 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload1);

    $response1->assertStatus(200);
    $prod1 = Producto::where('empresa_id', $empresa->id)->where('nombre', 'Producto 1 Auto SKU')->first();
    expect($prod1->codigo_interno)->toBe('1');

    // Crear Producto 2 sin codigo_interno
    $payload2 = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'nombre' => 'Producto 2 Auto SKU',
        'unidad_medida' => 'UND',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
    ];

    $response2 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload2);

    $response2->assertStatus(200);
    $prod2 = Producto::where('empresa_id', $empresa->id)->where('nombre', 'Producto 2 Auto SKU')->first();
    expect($prod2->codigo_interno)->toBe('2');
});

test('al guardar o editar un producto se persisten precios, costos y margenes sin resetear a por recepcion', function () {
    $empresa = Empresa::create([
        'rif' => 'J-10000008-8',
        'nombre' => 'Empresa Precios Test',
        'razon_social' => 'Empresa Precios Test C.A.',
        'direccion' => 'Av. Precios',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $categoria = Categoria::create(['empresa_id' => $empresa->id, 'codigo' => 'CAT-PR', 'nombre' => 'Cat Precios', 'estado' => true]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $payload = [
        'tipo' => 'producto',
        'categoria_id' => $categoria->id,
        'nombre' => 'Aceite Motor 20W50',
        'unidad_medida' => 'L',
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'precio_costo_usd' => 5.00,
        'ultimo_margen_detal' => 40.00,
        'precio_detal_usd' => 7.00,
        'ultimo_margen_mayorista' => 20.00,
        'precio_mayorista_usd' => 6.00,
        'stock_minimo' => 10,
        'stock_maximo' => 100,
    ];

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson(route('producto'), $payload);

    $response->assertStatus(200);
    $prod = Producto::where('empresa_id', $empresa->id)->where('nombre', 'Aceite Motor 20W50')->first();
    expect((float) $prod->precio_costo_usd)->toBe(5.0);
    expect((float) $prod->precio_detal_usd)->toBe(7.0);
    expect((float) $prod->precio_mayorista_usd)->toBe(6.0);
    expect((float) $prod->ultimo_margen_detal)->toBe(40.0);
    expect((float) $prod->ultimo_margen_mayorista)->toBe(20.0);

    // Consulta de detalle devuelve los movimientos de kardex
    $detalleResponse = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/productos/{$prod->id}");

    $detalleResponse->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'nombre',
                'precio_costo_usd',
                'precio_detal_usd',
                'precio_mayorista_usd',
                'movimientos_kardex',
            ],
        ]);
});
