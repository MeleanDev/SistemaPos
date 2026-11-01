<?php

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\CajaMovimiento;
use App\Models\CajaTurno;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\CuentaPorCobrar;
use App\Models\CuentaPorCobrarAbono;
use App\Models\DevolucionVenta;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Producto;
use App\Models\ProductoStockAlmacen;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Service\Empresa\CajaClass;
use App\Service\Finanzas\CuentaPorCobrarClass;
use App\Service\Inventario\KardexClass;
use App\Service\Ventas\VentaClass;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['SuperAdmin', 'Admin', 'Operador', 'Vendedor'] as $rol) {
        Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
    }
    foreach ([
        'pos.acceso', 'ventas.crear', 'ventas.ver', 'ventas.anular',
        'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
        'cajas.aperturar', 'cajas.cerrar', 'cajas.movimientos', 'cajas.arqueo',
        'vendedores.ver', 'vendedores.crear', 'vendedores.editar', 'vendedores.eliminar',
        'reportes.ver', 'reportes.vendedores',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
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

    // 7. Index View y Catálogos JSON
    $respIndex = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/cajas');

    $respIndex->assertOk()
        ->assertViewIs('Sistema.pages.empresa.caja');

    $respCatalogos = $this->actingAs($user1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/cajas/catalogos');

    $respCatalogos->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'usuario_actual_id' => $user1->id,
            ],
        ]);
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

    // 6. Consultar Index y Resumen Consolidado
    $respReporteIndex = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores');

    $respReporteIndex->assertOk()
        ->assertViewIs('Sistema.pages.empresa.reporte-vendedores')
        ->assertViewMissing('vendedores');

    $respReporteResumen = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/vendedores/resumen');

    $respReporteResumen->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'nombre', 'documento', 'comision_porcentaje', 'conteo_ventas', 'total_vendido_usd', 'total_comision_usd'],
            ],
        ]);

    $respCatalogos = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/vendedores/catalogos');

    $respCatalogos->assertOk()
        ->assertJsonStructure(['success', 'data' => ['vendedores', 'tasa_usd']]);

    // 7. Exportación PDF y Excel
    $respExportarPdf = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/pdf');

    $respExportarPdf->assertOk();

    $respExportarExcel = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get('/reportes/vendedores/excel');

    $respExportarExcel->assertOk();
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

