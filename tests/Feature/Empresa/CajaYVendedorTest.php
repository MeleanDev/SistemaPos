<?php

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaTurno;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use Database\Seeders\RolesYPermisosSeeder;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->seed(RolesYPermisosSeeder::class);
});

test('modulo de vendedores permite registrar, listar y actualizar asesores con comision', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880001-1',
        'nombre' => 'Empresa Vendedor Test',
        'razon_social' => 'Empresa Vendedor Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88880001']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Crear Vendedor
    $response = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '25123456',
            'nombre' => 'Carlos Vendedor',
            'telefono' => '0414-1112233',
            'correo' => 'carlos@vendedor.com',
            'comision_porcentaje' => 4.50,
        ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Vendedor registrado correctamente',
        ]);

    $this->assertDatabaseHas('vendedores', [
        'empresa_id' => $empresa->id,
        'documento' => '25123456',
        'nombre' => 'Carlos Vendedor',
        'comision_porcentaje' => 4.50,
    ]);

    $vendedor = Vendedor::where('documento', '25123456')->first();

    // 2. Actualizar Vendedor
    $respUpdate = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->putJson("/vendedores/actualizar/{$vendedor->id}", [
            'tipo_documento' => 'V',
            'documento' => '25123456',
            'nombre' => 'Carlos Mendoza',
            'telefono' => '0414-9998877',
            'correo' => 'carlos.mendoza@vendedor.com',
            'comision_porcentaje' => 5.00,
        ]);

    $respUpdate->assertOk();
    expect($vendedor->fresh()->nombre)->toBe('Carlos Mendoza')
        ->and((float) $vendedor->fresh()->comision_porcentaje)->toBe(5.00);

    // 3. Listar Vendedores Activos
    $respActivos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/vendedores/activos');

    $respActivos->assertOk()
        ->assertJsonFragment(['nombre' => 'Carlos Mendoza']);

    // 4. Index View y Catálogos JSON
    $respIndex = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/vendedores');

    $respIndex->assertOk()
        ->assertViewIs('Sistema.pages.empresa.vendedor');

    $respCatalogos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/vendedores/catalogos');

    $respCatalogos->assertOk()
        ->assertJsonStructure(['success', 'usuarios']);
});

test('modulo de cajas y apertura de turnos valida exclusividad de cajero y caja', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880002-2',
        'nombre' => 'Empresa Cajas Test',
        'razon_social' => 'Empresa Cajas Test C.A.',
        'direccion' => 'Av. Cajas',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user1 = User::factory()->create(['name' => 'V-88880002']);
    $user1->assignRole('SuperAdmin');
    $user1->empresas()->attach($empresa->id);

    $user2 = User::factory()->create(['name' => 'V-88880003']);
    $user2->assignRole('SuperAdmin');
    $user2->empresas()->attach($empresa->id);

    // 1. Crear Caja
    $respCaja = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas', [
            'nombre' => 'Caja Principal 01',
            'codigo' => 'CAJ-01',
            'descripcion' => 'Caja de cobro mostrador',
        ]);

    $respCaja->assertOk();
    $caja = Caja::where('nombre', 'Caja Principal 01')->first();
    expect($caja)->not->toBeNull();

    // 2. Aperturar Turno User 1 en Caja 1
    $respApertura = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja->id,
            'monto_apertura_usd' => 50.00,
            'monto_apertura_bs' => 2000.00,
            'observaciones' => 'Inicio de jornada',
        ]);

    $respApertura->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Turno de caja aperturado exitosamente',
        ]);

    $turno = CajaTurno::where('caja_id', $caja->id)->where('estado', 'abierta')->first();
    expect($turno)->not->toBeNull()
        ->and((float) $turno->monto_apertura_usd)->toBe(50.00);

    // 3. Aperturar una segunda caja diferente asignando a User 2 -> Permitido
    $caja2 = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Secundaria 2',
        'codigo' => 'CAJA-02',
        'estado' => true,
    ]);

    $respAperturaCaja2 = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja2->id,
            'user_id' => $user2->id,
            'monto_apertura_usd' => 20.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respAperturaCaja2->assertOk()
        ->assertJsonFragment(['success' => true]);

    // 4. Intentar abrir una caja que ya está en uso (Caja 1) -> Rechazado
    $user3 = User::factory()->create(['name' => 'V-88880004']);
    $user3->assignRole('Admin');
    $user3->empresas()->attach($empresa->id);

    $respCajaOcupada = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja->id,
            'user_id' => $user3->id,
            'monto_apertura_usd' => 10.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respCajaOcupada->assertStatus(422)
        ->assertJsonFragment(['success' => false]);

    // 5. Un cajero no puede tener múltiples cajas asignadas a la vez
    $caja3 = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Operador 3',
        'codigo' => 'CAJA-03',
        'estado' => true,
    ]);

    $respCajeroDuplicado = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja3->id,
            'user_id' => $user2->id, // User 2 ya tiene Caja 2 abierta
            'monto_apertura_usd' => 10.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respCajeroDuplicado->assertStatus(422)
        ->assertJsonFragment(['success' => false]);

    // 6. Operador sin permisos de administración no puede aperturar cajas directamente
    $operadorRole = Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
    $userOperador = User::factory()->create(['name' => 'V-88880005']);
    $userOperador->assignRole('Operador');
    $userOperador->empresas()->attach($empresa->id);

    $respOpSinPermiso = $this->actingAs($userOperador)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja3->id,
            'monto_apertura_usd' => 10.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respOpSinPermiso->assertForbidden();
});

