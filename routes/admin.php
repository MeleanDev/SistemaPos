<?php

use App\Http\Controllers\Administradores\EmpresaController;
use App\Http\Controllers\Administradores\UsuarioController;
use App\Http\Controllers\Empresa\AlmacenController;
use App\Http\Controllers\Empresa\CajaController;
use App\Http\Controllers\Empresa\CategoriaController;
use App\Http\Controllers\Empresa\ClienteController;
use App\Http\Controllers\Empresa\ConfiguracionController;
use App\Http\Controllers\Empresa\ConsultorPrecioController;
use App\Http\Controllers\Empresa\CuentaPorCobrarController;
use App\Http\Controllers\Empresa\CuentaPorPagarController;
use App\Http\Controllers\Empresa\FacturaController;
use App\Http\Controllers\Empresa\KardexController;
use App\Http\Controllers\Empresa\MetodoPagoController;
use App\Http\Controllers\Empresa\ModeloMotoController;
use App\Http\Controllers\Empresa\MotoController;
use App\Http\Controllers\Empresa\PosController;
use App\Http\Controllers\Empresa\ProductoController;
use App\Http\Controllers\Empresa\ProveedorController;
use App\Http\Controllers\Empresa\RecepcionController;
use App\Http\Controllers\Empresa\RecepcionMotoController;
use App\Http\Controllers\Empresa\ReporteController;
use App\Http\Controllers\Empresa\ReporteVendedorController;
use App\Http\Controllers\Empresa\ServicioController;
use App\Http\Controllers\Empresa\VendedorController;
use App\Http\Controllers\PanelPrincipalController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {

    Route::controller(PanelPrincipalController::class)->group(function () {
        Route::get('/panel-principal', 'index')->name('dashboard');
    });

    Route::post('/cambiar-empresa', [UsuarioController::class, 'cambiarEmpresa'])->name('cambiar_empresa');

    Route::controller(ConsultorPrecioController::class)->group(function () {
        Route::get('/consultor-precios', 'index')->name('consultor_precios');
        Route::get('/consultor-precios/datos', 'datos');
        Route::get('/consultor-precios/buscar', 'buscar');
    });

    Route::middleware('permission:pos.acceso')->controller(PosController::class)->group(function () {
        Route::get('/pos', 'index')->name('pos');
        Route::get('/pos/datos', 'datos');
        Route::get('/pos/buscar-clientes', 'buscarClientes');
        Route::post('/pos/guardar-cliente-rapido', 'guardarClienteRapido');
        Route::post('/pos/guardar', 'guardar')->middleware('permission:ventas.crear');
        Route::post('/pos/en-espera/guardar', 'guardarEnEspera');
        Route::get('/pos/en-espera/lista', 'listarEnEspera');
        Route::get('/pos/en-espera/{id}/recuperar', 'recuperarEnEspera');
        Route::delete('/pos/en-espera/{id}', 'eliminarEnEspera');
        Route::get('/pos/devolucion/buscar', 'buscarFacturaDevolucion')->middleware('permission:ventas.anular');
        Route::post('/pos/devolucion/procesar', 'procesarDevolucion')->middleware('permission:ventas.anular');
        Route::get('/pos/imprimir/{id}', 'imprimir')->name('pos.imprimir');
        Route::get('/pos/imprimir-carta/{id}', 'imprimirCarta')->name('pos.imprimir_carta');
        Route::get('/pos/imprimir-ticket/{id}', 'imprimirTicket')->name('pos.imprimir_ticket');
    });

    Route::middleware('permission:ventas.ver')->controller(FacturaController::class)->group(function () {
        Route::get('/facturas', 'index')->name('factura');
        Route::get('/facturas/kpis', 'kpis');
        Route::get('/facturas/lista', 'lista');
        Route::get('/facturas/{id}', 'detalle');
    });

    Route::controller(ConfiguracionController::class)->group(function () {
        Route::get('/configuracion', 'index')->name('configuracion')->middleware('permission:configuracion.ver');
        Route::get('/configuracion/datos', 'datos')->middleware('permission:configuracion.ver');
        Route::post('/configuracion/empresa', 'actualizarEmpresa')->middleware('permission:configuracion.editar');
        Route::post('/configuracion/monedas', 'actualizarMonedas')->middleware('permission:configuracion.editar');
    });

    Route::controller(ProductoController::class)->group(function () {
        Route::get('/productos', 'index')->name('producto')->middleware('permission:productos.ver');
        Route::get('/productos/lista', 'lista')->middleware('permission:productos.ver');
        Route::get('/productos/catalogos', 'catalogos')->middleware('permission:productos.ver');
        Route::get('/productos/{id}', 'detalle')->middleware('permission:productos.ver');
        Route::post('/productos', 'guardar')->middleware('permission:productos.crear');
        Route::put('/productos/actualizar/{id}', 'actualizar')->middleware('permission:productos.editar');
        Route::delete('/productos/{id}', 'eliminar')->middleware('permission:productos.eliminar');
    });

    Route::controller(MotoController::class)->group(function () {
        Route::get('/motos', 'index')->name('moto')->middleware('permission:motos.ver');
        Route::get('/motos/lista', 'lista')->middleware('permission:motos.ver');
        Route::get('/motos/catalogos', 'catalogos')->middleware('permission:motos.ver');
        Route::get('/motos/{id}', 'detalle')->middleware('permission:motos.ver');
        Route::put('/motos/actualizar/{id}', 'actualizar')->middleware('permission:motos.editar');
        Route::post('/motos/{id}/cambiar-estado', 'cambiarEstado')->middleware('permission:motos.eliminar');
    });

    Route::controller(ModeloMotoController::class)->group(function () {
        Route::get('/modelos-motos/lista', 'lista')->middleware('permission:motos.ver');
        Route::get('/modelos-motos/catalogos', 'catalogos')->middleware('permission:motos.ver');
        Route::get('/modelos-motos/proxima-referencia', 'proximaReferencia')->middleware('permission:motos.ver');
        Route::get('/modelos-motos/{id}', 'detalle')->middleware('permission:motos.ver');
        Route::post('/modelos-motos', 'guardar')->middleware('permission:motos.crear');
        Route::put('/modelos-motos/actualizar/{id}', 'actualizar')->middleware('permission:motos.editar');
        Route::delete('/modelos-motos/{id}', 'eliminar')->middleware('permission:motos.eliminar');
    });

    Route::middleware('permission:compras.recepcion')->controller(RecepcionController::class)->group(function () {
        Route::get('/recepciones', 'index')->name('recepcion');
        Route::get('/recepciones/lista', 'lista');
        Route::get('/recepciones/catalogos', 'catalogos');
        Route::get('/recepciones/borradores', 'listarBorradores');
        Route::get('/recepciones/borradores/{id}', 'recuperarBorrador');
        Route::post('/recepciones/borradores', 'guardarBorrador');
        Route::delete('/recepciones/borradores/{id}', 'eliminarBorrador');
        Route::get('/recepciones/{id}', 'detalle');
        Route::get('/recepciones/{id}/imprimir', 'imprimir')->name('recepcion.imprimir');
        Route::post('/recepciones', 'guardar');
        Route::post('/recepciones/{id}/anular', 'anular');
    });

    Route::middleware('permission:motos.recepcion')->controller(RecepcionMotoController::class)->group(function () {
        Route::get('/recepciones-motos', 'index')->name('recepcion_moto');
        Route::get('/recepciones-motos/lista', 'lista');
        Route::get('/recepciones-motos/catalogos', 'catalogos');
        Route::get('/recepciones-motos/borradores', 'listarBorradores');
        Route::get('/recepciones-motos/borradores/{id}', 'recuperarBorrador');
        Route::post('/recepciones-motos/borradores', 'guardarBorrador');
        Route::delete('/recepciones-motos/borradores/{id}', 'eliminarBorrador');
        Route::get('/recepciones-motos/{id}', 'detalle');
        Route::get('/recepciones-motos/{id}/imprimir', 'imprimir')->name('recepcion_moto.imprimir');
        Route::post('/recepciones-motos', 'guardar');
        Route::post('/recepciones-motos/{id}/anular', 'anular');
    });

    Route::controller(ServicioController::class)->group(function () {
        Route::get('/servicios', 'index')->name('servicio')->middleware('permission:servicios.ver');
        Route::get('/servicios/lista', 'lista')->middleware('permission:servicios.ver');
        Route::get('/servicios/catalogos', 'catalogos')->middleware('permission:servicios.ver');
        Route::get('/servicios/{id}', 'detalle')->middleware('permission:servicios.ver');
        Route::post('/servicios', 'guardar')->middleware('permission:servicios.crear');
        Route::put('/servicios/actualizar/{id}', 'actualizar')->middleware('permission:servicios.editar');
        Route::delete('/servicios/{id}', 'eliminar')->middleware('permission:servicios.eliminar');
    });

    Route::controller(CategoriaController::class)->group(function () {
        Route::get('/categorias', 'index')->name('categoria')->middleware('permission:categorias.ver');
        Route::get('/categorias/lista', 'lista')->middleware('permission:categorias.ver');
        Route::get('/categorias/{id}', 'detalle')->middleware('permission:categorias.ver');
        Route::post('/categorias', 'guardar')->middleware('permission:categorias.crear');
        Route::put('/categorias/actualizar/{id}', 'actualizar')->middleware('permission:categorias.editar');
        Route::delete('/categorias/{id}', 'eliminar')->middleware('permission:categorias.eliminar');
    });

    Route::controller(AlmacenController::class)->group(function () {
        Route::get('/almacenes', 'index')->name('almacen')->middleware('permission:almacenes.ver');
        Route::get('/almacenes/lista', 'lista')->middleware('permission:almacenes.ver');
        Route::get('/almacenes/{id}', 'detalle')->middleware('permission:almacenes.ver');
        Route::post('/almacenes', 'guardar')->middleware('permission:almacenes.crear');
        Route::put('/almacenes/actualizar/{id}', 'actualizar')->middleware('permission:almacenes.editar');
        Route::delete('/almacenes/{id}', 'eliminar')->middleware('permission:almacenes.eliminar');
    });

    Route::controller(KardexController::class)->group(function () {
        Route::get('/kardex', 'index')->name('kardex')->middleware('permission:inventario.kardex');
        Route::get('/kardex/lista', 'lista')->middleware('permission:inventario.kardex');
        Route::get('/kardex/kpis', 'kpis')->middleware('permission:inventario.kardex');
        Route::get('/kardex/catalogos', 'catalogos')->middleware('permission:inventario.kardex');
        Route::get('/kardex/stock-producto/{productoId}', 'stockProducto')->middleware('permission:inventario.kardex');
        Route::post('/kardex/ajuste', 'ajustar')->middleware('permission:inventario.ajustar_stock');
        Route::post('/kardex/traslado', 'trasladar')->middleware('permission:inventario.traslados');
        Route::get('/kardex/comprobante/{id}', 'comprobante')->name('kardex.comprobante')->middleware('permission:inventario.kardex');
    });

    Route::controller(UsuarioController::class)->group(function () {
        Route::get('/usuarios', 'index')->name('usuario')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/lista', 'lista')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/catalogos', 'catalogos')->middleware('permission:usuarios.ver');
        Route::get('/usuarios/{id}', 'detalle')->middleware('permission:usuarios.ver');
        Route::post('/usuarios', 'guardar')->middleware('permission:usuarios.crear');
        Route::put('/usuarios/actualizar/{id}', 'actualizar')->middleware('permission:usuarios.editar');
        Route::post('/usuarios/{id}/permisos', 'actualizarPermisos')->middleware('permission:usuarios.permisos');
        Route::delete('/usuarios/{id}', 'eliminar')->middleware('permission:usuarios.eliminar');
    });

    Route::middleware('role:SuperAdmin')->controller(EmpresaController::class)->group(function () {
        Route::get('/empresas', 'index')->name('empresa');
        Route::get('/empresas/lista', 'lista');
        Route::get('/empresas/{id}', 'detalle');
        Route::post('/empresas', 'guardar');
        Route::put('/empresas/actualizar/{id}', 'actualizar');
        Route::delete('/empresas/{id}', 'eliminar');
    });

    Route::controller(ClienteController::class)->group(function () {
        Route::get('/clientes', 'index')->name('cliente')->middleware('permission:clientes.ver');
        Route::get('/clientes/lista', 'lista')->middleware('permission:clientes.ver');
        Route::get('/clientes/{id}', 'detalle')->middleware('permission:clientes.ver');
        Route::post('/clientes', 'guardar')->middleware('permission:clientes.crear');
        Route::put('/clientes/actualizar/{id}', 'actualizar')->middleware('permission:clientes.editar');
        Route::delete('/clientes/{id}', 'eliminar')->middleware('permission:clientes.eliminar');
    });

    Route::controller(ProveedorController::class)->group(function () {
        Route::get('/proveedores', 'index')->name('proveedor')->middleware('permission:proveedores.ver');
        Route::get('/proveedores/lista', 'lista')->middleware('permission:proveedores.ver');
        Route::get('/proveedores/{id}', 'detalle')->middleware('permission:proveedores.ver');
        Route::post('/proveedores', 'guardar')->middleware('permission:proveedores.crear');
        Route::put('/proveedores/actualizar/{id}', 'actualizar')->middleware('permission:proveedores.editar');
        Route::delete('/proveedores/{id}', 'eliminar')->middleware('permission:proveedores.eliminar');
    });

    Route::controller(CuentaPorCobrarController::class)->group(function () {
        Route::get('/cuentas-por-cobrar', 'index')->name('cxc')->middleware('permission:cxc.ver');
        Route::get('/cuentas-por-cobrar/lista', 'lista')->middleware('permission:cxc.ver');
        Route::get('/cuentas-por-cobrar/catalogos', 'catalogos')->middleware('permission:cxc.ver');
        Route::get('/cuentas-por-cobrar/cliente/{id}', 'clienteDetalle')->middleware('permission:cxc.ver');
        Route::post('/cuentas-por-cobrar/abonar-factura', 'abonarFactura')->middleware('permission:cxc.abonar');
        Route::post('/cuentas-por-cobrar/abonar-general', 'abonarGeneral')->middleware('permission:cxc.abonar');
        Route::get('/cuentas-por-cobrar/ticket/{id}', 'imprimirTicket')->name('cxc.ticket')->middleware('permission:cxc.ver');
    });

    Route::controller(CuentaPorPagarController::class)->group(function () {
        Route::get('/cuentas-por-pagar', 'index')->name('cxp')->middleware('permission:cxp.ver');
        Route::get('/cuentas-por-pagar/lista', 'lista')->middleware('permission:cxp.ver');
        Route::get('/cuentas-por-pagar/catalogos', 'catalogos')->middleware('permission:cxp.ver');
        Route::get('/cuentas-por-pagar/proveedor/{id}', 'proveedorDetalle')->middleware('permission:cxp.ver');
        Route::post('/cuentas-por-pagar/abonar-factura', 'abonarFactura')->middleware('permission:cxp.abonar');
        Route::post('/cuentas-por-pagar/abonar-general', 'abonarGeneral')->middleware('permission:cxp.abonar');
        Route::get('/cuentas-por-pagar/ticket/{id}', 'imprimirTicket')->name('cxp.ticket')->middleware('permission:cxp.ver');
    });

    Route::middleware('role:SuperAdmin')->controller(MetodoPagoController::class)->group(function () {
        Route::get('/metodos-pago', 'index')->name('metodo_pago');
        Route::get('/metodos-pago/lista', 'lista');
        Route::get('/metodos-pago/{id}', 'detalle');
        Route::post('/metodos-pago', 'guardar');
        Route::put('/metodos-pago/actualizar/{id}', 'actualizar');
        Route::delete('/metodos-pago/{id}', 'eliminar');
    });

    Route::controller(VendedorController::class)->group(function () {
        Route::get('/vendedores', 'index')->name('vendedor')->middleware('permission:vendedores.ver');
        Route::get('/vendedores/lista', 'lista')->middleware('permission:vendedores.ver');
        Route::get('/vendedores/catalogos', 'catalogos')->middleware('permission:vendedores.ver');
        Route::get('/vendedores/activos', 'activos')->middleware('permission:vendedores.ver');
        Route::get('/vendedores/{id}', 'detalle')->middleware('permission:vendedores.ver');
        Route::post('/vendedores', 'guardar')->middleware('permission:vendedores.crear');
        Route::put('/vendedores/actualizar/{id}', 'actualizar')->middleware('permission:vendedores.editar');
        Route::delete('/vendedores/{id}', 'eliminar')->middleware('permission:vendedores.eliminar');
    });

    Route::controller(CajaController::class)->group(function () {
        Route::get('/cajas', 'index')->name('caja')->middleware('permission:cajas.ver');
        Route::get('/cajas/lista', 'lista')->middleware('permission:cajas.ver');
        Route::get('/cajas/turnos/lista', 'listaTurnos')->middleware('permission:cajas.ver');
        Route::get('/cajas/disponibles', 'cajasDisponibles')->middleware('permission:cajas.ver');
        Route::get('/cajas/cajeros-disponibles', 'cajerosDisponibles')->middleware('permission:cajas.ver');
        Route::get('/cajas/turno-activo', 'turnoActivo')->middleware('permission:cajas.ver');
        Route::get('/cajas/turnos/{id}/reporte-x', 'reporteX')->middleware('permission:cajas.arqueo');
        Route::get('/cajas/turnos/{id}/imprimir-x', 'imprimirReporteX')->name('cajas.imprimir_x')->middleware('permission:cajas.arqueo');
        Route::get('/cajas/turnos/{id}/imprimir-z', 'imprimirReporteZ')->name('cajas.imprimir_z')->middleware('permission:cajas.arqueo');
        Route::get('/cajas/{id}', 'detalle')->middleware('permission:cajas.ver');
        Route::post('/cajas', 'guardar')->middleware('permission:cajas.crear');
        Route::put('/cajas/actualizar/{id}', 'actualizar')->middleware('permission:cajas.editar');
        Route::delete('/cajas/{id}', 'eliminar')->middleware('permission:cajas.eliminar');
        Route::post('/cajas/turnos/aperturar', 'aperturarTurno')->middleware('permission:cajas.aperturar');
        Route::match(['post', 'put'], '/cajas/turnos/{id}/cerrar', 'cerrarTurno')->middleware('permission:cajas.cerrar');
    });

    Route::middleware('permission:reportes.ver')->controller(ReporteController::class)->group(function () {
        Route::get('/reportes', 'index')->name('reportes');
        Route::get('/reportes/ingresos', 'ingresos');
        Route::get('/reportes/ingresos/pdf', 'pdfIngresos')->name('reportes.ingresos.pdf');
        Route::get('/reportes/ingresos/excel', 'excelIngresos')->name('reportes.ingresos.excel');

        Route::get('/reportes/creditos', 'creditos');
        Route::get('/reportes/creditos/pdf', 'pdfCreditos')->name('reportes.creditos.pdf');
        Route::get('/reportes/creditos/excel', 'excelCreditos')->name('reportes.creditos.excel');

        Route::get('/reportes/inventario', 'inventario');
        Route::get('/reportes/inventario/pdf', 'pdfInventario')->name('reportes.inventario.pdf');
        Route::get('/reportes/inventario/excel', 'excelInventario')->name('reportes.inventario.excel');

        Route::get('/reportes/rentabilidad', 'rentabilidad');
        Route::get('/reportes/rentabilidad/pdf', 'pdfRentabilidad')->name('reportes.rentabilidad.pdf');
        Route::get('/reportes/rentabilidad/excel', 'excelRentabilidad')->name('reportes.rentabilidad.excel');

        Route::get('/reportes/vendedores/json', 'vendedores')->name('reportes.vendedores.json');
        Route::get('/reportes/vendedores/pdf', 'pdfVendedores')->name('reportes.vendedores.pdf');
        Route::get('/reportes/vendedores/excel', 'excelVendedores')->name('reportes.vendedores.excel');

        Route::get('/reportes/stock-almacenes', 'stockAlmacenes')->name('reportes.stock_almacenes');
        Route::get('/reportes/stock-almacenes/pdf', 'pdfStockAlmacenes')->name('reportes.stock_almacenes.pdf');
        Route::get('/reportes/stock-almacenes/excel', 'excelStockAlmacenes')->name('reportes.stock_almacenes.excel');
    });

    Route::middleware('permission:reportes.vendedores')->controller(ReporteVendedorController::class)->group(function () {
        Route::get('/reportes/vendedores', 'index')->name('reportes.vendedores');
        Route::get('/reportes/vendedores/kpis', 'kpis');
        Route::get('/reportes/vendedores/lista', 'lista');
        Route::get('/reportes/vendedores/resumen', 'resumenVendedores');
    });

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });
});
