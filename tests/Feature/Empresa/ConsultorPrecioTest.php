<?php

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Producto;
use App\Models\ProductoCodigoBarra;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'consultor.ver', 'guard_name' => 'web']);
});

test('puede acceder a la vista del consultor de precios y cargar datos iniciales', function () {
    $empresa = Empresa::create([
        'rif' => 'J-31000001-1',
        'nombre' => 'Empresa Consultor Precios',
        'razon_social' => 'Empresa Consultor Precios C.A.',
        'direccion' => 'Av Principal',
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'USD',
        'nombre' => 'Dólares Americanos',
        'simbolo' => '$',
        'tasa_cambio' => 50.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $responseView = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get(route('consultor_precios'));

    $responseView->assertStatus(200)
        ->assertSee('Consultor de Precios');

    $responseDatos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/datos');

    $responseDatos->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'tasa_usd' => 50.0000,
            ],
        ]);
});

test('puede buscar producto por sku y obtener precio con iva en usd y bs', function () {
    $empresa = Empresa::create([
        'rif' => 'J-31000002-1',
        'nombre' => 'Empresa Busqueda Consultor',
        'razon_social' => 'Empresa Busqueda Consultor C.A.',
        'direccion' => 'Av Bolivar',
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'USD',
        'nombre' => 'Dólares Americanos',
        'simbolo' => '$',
        'tasa_cambio' => 60.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-01',
        'nombre' => 'Mueblería',
        'estado' => true,
    ]);

    // Producto con IVA (16%): base 100 USD -> Con IVA: 116.00 USD -> En Bs: 116 * 60 = 6960.00 Bs
    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'CAMA-MAT-01',
        'nombre' => 'Cama Matrimonial King',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 50.00,
        'precio_detal_usd' => 100.00,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    ProductoCodigoBarra::create([
        'empresa_id' => $empresa->id,
        'producto_id' => $producto->id,
        'codigo_barra' => '7599988776655',
        'descripcion' => 'Código de barra principal',
    ]);

    $user = User::factory()->create(['estado' => true]);
    $user->syncRoles('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    // Búsqueda por SKU
    $responseSku = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/buscar?termino=CAMA-MAT-01');

    $responseSku->assertStatus(200)
        ->assertJson([
            'success' => true,
        ]);

    $data = $responseSku->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['codigo_interno'])->toBe('CAMA-MAT-01')
        ->and($data[0]['nombre'])->toBe('Cama Matrimonial King')
        ->and($data[0]['precio_usd_con_iva'])->toEqual(116.0)
        ->and($data[0]['precio_bs_con_iva'])->toEqual(6960.0);

    // Búsqueda por Código de Barra
    $responseBarcode = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/buscar?termino=7599988776655');

    $responseBarcode->assertStatus(200);
    $dataBarcode = $responseBarcode->json('data');
    expect($dataBarcode)->toHaveCount(1)
        ->and($dataBarcode[0]['codigo_interno'])->toBe('CAMA-MAT-01');

    // Búsqueda general vacía (retorna catálogo)
    $responseEmpty = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/buscar?termino=');

    $responseEmpty->assertStatus(200);
    expect($responseEmpty->json('data'))->toHaveCount(1);
});

test('usuario sin permiso consultor.ver recibe 403 en todas las rutas del consultor', function () {
    $empresa = Empresa::create([
        'rif' => 'J-31000003-1',
        'nombre' => 'Empresa Sin Permiso Consultor',
        'razon_social' => 'Empresa Sin Permiso C.A.',
        'direccion' => 'Av Principal',
        'estado' => true,
    ]);

    $userSinPermiso = User::factory()->create(['estado' => true]);
    $userSinPermiso->syncRoles('Operador');
    $userSinPermiso->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $this->actingAs($userSinPermiso)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get(route('consultor_precios'))
        ->assertForbidden();

    $this->actingAs($userSinPermiso)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/datos')
        ->assertForbidden();

    $this->actingAs($userSinPermiso)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/buscar?termino=')
        ->assertForbidden();
});

test('consultor de precios oculta costos de compra a usuarios que consultan', function () {
    $empresa = Empresa::create([
        'rif' => 'J-31000004-1',
        'nombre' => 'Empresa Ocultar Costos',
        'razon_social' => 'Empresa Ocultar Costos C.A.',
        'direccion' => 'Av Principal',
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'USD',
        'nombre' => 'Dólares',
        'simbolo' => '$',
        'tasa_cambio' => 50.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-COSTOS',
        'nombre' => 'Categoría Costos',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-COSTO-01',
        'nombre' => 'Producto con Costo Secreto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 25.50,
        'precio_costo_bs' => 1275.00,
        'precio_detal_usd' => 50.00,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'aplica_igtf' => false,
        'estado' => true,
    ]);

    $userConsultor = User::factory()->create(['estado' => true]);
    $userConsultor->syncRoles('Operador');
    $userConsultor->givePermissionTo('consultor.ver');
    $userConsultor->empresas()->attach($empresa->id, ['es_predeterminada' => true, 'estado' => true]);

    $response = $this->actingAs($userConsultor)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/consultor-precios/buscar?termino=PROD-COSTO-01');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $item = $response->json('data.0');
    expect($item)->not()->toBeNull()
        ->and($item)->not()->toHaveKey('precio_costo_usd')
        ->and($item)->not()->toHaveKey('precio_costo_bs')
        ->and($item)->not()->toHaveKey('costo')
        ->and($item['precio_usd_con_iva'])->toEqual(58.0);
});
