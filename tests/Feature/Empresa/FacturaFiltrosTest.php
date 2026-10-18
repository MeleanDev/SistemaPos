<?php

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\User;
use App\Models\Venta;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
});

test('facturas lista filtra correctamente por rango de fechas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000001-1',
        'nombre' => 'Empresa Factura Filtros Test',
        'razon_social' => 'Empresa Factura Filtros Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $cliente = Cliente::create([
        'nombre' => 'Carlos',
        'apellido' => 'Perez',
        'cedula' => 'V-88111222',
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-'.uniqid(),
        'nombre' => 'Almacen Filtros',
        'estado' => true,
    ]);

    $v1 = Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-F001',
        'fecha_emision' => '2026-10-10',
        'hora_emision' => '10:00:00',
        'total_usd' => 100,
        'condicion_pago' => 'contado',
        'estado' => 'completada',
    ]);

    $v2 = Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-F002',
        'fecha_emision' => '2026-10-15',
        'hora_emision' => '11:00:00',
        'total_usd' => 200,
        'condicion_pago' => 'credito',
        'estado' => 'completada',
    ]);

    $v3 = Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-F003',
        'fecha_emision' => '2026-10-18',
        'hora_emision' => '12:00:00',
        'total_usd' => 300,
        'condicion_pago' => 'contado',
        'estado' => 'anulada',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/facturas/lista?fecha_inicio=2026-10-12&fecha_fin=2026-10-16');

    $response->assertOk();
    $data = $response->json('data');
    expect($data)->toHaveCount(1)
        ->and($data[0]['codigo'])->toBe('VEN-F002');
});

test('facturas lista filtra correctamente por condicion de pago y estado', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000002-2',
        'nombre' => 'Empresa Factura Condicion Test',
        'razon_social' => 'Empresa Factura Condicion Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $cliente = Cliente::create([
        'nombre' => 'Ana',
        'apellido' => 'Gomez',
        'cedula' => 'V-88333444',
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-'.uniqid(),
        'nombre' => 'Almacen Condicion',
        'estado' => true,
    ]);

    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-C001',
        'fecha_emision' => '2026-10-15',
        'hora_emision' => '10:00:00',
        'total_usd' => 150,
        'condicion_pago' => 'credito',
        'estado' => 'completada',
    ]);

    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-C002',
        'fecha_emision' => '2026-10-15',
        'hora_emision' => '11:00:00',
        'total_usd' => 250,
        'condicion_pago' => 'contado',
        'estado' => 'completada',
    ]);

    $respCredito = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/facturas/lista?condicion_pago=credito');

    $respCredito->assertOk();
    $dataCredito = $respCredito->json('data');
    expect($dataCredito)->toHaveCount(1)
        ->and($dataCredito[0]['codigo'])->toBe('VEN-C001');

    $respEstado = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/facturas/lista?estado=completada');

    $respEstado->assertOk();
    expect($respEstado->json('data'))->toHaveCount(2);
});

test('facturas lista normaliza fechas futuras y rangos invertidos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88000003-3',
        'nombre' => 'Empresa Fechas Normalizadas Test',
        'razon_social' => 'Empresa Fechas Normalizadas Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88000003']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $cliente = Cliente::create([
        'nombre' => 'Luis',
        'apellido' => 'Mendoza',
        'cedula' => 'V-88555666',
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-'.uniqid(),
        'nombre' => 'Almacen Normalizado',
        'estado' => true,
    ]);

    $hoy = Carbon::now()->toDateString();

    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-N001',
        'fecha_emision' => $hoy,
        'hora_emision' => '10:00:00',
        'total_usd' => 100,
        'condicion_pago' => 'contado',
        'estado' => 'completada',
    ]);

    // Rango invertido: fecha_inicio posterior a fecha_fin
    $respInvertido = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/facturas/lista?fecha_inicio={$hoy}&fecha_fin=2026-01-01");

    $respInvertido->assertOk();
    expect($respInvertido->json('data'))->toHaveCount(1)
        ->and($respInvertido->json('data.0.codigo'))->toBe('VEN-N001');
});
