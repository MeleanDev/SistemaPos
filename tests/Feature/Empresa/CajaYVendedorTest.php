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
use App\Models\Kardex;
use App\Models\LiquidacionComision;
use App\Models\MetodoPago;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\ProductoSerial;
use App\Models\ProductoStockAlmacen;
use App\Models\Proveedor;
use App\Models\Secuencia;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Service\Administradores\UsuarioClass;
use App\Service\Empresa\CajaClass;
use App\Service\Finanzas\CuentaPorCobrarClass;
use App\Service\Inventario\KardexClass;
use App\Service\Ventas\VentaClass;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['SuperAdmin', 'Admin', 'Operador', 'Vendedor'] as $rol) {
        Role::firstOrCreate(['name' => $rol, 'guard_name' => 'web']);
    }
    foreach ([
        'pos.acceso', 'pos.preventa', 'ventas.crear', 'ventas.ver', 'ventas.anular', 'ventas.descuentos',
        'cajas.ver', 'cajas.crear', 'cajas.editar', 'cajas.eliminar',
        'cajas.aperturar', 'cajas.cerrar', 'cajas.movimientos', 'cajas.arqueo',
        'vendedores.ver', 'vendedores.crear', 'vendedores.editar', 'vendedores.eliminar', 'vendedores.liquidar',
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
        'maneja_vendedores' => true,
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

    $metodoEfectivoUsd = MetodoPago::create(['nombre' => 'Efectivo USD', 'descripcion' => 'Dólares', 'es_efectivo' => true, 'estado' => true]);
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
        'maneja_vendedores' => true,
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
        'maneja_vendedores' => true,
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
        'maneja_vendedores' => true,
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
        'costo_usd' => 5.00,
        'precio_detal_usd' => 10.00,
        'precio_mayor_usd' => 9.00,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'producto_id' => $producto->id,
        'cantidad_actual' => 100,
        'costo_promedio_usd' => 5.00,
        'costo_promedio_bs' => 200.00,
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

test('validaciones de autoridad del servidor en venta y kardex impiden manipular precios, descuentos sin permiso, recursos invalidos o vender sin stock', function () {
    $empresa = Empresa::create([
        'rif' => 'J-88880018-8',
        'nombre' => 'Empresa B07 Autoridad Servidor',
        'razon_social' => 'Empresa B07 Autoridad Servidor C.A.',
        'direccion' => 'Av. Principal',
        'maneja_motos' => true,
        'estado' => true,
    ]);

    $otraEmpresa = Empresa::create([
        'rif' => 'J-88880019-9',
        'nombre' => 'Empresa Externa B07',
        'razon_social' => 'Empresa Externa B07 C.A.',
        'direccion' => 'Av. Externa',
        'maneja_motos' => true,
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
        'nombre' => 'Almacén Activo B07',
        'codigo' => 'ALM-B07-1',
        'estado' => true,
    ]);

    $almacenInactivo = Almacen::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Almacén Inactivo B07',
        'codigo' => 'ALM-B07-INA',
        'estado' => false,
    ]);

    $almacenExterno = Almacen::create([
        'empresa_id' => $otraEmpresa->id,
        'nombre' => 'Almacén Externo B07',
        'codigo' => 'ALM-B07-EXT',
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'General B07',
        'codigo' => 'CAT-B07',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B07-NORM',
        'nombre' => 'Producto Normal B07',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 60.00,
        'precio_detal_usd' => 100.00,
        'precio_mayorista_usd' => 85.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'maneja_seriales' => false,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 5,
    ]);

    $productoSerializado = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B07-SER',
        'nombre' => 'Laptop Serializada B07',
        'tipo_item' => 'producto',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 300.00,
        'precio_detal_usd' => 500.00,
        'precio_mayorista_usd' => 450.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'maneja_seriales' => true,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $productoSerializado->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 2,
    ]);

    $serialDisponible = ProductoSerial::create([
        'empresa_id' => $empresa->id,
        'producto_id' => $productoSerializado->id,
        'almacen_id' => $almacen->id,
        'numero_serial' => 'SN-B07-DISP-001',
        'estado' => 'disponible',
    ]);

    $serialVendido = ProductoSerial::create([
        'empresa_id' => $empresa->id,
        'producto_id' => $productoSerializado->id,
        'almacen_id' => $almacen->id,
        'numero_serial' => 'SN-B07-VEND-002',
        'estado' => 'vendido',
    ]);

    $serialOtroProducto = ProductoSerial::create([
        'empresa_id' => $empresa->id,
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'numero_serial' => 'SN-B07-OTRO-003',
        'estado' => 'disponible',
    ]);

    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-12345678-0',
        'nombre' => 'Ensambladora Bera B07',
        'razon_social' => 'Bera Motors B07 C.A.',
        'estado' => true,
    ]);

    $motoVendida = Moto::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'referencia' => 'BR-150-B07',
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'anio' => '2026',
        'color' => 'Azul',
        'cilindrada' => '150cc',
        'numero_niv' => 'NIV-B07-VENDIDA-01',
        'numero_chasis' => 'CHASIS-B07-01',
        'numero_motor' => 'MOT-B07-01',
        'certificado_origen' => 'CERT-B07-01',
        'placa' => 'AB07CD',
        'precio_costo_usd' => 800.00,
        'precio_costo_bs' => 32000.00,
        'precio_detal_usd' => 1200.00,
        'precio_detal_bs' => 48000.00,
        'precio_detal_con_iva_usd' => 1200.00,
        'precio_mayorista_usd' => 1100.00,
        'precio_mayorista_bs' => 44000.00,
        'precio_mayorista_con_iva_usd' => 1100.00,
        'iva_porcentaje' => 16.00,
        'estado' => 'vendida',
    ]);

    $cliente = Cliente::create([
        'cedula' => 'V-55667788',
        'nombre' => 'Cliente',
        'apellido' => 'B07',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoActivo = MetodoPago::create([
        'nombre' => 'Efectivo Activo B07',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $metodoInactivo = MetodoPago::create([
        'nombre' => 'Transferencia Inactiva B07',
        'es_efectivo' => false,
        'estado' => false,
    ]);

    $cajeroSinPermisoDesc = User::factory()->create(['name' => 'Cajero Sin Descuento B07']);
    $cajeroSinPermisoDesc->assignRole('Operador');
    $cajeroSinPermisoDesc->givePermissionTo(['pos.acceso', 'ventas.crear']);
    $cajeroSinPermisoDesc->empresas()->attach($empresa->id, ['estado' => true]);

    $cajeroConPermisoDesc = User::factory()->create(['name' => 'Cajero Con Descuento B07']);
    $cajeroConPermisoDesc->assignRole('Operador');
    $cajeroConPermisoDesc->givePermissionTo(['pos.acceso', 'ventas.crear', 'ventas.descuentos']);
    $cajeroConPermisoDesc->empresas()->attach($empresa->id, ['estado' => true]);

    $caja1 = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja 1 B07',
        'estado' => true,
    ]);

    $caja2 = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja 2 B07',
        'estado' => true,
    ]);

    $turnoSinDesc = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja1->id,
        'user_id' => $cajeroSinPermisoDesc->id,
        'aperturado_por_id' => $cajeroSinPermisoDesc->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $turnoConDesc = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja2->id,
        'user_id' => $cajeroConPermisoDesc->id,
        'aperturado_por_id' => $cajeroConPermisoDesc->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $armarPayload = fn (array $itemOverrides = [], array $rootOverrides = [], array $pagoOverrides = []) => array_merge([
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'tipo_venta' => 'detal',
        'tasa_cambio' => 40.0000,
        'condicion_pago' => 'contado',
        'items' => [
            array_merge([
                'producto_id' => $producto->id,
                'tipo_item' => 'producto',
                'cantidad' => 1,
                'precio_unitario_usd' => 100.00,
                'almacen_id' => $almacen->id,
            ], $itemOverrides),
        ],
        'pagos' => [
            array_merge([
                'metodo_pago_id' => $metodoActivo->id,
                'monto' => 100.00,
                'moneda' => 'USD',
                'tasa_cambio' => 40.0000,
            ], $pagoOverrides),
        ],
    ], $rootOverrides);

    // 1. Precio manipulado sin permiso ventas.descuentos -> 422 (y excepción en VentaClass)
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['precio_unitario_usd' => 80.00], [], ['monto' => 80.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.precio_unitario_usd']);

    expect(fn () => app(VentaClass::class)->procesarVenta(
        $armarPayload(['precio_unitario_usd' => 80.00], ['caja_turno_id' => $turnoSinDesc->id], ['monto' => 80.00]),
        $empresa->id,
        $cajeroSinPermisoDesc->id
    ))->toThrow(Exception::class, 'ventas.descuentos');

    // 2. Descuento enviado sin permiso ventas.descuentos -> 422
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['descuento_porcentaje' => 10], [], ['monto' => 90.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.descuento_porcentaje']);

    // 3. Con permiso ventas.descuentos, se permite descuento válido, pero se rechaza precio neto < costo ($60) o descuento > 100%
    $this->actingAs($cajeroConPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['descuento_porcentaje' => 50], [], ['monto' => 50.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.precio_unitario_usd']);

    $this->actingAs($cajeroConPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['descuento_porcentaje' => 110], [], ['monto' => 10.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.descuento_porcentaje']);

    $this->actingAs($cajeroConPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['descuento_usd' => 120.00], [], ['monto' => 10.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.descuento_usd']);

    // Descuento válido con permiso (15% sobre $100 = $85 >= costo $60) -> 200 OK
    $this->actingAs($cajeroConPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['descuento_porcentaje' => 15], [], ['monto' => 85.00]))
        ->assertOk()
        ->assertJsonPath('success', true);

    // 4. Validación de precio de lista según tipo_venta ('mayor' exige $85.00 para usuario sin ventas.descuentos)
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['precio_unitario_usd' => 85.00], ['tipo_venta' => 'mayor'], ['monto' => 85.00]))
        ->assertOk()
        ->assertJsonPath('success', true);

    // 5. Validaciones de almacén de ítem, seriales, moto, método de pago y moneda
    // 5a. Almacén de ítem inactivo o de otra empresa -> 422
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['almacen_id' => $almacenInactivo->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.almacen_id']);

    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload(['almacen_id' => $almacenExterno->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.almacen_id']);

    // 5b. Producto que maneja seriales sin producto_serial_id, con serial vendido o serial de otro producto -> 422
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'producto_id' => $productoSerializado->id,
            'precio_unitario_usd' => 500.00,
            'producto_serial_id' => null,
        ], [], ['monto' => 500.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.producto_serial_id']);

    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'producto_id' => $productoSerializado->id,
            'precio_unitario_usd' => 500.00,
            'producto_serial_id' => $serialVendido->id,
        ], [], ['monto' => 500.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.producto_serial_id']);

    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'producto_id' => $productoSerializado->id,
            'precio_unitario_usd' => 500.00,
            'producto_serial_id' => $serialOtroProducto->id,
        ], [], ['monto' => 500.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.producto_serial_id']);

    // Serial disponible y válido -> 200 OK y pasa a vendido
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'producto_id' => $productoSerializado->id,
            'precio_unitario_usd' => 500.00,
            'producto_serial_id' => $serialDisponible->id,
        ], [], ['monto' => 500.00]))
        ->assertOk();

    expect($serialDisponible->fresh()->estado)->toBe('vendido');

    // 5c. Moto no disponible -> 422
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'producto_id' => $motoVendida->id,
            'tipo_item' => 'moto',
            'precio_unitario_usd' => 1200.00,
        ], [], ['monto' => 1200.00]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.producto_id']);

    // 5d. Método de pago inactivo o moneda inválida -> 422
    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([], [], ['metodo_pago_id' => $metodoInactivo->id]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pagos.0.metodo_pago_id']);

    $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([], [], ['moneda' => 'EUR']))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pagos.0.moneda']);

    // 6. Stock insuficiente en Kardex y en Venta: lanza excepción, no recorta a 0 y revierte la transacción sin persistir nada
    // Quedan 3 unidades de $producto (5 iniciales - 2 vendidas arriba)
    $stockRestante = (float) ProductoStockAlmacen::where('producto_id', $producto->id)
        ->where('almacen_id', $almacen->id)
        ->value('cantidad_actual');
    expect($stockRestante)->toBe(3.0);

    $ventasAntes = Venta::where('empresa_id', $empresa->id)->count();
    $kardexAntes = Kardex::where('empresa_id', $empresa->id)->where('producto_id', $producto->id)->count();

    $respSinStock = $this->actingAs($cajeroSinPermisoDesc)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', $armarPayload([
            'cantidad' => 10,
            'precio_unitario_usd' => 100.00,
        ], [], ['monto' => 1000.00]));

    $respSinStock->assertStatus(422);
    expect($respSinStock->json('message'))->toContain('Stock insuficiente');

    // Verificar que la transacción se revirtió por completo y el stock sigue en 3 (no fue recortado a 0)
    $stockDespues = (float) ProductoStockAlmacen::where('producto_id', $producto->id)
        ->where('almacen_id', $almacen->id)
        ->value('cantidad_actual');
    expect($stockDespues)->toBe(3.0)
        ->and(Venta::where('empresa_id', $empresa->id)->count())->toBe($ventasAntes)
        ->and(Kardex::where('empresa_id', $empresa->id)->where('producto_id', $producto->id)->count())->toBe($kardexAntes);
});