test('flujo pos integra turno de caja, vendedor y calcula corte x y cierre z', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880003-3',
        'nombre' => 'Empresa POS Integral',
        'razon_social' => 'Empresa POS Integral C.A.',
        'direccion' => 'Av. Integral',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 1.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Central',
        'codigo' => 'ALM-C',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Repuestos',
        'codigo' => 'CAT-REP',
        'estado' => true,
    ]);

    $metodoEfectivoUsd = MetodoPago::create(['nombre' => 'Efectivo USD', 'descripcion' => 'Dólares', 'estado' => true]);
    $metodoPagoMovil = MetodoPago::create(['nombre' => 'Pago Móvil', 'descripcion' => 'Bolívares', 'estado' => true]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-001',
        'nombre' => 'Filtro de Aceite',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 10.00,
        'precio_detal_usd' => 20.00,
        'precio_mayorista_usd' => 18.00,
        'aplica_iva' => false,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 50,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-12345678',
        'nombre' => 'Pedro',
        'apellido' => 'Gómez',
        'telefono' => '0414-0001122',
        'correo' => 'pedro@gmail.com',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $vendedor = Vendedor::create([
        'empresa_id' => $empresa->id,
        'tipo_documento' => 'V',
        'documento' => '19000111',
        'nombre' => 'Marcos Asesor',
        'comision_porcentaje' => 10.00, // 10%
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja POS 01',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88880004']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    // 1. Aperturar Turno con $20 inicial
    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja->id,
            'monto_apertura_usd' => 20.00,
            'monto_apertura_bs' => 0.00,
        ])->assertOk();

    $turno = CajaTurno::where('caja_id', $caja->id)->where('estado', 'abierta')->first();

    // 2. Facturar en POS asignando Vendedor y Turno
    $respVenta = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'vendedor_id' => $vendedor->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 50.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 2, // 2 * $20 = $40
                    'precio_unitario_usd' => 20.00,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoEfectivoUsd->id,
                    'monto' => 40.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 50.0000,
                ],
            ],
        ]);

    $respVenta->assertOk()
        ->assertJson(['success' => true]);

    $venta = Venta::latest('id')->first();
    expect($venta->caja_id)->toBe($caja->id)
        ->and($venta->caja_turno_id)->toBe($turno->id)
        ->and($venta->vendedor_id)->toBe($vendedor->id)
        ->and((float) $venta->comision_porcentaje)->toBe(10.00)
        ->and((float) $venta->comision_monto_usd)->toBe(4.00); // 10% de $40 = $4.00

    // 3. Consultar Corte X
    $respCorteX = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/cajas/turnos/{$turno->id}/reporte-x");

    $respCorteX->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'total_ventas_usd' => 40.00,
                'monto_apertura_usd' => 20.00,
                'efectivo_esperado_usd' => 60.00, // $20 apertura + $40 efectivo ventas
            ],
        ]);

    // 4. Cerrar Turno (Cierre Z) con Arqueo físico de $60 exacto
    $respCierre = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/cajas/turnos/{$turno->id}/cerrar", [
            'monto_cierre_usd' => 60.00,
            'monto_cierre_bs' => 0.00,
            'observaciones' => 'Cuadre perfecto',
        ]);

    $respCierre->assertOk()
        ->assertJson(['success' => true]);

    $turnoActualizado = $turno->fresh();
    expect($turnoActualizado->estado)->toBe('cerrada')
        ->and((float) $turnoActualizado->total_ventas_usd)->toBe(40.00)
        ->and((float) $turnoActualizado->monto_cierre_usd)->toBe(60.00)
        ->and((float) $turnoActualizado->diferencia_usd)->toBe(0.00);

    // 5. Consultar Reporte de Vendedores
    $respReporteKpis = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/vendedores/kpis');

    $respReporteKpis->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'total_ventas_usd' => 40.00,
                'total_comisiones_usd' => 4.00,
                'conteo_facturas' => 1,
            ],
        ]);
});