test('cajeros disponibles y apertura de turno respetan aislamiento multi-empresa', function () {
    // Empresa A
    $empresaA = Empresa::create([
        'rif' => 'J-11111111-1',
        'nombre' => 'Empresa A',
        'razon_social' => 'Empresa A C.A.',
        'direccion' => 'Calle A',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    // Empresa B
    $empresaB = Empresa::create([
        'rif' => 'J-22222222-2',
        'nombre' => 'Empresa B',
        'razon_social' => 'Empresa B C.A.',
        'direccion' => 'Calle B',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $adminEmpresaA = User::factory()->create(['name' => 'Admin Empresa A', 'email' => 'adminA@test.com']);
    $adminEmpresaA->assignRole('Admin');
    $adminEmpresaA->empresas()->attach($empresaA->id, ['estado' => true]);

    $superUser = User::factory()->create(['name' => 'Super User Global', 'email' => 'superglobal@test.com']);
    $superUser->assignRole('SuperAdmin');
    $superUser->empresas()->attach($empresaA->id, ['estado' => true]);

    $cajeroEmpresaA = User::factory()->create(['name' => 'Cajero Empresa A', 'email' => 'cajeroA@test.com']);
    $cajeroEmpresaA->assignRole('Operador');
    $cajeroEmpresaA->empresas()->attach($empresaA->id, ['estado' => true]);

    $cajeroEmpresaB = User::factory()->create(['name' => 'Cajero Empresa B', 'email' => 'cajeroB@test.com']);
    $cajeroEmpresaB->assignRole('Operador');
    $cajeroEmpresaB->empresas()->attach($empresaB->id, ['estado' => true]);

    $cajaA = Caja::create([
        'empresa_id' => $empresaA->id,
        'nombre' => 'Caja Empresa A',
        'estado' => true,
    ]);

    // 1. Verificar que en la lista de cajeros de Empresa A NO aparece el cajero de Empresa B ni el SuperAdmin
    $respCajerosA = $this->actingAs($adminEmpresaA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->getJson('/cajas/cajeros-disponibles');

    $respCajerosA->assertOk();
    $cajerosData = collect($respCajerosA->json('data'));

    expect($cajerosData->pluck('id')->all())->toContain($adminEmpresaA->id)
        ->and($cajerosData->pluck('id')->all())->toContain($cajeroEmpresaA->id)
        ->and($cajerosData->pluck('id')->all())->not->toContain($cajeroEmpresaB->id)
        ->and($cajerosData->pluck('id')->all())->not->toContain($superUser->id);

    // Verificar en Vendedores / Catalogos que tampoco aparece el SuperAdmin ni usuarios de Empresa B
    $respVendedoresCatalogos = $this->actingAs($adminEmpresaA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->getJson('/vendedores/catalogos');

    $respVendedoresCatalogos->assertOk();
    $usuariosVendedorData = collect($respVendedoresCatalogos->json('usuarios'));
    expect($usuariosVendedorData->pluck('id')->all())->toContain($adminEmpresaA->id)
        ->and($usuariosVendedorData->pluck('id')->all())->toContain($cajeroEmpresaA->id)
        ->and($usuariosVendedorData->pluck('id')->all())->not->toContain($cajeroEmpresaB->id)
        ->and($usuariosVendedorData->pluck('id')->all())->not->toContain($superUser->id);

    // 2. Intentar aperturar caja en Empresa A asignando al cajero de Empresa B -> Debe ser rechazado
    $respAperturaInvalida = $this->actingAs($adminEmpresaA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $cajaA->id,
            'user_id' => $cajeroEmpresaB->id,
            'monto_apertura_usd' => 0.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respAperturaInvalida->assertStatus(422);

    // 3. Aperturar con cajero legítimo de Empresa A -> Debe tener éxito
    $respAperturaValida = $this->actingAs($adminEmpresaA)
        ->withSession(['empresa_activa_id' => $empresaA->id])
        ->postJson('/cajas/turnos/aperturar', [
            'caja_id' => $cajaA->id,
            'user_id' => $cajeroEmpresaA->id,
            'monto_apertura_usd' => 10.00,
            'monto_apertura_bs' => 0.00,
        ]);

    $respAperturaValida->assertOk()
        ->assertJson(['success' => true]);
});

test('validaciones de cajas y vendedores impiden duplicados, comisiones invalidas y enlaces erroneos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-33333333-3',
        'nombre' => 'Empresa Validaciones',
        'razon_social' => 'Empresa Validaciones C.A.',
        'direccion' => 'Calle Validaciones',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $empresaExterna = Empresa::create([
        'rif' => 'J-44444444-4',
        'nombre' => 'Empresa Externa',
        'razon_social' => 'Empresa Externa C.A.',
        'direccion' => 'Calle Externa',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'Admin Val']);
    $admin->assignRole('Admin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $usuarioVendedor = User::factory()->create(['name' => 'User Vendedor']);
    $usuarioVendedor->assignRole('Operador');
    $usuarioVendedor->empresas()->attach($empresa->id, ['estado' => true]);

    $usuarioExterno = User::factory()->create(['name' => 'User Externo']);
    $usuarioExterno->assignRole('Operador');
    $usuarioExterno->empresas()->attach($empresaExterna->id, ['estado' => true]);

    // 1. Validaciones Caja: nombre obligatorio, duplicado, código duplicado
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas', [
            'nombre' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nombre']);

    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas', [
            'nombre' => 'Caja Test',
            'codigo' => 'CAJ-VAL-1',
        ])
        ->assertOk();

    // Intentar crear otra caja con el mismo nombre o código en la misma empresa -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas', [
            'nombre' => 'Caja Test',
            'codigo' => 'CAJ-VAL-2',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nombre']);

    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas', [
            'nombre' => 'Caja Test 2',
            'codigo' => 'CAJ-VAL-1',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['codigo']);

    // 2. Validaciones Vendedor: comision > 100, user ajeno, user ya vinculado
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '99887766',
            'nombre' => 'Vendedor Error Comision',
            'comision_porcentaje' => 150, // Inválido (>100)
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['comision_porcentaje']);

    // Intentar vincular usuario de otra empresa -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '99887766',
            'nombre' => 'Vendedor Error Usuario',
            'user_id' => $usuarioExterno->id,
            'comision_porcentaje' => 5,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    // Crear vendedor legítimo vinculando usuario
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '99887766',
            'nombre' => 'Vendedor Legitimo',
            'user_id' => $usuarioVendedor->id,
            'comision_porcentaje' => 5,
        ])
        ->assertOk();

    // Intentar vincular el mismo usuario a un segundo vendedor -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '99887767',
            'nombre' => 'Segundo Vendedor',
            'user_id' => $usuarioVendedor->id,
            'comision_porcentaje' => 3,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);
});

test('caja permite registrar movimientos menores y se reflejan en el balance esperado del turno', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880009-9',
        'nombre' => 'Empresa Movimientos Test',
        'razon_social' => 'Empresa Movimientos Test C.A.',
        'direccion' => 'Av. Test',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88880009']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Movimientos',
        'codigo' => 'MOV-01',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $user->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 100.00,
        'monto_apertura_bs' => 4000.00,
        'estado' => 'abierta',
    ]);

    // Registrar egreso menor
    $respEgreso = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/movimientos', [
            'caja_turno_id' => $turno->id,
            'tipo' => 'egreso',
            'monto_usd' => 15.00,
            'monto_bs' => 0.00,
            'concepto' => 'Compra de artículos de limpieza',
        ]);

    $respEgreso->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('caja_movimientos', [
        'caja_turno_id' => $turno->id,
        'tipo' => 'egreso',
        'monto_usd' => 15.00,
        'monto_bs' => 0.00,
    ]);

    // Rechazar movimiento sin concepto o con ambos montos en cero
    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/movimientos', [
            'caja_turno_id' => $turno->id,
            'tipo' => 'egreso',
            'monto_usd' => 0.00,
            'monto_bs' => 0.00,
            'concepto' => 'Monto cero inválido',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['monto_usd']);

    // Registrar movimiento válido solo en Bolívares
    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/movimientos', [
            'caja_turno_id' => $turno->id,
            'tipo' => 'egreso',
            'monto_usd' => 0.00,
            'monto_bs' => 200.00,
            'concepto' => 'Pago de flete en Bs',
        ])
        ->assertOk();

    // Registrar ingreso menor con usd y bs válidos
    $respIngresoOk = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/movimientos', [
            'caja_turno_id' => $turno->id,
            'tipo' => 'ingreso',
            'monto_usd' => 10.00,
            'monto_bs' => 500.00,
            'concepto' => 'Cambio de billetes recibido',
        ]);

    $respIngresoOk->assertOk();

    // Validar cálculo de turno
    $cajaClass = app(CajaClass::class);
    $reporte = $cajaClass->calcularReporteTurno($turno->id, $empresa->id);

    // Esperado en USD = 100 apertura - 15 egreso + 10 ingreso = 95.00
    expect((float) $reporte['efectivo_esperado_usd'])->toBe(95.00);
    // Esperado en BS = 4000 apertura - 200 egreso + 500 ingreso = 4300.00
    expect((float) $reporte['efectivo_esperado_bs'])->toBe(4300.00);

    // Verificar que los tickets térmicos X y Z muestren la sección y el desglose de movimientos
    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/cajas/turnos/{$turno->id}/imprimir-x")
        ->assertOk()
        ->assertSee('4. MOVIMIENTOS DE CAJA (ENTRADAS / SALIDAS)')
        ->assertSee('Compra de artículos de limpieza')
        ->assertSee('Pago de flete en Bs');

    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/cajas/turnos/{$turno->id}/imprimir-z")
        ->assertOk()
        ->assertSee('4. MOVIMIENTOS DE CAJA (ENTRADAS / SALIDAS)')
        ->assertSee('Compra de artículos de limpieza')
        ->assertSee('Pago de flete en Bs');
});