test('B-08 correlativos unicos usan tabla secuencias con lock sin escanear ventas e impiden duplicados en BD', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000801-1',
        'nombre' => 'Empresa B08 Secuencias',
        'razon_social' => 'Empresa B08 Secuencias C.A.',
        'direccion' => 'Av. Principal B08',
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-B08',
        'nombre' => 'Almacén B08',
        'es_principal' => true,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-80808001',
        'nombre' => 'Cliente',
        'apellido' => 'B08',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $user = User::factory()->create(['name' => 'V-80808002']);
    $user->assignRole('SuperAdmin');
    $user->empresas()->attach($empresa->id, ['estado' => true]);

    $ventaService = app(VentaClass::class);

    // 1. Lectura sin incrementar para mostrar próximo código en POS
    expect($ventaService->generarCodigo($empresa->id, false))->toBe('VEN-00001')
        ->and($ventaService->generarNumeroControl($empresa->id, false))->toBe('00-00000001')
        ->and(Secuencia::where('empresa_id', $empresa->id)->count())->toBe(0);

    // 2. Incremento atómico con lockForUpdate
    expect($ventaService->generarCodigo($empresa->id, true))->toBe('VEN-00001')
        ->and($ventaService->generarCodigo($empresa->id, true))->toBe('VEN-00002')
        ->and($ventaService->generarNumeroControl($empresa->id, true))->toBe('00-00000001')
        ->and($ventaService->generarNumeroControl($empresa->id, true))->toBe('00-00000002')
        ->and($ventaService->generarCodigoDevolucion($empresa->id, true))->toBe('DEV-00001')
        ->and($ventaService->generarCodigoDevolucion($empresa->id, true))->toBe('DEV-00002');

    // 3. Restricción UNIQUE en BD para (empresa_id, codigo) y (empresa_id, numero_control)
    Venta::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-00099',
        'numero_control' => '00-00000099',
        'tipo_venta' => 'detal',
        'condicion_pago' => 'contado',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'tasa_cambio' => 40.00,
        'subtotal_usd' => 10.00,
        'subtotal_bs' => 400.00,
        'subtotal_neto_usd' => 10.00,
        'subtotal_neto_bs' => 400.00,
        'total_usd' => 10.00,
        'total_bs' => 400.00,
        'monto_pagado_usd' => 10.00,
        'monto_pagado_bs' => 400.00,
        'estado' => 'completada',
    ]);

    expect(fn () => Venta::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'cliente_id' => $cliente->id,
        'user_id' => $user->id,
        'codigo' => 'VEN-00099',
        'numero_control' => '00-00000100',
        'tipo_venta' => 'detal',
        'condicion_pago' => 'contado',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'tasa_cambio' => 40.00,
        'subtotal_usd' => 10.00,
        'subtotal_bs' => 400.00,
        'subtotal_neto_usd' => 10.00,
        'subtotal_neto_bs' => 400.00,
        'total_usd' => 10.00,
        'total_bs' => 400.00,
        'monto_pagado_usd' => 10.00,
        'monto_pagado_bs' => 400.00,
        'estado' => 'completada',
    ]))->toThrow(QueryException::class);
});

