<?php

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->superAdmin = User::create([
        'name' => 'V-00112233',
        'nombre' => 'Super',
        'apellido' => 'Admin',
        'email' => 'superadmin@empresa.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->superAdmin->assignRole('SuperAdmin');
});

test('companies index view loads correctly for superadmin', function () {
    $response = $this->actingAs($this->superAdmin)
        ->get('/empresas');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.administradores.empresa');
});

test('company is created successfully with maneja_motos toggle', function () {
    $payload = [
        'rif' => 'J-445566778',
        'nombre' => 'Motos del Centro',
        'razon_social' => 'Motos del Centro C.A.',
        'direccion' => 'Av. Bolívar, Local 10',
        'telefono' => '+584125556677',
        'correo' => 'contacto@motoscentro.com',
        'maneja_motos' => 1,
    ];

    $response = $this->actingAs($this->superAdmin)
        ->postJson('/empresas', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $empresa = Empresa::where('rif', 'J-445566778')->first();
    expect($empresa)->not->toBeNull()
        ->and($empresa->maneja_motos)->toBeTrue()
        ->and($empresa->estado)->toBeTrue();
});

test('companies list returns active companies', function () {
    Empresa::create([
        'rif' => 'J-999888777',
        'nombre' => 'Empresa Listado Test',
        'razon_social' => 'Empresa Listado Test C.A.',
        'direccion' => 'Sede Prueba',
        'estado' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->getJson('/empresas/lista');

    $response->assertOk();
    $data = $response->json('data');
    $nombres = collect($data)->pluck('nombre')->all();

    expect($nombres)->toContain('Empresa Listado Test');
});

test('company is deactivated on logical delete', function () {
    $empresa = Empresa::create([
        'rif' => 'J-112233445',
        'nombre' => 'Empresa Para Eliminar',
        'razon_social' => 'Empresa Para Eliminar C.A.',
        'direccion' => 'Calle 5',
        'estado' => true,
    ]);

    $response = $this->actingAs($this->superAdmin)
        ->deleteJson("/empresas/{$empresa->id}");

    $response->assertOk()
        ->assertJsonPath('success', true);

    $empresa->refresh();
    expect($empresa->estado)->toBeFalse();
});
