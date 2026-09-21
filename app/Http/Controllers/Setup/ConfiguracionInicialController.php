<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setup\InstalacionInicialRequest;
use App\Models\Empresa;
use App\Models\MetodoPago;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ConfiguracionInicialController extends Controller
{
    /**
     * Muestra la pantalla del Asistente de Configuración Inicial
     */
    public function index(): View|RedirectResponse
    {
        if ($this->sistemaEstaConfigurado()) {
            return redirect()->route('dashboard');
        }

        // Asegurar que la matriz de roles y permisos esté inicializada
        $this->asegurarRolesYPermisos();

        return view('Setup.configuracion_inicial');
    }

    /**
     * Procesa la instalación inicial atómicamente en una sola transacción
     */
    public function procesar(InstalacionInicialRequest $request): JsonResponse
    {
        if ($this->sistemaEstaConfigurado()) {
            return response()->json([
                'success' => false,
                'message' => 'El sistema ya ha sido configurado previamente.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // 0. Asegurar que los roles y permisos existan
            $this->asegurarRolesYPermisos();

            // 1. Crear Empresa Matriz
            $rifEmpresa = $request->empresa_tipo_cedula.trim($request->empresa_cedula_numero);
            $telefonoEmpresa = $request->empresa_telefono_numero
                ? ($request->empresa_codigo_pais.trim($request->empresa_telefono_numero))
                : null;

            $logoPath = null;
            if ($request->hasFile('empresa_logo')) {
                $logoPath = $request->file('empresa_logo')->store('empresas', 'public');
            }

            $empresa = Empresa::create([
                'rif' => $rifEmpresa,
                'nombre' => trim($request->empresa_nombre),
                'razon_social' => trim($request->empresa_razon_social),
                'direccion' => trim($request->empresa_direccion),
                'telefono' => $telefonoEmpresa,
                'correo' => $request->empresa_correo ? trim($request->empresa_correo) : null,
                'logo' => $logoPath,
                'maneja_motos' => $request->boolean('empresa_maneja_motos'),
                'estado' => true,
            ]);

            // 2. Crear SuperAdministrador Raíz
            $cedulaAdmin = $request->admin_tipo_cedula.trim($request->admin_cedula_numero);

            $superAdmin = User::create([
                'name' => $cedulaAdmin,
                'nombre' => trim($request->admin_nombre),
                'apellido' => trim($request->admin_apellido),
                'email' => trim($request->admin_email),
                'password' => Hash::make($request->admin_password),
                'estado' => true,
            ]);

            // Asignar rol SuperAdmin
            $superAdmin->syncRoles('SuperAdmin');

            // Vincular con la Empresa Matriz
            $superAdmin->empresas()->attach($empresa->id, [
                'es_predeterminada' => true,
                'estado' => true,
            ]);

            // 3. Crear Métodos de Pago seleccionados
            $metodosSeleccionados = $request->input('metodos_pago', []);
            if (! empty($metodosSeleccionados) && is_array($metodosSeleccionados)) {
                foreach ($metodosSeleccionados as $nombreMetodo) {
                    MetodoPago::firstOrCreate(
                        ['nombre' => trim($nombreMetodo)],
                        [
                            'descripcion' => 'Método de pago configurado en la instalación inicial',
                            'estado' => true,
                        ]
                    );
                }
            }

            DB::commit();

            // 4. Iniciar sesión automáticamente y establecer contexto
            Auth::login($superAdmin);
            session(['empresa_activa_id' => $empresa->id]);

            return response()->json([
                'success' => true,
                'message' => '¡Sistema configurado e instalado con éxito! Bienvenido.',
                'redirect' => route('dashboard'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error durante la instalación inicial: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Asegura que los roles y permisos de Spatie existan en la BD
     */
    private function asegurarRolesYPermisos(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        if (Role::count() === 0) {
            (new RolesYPermisosSeeder)->run();
        } else {
            Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'Operador', 'guard_name' => 'web']);
        }
    }

    /**
     * Verifica si el sistema ya cuenta con SuperAdmin y Empresa
     */
    private function sistemaEstaConfigurado(): bool
    {
        $hayUsuarios = User::where('estado', true)->exists();
        $hayEmpresas = Empresa::where('estado', true)->exists();

        return $hayUsuarios && $hayEmpresas;
    }
}