test('devolucion parcial o total recalcula o anula la comision del vendedor', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880010-0',
        'nombre' => 'Empresa Devolucion Test',
        'razon_social' => 'Empresa Devolucion Test C.A.',
        'direccion' => 'Av. Devolucion',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88880010']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Principal Dev',
        'codigo' => 'ALM-DEV-1',
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-12345678',
        'nombre' => 'Cliente',
        'apellido' => 'Devolución',
        'direccion' => 'Caracas',
        'limite_credito' => 1000,
        'dias_credito' => 15,
        'estado' => true,
    ]);

    $vendedor = Vendedor::create([
        'empresa_id' => $empresa->id,
        'documento' => '99887700',
        'nombre' => 'Vendedor Comisión',
        'comision_porcentaje' => 10.00,
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
        'codigo_interno' => 'PRD-DEV-1',
        'codigo_barra' => '999888777',
        'nombre' => 'Producto Devolucion',
        'precio_base' => 10.00,
        'precio_venta' => 10.00,
        'maneja_inventario' => true,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'producto_id' => $producto->id,
        'stock' => 100,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Dev',
        'codigo' => 'CAJ-DEV',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $user->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $metodoEfectivoUsd = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Efectivo USD',
        'tipo' => 'efectivo',
        'moneda' => 'USD',
        'estado' => true,
    ]);

    $ventaClass = app(VentaClass::class);

    // Venta de 2 unidades a $10 = $20 total. Comisión 10% = $2.00
    $venta = $ventaClass->procesarVenta([
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'caja_id' => $caja->id,
        'caja_turno_id' => $turno->id,
        'vendedor_id' => $vendedor->id,
        'tipo_venta' => 'detal',
        'condicion_pago' => 'contado',
        'tasa_cambio' => 40.00,
        'subtotal' => 20.00,
        'descuento' => 0.00,
        'iva' => 0.00,
        'igtf' => 0.00,
        'total' => 20.00,
        'items' => [
            [
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 2,
                'precio_unitario_usd' => 10.00,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodoEfectivoUsd->id,
                'monto' => 20.00,
                'moneda' => 'USD',
                'tasa_cambio' => 1.0,
                'monto_usd' => 20.00,
            ],
        ],
    ], $empresa->id, $user->id);

    $venta->refresh();
    expect((float) $venta->comision_monto_usd)->toBe(1.72);

    // Devolución parcial de 1 unidad ($10 devuelto de $20 total = 50%)
    $detalle = $venta->detalles->first();
    $devolucionParcial = $ventaClass->procesarDevolucion([
        'venta_id' => $venta->id,
        'motivo' => 'Cliente devolvió 1 unidad',
        'tipo_reembolso' => 'efectivo',
        'items' => [
            [
                'venta_detalle_id' => $detalle->id,
                'cantidad' => 1,
            ],
        ],
    ], $empresa->id, $user->id);

    $venta->refresh();
    expect($venta->estado)->toBe('devuelta_parcial');
    // Comisión recalculada: de $17.24 netos quedaron $8.62 -> 10% = 0.86
    expect((float) $venta->comision_monto_usd)->toBe(0.86);

    // Devolución de la unidad restante (Devolución total)
    $devolucionTotal = $ventaClass->procesarDevolucion([
        'venta_id' => $venta->id,
        'motivo' => 'Cliente devolvió la otra unidad',
        'tipo_reembolso' => 'efectivo',
        'items' => [
            [
                'venta_detalle_id' => $detalle->id,
                'cantidad' => 1,
            ],
        ],
    ], $empresa->id, $user->id);

    $venta->refresh();
    expect($venta->estado)->toBe('devuelta_total');
    // Comisión debe ser 0.00
    expect((float) $venta->comision_monto_usd)->toBe(0.00);
});