test('B-09 y B-10 devolucion proporcional con descuento e IVA y anulacion de factura reintegran stock restante liberan seriales motos anulan CXC crean egreso en caja y muestran sello ANULADA', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99000901-1',
        'nombre' => 'Empresa B09 B10 Test',
        'razon_social' => 'Empresa B09 B10 Test C.A.',
        'direccion' => 'Av. Principal B09',
        'tasa_usd' => 40.0000,
        'maneja_motos' => true,
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Dólares',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_respecto_moneda_base' => 1.0000,
        'es_moneda_base' => true,
        'permite_cobro' => true,
        'permite_vuelto' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-B09',
        'nombre' => 'Almacén Central B09',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-B09',
        'nombre' => 'Categoría B09',
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-90909001',
        'nombre' => 'Cliente',
        'apellido' => 'B09',
        'tipo_cliente' => 'detal',
        'limite_credito' => 5000.00,
        'dias_credito' => 15,
        'estado' => true,
    ]);

    $metodoEfectivo = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Efectivo USD B09',
        'tipo' => 'efectivo',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'V-90909002']);
    $admin->assignRole('Admin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $operadorSinAnular = User::factory()->create(['name' => 'V-90909003']);
    $operadorSinAnular->assignRole('Operador');
    $operadorSinAnular->empresas()->attach($empresa->id, ['estado' => true]);
    $operadorSinAnular->givePermissionTo(['pos.acceso', 'ventas.ver', 'ventas.crear']);

    $vendedor = Vendedor::create([
        'empresa_id' => $empresa->id,
        'documento' => 'V-90909004',
        'nombre' => 'Asesor B09',
        'comision_porcentaje' => 10.00,
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja Principal B09',
        'codigo' => 'CAJ-B09-01',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $admin->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'tasa_cambio_apertura' => 40.0000,
        'monto_apertura_usd' => 100.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-B09-01',
        'nombre' => 'Producto con Descuento e IVA',
        'costo_usd' => 40.00,
        'precio_detal_usd' => 100.00,
        'precio_mayor_usd' => 90.00,
        'aplica_iva' => true,
        'iva_porcentaje' => 16.00,
        'maneja_seriales' => false,
        'estado' => true,
    ]);

    app(KardexClass::class)->registrarMovimiento([
        'empresa_id' => $empresa->id,
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'user_id' => $admin->id,
        'tipo_movimiento' => 'entrada_recepcion',
        'cantidad' => 10,
        'costo_unitario_usd' => 40.00,
        'costo_unitario_bs' => 1600.00,
        'documento_tipo' => 'recepcion',
        'documento_id' => 1,
        'concepto' => 'Stock inicial B09',
    ]);

    // Emitir venta de 2 unidades a $100 c/u (precio incluye IVA 16%) con 10% de descuento:
    // Total con IVA = $180.00, Base neta sin IVA = $155.17, IVA = $24.83, Comisión 10% = $15.52
    // Condición crédito con inicial de $120 en efectivo -> saldo CXC = $60.00
    $respVenta = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'vendedor_id' => $vendedor->id,
            'tipo_venta' => 'detal',
            'condicion_pago' => 'credito',
            'dias_credito' => 15,
            'tasa_cambio' => 40.0000,
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 2,
                    'precio_unitario_usd' => 100.00,
                    'descuento_porcentaje' => 10,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoEfectivo->id,
                    'monto' => 120.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ]);

    $respVenta->assertOk();
    $venta = Venta::with(['detalles', 'cuentaPorCobrar'])->findOrFail($respVenta->json('data.id'));
    $detalle = $venta->detalles->first();

    // Verificar valores iniciales de la factura:
    expect((float) $venta->subtotal_neto_usd)->toBe(155.17)
        ->and((float) $venta->total_usd)->toBe(180.0)
        ->and((float) $venta->comision_monto_usd)->toBe(15.52)
        ->and((float) $venta->cuentaPorCobrar->saldo_pendiente_usd)->toBe(60.0);

    // B-10: Devolver 1 de las 2 unidades con tipo_reembolso = 'efectivo'.
    // Valor neto proporcional de 1 unidad (con su descuento del 10% e IVA) = $180.00 / 2 = $90.00 (NO $100 sin descuento).
    // Como la CXC tiene saldo pendiente de $60.00, primero descuenta los $60.00 de la CXC (quedando CXC en 0 / pagada),
    // y solo reembolsa el excedente ($90.00 - $60.00 = $30.00) en caja_movimientos!
    // Además, la comisión del vendedor se recalcula sobre el subtotal neto sin IVA restante ($155.17 / 2 = $77.585 * 10% = $7.76).
    $respDev = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$venta->id}/devolver", [
            'venta_id' => $venta->id,
            'caja_turno_id' => $turno->id,
            'motivo' => 'Devolución parcial de 1 unidad',
            'tipo_reembolso' => 'efectivo',
            'metodo_pago_id' => $metodoEfectivo->id,
            'items' => [
                [
                    'venta_detalle_id' => $detalle->id,
                    'cantidad' => 1,
                ],
            ],
        ]);

    $respDev->assertOk();
    $devolucion = DevolucionVenta::findOrFail($respDev->json('data.id'));

    expect((float) $devolucion->total_devuelto_usd)->toBe(90.0)
        ->and((int) $devolucion->caja_turno_id)->toBe($turno->id);

    $venta->refresh();
    $cxc = $venta->cuentaPorCobrar->fresh();
    expect($venta->estado)->toBe('devuelta_parcial')
        ->and((float) $venta->comision_monto_usd)->toBe(7.76)
        ->and((float) $cxc->saldo_pendiente_usd)->toBe(0.0)
        ->and($cxc->estado)->toBe('pagada');

    // Movimiento de egreso en caja por el excedente efectivamente reembolsado ($30.00)
    $movDevolucion = CajaMovimiento::where('caja_turno_id', $turno->id)
        ->where('documento_tipo', 'devolucion_venta')
        ->where('documento_id', $devolucion->id)
        ->first();

    expect($movDevolucion)->not->toBeNull()
        ->and($movDevolucion->tipo)->toBe('egreso')
        ->and((float) $movDevolucion->monto_usd)->toBe(30.0);

    // Verificar que CajaClass::calcularReporteTurno NO descuenta dos veces la devolución:
    // Apertura ($100) + Pago venta ($120) - Reembolso devolución ($30.00) = $190.00
    $reporteParcial = app(CajaClass::class)->calcularReporteTurno($turno->id, $empresa->id);
    expect((float) $reporteParcial['efectivo_esperado_usd'])->toBe(190.0);

    // B-09: Operador sin permiso ventas.anular recibe 403 al intentar anular
    $this->actingAs($operadorSinAnular)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$venta->id}/anular", [
            'motivo' => 'Intento sin permiso',
            'caja_turno_id' => $turno->id,
        ])
        ->assertForbidden();

    // Motivo menor a 5 caracteres -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$venta->id}/anular", [
            'motivo' => 'abc',
            'caja_turno_id' => $turno->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['motivo']);

    // Anular la factura (que está en estado devuelta_parcial con 1 unidad aún no devuelta y $90.00 netos cobrados aún en caja)
    $respAnular = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$venta->id}/anular", [
            'motivo' => 'Anulación total del resto de la factura',
            'caja_turno_id' => $turno->id,
        ]);

    $respAnular->assertOk()
        ->assertJsonPath('success', true);

    $venta->refresh();
    $cxc->refresh();

    // Estado pasa a anulada, guarda auditoría, comisión queda en 0, CXC queda anulada con saldo 0
    expect($venta->estado)->toBe('anulada')
        ->and((int) $venta->anulada_por_id)->toBe($admin->id)
        ->and($venta->anulada_at)->not->toBeNull()
        ->and($venta->motivo_anulacion)->toBe('Anulación total del resto de la factura')
        ->and((float) $venta->comision_monto_usd)->toBe(0.0)
        ->and((float) $venta->comision_monto_bs)->toBe(0.0)
        ->and($cxc->estado)->toBe('anulada')
        ->and((float) $cxc->saldo_pendiente_usd)->toBe(0.0);

    // Stock vuelve a 10 (10 iniciales - 2 vendidas + 1 devuelta en B-10 + 1 reintegrada en anulación B-09)
    $stockActual = (float) ProductoStockAlmacen::where('producto_id', $producto->id)
        ->where('almacen_id', $almacen->id)
        ->value('cantidad_actual');
    expect($stockActual)->toBe(10.0);

    // Movimiento de egreso por anulación en caja por el neto restante cobrado ($120 cobrados - $30 ya reembolsados = $90.00)
    $movAnulacion = CajaMovimiento::where('caja_turno_id', $turno->id)
        ->where('documento_tipo', 'anulacion_venta')
        ->where('documento_id', $venta->id)
        ->first();

    expect($movAnulacion)->not->toBeNull()
        ->and($movAnulacion->tipo)->toBe('egreso')
        ->and((float) $movAnulacion->monto_usd)->toBe(90.0);

    // Efectivo esperado en el turno vuelve exactamente al fondo de apertura ($100.00)
    $reporteFinal = app(CajaClass::class)->calcularReporteTurno($turno->id, $empresa->id);
    expect((float) $reporteFinal['efectivo_esperado_usd'])->toBe(100.0);

    // Intentar anular por segunda vez -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$venta->id}/anular", [
            'motivo' => 'Segundo intento de anulación',
            'caja_turno_id' => $turno->id,
        ])
        ->assertStatus(422);

    // Vistas de impresión muestran sello ANULADA
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-ticket/{$venta->id}")
        ->assertOk()
        ->assertSee('ANULADA');

    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->get("/pos/imprimir-carta/{$venta->id}")
        ->assertOk()
        ->assertSee('ANULADA');

    // B-12: Verificar que al anular una factura con serial y moto (con lockForUpdate), ambos vuelven a estado 'disponible'
    $proveedor = Proveedor::create([
        'empresa_id' => $empresa->id,
        'rif' => 'J-90909099-9',
        'nombre' => 'Proveedor Motos B12',
        'razon_social' => 'Proveedor Motos B12 C.A.',
        'estado' => true,
    ]);

    $prodSerial = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PROD-SER-B12',
        'nombre' => 'Laptop Serial B12',
        'costo_usd' => 200.00,
        'precio_detal_usd' => 300.00,
        'precio_mayor_usd' => 280.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'maneja_seriales' => true,
        'estado' => true,
    ]);

    app(KardexClass::class)->registrarMovimiento([
        'empresa_id' => $empresa->id,
        'producto_id' => $prodSerial->id,
        'almacen_id' => $almacen->id,
        'user_id' => $admin->id,
        'tipo_movimiento' => 'entrada_recepcion',
        'cantidad' => 1,
        'costo_unitario_usd' => 200.00,
        'costo_unitario_bs' => 8000.00,
        'documento_tipo' => 'recepcion',
        'documento_id' => 2,
        'concepto' => 'Stock serial B12',
    ]);

    $serialItem = ProductoSerial::create([
        'empresa_id' => $empresa->id,
        'producto_id' => $prodSerial->id,
        'almacen_id' => $almacen->id,
        'numero_serial' => 'SN-B12-UNICO-001',
        'estado' => 'disponible',
    ]);

    $motoItem = Moto::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'proveedor_id' => $proveedor->id,
        'referencia' => 'REF-B12-001',
        'marca' => 'Bera',
        'modelo' => 'SBR 150',
        'anio' => 2026,
        'cilindrada' => '150cc',
        'color' => 'Azul',
        'numero_chasis' => 'CHA-B12-001',
        'numero_motor' => 'MOT-B12-001',
        'numero_niv' => 'NIV-B12-001',
        'certificado_origen' => 'CERT-B12-001',
        'costo_usd' => 800.00,
        'precio_detal_usd' => 1200.00,
        'precio_mayor_usd' => 1100.00,
        'estado' => 'disponible',
    ]);

    $respVentaSerialMoto = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'tipo_venta' => 'detal',
            'condicion_pago' => 'contado',
            'tasa_cambio' => 40.0000,
            'items' => [
                [
                    'producto_id' => $prodSerial->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 300.00,
                    'producto_serial_id' => $serialItem->id,
                    'almacen_id' => $almacen->id,
                ],
                [
                    'producto_id' => $motoItem->id,
                    'tipo_item' => 'moto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 1200.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoEfectivo->id,
                    'monto' => 1500.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ]);

    $respVentaSerialMoto->assertOk();
    $ventaSMId = $respVentaSerialMoto->json('data.id');

    expect($serialItem->fresh()->estado)->toBe('vendido')
        ->and($motoItem->fresh()->estado)->toBe('vendida');

    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$ventaSMId}/anular", [
            'motivo' => 'Anulación de venta con serial y moto',
            'caja_turno_id' => $turno->id,
        ])
        ->assertOk();

    expect($serialItem->fresh()->estado)->toBe('disponible')
        ->and($motoItem->fresh()->estado)->toBe('disponible');
});

