<?php

use App\Models\Categoria;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\Servicio;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);

    $this->empresa = Empresa::create([
        'rif' => 'J-414141414',
        'nombre' => 'Servicios Corp',
        'razon_social' => 'Servicios Corp C.A.',
        'direccion' => 'Sede Servicios',
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $this->empresa->id,
        'nombre' => 'Dólar Estadounidense',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 50.00,
        'es_principal' => false,
        'estado' => true,
    ]);

    $this->categoria = Categoria::create([
        'empresa_id' => $this->empresa->id,
        'codigo' => 'CAT-SRV',
        'nombre' => 'Mantenimiento General',
        'estado' => true,
    ]);

    $this->userAdmin = User::create([
        'name' => 'V-40404040',
        'nombre' => 'Admin',
        'apellido' => 'Servicios',
        'email' => 'admin@servicios.com',
        'password' => bcrypt('password123'),
        'estado' => true,
    ]);
    $this->userAdmin->assignRole('Admin');
    $this->userAdmin->empresas()->attach($this->empresa->id, ['es_predeterminada' => true, 'estado' => true]);
});

test('la vista de servicios carga correctamente', function () {
    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->get('/servicios');

    $response->assertOk()
        ->assertViewIs('Sistema.pages.empresa.servicio');
});

test('puede registrar un servicio con aplica_iva e iva_porcentaje guardados correctamente', function () {
    $payload = [
        'categoria_id' => $this->categoria->id,
        'codigo' => 'SRV-001',
        'nombre' => 'Mantenimiento Premium',
        'descripcion' => 'Revisión y cambio de fluidos',
        'precio_venta_usd' => 50.00,
        'aplica_iva' => '1',
        'iva_porcentaje' => 16.00,
    ];

    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/servicios', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $servicio = Servicio::where('codigo', 'SRV-001')->first();
    expect($servicio)->not->toBeNull()
        ->and($servicio->empresa_id)->toBe($this->empresa->id)
        ->and($servicio->nombre)->toBe('Mantenimiento Premium')
        ->and((bool) $servicio->aplica_iva)->toBeTrue()
        ->and((float) $servicio->iva_porcentaje)->toBe(16.00);
});

test('puede registrar un servicio exento de iva', function () {
    $payload = [
        'categoria_id' => $this->categoria->id,
        'codigo' => 'SRV-002',
        'nombre' => 'Asesoría Técnica Exenta',
        'descripcion' => 'Consultoría especializada',
        'precio_venta_usd' => 100.00,
    ];

    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->postJson('/servicios', $payload);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $servicio = Servicio::where('codigo', 'SRV-002')->first();
    expect($servicio)->not->toBeNull()
        ->and((bool) $servicio->aplica_iva)->toBeFalse()
        ->and((float) $servicio->iva_porcentaje)->toBe(0.00);
});

test('puede actualizar un servicio modificando el iva', function () {
    $servicio = Servicio::create([
        'empresa_id' => $this->empresa->id,
        'categoria_id' => $this->categoria->id,
        'codigo' => 'SRV-003',
        'nombre' => 'Servicio a Modificar',
        'precio_venta_usd' => 30.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0.00,
        'estado' => true,
    ]);

    $payloadActualizar = [
        'categoria_id' => $this->categoria->id,
        'codigo' => 'SRV-003',
        'nombre' => 'Servicio Modificado con IVA',
        'precio_venta_usd' => 35.00,
        'aplica_iva' => '1',
        'iva_porcentaje' => 16.00,
    ];

    $response = $this->actingAs($this->userAdmin)
        ->withSession(['empresa_activa_id' => $this->empresa->id])
        ->putJson("/servicios/actualizar/{$servicio->id}", $payloadActualizar);

    $response->assertOk()
        ->assertJsonPath('success', true);

    $servicioActualizado = $servicio->fresh();
    expect((bool) $servicioActualizado->aplica_iva)->toBeTrue()
        ->and((float) $servicioActualizado->iva_porcentaje)->toBe(16.00);
});