test('vendedor con login solo puede ver sus propias ventas en el listado de facturas', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880011-1',
        'nombre' => 'Empresa Privacidad Vendedor',
        'razon_social' => 'Empresa Privacidad Vendedor C.A.',
        'direccion' => 'Av. Privacidad',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $usuarioVendedor = User::factory()->create(['name' => 'V-88880011']);
    // Asignar permiso ventas.ver pero NO ventas.anular ni rol SuperAdmin/Admin
    $usuarioVendedor->givePermissionTo('ventas.ver');
    $usuarioVendedor->empresas()->attach($empresa->id);

    $vendedor1 = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $usuarioVendedor->id,
        'documento' => '11223344',
        'nombre' => 'Vendedor Con Login',
        'comision_porcentaje' => 5.00,
        'estado' => true,
    ]);

    $vendedorOtro = Vendedor::create([
        'empresa_id' => $empresa->id,
        'documento' => '22334455',
        'nombre' => 'Otro Vendedor',
        'comision_porcentaje' => 5.00,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-99001122',
        'nombre' => 'Cliente',
        'apellido' => 'Test',
        'direccion' => 'Caracas',
        'limite_credito' => 1000,
        'dias_credito' => 15,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Fac',
        'codigo' => 'ALM-FAC',
        'estado' => true,
    ]);

    // Venta de este vendedor
    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => $vendedor1->id,
        'user_id' => $usuarioVendedor->id,
        'codigo' => 'FAC-001',
        'numero_control' => 'CTRL-001',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'condicion_pago' => 'contado',
        'moneda' => 'USD',
        'tasa_cambio' => 40.00,
        'total_usd' => 100.00,
        'total_bs' => 4000.00,
        'estado' => 'completada',
    ]);

    // Venta de otro vendedor
    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'vendedor_id' => $vendedorOtro->id,
        'user_id' => $usuarioVendedor->id,
        'codigo' => 'FAC-002',
        'numero_control' => 'CTRL-002',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'condicion_pago' => 'contado',
        'moneda' => 'USD',
        'tasa_cambio' => 40.00,
        'total_usd' => 200.00,
        'total_bs' => 8000.00,
        'estado' => 'completada',
    ]);

    $response = $this->actingAs($usuarioVendedor)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/facturas/lista');

    $response->assertOk();
    $data = $response->json('data');

    // Debe contener solo 1 registro (la venta propia FAC-001)
    expect(count($data))->toBe(1);
    expect($data[0]['codigo'])->toBe('FAC-001');
});

test('arqueo usa es_efectivo de metodos_pago y venta con vuelto registra egreso en caja_movimientos', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880012-2',
        'nombre' => 'Empresa Vuelto y Efectivo Test',
        'razon_social' => 'Empresa Vuelto y Efectivo Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 50.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Vuelto',
        'codigo' => 'ALM-VLT',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'General',
        'codigo' => 'CAT-VLT',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-VLT-15',
        'nombre' => 'Producto de 15 USD',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 8.00,
        'precio_detal_usd' => 15.00,
        'precio_mayorista_usd' => 14.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 30,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-55443322',
        'nombre' => 'Luis',
        'apellido' => 'Rojas',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoBilletesGaveta = MetodoPago::create([
        'nombre' => 'Divisas en Gaveta Física',
        'descripcion' => 'Sin la palabra efectivo en el nombre pero con es_efectivo=true',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $metodoFalsoEfectivo = MetodoPago::create([
        'nombre' => 'Transferencia Efectivo Digital',
        'descripcion' => 'Con la palabra efectivo en el nombre pero es_efectivo=false',
        'es_efectivo' => false,
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja Vuelto 01',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-88880012']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['estado' => true]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $user->id,
        'aperturado_por_id' => $user->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 10.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    // Venta 1: Producto de 15 USD pagado con billete de 20 USD (método con es_efectivo = true)
    // Vuelto entregado = 5 USD -> efectivo neto que queda en gaveta por la venta = +15 USD
    $respVenta1 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 50.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 15.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoBilletesGaveta->id,
                    'monto' => 20.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 50.0000,
                ],
            ],
        ]);

    $respVenta1->assertOk()->assertJsonPath('success', true);
    $venta1Id = $respVenta1->json('data.id');

    $movVuelto = CajaMovimiento::where('caja_turno_id', $turno->id)
        ->where('documento_tipo', 'venta')
        ->where('documento_id', $venta1Id)
        ->first();

    expect($movVuelto)->not->toBeNull()
        ->and($movVuelto->tipo)->toBe('egreso')
        ->and($movVuelto->metodo_pago_id)->toBe($metodoBilletesGaveta->id)
        ->and($movVuelto->moneda)->toBe('USD')
        ->and((float) $movVuelto->monto_usd)->toBe(5.00)
        ->and((float) $movVuelto->monto_bs)->toBe(250.00);

    // Venta 2: Producto de 15 USD pagado con método que tiene "Efectivo" en el nombre pero es_efectivo = false
    // No debe sumar al efectivo esperado del turno
    $respVenta2 = $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 50.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 15.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoFalsoEfectivo->id,
                    'monto' => 15.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 50.0000,
                ],
            ],
        ]);

    $respVenta2->assertOk()->assertJsonPath('success', true);

    // Registrar además un egreso manual de 2 USD en el mismo turno
    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cajas/movimientos', [
            'caja_turno_id' => $turno->id,
            'tipo' => 'egreso',
            'monto_usd' => 2.00,
            'monto_bs' => 0.00,
            'concepto' => 'Compra menor de papelería',
        ])
        ->assertOk();

    $reporte = app(CajaClass::class)->calcularReporteTurno($turno->id, $empresa->id);

    // El vuelto (5 USD) se separa en total_vuelto_usd y NO se mezcla ni resta dos veces en total_egresos_caja_usd (2 USD)
    // Esperado = Apertura (10) + Pago efectivo recibido (20) - Vuelto (5) - Egreso manual (2) = 23.00 USD (es decir: apertura + 15 - 2)
    expect((float) $reporte['total_efectivo_ventas_usd'])->toBe(20.00)
        ->and((float) $reporte['total_vuelto_usd'])->toBe(5.00)
        ->and((float) $reporte['total_egresos_caja_usd'])->toBe(2.00)
        ->and((float) $reporte['efectivo_esperado_usd'])->toBe(23.00)
        ->and((float) $reporte['efectivo_esperado_bs'])->toBe(0.00);

    $movVueltoItem = collect($reporte['movimientos_listado'])->firstWhere('id', $movVuelto->id);
    expect($movVueltoItem)->not->toBeNull()
        ->and($movVueltoItem['es_vuelto'])->toBeTrue()
        ->and($movVueltoItem['etiqueta_tipo'])->toBe('Vuelto entregado');

    $this->actingAs($user)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/cajas/turnos/{$turno->id}/imprimir-x")
        ->assertOk()
        ->assertSee('Vuelto Entregado')
        ->assertSee('Vuelto entregado:');
});

