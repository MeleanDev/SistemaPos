<?php

use App\Models\Empresa;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

test('administrador solo puede listar usuarios pertenecientes a sus empresas y no ve a los superadministradores', function () {
    $empresaA = Empresa::create([
        'rif' => 'J-12345678-1',
        'nombre' => 'Empresa Alfa',
        'razon_social' => 'Empresa Alfa C.A.',
        'direccion' => 'Calle 1',
        'estado' => true,
    ]);

    $empresaB = Empresa::create([
        'rif' => 'J-87654321-2',
        'nombre' => 'Empresa Beta',
        'razon_social' => 'Empresa Beta C.A.',
        'direccion' => 'Calle 2',
        'estado' => true,
    ]);

    $superAdmin = User::factory()->create(['name' => 'Super User', 'email' => 'super@test.com']);
    $superAdmin->assignRole('SuperAdmin');

    $adminA = User::factory()->create(['name' => 'Admin Alfa', 'email' => 'admin.alfa@test.com']);
    $adminA->assignRole('Admin');
    $adminA->empresas()->attach($empresaA->id, ['estado' => true]);

    $operadorA = User::factory()->create(['name' => 'Operador Alfa', 'email' => 'op.alfa@test.com']);
    $operadorA->assignRole('Operador');
    $operadorA->empresas()->attach($empresaA->id, ['estado' => true]);

    $operadorB = User::factory()->create(['name' => 'Operador Beta', 'email' => 'op.beta@test.com']);
    $operadorB->assignRole('Operador');
    $operadorB->empresas()->attach($empresaB->id, ['estado' => true]);

    // Admin A consulta la lista de usuarios
    $response = $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->getJson('/usuarios/lista');

    $response->assertOk();
    $data = collect($response->json('data'));

    // Debe contener a Admin A y Operador A
    expect($data->pluck('id')->all())->toContain($adminA->id)
        ->and($data->pluck('id')->all())->toContain($operadorA->id)
        // NO debe contener al SuperAdmin ni al Operador de Empresa B
        ->and($data->pluck('id')->all())->not->toContain($superAdmin->id)
        ->and($data->pluck('id')->all())->not->toContain($operadorB->id);
});

test('administrador solo puede crear operadores o administradores en sus empresas autorizadas', function () {
    $empresaA = Empresa::create([
        'rif' => 'J-12345678-3',
        'nombre' => 'Empresa Gamma',
        'razon_social' => 'Empresa Gamma C.A.',
        'direccion' => 'Calle 3',
        'estado' => true,
    ]);

    $empresaB = Empresa::create([
        'rif' => 'J-87654321-4',
        'nombre' => 'Empresa Delta',
        'razon_social' => 'Empresa Delta C.A.',
        'direccion' => 'Calle 4',
        'estado' => true,
    ]);

    $adminA = User::factory()->create(['name' => 'Admin Gamma', 'email' => 'admin.gamma@test.com']);
    $adminA->assignRole('Admin');
    $adminA->empresas()->attach($empresaA->id, ['estado' => true]);

    // 1. Intentar crear un usuario con rol SuperAdmin -> Rechazado por validación
    $respSuper = $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->postJson('/usuarios', [
            'name' => 'V-99001',
            'nombre' => 'Hacker',
            'apellido' => 'Test',
            'email' => 'hacker@test.com',
            'password' => 'password123',
            'rol' => 'SuperAdmin',
            'empresas' => [$empresaA->id],
        ]);

    $respSuper->assertStatus(422);

    // 2. Intentar asignar una empresa a la cual el Admin no pertenece (Empresa Delta) -> Rechazado
    $respEmpresaAjena = $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->postJson('/usuarios', [
            'name' => 'V-99002',
            'nombre' => 'Operador',
            'apellido' => 'Invalido',
            'email' => 'invalido@test.com',
            'password' => 'password123',
            'rol' => 'Operador',
            'empresas' => [$empresaB->id],
        ]);

    $respEmpresaAjena->assertStatus(422);

    // 3. Crear Operador en la empresa autorizada -> Exitoso
    $respValido = $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->postJson('/usuarios', [
            'name' => 'V-99003',
            'nombre' => 'Operador',
            'apellido' => 'Legitimo',
            'email' => 'legitimo@test.com',
            'password' => 'password123',
            'rol' => 'Operador',
            'empresas' => [$empresaA->id],
            'permisos' => ['clientes.ver', 'cajas.ver'],
        ]);

    $respValido->assertOk()
        ->assertJson(['success' => true]);

    $nuevoUsuario = User::where('name', 'V-99003')->first();
    expect($nuevoUsuario)->not->toBeNull()
        ->and($nuevoUsuario->hasRole('Operador'))->toBeTrue()
        ->and($nuevoUsuario->empresas()->where('empresas.id', $empresaA->id)->exists())->toBeTrue();
});

