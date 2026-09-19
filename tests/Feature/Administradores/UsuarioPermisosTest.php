<?php

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-123456789',
        'nombre' => 'Empresa Principal',
        'razon_social' => 'Empresa Principal C.A.',
        'direccion' => 'Sede Central',
        'estado' => true,
    ]);

    $this->superAdmin = User::create([
        'name' => 'V-99999999',
        'nombre' => 'Super',
        'apellido' => 'Admin',
        'email' => 'super@admin.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->superAdmin->assignRole('SuperAdmin');
});

test('operator user can be created with granular module permissions', function () {
    $payload = [
        'name' => 'V-20111222',
        'nombre' => 'Operador',
        'apellido' => 'Caja',
        'email' => 'cajero@empresa.com',
        'password' => 'cajero123',
        'rol' => 'Operador',
        'empresas' => [$this->empresa->id],
        'permisos' => ['clientes.ver', 'clientes.crear', 'metodos_pago.ver'],
    ];

    $response = $this->actingAs($this->superAdmin)->postJson('/usuarios', $payload);

    $response->assertStatus(200);
    $response->assertJson(['success' => true]);

    $usuario = User::where('name', 'V-20111222')->first();
    expect($usuario)->not->toBeNull();
    expect($usuario->hasRole('Operador'))->toBeTrue();
    expect($usuario->hasPermissionTo('clientes.ver'))->toBeTrue();
    expect($usuario->hasPermissionTo('clientes.crear'))->toBeTrue();
    expect($usuario->hasPermissionTo('metodos_pago.ver'))->toBeTrue();
    expect($usuario->hasPermissionTo('clientes.eliminar'))->toBeFalse();
});

test('operator user permissions can be updated dynamically', function () {
    $usuario = User::create([
        'name' => 'V-30444555',
        'nombre' => 'Maria',
        'apellido' => 'Lopez',
        'email' => 'maria@empresa.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $usuario->assignRole('Operador');
    $usuario->givePermissionTo('clientes.ver');

    $payload = [
        'name' => 'V-30444555',
        'nombre' => 'Maria',
        'apellido' => 'Lopez',
        'email' => 'maria@empresa.com',
        'rol' => 'Operador',
        'empresas' => [$this->empresa->id],
        'permisos' => ['proveedores.ver', 'proveedores.crear'],
    ];

    $response = $this->actingAs($this->superAdmin)->putJson("/usuarios/actualizar/{$usuario->id}", $payload);

    $response->assertStatus(200);
    $usuario->refresh();

    expect($usuario->hasPermissionTo('proveedores.ver'))->toBeTrue();
    expect($usuario->hasPermissionTo('proveedores.crear'))->toBeTrue();
    expect($usuario->hasPermissionTo('clientes.ver'))->toBeFalse();
});

test('users collection list returns data for cards view', function () {
    $response = $this->actingAs($this->superAdmin)->getJson('/usuarios/lista');

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'success',
        'data' => [
            '*' => [
                'id',
                'name',
                'nombre',
                'apellido',
                'email',
                'rol',
                'empresas',
                'permisos',
                'created_at',
            ],
        ],
    ]);
});