test('B-11 abono CXC exige turno abierto rechaza sobrepago con tolerancia 0.01 y actualiza saldos en la factura asociada', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99001101-1',
        'nombre' => 'Empresa B11 CXC',
        'razon_social' => 'Empresa B11 CXC C.A.',
        'direccion' => 'Av. Principal B11',
        'tasa_usd' => 40.0000,
        'maneja_motos' => false,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'ALM-B11',
        'nombre' => 'Almacén B11',
        'es_principal' => true,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-11001101',
        'nombre' => 'Cliente',
        'apellido' => 'B11',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoPago = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Efectivo USD B11',
        'tipo' => 'efectivo',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'V-11001102']);
    $admin->assignRole('SuperAdmin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $venta = Venta::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'cliente_id' => $cliente->id,
        'user_id' => $admin->id,
        'codigo' => 'VEN-B11-01',
        'numero_control' => '00-00001101',
        'tipo_venta' => 'detal',
        'condicion_pago' => 'credito',
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'tasa_cambio' => 40.0000,
        'subtotal_usd' => 100.00,
        'subtotal_bs' => 4000.00,
        'subtotal_neto_usd' => 100.00,
        'subtotal_neto_bs' => 4000.00,
        'total_usd' => 100.00,
        'total_bs' => 4000.00,
        'monto_pagado_usd' => 20.00,
        'monto_pagado_bs' => 800.00,
        'saldo_pendiente_usd' => 80.00,
        'saldo_pendiente_bs' => 3200.00,
        'estado' => 'completada',
    ]);

    $cxc = CuentaPorCobrar::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'venta_id' => $venta->id,
        'numero_factura' => $venta->codigo,
        'fecha_emision' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(15)->toDateString(),
        'monto_total_usd' => 100.00,
        'monto_total_bs' => 4000.00,
        'monto_pagado_usd' => 20.00,
        'monto_pagado_bs' => 800.00,
        'saldo_pendiente_usd' => 80.00,
        'saldo_pendiente_bs' => 3200.00,
        'estado' => 'parcial',
    ]);

    // 1. Sin turno abierto -> 422 (y excepción en el servicio)
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-factura', [
            'cuenta_id' => $cxc->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 30.00,
            'tasa_cambio' => 40.0000,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['caja_turno_id']);

    expect(fn () => app(CuentaPorCobrarClass::class)->abonarFacturaEspecifica($cxc->id, [
        'metodo_pago_id' => $metodoPago->id,
        'moneda' => 'USD',
        'monto' => 30.00,
        'tasa_cambio' => 40.0000,
    ], $admin->id, $empresa->id))->toThrow(Exception::class, 'turno de caja abierto');

    // 2. Abrir turno de caja
    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B11',
        'codigo' => 'CAJ-B11-01',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $admin->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'tasa_cambio_apertura' => 40.0000,
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    // 3. Sobrepago mayor a saldo + 0.01 (ej. $80.03 sobre $80.00) -> rechazado
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-factura', [
            'cuenta_id' => $cxc->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 80.03,
            'tasa_cambio' => 40.0000,
        ])
        ->assertStatus(422);

    // 4. Abono válido de $50 -> actualiza tanto CuentaPorCobrar como Venta asociada y guarda caja_turno_id no nulo
    $respAbono = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/cuentas-por-cobrar/abonar-factura', [
            'cuenta_id' => $cxc->id,
            'metodo_pago_id' => $metodoPago->id,
            'moneda' => 'USD',
            'monto' => 50.00,
            'tasa_cambio' => 40.0000,
        ]);

    $respAbono->assertOk();

    $cxc->refresh();
    $venta->refresh();
    $abono = CuentaPorCobrarAbono::findOrFail($respAbono->json('data.abono_id'));

    expect((int) $abono->caja_turno_id)->toBe($turno->id)
        ->and((float) $cxc->monto_pagado_usd)->toBe(70.0)
        ->and((float) $cxc->saldo_pendiente_usd)->toBe(30.0)
        ->and((float) $venta->monto_pagado_usd)->toBe(70.0)
        ->and((float) $venta->saldo_pendiente_usd)->toBe(30.0);
});