test('administrador no puede consultar, editar, modificar permisos ni eliminar a un superadministrador', function () {
    $empresa = Empresa::create([
        'rif' => 'J-12345678-5',
        'nombre' => 'Empresa Omega',
        'razon_social' => 'Empresa Omega C.A.',
        'direccion' => 'Calle 5',
        'estado' => true,
    ]);

    $superAdmin = User::factory()->create(['name' => 'Super Boss', 'email' => 'superboss@test.com']);
    $superAdmin->assignRole('SuperAdmin');

    $admin = User::factory()->create(['name' => 'Admin Omega', 'email' => 'admin.omega@test.com']);
    $admin->assignRole('Admin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    // 1. Detalle del SuperAdmin -> 403
    $respDetalle = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/usuarios/{$superAdmin->id}");

    $respDetalle->assertStatus(403);

    // 2. Actualizar SuperAdmin -> 403
    $respActualizar = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->putJson("/usuarios/actualizar/{$superAdmin->id}", [
            'name' => 'V-111111',
            'nombre' => 'Super',
            'apellido' => 'Hacked',
            'email' => 'superboss@test.com',
            'rol' => 'Operador',
            'empresas' => [$empresa->id],
        ]);

    $respActualizar->assertStatus(403);

    // 3. Modificar permisos de SuperAdmin -> 403
    $respPermisos = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/usuarios/{$superAdmin->id}/permisos", [
            'permisos' => ['clientes.ver'],
        ]);

    $respPermisos->assertStatus(403);

    // 4. Eliminar SuperAdmin -> 403
    $respEliminar = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->deleteJson("/usuarios/{$superAdmin->id}");

    $respEliminar->assertStatus(403);
});

test('administrador no puede consultar ni modificar usuarios de otras empresas', function () {
    $empresaA = Empresa::create([
        'rif' => 'J-12345678-6',
        'nombre' => 'Empresa A',
        'razon_social' => 'Empresa A C.A.',
        'direccion' => 'Calle A',
        'estado' => true,
    ]);

    $empresaB = Empresa::create([
        'rif' => 'J-12345678-7',
        'nombre' => 'Empresa B',
        'razon_social' => 'Empresa B C.A.',
        'direccion' => 'Calle B',
        'estado' => true,
    ]);

    $adminA = User::factory()->create(['name' => 'Admin A', 'email' => 'adminA@test.com']);
    $adminA->assignRole('Admin');
    $adminA->empresas()->attach($empresaA->id, ['estado' => true]);

    $operadorB = User::factory()->create(['name' => 'Operador B', 'email' => 'opB@test.com']);
    $operadorB->assignRole('Operador');
    $operadorB->empresas()->attach($empresaB->id, ['estado' => true]);

    // Detalle de Operador B -> 403
    $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->getJson("/usuarios/{$operadorB->id}")
        ->assertStatus(403);

    // Actualizar Operador B -> 403
    $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->putJson("/usuarios/actualizar/{$operadorB->id}", [
            'name' => 'V-222222',
            'nombre' => 'Operador',
            'apellido' => 'B Modificado',
            'email' => 'opB@test.com',
            'rol' => 'Operador',
            'empresas' => [$empresaA->id],
        ])
        ->assertStatus(403);

    // Eliminar Operador B -> 403
    $this->actingAs($adminA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->deleteJson("/usuarios/{$operadorB->id}")
        ->assertStatus(403);
});