test('calcularReporteTurno cuadra al centavo por moneda y permite efectivo esperado negativo sin max(0)', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880013-3',
        'nombre' => 'Empresa Formula B03 Test',
        'razon_social' => 'Empresa Formula B03 Test C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 40.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén B03',
        'codigo' => 'ALM-B03',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'General B03',
        'codigo' => 'CAT-B03',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B03',
        'nombre' => 'Producto B03',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 5.00,
        'precio_detal_usd' => 15.00,
        'precio_mayorista_usd' => 12.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 50,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-66554433',
        'nombre' => 'Ana',
        'apellido' => 'Silva',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoEfectivoUsd = MetodoPago::create([
        'nombre' => 'Efectivo Gaveta USD',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $metodoEfectivoBs = MetodoPago::create([
        'nombre' => 'Efectivo Gaveta Bs',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $metodoDigital = MetodoPago::create([
        'nombre' => 'Zelle Digital',
        'es_efectivo' => false,
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B03',
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'V-88880013']);
    $admin->assignRole('SuperAdmin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $admin->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 1000.00,
        'estado' => 'abierta',
    ]);

    // 1. Venta de 15 USD pagada con 20 USD en efectivo -> +20 USD pago efectivo, -5 USD vuelto
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 40.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 15.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoEfectivoUsd->id,
                    'monto' => 20.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ])
        ->assertOk();

    // 2. Abono CXC en efectivo USD (+12.50 USD) y en efectivo Bs (+300.00 Bs)
    $cxc = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'numero_factura' => 'FAC-CXC-01',
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'monto_total_usd' => 100.00,
        'monto_total_bs' => 4000.00,
        'monto_pagado_usd' => 0.00,
        'monto_pagado_bs' => 0.00,
        'saldo_pendiente_usd' => 100.00,
        'saldo_pendiente_bs' => 4000.00,
        'estado' => 'pendiente',
    ]);

    CuentaPorCobrarAbono::create([
        'cuenta_por_cobrar_id' => $cxc->id,
        'caja_id' => $caja->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'metodo_pago_id' => $metodoEfectivoUsd->id,
        'fecha_abono' => now()->toDateString(),
        'monto_usd' => 12.50,
        'monto_bs' => 500.00,
        'tasa_cambio' => 40.0000,
    ]);

    CuentaPorCobrarAbono::create([
        'cuenta_por_cobrar_id' => $cxc->id,
        'caja_id' => $caja->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'metodo_pago_id' => $metodoEfectivoBs->id,
        'fecha_abono' => now()->toDateString(),
        'monto_usd' => 7.50,
        'monto_bs' => 300.00,
        'tasa_cambio' => 40.0000,
    ]);

    // 3. Devolución en efectivo USD (-4.25 USD)
    $ventaExistente = Venta::where('caja_turno_id', $turno->id)->first();
    DevolucionVenta::create([
        'empresa_id' => $empresa->id,
        'venta_id' => $ventaExistente->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'codigo' => 'DEV-B03-01',
        'motivo' => 'Devolución parcial en efectivo',
        'total_devuelto_usd' => 4.25,
        'total_devuelto_bs' => 0.00,
        'tipo_reembolso' => 'efectivo',
    ]);

    // 4. Movimiento manual de ingreso (+3.10 USD) y egreso (-6.35 USD, -150.00 Bs) sin método (cuenta como efectivo)
    CajaMovimiento::create([
        'empresa_id' => $empresa->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'tipo' => 'ingreso',
        'metodo_pago_id' => null,
        'moneda' => 'USD',
        'monto_usd' => 3.10,
        'monto_bs' => 0.00,
        'concepto' => 'Ingreso sencillo USD',
    ]);

    CajaMovimiento::create([
        'empresa_id' => $empresa->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'tipo' => 'egreso',
        'metodo_pago_id' => null,
        'moneda' => 'MIXTA',
        'monto_usd' => 6.35,
        'monto_bs' => 150.00,
        'concepto' => 'Egreso manual mixto',
    ]);

    // 5. Movimiento con método NO efectivo (no debe alterar el efectivo esperado)
    CajaMovimiento::create([
        'empresa_id' => $empresa->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'tipo' => 'egreso',
        'metodo_pago_id' => $metodoDigital->id,
        'moneda' => 'USD',
        'monto_usd' => 99.00,
        'monto_bs' => 0.00,
        'concepto' => 'Comisión bancaria digital',
    ]);

    $cajaClass = app(CajaClass::class);
    $reporte = $cajaClass->calcularReporteTurno($turno->id, $empresa->id);

    // USD esperado = 50.00 (apertura) + 20.00 (venta ef) + 12.50 (abono ef) + 3.10 (ingreso) - 5.00 (vuelto) - 4.25 (devolucion) - 6.35 (egreso) = 70.00
    // Bs esperado = 1000.00 (apertura) + 0 (venta ef) + 300.00 (abono ef) + 0 (ingreso) - 0 (vuelto) - 0 (devolucion) - 150.00 (egreso) = 1150.00
    expect((float) $reporte['efectivo_esperado_usd'])->toBe(70.00)
        ->and((float) $reporte['efectivo_esperado_bs'])->toBe(1150.00);

    // 6. Si hay un egreso mayor al disponible, el esperado puede ser negativo y debe mostrarse sin max(0, ...)
    CajaMovimiento::create([
        'empresa_id' => $empresa->id,
        'caja_turno_id' => $turno->id,
        'user_id' => $admin->id,
        'tipo' => 'egreso',
        'metodo_pago_id' => null,
        'moneda' => 'USD',
        'monto_usd' => 85.50,
        'monto_bs' => 0.00,
        'concepto' => 'Retiro extraordinario que deja saldo negativo',
    ]);

    $reporteNegativo = $cajaClass->calcularReporteTurno($turno->id, $empresa->id);
    expect((float) $reporteNegativo['efectivo_esperado_usd'])->toBe(-15.50);
});

