<?php

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

test('unconfigured system redirects guests to setup page', function () {
    $response = $this->get('/login');

    $response->assertRedirect(route('setup.index'));
});

test('setup screen can be rendered when system is unconfigured', function () {
    $response = $this->get('/configuracion-inicial');

    $response->assertStatus(200);
    $response->assertSee('Configuración Inicial');
    $response->assertSee('Empresa Matriz');
    $response->assertSee('SuperAdministrador');
});

test('setup validates required fields and password length', function () {
    $response = $this->postJson('/configuracion-inicial', [
        'empresa_nombre' => '',
        'admin_password' => '1234',
        'admin_password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'empresa_nombre',
        'empresa_razon_social',
        'empresa_cedula_numero',
        'empresa_direccion',
        'admin_cedula_numero',
        'admin_email',
        'admin_nombre',
        'admin_apellido',
        'admin_password',
    ]);
});

test('initial setup creates empresa, superadmin, payment methods and logs in', function () {
    $payload = [
        'empresa_tipo_cedula' => 'J-',
        'empresa_cedula_numero' => '123456789',
        'empresa_nombre' => 'Supermercado Central',
        'empresa_razon_social' => 'Supermercado Central C.A.',
        'empresa_codigo_pais' => '+58',
        'empresa_telefono_numero' => '4121234567',
        'empresa_correo' => 'contacto@supercentral.com',
        'empresa_direccion' => 'Av. Bolívar, Centro Comercial Principal',
        'admin_tipo_cedula' => 'V-',
        'admin_cedula_numero' => '25896321',
        'admin_nombre' => 'Carlos',
        'admin_apellido' => 'Pérez',
        'admin_email' => 'admin@supercentral.com',
        'admin_password' => 'Admin2026*',
        'admin_password_confirmation' => 'Admin2026*',
        'metodos_pago' => ['Efectivo USD', 'Pago Móvil', 'Punto de Venta / Débito'],
    ];

    $response = $this->postJson('/configuracion-inicial', $payload);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'redirect' => route('dashboard'),
    ]);

    // Verificar Empresa Matriz
    $this->assertDatabaseHas('empresas', [
        'rif' => 'J-123456789',
        'nombre' => 'Supermercado Central',
        'razon_social' => 'Supermercado Central C.A.',
        'estado' => 1,
    ]);

    // Verificar SuperAdministrador
    $this->assertDatabaseHas('users', [
        'name' => 'V-25896321',
        'nombre' => 'Carlos',
        'apellido' => 'Pérez',
        'email' => 'admin@supercentral.com',
        'estado' => 1,
    ]);

    $superAdmin = User::where('name', 'V-25896321')->first();
    expect($superAdmin->hasRole('SuperAdmin'))->toBeTrue();
    expect($superAdmin->empresas()->count())->toBe(1);

    // Verificar Métodos de Pago
    $this->assertDatabaseHas('metodos_pago', ['nombre' => 'Efectivo USD']);
    $this->assertDatabaseHas('metodos_pago', ['nombre' => 'Pago Móvil']);
    $this->assertDatabaseHas('metodos_pago', ['nombre' => 'Punto de Venta / Débito']);

    // Verificar autenticación activa
    $this->assertAuthenticatedAs($superAdmin);
});

test('configured system redirects setup page to dashboard', function () {
    $empresa = Empresa::create([
        'rif' => 'J-999999999',
        'nombre' => 'Empresa Activa',
        'razon_social' => 'Empresa Activa S.A.',
        'direccion' => 'Calle Principal',
        'estado' => true,
    ]);

    $user = User::create([
        'name' => 'V-11111111',
        'nombre' => 'Root',
        'apellido' => 'Admin',
        'email' => 'root@pos.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $user->assignRole('SuperAdmin');

    $response = $this->actingAs($user)->get('/configuracion-inicial');
    $response->assertRedirect(route('dashboard'));
});