test('B-13 FK caja_turnos.user_id restrict y UsuarioClass impiden borrar usuarios con turnos de caja', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99001301-1',
        'nombre' => 'Empresa B13 Turnos',
        'razon_social' => 'Empresa B13 Turnos C.A.',
        'direccion' => 'Av. Principal B13',
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'V-13001301']);
    $admin->assignRole('SuperAdmin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $cajero = User::factory()->create(['name' => 'V-13001302']);
    $cajero->assignRole('Operador');
    $cajero->empresas()->attach($empresa->id, ['estado' => true]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Caja B13',
        'codigo' => 'CAJ-B13-01',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $cajero->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 20.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'cerrada',
    ]);

    // 1. Eliminar vía endpoint / UsuarioClass::eliminar -> 422 controlado
    $respDelete = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->deleteJson("/usuarios/{$cajero->id}");

    $respDelete->assertStatus(422)
        ->assertJsonPath('success', false);

    expect(fn () => app(UsuarioClass::class)->eliminar($cajero->id, $admin))
        ->toThrow(Exception::class, 'turnos de caja');

    // 2. Intentar borrar físicamente el modelo User o directamente en BD -> bloqueado y el turno permanece intacto
    expect(fn () => $cajero->delete())->toThrow(Exception::class, 'turnos de caja');
    expect(fn () => DB::table('users')->where('id', $cajero->id)->delete())
        ->toThrow(QueryException::class);

    expect(CajaTurno::find($turno->id))->not->toBeNull();
});

test('B-20 y B-21 matriz de permisos expone pos.preventa y vendedores.liquidar y permite asignar rol Vendedor con permisos base', function () {
    foreach (['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'usuarios.permisos'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    $empresa = Empresa::create([
        'rif' => 'J-99002001-1',
        'nombre' => 'Empresa B20 B21',
        'razon_social' => 'Empresa B20 B21 C.A.',
        'direccion' => 'Av. Principal B20',
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['usuarios.ver', 'usuarios.crear', 'usuarios.editar', 'pos.acceso', 'pos.preventa', 'vendedores.ver', 'vendedores.liquidar']);

    $admin = User::factory()->create(['name' => 'V-20002001']);
    $admin->assignRole('Admin');
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $usuarioClass = app(UsuarioClass::class);
    $catalogos = $usuarioClass->obtenerRolesYEmpresas($admin);

    // 1. El selector de roles para Admin incluye Vendedor junto a Admin y Operador
    $nombresRoles = collect($catalogos['roles'])->pluck('name')->all();
    expect($nombresRoles)->toContain('Admin')
        ->and($nombresRoles)->toContain('Operador')
        ->and($nombresRoles)->toContain('Vendedor')
        ->and($nombresRoles)->not->toContain('SuperAdmin');

    // 2. Matriz de permisos incluye pos.preventa y vendedores.liquidar, y no existe cajas.administrar_todas
    $todosPermisosMatriz = collect($catalogos['permisos_modulos'])
        ->pluck('permisos')
        ->flatten(1)
        ->pluck('name')
        ->all();

    expect($todosPermisosMatriz)->toContain('pos.preventa')
        ->and($todosPermisosMatriz)->toContain('vendedores.liquidar')
        ->and($todosPermisosMatriz)->not->toContain('cajas.administrar_todas');

    // 3. normalizarPermisos agrega pos.acceso desde pos.preventa y vendedores.ver desde vendedores.liquidar
    expect($usuarioClass->normalizarPermisos(['pos.preventa']))
        ->toEqualCanonicalizing(['pos.preventa', 'pos.acceso']);
    expect($usuarioClass->normalizarPermisos(['vendedores.liquidar']))
        ->toEqualCanonicalizing(['vendedores.liquidar', 'vendedores.ver']);

    // 4. Crear usuario con rol Vendedor desde endpoint /usuarios sin permisos manuales asigna sus permisos base
    $respCrear = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/usuarios', [
            'name' => 'V-20002002',
            'nombre' => 'Valeria',
            'apellido' => 'Asesora',
            'email' => 'valeria.b21@empresa.com',
            'password' => 'secret123',
            'rol' => 'Vendedor',
            'empresas' => [$empresa->id],
        ]);

    $respCrear->assertOk()
        ->assertJsonPath('success', true);

    $nuevoVendedorUser = User::where('name', 'V-20002002')->firstOrFail();
    expect($nuevoVendedorUser->hasRole('Vendedor'))->toBeTrue()
        ->and($nuevoVendedorUser->getDirectPermissions()->pluck('name')->all())
        ->toEqualCanonicalizing(['pos.acceso', 'pos.preventa', 'reportes.vendedores']);
});

test('B-22 y B-23 vendedor puro opera preventas propias sin facturar y cajero preserva vendedor_id fijo al recuperar preventa', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99002201-1',
        'nombre' => 'Empresa B22 B23 Preventas',
        'razon_social' => 'Empresa B22 B23 Preventas C.A.',
        'direccion' => 'Av. Principal B22',
        'maneja_vendedores' => true,
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
        'codigo' => 'ALM-B22',
        'nombre' => 'Almacén B22',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-B22',
        'nombre' => 'Categoría B22',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B22-01',
        'nombre' => 'Producto Preventa B22',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 30.00,
        'precio_detal_usd' => 50.00,
        'precio_mayorista_usd' => 45.00,
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
        'empresa_id' => $empresa->id,
        'cedula' => 'V-22002200',
        'nombre' => 'Cliente',
        'apellido' => 'Preventa',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoPago = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Efectivo USD B22',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $vendedorUser1 = User::factory()->create(['name' => 'V-22002201']);
    $vendedorUser1->assignRole('Vendedor');
    $vendedorUser1->givePermissionTo(['pos.acceso', 'pos.preventa', 'reportes.vendedores']);
    $vendedorUser1->empresas()->attach($empresa->id, ['estado' => true]);

    $vendedorUser2 = User::factory()->create(['name' => 'V-22002202']);
    $vendedorUser2->assignRole('Vendedor');
    $vendedorUser2->givePermissionTo(['pos.acceso', 'pos.preventa', 'reportes.vendedores']);
    $vendedorUser2->empresas()->attach($empresa->id, ['estado' => true]);

    $vendedor1 = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $vendedorUser1->id,
        'tipo_documento' => 'V',
        'documento' => '22002201',
        'nombre' => 'Asesor Uno',
        'comision_porcentaje' => 10.00,
        'estado' => true,
    ]);

    $vendedor2 = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $vendedorUser2->id,
        'tipo_documento' => 'V',
        'documento' => '22002202',
        'nombre' => 'Asesor Dos',
        'comision_porcentaje' => 5.00,
        'estado' => true,
    ]);

    $cajeroUser = User::factory()->create(['name' => 'V-22002203']);
    $cajeroUser->assignRole('Operador');
    $cajeroUser->givePermissionTo(['pos.acceso', 'ventas.crear', 'ventas.ver', 'cajas.ver']);
    $cajeroUser->empresas()->attach($empresa->id, ['estado' => true]);

    $adminUser = User::factory()->create(['name' => 'V-22002204']);
    $adminUser->assignRole('Admin');
    $adminUser->givePermissionTo(['pos.acceso', 'pos.preventa', 'ventas.crear', 'ventas.ver', 'cajas.ver', 'cajas.aperturar']);
    $adminUser->empresas()->attach($empresa->id, ['estado' => true]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B22',
        'codigo' => 'CAJ-B22-01',
        'estado' => true,
    ]);

    $turnoCajero = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $cajeroUser->id,
        'aperturado_por_id' => $adminUser->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 100.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    // 1. Vendedor 1 entra a /pos/datos -> es_vendedor = true, es_cajero = false, puede_operar = true
    $respDatosV1 = $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/datos');

    $respDatosV1->assertOk()
        ->assertJsonPath('data.es_vendedor', true)
        ->assertJsonPath('data.es_cajero', false)
        ->assertJsonPath('data.es_admin', false)
        ->assertJsonPath('data.puede_operar', true)
        ->assertJsonPath('data.vendedor_asociado.id', $vendedor1->id);

    // 2. Vendedor 1 guarda preventa (incluso intentando enviar vendedor_id de Vendedor 2) -> servidor fuerza vendedor1->id
    $respEspera1 = $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/en-espera/guardar', [
            'cliente_id' => $cliente->id,
            'vendedor_id' => $vendedor2->id,
            'tipo_venta' => 'detal',
            'total_usd' => 100.00,
            'total_bs' => 4000.00,
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 2,
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
        ]);

    $respEspera1->assertOk();
    $espera1Id = (int) $respEspera1->json('data.id');
    expect((int) $respEspera1->json('data.datos_json.vendedor_id'))->toBe($vendedor1->id);

    // 3. Vendedor 2 guarda su propia preventa
    $respEspera2 = $this->actingAs($vendedorUser2)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/en-espera/guardar', [
            'cliente_id' => $cliente->id,
            'tipo_venta' => 'detal',
            'total_usd' => 50.00,
            'total_bs' => 2000.00,
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
        ]);

    $respEspera2->assertOk();
    $espera2Id = (int) $respEspera2->json('data.id');
    expect((int) $respEspera2->json('data.datos_json.vendedor_id'))->toBe($vendedor2->id);

    // 4. Vendedor 1 lista preventas -> solo ve la suya (1), y no puede recuperar ni eliminar la de Vendedor 2 (403)
    $respListaV1 = $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/en-espera/lista');

    $respListaV1->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $espera1Id);

    $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/pos/en-espera/{$espera2Id}/recuperar")
        ->assertForbidden();

    $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->deleteJson("/pos/en-espera/{$espera2Id}")
        ->assertForbidden();

    // 5. Vendedor 1 intenta facturar directamente en POST /pos/guardar -> 403 Forbidden
    $this->actingAs($vendedorUser1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
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
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => 50.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ])
        ->assertForbidden();

    // 6. Cajero lista preventas -> ve todas las de la empresa (2)
    $this->actingAs($cajeroUser)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/pos/en-espera/lista')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    // 7. Cajero recupera y factura espera1 intentando cambiar vendedor_id a vendedor2 (o quitarlo) -> servidor preserva vendedor1
    $respVentaCajero = $this->actingAs($cajeroUser)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turnoCajero->id,
            'venta_en_espera_id' => $espera1Id,
            'vendedor_id' => $vendedor2->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 40.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 2,
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => 100.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ]);

    $respVentaCajero->assertOk();
    $ventaDePreventa1 = Venta::findOrFail($respVentaCajero->json('data.id'));
    expect((int) $ventaDePreventa1->vendedor_id)->toBe($vendedor1->id)
        ->and((float) $ventaDePreventa1->comision_porcentaje)->toBe(10.00)
        ->and((float) $ventaDePreventa1->comision_monto_usd)->toBe(10.00)
        ->and($ventaDePreventa1->comision_estado)->toBe('pendiente');

    // 8. Admin sí puede cambiar el vendedor al facturar espera2
    $respVentaAdmin = $this->actingAs($adminUser)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turnoCajero->id,
            'venta_en_espera_id' => $espera2Id,
            'vendedor_id' => $vendedor1->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 40.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 50.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodoPago->id,
                    'monto' => 50.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ]);

    $respVentaAdmin->assertOk();
    $ventaDePreventa2 = Venta::findOrFail($respVentaAdmin->json('data.id'));
    expect((int) $ventaDePreventa2->vendedor_id)->toBe($vendedor1->id);
});