test('cierre Z permite al cajero cerrar solo su turno a ciegas y rechaza turno ajeno con 403', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880014-4',
        'nombre' => 'Empresa Cierre Ciego B04',
        'razon_social' => 'Empresa Cierre Ciego B04 C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'Admin B04']);
    $admin->assignRole('Admin');
    $admin->givePermissionTo(['cajas.ver', 'cajas.arqueo', 'cajas.cerrar', 'cajas.aperturar']);
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $cajero1 = User::factory()->create(['name' => 'Cajero 1 B04']);
    $cajero1->assignRole('Operador');
    $cajero1->givePermissionTo(['cajas.ver', 'cajas.arqueo', 'cajas.cerrar']);
    $cajero1->empresas()->attach($empresa->id, ['estado' => true]);

    $cajero2 = User::factory()->create(['name' => 'Cajero 2 B04']);
    $cajero2->assignRole('Operador');
    $cajero2->givePermissionTo(['cajas.ver', 'cajas.arqueo', 'cajas.cerrar']);
    $cajero2->empresas()->attach($empresa->id, ['estado' => true]);

    $caja1 = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Cajero 1',
        'estado' => true,
    ]);

    $caja2 = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja Cajero 2',
        'estado' => true,
    ]);

    $turnoCajero1 = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja1->id,
        'user_id' => $cajero1->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 45.00,
        'monto_apertura_bs' => 800.00,
        'estado' => 'abierta',
    ]);

    $turnoCajero2 = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja2->id,
        'user_id' => $cajero2->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 30.00,
        'monto_apertura_bs' => 500.00,
        'estado' => 'abierta',
    ]);

    // 1. Cajero 1 intenta cerrar el turno de Cajero 2 -> 403 Forbidden
    $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/cajas/turnos/{$turnoCajero2->id}/cerrar", [
            'monto_cierre_usd' => 30.00,
            'monto_cierre_bs' => 500.00,
        ])
        ->assertForbidden();

    expect($turnoCajero2->fresh()->estado)->toBe('abierta');

    // 2. Respuesta previa al conteo (reporte-x) para Cajero 1 NO incluye efectivo_esperado_usd ni efectivo_esperado_bs
    $respPreConteoCajero = $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/cajas/turnos/{$turnoCajero1->id}/reporte-x");

    $respPreConteoCajero->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.cierre_ciego', true);

    $dataCajero = $respPreConteoCajero->json('data');
    expect($dataCajero)->not->toHaveKey('efectivo_esperado_usd')
        ->and($dataCajero)->not->toHaveKey('efectivo_esperado_bs');

    // 3. Admin sí ve efectivo_esperado_usd y efectivo_esperado_bs en la respuesta previa
    $respPreConteoAdmin = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/cajas/turnos/{$turnoCajero1->id}/reporte-x");

    $respPreConteoAdmin->assertOk()
        ->assertJsonPath('data.cierre_ciego', false)
        ->assertJsonPath('data.efectivo_esperado_usd', 45)
        ->assertJsonPath('data.efectivo_esperado_bs', 800);

    // 4. Cajero 1 cierra su propio turno a ciegas ingresando 40 USD (faltante -5 USD) y 850 Bs (sobrante +50 Bs)
    $respCierrePropio = $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/cajas/turnos/{$turnoCajero1->id}/cerrar", [
            'monto_cierre_usd' => 40.00,
            'monto_cierre_bs' => 850.00,
            'observaciones' => 'Cierre a ciegas por cajero',
        ]);

    $respCierrePropio->assertOk()
        ->assertJsonPath('success', true);

    $turno1Cerrado = $turnoCajero1->fresh();
    expect($turno1Cerrado->estado)->toBe('cerrada')
        ->and((float) $turno1Cerrado->diferencia_usd)->toBe(-5.00)
        ->and((float) $turno1Cerrado->diferencia_bs)->toBe(50.00);

    // 5. Admin puede cerrar el turno de cualquier cajero (turnoCajero2)
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/cajas/turnos/{$turnoCajero2->id}/cerrar", [
            'monto_cierre_usd' => 30.00,
            'monto_cierre_bs' => 500.00,
            'observaciones' => 'Cerrado por administrador',
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($turnoCajero2->fresh()->estado)->toBe('cerrada')
        ->and((int) $turnoCajero2->fresh()->cerrado_por_id)->toBe($admin->id);
});