test('flujo de control de apertura por administrador, bloqueo de cajero no asignado y preventa inmutable', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880099-9',
        'nombre' => 'Empresa Seguridad POS',
        'razon_social' => 'Empresa Seguridad POS C.A.',
        'direccion' => 'Av. Seguridad',
        'maneja_motos' => false,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 1.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Central',
        'codigo' => 'ALM-SEC',
        'tipo' => 'principal',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'General',
        'codigo' => 'CAT-GEN',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-SEC-01',
        'nombre' => 'Batería 12V',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 30.00,
        'precio_detal_usd' => 50.00,
        'precio_mayorista_usd' => 45.00,
        'aplica_iva' => false,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 10,
    ]);

    $metodoEfectivo = MetodoPago::create(['nombre' => 'Efectivo USD', 'descripcion' => 'Dólares', 'estado' => true]);

    $cliente = Cliente::create([
        'cedula' => 'V-99887766',
        'nombre' => 'María',
        'apellido' => 'Pérez',
        'telefono' => '0412-9998877',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja Mostrador 1',
        'estado' => true,
    ]);

    // 1. Usuarios: Admin, Cajero (Operador), Vendedor
    $admin = User::factory()->create(['name' => 'V-77000001']);
    $admin->assignRole('Admin');
    $admin->empresas()->attach($empresa->id);

    $cajero = User::factory()->create(['name' => 'V-77000002']);
    $cajero->assignRole('Operador');
    $cajero->givePermissionTo(['pos.acceso', 'ventas.crear']);
    $cajero->empresas()->attach($empresa->id);

    $vendedorUser = User::factory()->create(['name' => 'V-77000003']);
    $vendedorUser->assignRole('Vendedor');
    $vendedorUser->empresas()->attach($empresa->id);

    $vendedor = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $vendedorUser->id,
        'tipo_documento' => 'V',
        'documento' => '77000003',
        'nombre' => 'Asesor Preventa',
        'comision_porcentaje' => 5.00,
        'estado' => true,
    ]);

    // 2. Cajero entra al POS antes de que admin abra caja -> puede_operar es false
    $respPosCajeroSinCaja = $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos');

    $respPosCajeroSinCaja->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'es_cajero' => true,
                'puede_operar' => false,
                'turno_activo' => null,
            ],
        ]);

    // 3. Admin apertura la caja y se la asigna al cajero
    $respAperturaAdmin = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $caja->id,
            'user_id' => $cajero->id,
            'monto_apertura_usd' => 15.00,
            'monto_apertura_bs' => 0.00,
            'observaciones' => 'Apertura matutina para cajero',
        ]);

    $respAperturaAdmin->assertOk()
        ->assertJson(['success' => true]);

    $turno = CajaTurno::where('caja_id', $caja->id)->where('estado', 'abierta')->first();
    expect($turno->user_id)->toBe($cajero->id)
        ->and($turno->aperturado_por_id)->toBe($admin->id);

    // 4. Ahora el cajero consulta /pos/datos -> puede_operar es true y tiene el turno activo asignado
    $respPosCajeroConCaja = $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos');

    $respPosCajeroConCaja->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'es_cajero' => true,
                'puede_operar' => true,
                'turno_activo' => [
                    'id' => $turno->id,
                ],
            ],
        ]);

    // 5. Vendedor entra al POS en modo preventa (no necesita turno y es_vendedor es true)
    $respPosVendedor = $this->actingAs($vendedorUser)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos');

    $respPosVendedor->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'es_vendedor' => true,
                'puede_operar' => true,
                'vendedor_asociado' => [
                    'id' => $vendedor->id,
                    'nombre' => 'Asesor Preventa',
                ],
            ],
        ]);

    // 6. Vendedor guarda una orden en espera / preventa
    $respPreventa = $this->actingAs($vendedorUser)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/en-espera/guardar', [
            'cliente_id' => $cliente->id,
            'tipo_venta' => 'detal',
            'nota_referencia' => 'Preventa Asesor - Cliente María',
            'carrito' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
        ]);

    $respPreventa->assertOk()
        ->assertJson(['success' => true]);

    $esperaId = $respPreventa->json('data.id');

    // 7. Cajero recupera la preventa en POS -> el vendedor_id viene preseleccionado
    $respRecuperar = $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/pos/en-espera/{$esperaId}/recuperar");

    $respRecuperar->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'vendedor_id' => $vendedor->id,
            ],
        ]);

    // 8. Cajero factura la preventa
    $respCobro = $this->actingAs($cajero)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'vendedor_id' => $vendedor->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 50.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 50.00,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoEfectivo->id,
                    'monto' => 50.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 50.0000,
                ],
            ],
        ]);

    $respCobro->assertOk()
        ->assertJson(['success' => true]);

    $ventaRealizada = Venta::latest('id')->first();
    expect($ventaRealizada->user_id)->toBe($cajero->id)
        ->and($ventaRealizada->vendedor_id)->toBe($vendedor->id)
        ->and((float) $ventaRealizada->comision_monto_usd)->toBe(2.50); // 5% de $50 = $2.50
});