test('B-24 unique(empresa_id, user_id) en vendedores y bloqueo real cuando maneja_vendedores es false', function () {
    $empresaConVend = Empresa::create([
        'rif' => 'J-99002401-1',
        'nombre' => 'Empresa B24 Con Vendedores',
        'razon_social' => 'Empresa B24 Con Vendedores C.A.',
        'direccion' => 'Av. Principal B24',
        'maneja_vendedores' => true,
        'estado' => true,
    ]);

    $empresaSinVend = Empresa::create([
        'rif' => 'J-99002402-2',
        'nombre' => 'Empresa B24 Sin Vendedores',
        'razon_social' => 'Empresa B24 Sin Vendedores C.A.',
        'direccion' => 'Av. Secundaria B24',
        'maneja_vendedores' => false,
        'estado' => true,
    ]);

    $superAdmin = User::factory()->create(['name' => 'V-24002401']);
    $superAdmin->assignRole('SuperAdmin');
    $superAdmin->empresas()->attach([$empresaConVend->id, $empresaSinVend->id]);

    $usuarioEmpresa = User::factory()->create(['name' => 'V-24002402', 'estado' => true]);
    $usuarioEmpresa->assignRole('Vendedor');
    $usuarioEmpresa->empresas()->attach($empresaConVend->id, ['estado' => true]);

    $usuarioOtraEmpresa = User::factory()->create(['name' => 'V-24002403', 'estado' => true]);
    $usuarioOtraEmpresa->assignRole('Vendedor');
    $usuarioOtraEmpresa->empresas()->attach($empresaSinVend->id, ['estado' => true]);

    // 1. No permite vincular a un SuperAdmin como vendedor
    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaConVend->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '24002401',
            'nombre' => 'Intento SuperAdmin Vendedor',
            'user_id' => $superAdmin->id,
            'comision_porcentaje' => 5.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    // 2. No permite vincular a un usuario de otra empresa
    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaConVend->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '24002403',
            'nombre' => 'Intento Otra Empresa',
            'user_id' => $usuarioOtraEmpresa->id,
            'comision_porcentaje' => 5.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    // 3. Permite vincular usuario válido una vez, pero rechaza duplicarlo en otro vendedor de la misma empresa (Request y BD)
    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaConVend->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '24002402',
            'nombre' => 'Vendedor Vinculado 1',
            'user_id' => $usuarioEmpresa->id,
            'comision_porcentaje' => 5.00,
        ])
        ->assertOk();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaConVend->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '24002499',
            'nombre' => 'Vendedor Vinculado Duplicado',
            'user_id' => $usuarioEmpresa->id,
            'comision_porcentaje' => 5.00,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['user_id']);

    expect(fn () => Vendedor::create([
        'empresa_id' => $empresaConVend->id,
        'user_id' => $usuarioEmpresa->id,
        'tipo_documento' => 'V',
        'documento' => '24002498',
        'nombre' => 'Duplicado Directo BD',
        'comision_porcentaje' => 3.00,
        'estado' => true,
    ]))->toThrow(QueryException::class);

    // 4. Cuando maneja_vendedores = false: rutas /vendedores* y /reportes/vendedores* abortan con 403
    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->get('/vendedores')
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->getJson('/vendedores/activos')
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->postJson('/vendedores', [
            'tipo_documento' => 'V',
            'documento' => '24999999',
            'nombre' => 'Bloqueado',
            'comision_porcentaje' => 5.00,
        ])
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->get('/reportes/vendedores')
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->getJson('/reportes/vendedores/kpis')
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->getJson('/reportes/vendedores/json')
        ->assertForbidden();

    // 5. En empresa con maneja_vendedores = false, /pos/datos devuelve vendedores = [] y procesarVenta ignora vendedor_id
    $vendedorFantasma = Vendedor::create([
        'empresa_id' => $empresaSinVend->id,
        'tipo_documento' => 'V',
        'documento' => '24888888',
        'nombre' => 'Vendedor Preexistente Inactivo Modulo',
        'comision_porcentaje' => 12.00,
        'estado' => true,
    ]);

    EmpresaMoneda::create([
        'empresa_id' => $empresaSinVend->id,
        'nombre' => 'Dólares Americanos',
        'codigo' => 'USD',
        'simbolo' => '$',
        'tasa_cambio' => 40.0000,
        'es_principal' => true,
        'estado' => true,
    ]);

    $almacen = Almacen::create([
        'empresa_id' => $empresaSinVend->id,
        'codigo' => 'ALM-B24-SIN',
        'nombre' => 'Almacén Sin Vend',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresaSinVend->id,
        'codigo' => 'CAT-B24-SIN',
        'nombre' => 'Cat Sin Vend',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresaSinVend->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B24-SIN',
        'nombre' => 'Producto Sin Vendedor',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 10.00,
        'precio_detal_usd' => 20.00,
        'precio_mayorista_usd' => 18.00,
        'aplica_iva' => false,
        'iva_porcentaje' => 0,
        'estado' => true,
    ]);

    ProductoStockAlmacen::create([
        'producto_id' => $producto->id,
        'almacen_id' => $almacen->id,
        'cantidad_actual' => 20,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresaSinVend->id,
        'cedula' => 'V-24888800',
        'nombre' => 'Cliente',
        'apellido' => 'SinVend',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodo = MetodoPago::create([
        'empresa_id' => $empresaSinVend->id,
        'nombre' => 'Efectivo B24',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresaSinVend->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B24',
        'codigo' => 'CAJ-B24',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresaSinVend->id,
        'caja_id' => $caja->id,
        'user_id' => $superAdmin->id,
        'aperturado_por_id' => $superAdmin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->getJson('/pos/datos')
        ->assertOk()
        ->assertJsonPath('data.maneja_vendedores', false)
        ->assertJsonCount(0, 'data.vendedores');

    $respVenta = $this->actingAs($superAdmin)
        ->withSession(['empresa_activa_id' => $empresaSinVend->id])
        ->postJson('/pos/guardar', [
            'cliente_id' => $cliente->id,
            'almacen_id' => $almacen->id,
            'caja_turno_id' => $turno->id,
            'vendedor_id' => $vendedorFantasma->id,
            'tipo_venta' => 'detal',
            'tasa_cambio' => 40.0000,
            'condicion_pago' => 'contado',
            'items' => [
                [
                    'producto_id' => $producto->id,
                    'tipo_item' => 'producto',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 20.00,
                    'almacen_id' => $almacen->id,
                ],
            ],
            'pagos' => [
                [
                    'metodo_pago_id' => $metodo->id,
                    'monto' => 20.00,
                    'moneda' => 'USD',
                    'tasa_cambio' => 40.0000,
                ],
            ],
        ]);

    $respVenta->assertOk();
    $ventaSinVend = Venta::findOrFail($respVenta->json('data.id'));
    expect($ventaSinVend->vendedor_id)->toBeNull()
        ->and((float) $ventaSinVend->comision_porcentaje)->toBe(0.0)
        ->and((float) $ventaSinVend->comision_monto_usd)->toBe(0.0)
        ->and($ventaSinVend->comision_estado)->toBe('sin_comision');
});

test('B-25 comision_estado y liquidacion de comisiones con bloqueo transaccional y egreso opcional de caja', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99002501-1',
        'nombre' => 'Empresa B25 Liquidaciones',
        'razon_social' => 'Empresa B25 Liquidaciones C.A.',
        'direccion' => 'Av. Principal B25',
        'maneja_vendedores' => true,
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
        'codigo' => 'ALM-B25',
        'nombre' => 'Almacén B25',
        'es_principal' => true,
        'estado' => true,
    ]);

    $categoria = Categoria::create([
        'empresa_id' => $empresa->id,
        'codigo' => 'CAT-B25',
        'nombre' => 'Categoría B25',
        'estado' => true,
    ]);

    $producto = Producto::create([
        'empresa_id' => $empresa->id,
        'categoria_id' => $categoria->id,
        'codigo_interno' => 'PRD-B25-01',
        'nombre' => 'Producto Comisión B25',
        'unidad_medida' => 'UND',
        'precio_costo_usd' => 50.00,
        'precio_detal_usd' => 100.00,
        'precio_mayorista_usd' => 90.00,
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
        'empresa_id' => $empresa->id,
        'cedula' => 'V-25002500',
        'nombre' => 'Cliente',
        'apellido' => 'Comision',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $metodoEfectivo = MetodoPago::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Efectivo USD B25',
        'es_efectivo' => true,
        'estado' => true,
    ]);

    $admin = User::factory()->create(['name' => 'V-25002501']);
    $admin->assignRole('Admin');
    $admin->givePermissionTo([
        'pos.acceso', 'ventas.crear', 'ventas.ver', 'ventas.anular',
        'vendedores.ver', 'vendedores.liquidar', 'cajas.ver', 'cajas.aperturar',
    ]);
    $admin->empresas()->attach($empresa->id, ['estado' => true]);

    $operadorSinLiquidar = User::factory()->create(['name' => 'V-25002502']);
    $operadorSinLiquidar->assignRole('Operador');
    $operadorSinLiquidar->givePermissionTo(['vendedores.ver']);
    $operadorSinLiquidar->empresas()->attach($empresa->id, ['estado' => true]);

    $vendedor = Vendedor::create([
        'empresa_id' => $empresa->id,
        'tipo_documento' => 'V',
        'documento' => '25002503',
        'nombre' => 'Asesor Liquidación B25',
        'comision_porcentaje' => 10.00,
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B25',
        'codigo' => 'CAJ-B25-01',
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $admin->id,
        'aperturado_por_id' => $admin->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 200.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    $crearVenta = function (int $cantidad, ?int $vendId) use ($admin, $empresa, $cliente, $almacen, $turno, $producto, $metodoEfectivo): Venta {
        $total = $cantidad * 100.00;
        $resp = $this->actingAs($admin)
            ->withSession(['empresa_activa_id' => $empresa->id])
            ->postJson('/pos/guardar', [
                'cliente_id' => $cliente->id,
                'almacen_id' => $almacen->id,
                'caja_turno_id' => $turno->id,
                'vendedor_id' => $vendId,
                'tipo_venta' => 'detal',
                'tasa_cambio' => 40.0000,
                'condicion_pago' => 'contado',
                'items' => [
                    [
                        'producto_id' => $producto->id,
                        'tipo_item' => 'producto',
                        'cantidad' => $cantidad,
                        'precio_unitario_usd' => 100.00,
                        'almacen_id' => $almacen->id,
                    ],
                ],
                'pagos' => [
                    [
                        'metodo_pago_id' => $metodoEfectivo->id,
                        'monto' => $total,
                        'moneda' => 'USD',
                        'tasa_cambio' => 40.0000,
                    ],
                ],
            ]);
        $resp->assertOk();

        return Venta::findOrFail($resp->json('data.id'));
    };

    // 1. Venta sin vendedor -> sin_comision; Ventas con vendedor -> pendiente
    $ventaSinVend = $crearVenta(1, null);
    expect($ventaSinVend->comision_estado)->toBe('sin_comision');

    $venta1 = $crearVenta(1, $vendedor->id); // Comisión $10.00
    $venta2 = $crearVenta(2, $vendedor->id); // Comisión $20.00
    $ventaAnulable = $crearVenta(1, $vendedor->id); // Comisión $10.00 que será anulada

    expect($venta1->comision_estado)->toBe('pendiente')
        ->and($venta2->comision_estado)->toBe('pendiente')
        ->and($ventaAnulable->comision_estado)->toBe('pendiente');

    // 2. Anular $ventaAnulable -> pasa a comision_estado = 'sin_comision'
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/facturas/{$ventaAnulable->id}/anular", [
            'motivo' => 'Anulación de prueba B25',
            'caja_turno_id' => $turno->id,
        ])
        ->assertOk();

    expect($ventaAnulable->fresh()->comision_estado)->toBe('sin_comision');

    // 3. GET /vendedores/{id}/comisiones-pendientes lista solo venta1 y venta2 ($30.00 total)
    $respPendientes = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/vendedores/{$vendedor->id}/comisiones-pendientes");

    $respPendientes->assertOk()
        ->assertJsonPath('data.cantidad_ventas', 2)
        ->assertJsonPath('data.total_comision_usd', 30);

    // 4. Operador sin permiso vendedores.liquidar recibe 403
    $this->actingAs($operadorSinLiquidar)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/vendedores/{$vendedor->id}/liquidar", [
            'venta_ids' => [$venta1->id],
            'registrar_egreso_caja' => false,
        ])
        ->assertForbidden();

    // 5. Liquidar venta1 y venta2 con registrar_egreso_caja = true -> crea LiquidacionComision, marca ventas como 'pagada' y registra CajaMovimiento egreso
    $respLiquidar = $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/vendedores/{$vendedor->id}/liquidar", [
            'venta_ids' => [$venta1->id, $venta2->id],
            'registrar_egreso_caja' => true,
            'caja_turno_id' => $turno->id,
            'metodo_pago_id' => $metodoEfectivo->id,
            'observaciones' => 'Pago quincenal de comisiones',
        ]);

    $respLiquidar->assertOk()
        ->assertJsonPath('success', true);

    $liquidacion = LiquidacionComision::findOrFail($respLiquidar->json('data.id'));
    expect($liquidacion->codigo)->toStartWith('LIQ-')
        ->and($liquidacion->cantidad_ventas)->toBe(2)
        ->and((float) $liquidacion->total_comision_usd)->toBe(30.00)
        ->and((float) $liquidacion->total_comision_bs)->toBe(1200.00)
        ->and($liquidacion->pagado_por_caja)->toBeTrue();

    expect($venta1->fresh()->comision_estado)->toBe('pagada')
        ->and((int) $venta1->fresh()->liquidacion_comision_id)->toBe($liquidacion->id)
        ->and($venta2->fresh()->comision_estado)->toBe('pagada')
        ->and((int) $venta2->fresh()->liquidacion_comision_id)->toBe($liquidacion->id);

    $movEgreso = CajaMovimiento::where('caja_turno_id', $turno->id)
        ->where('documento_tipo', 'liquidacion_comision')
        ->where('documento_id', $liquidacion->id)
        ->first();

    expect($movEgreso)->not->toBeNull()
        ->and($movEgreso->tipo)->toBe('egreso')
        ->and((float) $movEgreso->monto_usd)->toBe(30.00);

    // 6. Intentar liquidar por segunda vez la misma factura -> 422
    $this->actingAs($admin)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->postJson("/vendedores/{$vendedor->id}/liquidar", [
            'venta_ids' => [$venta1->id],
            'registrar_egreso_caja' => false,
        ])
        ->assertStatus(422);
});

