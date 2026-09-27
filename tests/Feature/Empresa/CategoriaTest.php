<?php

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-313131313',
        'nombre' => 'Categoria Corp',
        'razon_social' => 'Categoria Corp C.A.',
        'direccion' => 'Sede Central',
        'estado' => true,
    ]);

    $this->userAdmin = User::create([
        'name' => 'V-30303030',
        'nombre' => 'Admin',
        'apellido' => 'Categoria',
        'email' => 'admin@categoria.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdmin->assignRole('Admin');
    $this->userAdmin->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('la vista de categorias carga correctamente', function () {
    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get('/categorias');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.categoria');
});

test('puede registrar una categoria con multi-tenancy', function () {
    $payload = [
        'codigo' => 'CAT-TEST-01',
        'nombre' => 'Categoría de Prueba',
        'descripcion' => 'Descripción de la categoría de prueba',
    ];

    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/categorias', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $categoria = Categoria::where('codigo', 'CAT-TEST-01')->first();
    expect($categoria)->not->toBeNull()
        ->and($categoria->empresa_id)->toBe($this->empresa->id)
        ->and($categoria->nombre)->toBe('Categoría de Prueba');
});
