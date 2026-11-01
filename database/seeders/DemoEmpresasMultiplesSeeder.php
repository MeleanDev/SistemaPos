<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\EmpresaMoneda;
use App\Models\MetodoPago;
use App\Models\Moto;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Vendedor;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoEmpresasMultiplesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $admin = User::find(1);
            if (! $admin) {
                $admin = User::create([
                    'name' => 'V-11223344',
                    'nombre' => 'Administrador',
                    'apellido' => 'General',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('password123'),
                    'estado' => true,
                ]);
                $admin->assignRole('Admin');
            }

            $metodoEfectivoUsd = MetodoPago::where('nombre', 'like', '%USD%')->first() ?? MetodoPago::first();
            $metodoPagoMovil = MetodoPago::where('nombre', 'like', '%Móvil%')->first() ?? MetodoPago::first();

            // =========================================================================
            // EMPRESA 1: MEGASTORE ELECTRONICS, C.A. (Retail / Electrodomésticos)
            // =========================================================================
            $empresa1 = Empresa::updateOrCreate(
                ['rif' => 'J-405398434'],
                [
                    'nombre' => 'MEGASTORE',
                    'razon_social' => 'MEGASTORE ELECTRONICS, C.A.',
                    'direccion' => 'CC Las Playitas estacionamiento principal, Maracaibo - Edo. Zulia',
                    'telefono' => '0424-6571901',
                    'correo' => 'ventas@megastore.com',
                    'maneja_motos' => false,
                    'maneja_vendedores' => true,
                    'estado' => true,
                ]
            );

            // Asignar al admin
            $admin->empresas()->syncWithoutDetaching([
                $empresa1->id => ['es_predeterminada' => true, 'estado' => true],
            ]);

            // Monedas
            EmpresaMoneda::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo' => 'USD'],
                ['nombre' => 'Dólar Americano', 'simbolo' => '$', 'tasa_cambio' => 45.0000, 'es_principal' => false, 'estado' => true]
            );

            // Almacén
            $almacen1 = Almacen::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo' => 'ALM-MEGA-01'],
                ['nombre' => 'Almacén Principal Megastore', 'direccion' => 'CC Las Playitas, Local 14', 'estado' => true]
            );

            // Caja
            $caja1 = Caja::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo' => 'CAJA-01'],
                ['nombre' => 'Caja Principal 1', 'estado' => true]
            );

            // Vendedor
            $vendedor1 = Vendedor::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'documento' => 'V-20112233'],
                [
                    'tipo_documento' => 'V',
                    'nombre' => 'NAYBETH MARIN',
                    'telefono' => '0424-6571901',
                    'correo' => 'naybeth@megastore.com',
                    'comision_porcentaje' => 2.5,
                    'estado' => true,
                ]
            );

            // Cliente
            $cliente1 = Cliente::updateOrCreate(
                ['cedula' => 'V-8500989'],
                [
                    'nombre' => 'ALEXANDER',
                    'apellido' => 'GONZALEZ',
                    'telefono' => '0424-5031713',
                    'direccion' => 'MARACAIBO - ZULIA',
                    'correo' => 'alexander.gonzalez@gmail.com',
                    'estado' => true,
                ]
            );

            // Categoría y Productos
            $cat1 = Categoria::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo' => 'CAT-ELECT-01'],
                ['nombre' => 'Cables y Electricidad', 'estado' => true]
            );

            $prod1 = Producto::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo_interno' => '7453021132671'],
                [
                    'categoria_id' => $cat1->id,
                    'nombre' => 'CABLE VOLTAGE ELECTRONICS ART CABLE ELECTRICO 6AWG (NEGRO) MOD:VT-CC-6-B',
                    'descripcion' => 'Marca VOLTAGE - Cable eléctrico de alta conductividad',
                    'unidad_medida' => 'UND',
                    'precio_costo_usd' => 120.00,
                    'precio_detal_usd' => 185.00,
                    'precio_detal_bs' => 8325.00,
                    'estado' => true,
                ]
            );

            $prod2 = Producto::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo_interno' => '7453012383310'],
                [
                    'categoria_id' => $cat1->id,
                    'nombre' => 'CABLE ELECTRICO V-TEG 6AWG 100% COBRE MOD VT-2271',
                    'descripcion' => 'Marca V-TEG - 100% Cobre',
                    'unidad_medida' => 'UND',
                    'precio_costo_usd' => 110.00,
                    'precio_detal_usd' => 184.99,
                    'precio_detal_bs' => 8324.55,
                    'estado' => true,
                ]
            );

            // Venta Real (Idéntica al comprobante físico del usuario F00024760)
            $venta1 = Venta::updateOrCreate(
                ['empresa_id' => $empresa1->id, 'codigo' => 'F00024760'],
                [
                    'cliente_id' => $cliente1->id,
                    'almacen_id' => $almacen1->id,
                    'caja_id' => $caja1->id,
                    'user_id' => $admin->id,
                    'vendedor_id' => $vendedor1->id,
                    'numero_control' => '00-0024760',
                    'tipo_venta' => 'detal',
                    'moneda' => 'USD',
                    'tasa_cambio' => 45.00,
                    'fecha_emision' => now()->toDateString(),
                    'hora_emision' => now()->toTimeString(),
                    'monto_bruto_usd' => 369.99,
                    'monto_bruto_bs' => 16649.55,
                    'subtotal_neto_usd' => 369.99,
                    'subtotal_neto_bs' => 16649.55,
                    'iva_monto_usd' => 0.00,
                    'iva_monto_bs' => 0.00,
                    'total_usd' => 369.99,
                    'total_bs' => 16649.55,
                    'condicion_pago' => 'contado',
                    'monto_pagado_usd' => 370.00,
                    'monto_pagado_bs' => 16650.00,
                    'vuelto_usd' => 0.01,
                    'vuelto_bs' => 0.45,
                    'estado' => 'completada',
                    'observaciones' => 'ENTREGADO - Despachado por almacén central.',
                ]
            );

            // Venta Detalles
            VentaDetalle::updateOrCreate(
                ['venta_id' => $venta1->id, 'producto_id' => $prod1->id],
                [
                    'almacen_id' => $almacen1->id,
                    'tipo_item' => 'producto',
                    'nombre_item' => $prod1->nombre,
                    'serial_identificador' => 'SN-VTCC6B-8891',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 185.00,
                    'precio_unitario_bs' => 8325.00,
                    'subtotal_usd' => 185.00,
                    'subtotal_bs' => 8325.00,
                    'aplica_iva' => false,
                ]
            );

            VentaDetalle::updateOrCreate(
                ['venta_id' => $venta1->id, 'producto_id' => $prod2->id],
                [
                    'almacen_id' => $almacen1->id,
                    'tipo_item' => 'producto',
                    'nombre_item' => $prod2->nombre,
                    'serial_identificador' => 'SN-VTEG-77210',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 184.99,
                    'precio_unitario_bs' => 8324.55,
                    'subtotal_usd' => 184.99,
                    'subtotal_bs' => 8324.55,
                    'aplica_iva' => false,
                ]
            );

            VentaPago::updateOrCreate(
                ['venta_id' => $venta1->id, 'metodo_pago_id' => $metodoEfectivoUsd->id],
                [
                    'moneda' => 'USD',
                    'monto_origen' => 370.00,
                    'tasa_cambio' => 1.00,
                    'monto_usd' => 370.00,
                    'monto_bs' => 16650.00,
                ]
            );

            // =========================================================================
            // EMPRESA 2: MOTOS & REPUESTOS DEL ZULIA, C.A. (Motos y Vehículos)
            // =========================================================================
            $empresa2 = Empresa::updateOrCreate(
                ['rif' => 'J-501889921'],
                [
                    'nombre' => 'MOTOS & REPUESTOS DEL ZULIA',
                    'razon_social' => 'MOTOS & REPUESTOS DEL ZULIA, C.A.',
                    'direccion' => 'Av. Bella Vista con Calle 72, Maracaibo - Edo. Zulia',
                    'telefono' => '0412-9876543',
                    'correo' => 'motosdelzulia@gmail.com',
                    'maneja_motos' => true,
                    'maneja_vendedores' => true,
                    'estado' => true,
                ]
            );

            $admin->empresas()->syncWithoutDetaching([
                $empresa2->id => ['es_predeterminada' => false, 'estado' => true],
            ]);

            EmpresaMoneda::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'codigo' => 'USD'],
                ['nombre' => 'Dólar Americano', 'simbolo' => '$', 'tasa_cambio' => 45.0000, 'es_principal' => false, 'estado' => true]
            );

            $almacen2 = Almacen::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'codigo' => 'ALM-MOTO-01'],
                ['nombre' => 'Patio Central de Ensamblaje y Ventas', 'direccion' => 'Av. Bella Vista #72-10', 'estado' => true]
            );

            $caja2 = Caja::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'codigo' => 'CAJA-MOTO-01'],
                ['nombre' => 'Caja Showroom Motos', 'estado' => true]
            );

            $vendedor2 = Vendedor::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'documento' => 'V-17889900'],
                [
                    'tipo_documento' => 'V',
                    'nombre' => 'ROBERTO SANCHEZ',
                    'telefono' => '0412-5551234',
                    'correo' => 'roberto.sanchez@motoszulia.com',
                    'comision_porcentaje' => 3.0,
                    'estado' => true,
                ]
            );

            $cliente2 = Cliente::updateOrCreate(
                ['cedula' => 'V-19882233'],
                [
                    'nombre' => 'CARLOS',
                    'apellido' => 'MENDOZA',
                    'telefono' => '0414-9988776',
                    'direccion' => 'URB. COROMOTO, CALLE 175',
                    'correo' => 'carlos.mendoza@hotmail.com',
                    'estado' => true,
                ]
            );

            $proveedor2 = Proveedor::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'rif' => 'J-123456789'],
                [
                    'nombre' => 'CORPORACION BERA C.A.',
                    'razon_social' => 'CORPORACION BERA C.A.',
                    'estado' => true,
                ]
            );

            // Registro de Moto
            $moto1 = Moto::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'numero_niv' => '8X2BERASBR20269911'],
                [
                    'proveedor_id' => $proveedor2->id,
                    'almacen_id' => $almacen2->id,
                    'referencia' => 'MOTO-SBR-01',
                    'marca' => 'BERA',
                    'modelo' => 'SBR 150 NEW SPECIAL',
                    'anio' => 2026,
                    'color' => 'AZUL METÁLICO',
                    'cilindrada' => '150cc',
                    'numero_chasis' => '8X2BERASBR20269911',
                    'numero_motor' => '162FMJ-26A08912',
                    'certificado_origen' => 'CO-2026-9911',
                    'placa' => 'AB1C23D',
                    'costo_base_usd' => 700.00,
                    'costo_base_bs' => 31500.00,
                    'precio_costo_usd' => 700.00,
                    'precio_costo_bs' => 31500.00,
                    'precio_detal_usd' => 980.00,
                    'precio_detal_bs' => 44100.00,
                    'precio_detal_con_iva_usd' => 980.00,
                    'precio_detal_con_iva_bs' => 44100.00,
                    'estado' => 'vendida',
                ]
            );

            $venta2 = Venta::updateOrCreate(
                ['empresa_id' => $empresa2->id, 'codigo' => 'MOTO-2026-0001'],
                [
                    'cliente_id' => $cliente2->id,
                    'almacen_id' => $almacen2->id,
                    'caja_id' => $caja2->id,
                    'user_id' => $admin->id,
                    'vendedor_id' => $vendedor2->id,
                    'numero_control' => '00-0099811',
                    'tipo_venta' => 'detal',
                    'moneda' => 'USD',
                    'tasa_cambio' => 45.00,
                    'fecha_emision' => now()->toDateString(),
                    'hora_emision' => now()->toTimeString(),
                    'monto_bruto_usd' => 980.00,
                    'monto_bruto_bs' => 44100.00,
                    'subtotal_neto_usd' => 980.00,
                    'subtotal_neto_bs' => 44100.00,
                    'iva_monto_usd' => 0.00,
                    'iva_monto_bs' => 0.00,
                    'total_usd' => 980.00,
                    'total_bs' => 44100.00,
                    'condicion_pago' => 'contado',
                    'monto_pagado_usd' => 980.00,
                    'monto_pagado_bs' => 44100.00,
                    'estado' => 'completada',
                    'observaciones' => 'Unidad 0km entregada con llave, batería precintada y certificado de origen.',
                ]
            );

            VentaDetalle::updateOrCreate(
                ['venta_id' => $venta2->id, 'moto_id' => $moto1->id],
                [
                    'almacen_id' => $almacen2->id,
                    'tipo_item' => 'moto',
                    'nombre_item' => 'MOTOCICLETA BERA SBR 150 NEW SPECIAL (2026)',
                    'serial_identificador' => 'NIV: 8X2BERASBR20269911',
                    'cantidad' => 1,
                    'precio_unitario_usd' => 980.00,
                    'precio_unitario_bs' => 44100.00,
                    'subtotal_usd' => 980.00,
                    'subtotal_bs' => 44100.00,
                    'aplica_iva' => false,
                ]
            );

            VentaPago::updateOrCreate(
                ['venta_id' => $venta2->id, 'metodo_pago_id' => $metodoEfectivoUsd->id],
                [
                    'moneda' => 'USD',
                    'monto_origen' => 980.00,
                    'tasa_cambio' => 1.00,
                    'monto_usd' => 980.00,
                    'monto_bs' => 44100.00,
                ]
            );
        });
    }
}