test('B-26 vendedor vinculado no admin solo puede ver sus propias ventas y comisiones en todos los endpoints de reportes', function () {
    $empresa = Empresa::create([
        'rif' => 'J-99002601-1',
        'nombre' => 'Empresa B26 Privacidad Vendedor',
        'razon_social' => 'Empresa B26 Privacidad Vendedor C.A.',
        'direccion' => 'Av. Principal B26',
        'maneja_vendedores' => true,
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
        'codigo' => 'ALM-B26',
        'nombre' => 'Almacén B26',
        'es_principal' => true,
        'estado' => true,
    ]);

    $cliente = Cliente::create([
        'empresa_id' => $empresa->id,
        'cedula' => 'V-26002600',
        'nombre' => 'Cliente',
        'apellido' => 'Reporte',
        'tipo_cliente' => 'detal',
        'estado' => true,
    ]);

    $caja = Caja::create([
        'empresa_id' => $empresa->id,
        'almacen_id' => $almacen->id,
        'nombre' => 'Caja B26',
        'codigo' => 'CAJ-B26',
        'estado' => true,
    ]);

    $userVendedor1 = User::factory()->create(['name' => 'V-26002601']);
    $userVendedor1->assignRole('Vendedor');
    // Incluso si tiene reportes.ver además de reportes.vendedores, no es Admin/SuperAdmin
    $userVendedor1->givePermissionTo(['pos.acceso', 'pos.preventa', 'reportes.vendedores', 'reportes.ver']);
    $userVendedor1->empresas()->attach($empresa->id, ['estado' => true]);

    $userVendedor2 = User::factory()->create(['name' => 'V-26002602']);
    $userVendedor2->assignRole('Vendedor');
    $userVendedor2->givePermissionTo(['pos.acceso', 'pos.preventa', 'reportes.vendedores']);
    $userVendedor2->empresas()->attach($empresa->id, ['estado' => true]);

    $vendedor1 = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $userVendedor1->id,
        'tipo_documento' => 'V',
        'documento' => '26002601',
        'nombre' => 'Vendedor Propio Uno',
        'comision_porcentaje' => 10.00,
        'estado' => true,
    ]);

    $vendedor2 = Vendedor::create([
        'empresa_id' => $empresa->id,
        'user_id' => $userVendedor2->id,
        'tipo_documento' => 'V',
        'documento' => '26002602',
        'nombre' => 'Vendedor Ajeno Dos',
        'comision_porcentaje' => 10.00,
        'estado' => true,
    ]);

    $turno = CajaTurno::create([
        'empresa_id' => $empresa->id,
        'caja_id' => $caja->id,
        'user_id' => $userVendedor1->id,
        'aperturado_por_id' => $userVendedor1->id,
        'fecha_apertura' => now()->toDateString(),
        'hora_apertura' => now()->toTimeString(),
        'monto_apertura_usd' => 50.00,
        'monto_apertura_bs' => 0.00,
        'estado' => 'abierta',
    ]);

    // Crear 1 venta de $100 para Vendedor 1 y 1 venta de $500 para Vendedor 2
    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'caja_id' => $caja->id,
        'caja_turno_id' => $turno->id,
        'vendedor_id' => $vendedor1->id,
        'user_id' => $userVendedor1->id,
        'codigo' => 'VEN-B26-01',
        'numero_control' => '00-00092601',
        'tipo_venta' => 'detal',
        'moneda' => 'USD',
        'tasa_cambio' => 40.0000,
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'monto_bruto_usd' => 100.00,
        'monto_bruto_bs' => 4000.00,
        'subtotal_neto_usd' => 100.00,
        'subtotal_neto_bs' => 4000.00,
        'total_usd' => 100.00,
        'total_bs' => 4000.00,
        'condicion_pago' => 'contado',
        'monto_pagado_usd' => 100.00,
        'monto_pagado_bs' => 4000.00,
        'comision_porcentaje' => 10.00,
        'comision_monto_usd' => 10.00,
        'comision_monto_bs' => 400.00,
        'comision_estado' => 'pendiente',
        'estado' => 'completada',
    ]);

    Venta::create([
        'empresa_id' => $empresa->id,
        'cliente_id' => $cliente->id,
        'almacen_id' => $almacen->id,
        'caja_id' => $caja->id,
        'caja_turno_id' => $turno->id,
        'vendedor_id' => $vendedor2->id,
        'user_id' => $userVendedor2->id,
        'codigo' => 'VEN-B26-02',
        'numero_control' => '00-00092602',
        'tipo_venta' => 'detal',
        'moneda' => 'USD',
        'tasa_cambio' => 40.0000,
        'fecha_emision' => now()->toDateString(),
        'hora_emision' => now()->toTimeString(),
        'monto_bruto_usd' => 500.00,
        'monto_bruto_bs' => 20000.00,
        'subtotal_neto_usd' => 500.00,
        'subtotal_neto_bs' => 20000.00,
        'total_usd' => 500.00,
        'total_bs' => 20000.00,
        'condicion_pago' => 'contado',
        'monto_pagado_usd' => 500.00,
        'monto_pagado_bs' => 20000.00,
        'comision_porcentaje' => 10.00,
        'comision_monto_usd' => 50.00,
        'comision_monto_bs' => 2000.00,
        'comision_estado' => 'pendiente',
        'estado' => 'completada',
    ]);

    // 1. ReporteVendedorController::catalogos solo devuelve a Vendedor 1
    $this->actingAs($userVendedor1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson('/reportes/vendedores/catalogos')
        ->assertOk()
        ->assertJsonCount(1, 'data.vendedores')
        ->assertJsonPath('data.vendedores.0.id', $vendedor1->id);

    // 2. ReporteVendedorController::kpis (incluso pasando ?vendedor_id=vendedor2) fuerza vendedor1 ($100 ventas, $10 comisión)
    $this->actingAs($userVendedor1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/reportes/vendedores/kpis?vendedor_id={$vendedor2->id}")
        ->assertOk()
        ->assertJsonPath('data.total_ventas_usd', 100)
        ->assertJsonPath('data.total_comisiones_usd', 10)
        ->assertJsonPath('data.conteo_facturas', 1);

    // 3. ReporteVendedorController::resumenVendedores (incluso pasando ?vendedor_id=vendedor2) solo devuelve a Vendedor 1
    $this->actingAs($userVendedor1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/reportes/vendedores/resumen?vendedor_id={$vendedor2->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $vendedor1->id)
        ->assertJsonPath('data.0.total_vendido_usd', 100);

    // 4. ReporteController::vendedores (/reportes/vendedores/json) también fuerza Vendedor 1 aunque tenga reportes.ver
    $this->actingAs($userVendedor1)
        ->withSession(['empresa_activa_id' => $empresa->id])
        ->getJson("/reportes/vendedores/json?vendedor_id={$vendedor2->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data.resumen')
        ->assertJsonPath('data.resumen.0.id', $vendedor1->id)
        ->assertJsonPath('data.kpis.total_ventas_usd', 100)
        ->assertJsonPath('data.kpis.total_comisiones_usd', 10);
});
