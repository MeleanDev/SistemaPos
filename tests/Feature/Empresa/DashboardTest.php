<?php

use App\Models\Empresa;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);

    $permisos = [
        'pos.acceso',
        'ventas.ver',
        'ventas.crear',
        'productos.ver',
        'productos.crear',
        'clientes.ver',
        'clientes.crear',
        'cajas.ver',
        'cajas.aperturar',
        'cajas.cerrar',
        'cxc.ver',
        'cxp.ver',
        'compras.recepcion',
        'reportes.ver',
    ];

    foreach ($permisos as $p) {
        Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
    }
});

test('superadmin puede ver la vista principal del dashboard con todas las métricas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000001-1',
        'nombre' => 'Dashboard Express',
        'razon_social' => 'Dashboard Express C.A.',
        'direccion' => 'Av. Central 100',
        'tasa_usd' => 50.00,
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000001', 'nombre' => 'Super', 'apellido' => 'Admin']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/panel-principal');

    $response->assertOk()
        ->assertViewIs('Sistema.dashboard')
        ->assertViewHas('dashboard')
        ->assertSee('Panel Principal')
        ->assertSee('Dashboard Express')
        ->assertSee('Terminal POS');
});

test('endpoint json /panel-principal/datos devuelve estructura completa y métricas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000002-2',
        'nombre' => 'Dashboard API Corp',
        'razon_social' => 'Dashboard API Corp C.A.',
        'direccion' => 'Av. Principal',
        'tasa_usd' => 50.00,
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/panel-principal/datos');

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'success',
            'data' => [
                'empresa',
                'usuario',
                'permisos',
                'kpis',
                'grafico_ventas',
                'caja_estado',
                'transacciones_recientes',
                'alertas_stock',
            ],
        ]);
});

test('operador con permisos limitados no ve ni recibe métricas financieras restringidas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000003-3',
        'nombre' => 'Restricted Shop',
        'razon_social' => 'Restricted Shop C.A.',
        'direccion' => 'Calle 5',
        'tasa_usd' => 50.00,
        'maneja_motos' => false,
        'estado' => true,
    ]);

    // Operador con SOLO acceso al POS y ventas.crear (sin ventas.ver ni cxc.ver ni cxp.ver)
    $operador = User::factory()->create(['name' => 'V-88000003', 'nombre' => 'Cajero', 'apellido' => 'Operativo']);
    $operador->assignRole('Operador');
    $operador->givePermissionTo(['pos.acceso', 'ventas.crear']);
    $operador->empresas()->attach($empresa->id);

    // 1. Verificación en API JSON
    $responseJson = $this->actingAs($operador)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/panel-principal/datos');

    $responseJson->assertOk();
    $data = $responseJson->json('data');

    expect($data['permisos']['ventas_ver'])->toBeFalse();
    expect($data['permisos']['cxc_ver'])->toBeFalse();
    expect($data['permisos']['cxp_ver'])->toBeFalse();
    expect($data['permisos']['pos_acceso'])->toBeTrue();
    expect($data['grafico_ventas'])->toBeNull();
    expect($data['kpis'])->not()->toHaveKey('ventas_hoy_usd');
    expect($data['kpis'])->not()->toHaveKey('cxc_pendiente_usd');

    // 2. Verificación en vista Blade
    $responseBlade = $this->actingAs($operador)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/panel-principal');

    $responseBlade->assertOk()
        ->assertSee('Terminal POS')
        ->assertDontSee('Ventas de los Últimos 7 Días')
        ->assertDontSee('Cuentas por Cobrar')
        ->assertDontSee('Cuentas por Pagar');
});