test('facturacion exige caja_id y caja_turno_id no nulos para admin con 0, 1 y 2 cajas abiertas y para cajero', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880015-5',
        'nombre' => 'Empresa B05 Turnos Obligatorios',
        'razon_social' => 'Empresa B05 Turnos Obligatorios C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $otraEmpresa = Empresa::create([
        'rif' => 'J-88880016-6',
        'nombre' => 'Empresa Externa B05',
        'razon_social' => 'Empresa Externa B05 C.A.',
        'direccion' => 'Av. Externa',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 40.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén B05',
        'codigo' => 'ALM-B05',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'General B05',
        'codigo' => 'CAT-B05',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B05',
        'nombre' => 'Producto B05',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 5.00,
        'precio_detal_usd' => 10.00,
        'precio_mayorista_usd' => 9.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 100,
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-44556677',
        'nombre' => 'Roberto',
        'apellido' => 'Díaz',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoEfectivo = MetodoPago::create([
        'nombre' => 'Efectivo B05',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'Admin B05']);
    $admin->assignRole('Admin');
    $admin->givePermissionTo(['pos.acceso', 'ventas.crear', 'cajas.ver', 'cajas.aperturar', 'cajas.cerrar']);
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $cajero1 = User::factory()->create(['name' => 'Cajero 1 B05']);
    $cajero1->assignRole('Operador');
    $cajero1->givePermissionTo(['pos.acceso', 'ventas.crear']);
    $cajero1->empresas()->attach($empresa->id, ['estado' => true]);

    $cajero2 = User::factory()->create(['name' => 'Cajero 2 B05']);
    $cajero2->assignRole('Operador');
    $cajero2->givePermissionTo(['pos.acceso', 'ventas.crear']);
    $cajero2->empresas()->attach($empresa->id, ['estado' => true]);

    $caja1 = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B05 - 1',
        'estado' => true,
    ]);

    $caja2 = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B05 - 2',
        'estado' => true,
    ]);

    $payloadBase = [
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 40.0000,
        'condicion_pago' => 'contado',
        'items' => [
            [
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 1,
                'precio_unitario_usd' => 10.00,
                'almacen_id' => $almacen->id,
            ],
        ],
        'pagos' => [
            [
                'metodo_pago_id' => $metodoEfectivo->id,
                'monto' => 10.00,
                'moneda' => 'USD',
                'tasa_cambio' => 40.0000,
            ],
        ],
    ];

    // 1. Admin con 0 cajas abiertas -> turno_activo = null y facturación rechazada con "No hay cajas abiertas"
    $datosAdmin0 = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos')
        ->assertOk()
        ->json('data');

    expect($datosAdmin0['puede_operar'])->toBeFalse()
        ->and($datosAdmin0['turno_activo'])->toBeNull()
        ->and($datosAdmin0['turnos_activos'])->toBeEmpty();

    $respAdminSinCaja = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payloadBase);

    $respAdminSinCaja->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);
    expect($respAdminSinCaja->json('errors.caja_turno_id.0'))->toContain('No hay cajas abiertas');

    expect(fn () => app(VentaClass::class)->procesarVenta($payloadBase, $empresa->id, $admin->id))
        ->toThrow(Exception::class, 'No hay cajas abiertas');

    // 2. Cajero sin turno abierto -> rechazado con error claro
    $respCajeroSinTurno = $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payloadBase);

    $respCajeroSinTurno->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);
    expect($respCajeroSinTurno->json('errors.caja_turno_id.0'))->toContain('No tienes una caja registradora aperturada');

    expect(fn () => app(VentaClass::class)->procesarVenta($payloadBase, $empresa->id, $cajero1->id))
        ->toThrow(Exception::class, 'No tienes una caja registradora aperturada');

    // 3. Admin con 1 caja abierta -> /pos/datos preselecciona esa única caja en la UI, pero el backend exige enviar caja_turno_id explícito
    $turno1 = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja1->id,
        'user_id' => $cajero1->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 25.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $datosAdmin1 = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos')
        ->assertOk()
        ->json('data');

    expect($datosAdmin1['puede_operar'])->toBeTrue()
        ->and(count($datosAdmin1['turnos_activos']))->toBe(1)
        ->and($datosAdmin1['turno_activo']['id'])->toBe($turno1->id);

    // Si el admin NO envía caja_turno_id, el backend no elige automáticamente: lo rechaza
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payloadBase)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);

    expect(fn () => app(VentaClass::class)->procesarVenta($payloadBase, $empresa->id, $admin->id))
        ->toThrow(Exception::class, 'Debes seleccionar una caja con turno abierto');

    // Si el admin envía el caja_turno_id seleccionado, se procesa con caja_id y caja_turno_id no nulos
    $respVentaAdmin1 = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', array_merge($payloadBase, ['caja_turno_id' => $turno1->id]));

    $respVentaAdmin1->assertOk()->assertJsonPath('success', true);
    $venta1 = Venta::findOrFail($respVentaAdmin1->json('data.id'));
    expect($venta1->caja_id)->toBe($caja1->id)
        ->and($venta1->caja_turno_id)->toBe($turno1->id);

    // 4. Admin con 2 cajas abiertas -> /pos/datos devuelve turno_activo = null para obligar a elegir en el selector
    $turno2 = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja2->id,
        'user_id' => $cajero2->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 30.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $datosAdmin2 = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos')
        ->assertOk()
        ->json('data');

    expect($datosAdmin2['puede_operar'])->toBeTrue()
        ->and(count($datosAdmin2['turnos_activos']))->toBe(2)
        ->and($datosAdmin2['turno_activo'])->toBeNull();

    // Validar que GuardarVentaRequest rechace un turno cerrado o de otra empresa
    $cajaExterna = Caja::create([
        'empresa_id' => $otraEmpresa->id,
        'nombre' => 'Caja Externa',
        'estado' => true,
    ]);

    $turnoExterno = CajaTurno::create([
        'empresa_id' => $otraEmpresa->id,
        'caja_id' => $cajaExterna->id,
        'user_id' => $admin->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 10.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', array_merge($payloadBase, ['caja_turno_id' => $turnoExterno->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);

    // Admin elige explícitamente turno2 -> venta queda en caja2 / turno2
    $respVentaAdmin2 = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', array_merge($payloadBase, ['caja_turno_id' => $turno2->id]));

    $respVentaAdmin2->assertOk()->assertJsonPath('success', true);
    $venta2 = Venta::findOrFail($respVentaAdmin2->json('data.id'));
    expect($venta2->caja_id)->toBe($caja2->id)
        ->and($venta2->caja_turno_id)->toBe($turno2->id);

    // 5. Cajero 1 intenta facturar enviando el turno de Cajero 2 -> 422
    $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', array_merge($payloadBase, ['caja_turno_id' => $turno2->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);

    // Cajero 1 factura con su propio turno -> éxito y caja_id/caja_turno_id no nulos
    $respVentaCajero1 = $this->actingAs($cajero1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $payloadBase);

    $respVentaCajero1->assertOk()->assertJsonPath('success', true);
    $venta3 = Venta::findOrFail($respVentaCajero1->json('data.id'));
    expect($venta3->caja_id)->toBe($caja1->id)
        ->and($venta3->caja_turno_id)->toBe($turno1->id);

    // Ninguna venta de la empresa queda con caja_id o caja_turno_id null
    $ventasSinCaja = Venta::where('empresa_id', $empresa->id)
        ->where(fn ($q) => $q->whereNull('caja_id')->orWhereNull('caja_turno_id'))
        ->count();
    expect($ventasSinCaja)->toBe(0);
});

test('servicios rechazan operaciones sin contexto de empresa, usuario o almacen sin caer en fallback 1', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880017-7',
        'nombre' => 'Empresa B06 Sin Fallbacks',
        'razon_social' => 'Empresa B06 Sin Fallbacks C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'Admin B06']);
    $user->assignRole('SuperAdmin');

    $cliente = Cliente::create([
        'cedula' => 'V-33221100',
        'nombre' => 'Cliente',
        'apellido' => 'B06',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    auth()->logout();
    session()->forget('empresa_activa_id');

    $ventaClass = app(VentaClass::class);
    $kardexClass = app(KardexClass::class);
    $cajaClass = app(CajaClass::class);
    $cxcClass = app(CuentaPorCobrarClass::class);

    // 1. VentaClass sin empresa activa ni sesión -> excepción clara (no fallback a 1)
    expect(fn () => $ventaClass->datosInicialesPos(null))
        ->toThrow(Exception::class, 'No se pudo determinar la empresa activa');

    expect(fn () => $ventaClass->procesarVenta(['cliente_id' => $cliente->id], null, $user->id))
        ->toThrow(Exception::class, 'No se pudo determinar la empresa activa');

    // 2. VentaClass sin usuario autenticado -> excepción clara (no fallback a 1)
    expect(fn () => $ventaClass->procesarVenta(['cliente_id' => $cliente->id], $empresa->id, null))
        ->toThrow(Exception::class, 'No se pudo determinar el usuario autenticado');

    // 3. VentaClass sin almacen_id -> excepción clara (no fallback a 1)
    expect(fn () => $ventaClass->procesarVenta(['cliente_id' => $cliente->id], $empresa->id, $user->id))
        ->toThrow(Exception::class, 'El almacén de despacho es obligatorio');

    // 4. KardexClass sin empresa_id, sin almacen_id o sin user_id -> excepción clara
    expect(fn () => $kardexClass->registrarMovimiento([
        'almacen_id' => 1,
        'producto_id' => 1,
        'user_id' => $user->id,
        'tipo_movimiento' => 'entrada_recepcion',
        'documento_tipo' => 'ajuste',
        'documento_id' => 1,
        'cantidad' => 1,
    ]))->toThrow(Exception::class, 'No se pudo determinar la empresa activa');

    expect(fn () => $kardexClass->registrarMovimiento([
        'empresa_id' => $empresa->id,
        'producto_id' => 1,
        'user_id' => $user->id,
        'tipo_movimiento' => 'entrada_recepcion',
        'documento_tipo' => 'ajuste',
        'documento_id' => 1,
        'cantidad' => 1,
    ]))->toThrow(Exception::class, 'El almacén es obligatorio');

    expect(fn () => $kardexClass->registrarMovimiento([
        'empresa_id' => $empresa->id,
        'almacen_id' => 1,
        'producto_id' => 1,
        'tipo_movimiento' => 'entrada_recepcion',
        'documento_tipo' => 'ajuste',
        'documento_id' => 1,
        'cantidad' => 1,
    ]))->toThrow(Exception::class, 'No se pudo determinar el usuario autenticado');

    // 5. CajaClass y CuentaPorCobrarClass sin contexto válido (0) -> excepción clara
    expect(fn () => $cajaClass->aperturarTurno(['caja_id' => 1], 0, $user->id))
        ->toThrow(Exception::class, 'No se pudo determinar la empresa activa');

    expect(fn () => $cxcClass->abonarFacturaEspecifica(1, ['monto' => 10], 0, $empresa->id))
        ->toThrow(Exception::class, 'No se pudo determinar el usuario autenticado');
});
