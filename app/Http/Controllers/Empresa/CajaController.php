<?php

namespace App\Http\Controllers\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\Caja\ActualizarRequest;
use App\Http\Requests\Caja\AperturarTurnoRequest;
use App\Http\Requests\Caja\CerrarTurnoRequest;
use App\Http\Requests\Caja\CrearRequest;
use App\Models\Almacen;
use App\Service\Empresa\CajaClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CajaController extends Controller
{
    public function __construct(private CajaClass $cajaClass) {}

    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $almacenes = Almacen::where('empresa_id', $empresaId)->where('estado', true)->orderBy('nombre')->get();
        $turnoActivo = $this->cajaClass->obtenerTurnoActivoUsuario(Auth::id(), $empresaId);
        $cajeros = $this->cajaClass->cajerosDisponibles($empresaId);

        return view('Sistema.pages.empresa.caja', compact('almacenes', 'turnoActivo', 'cajeros'));
    }

    public function lista(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $cajas = $this->cajaClass->listaCajas($empresaId);

        return datatables()->of($cajas)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->where('nombre', 'LIKE', "%{$search}%")
                            ->orWhere('codigo', 'LIKE', "%{$search}%")
                            ->orWhere('descripcion', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function listaTurnos(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $turnos = $this->cajaClass->listaTurnos($empresaId);

        return datatables()->of($turnos)
            ->filter(function ($query) {
                if ($search = request('search.value')) {
                    $query->where(function ($q) use ($search) {
                        $q->whereHas('caja', fn ($c) => $c->where('nombre', 'LIKE', "%{$search}%"))
                            ->orWhereHas('usuario', fn ($u) => $u->where('name', 'LIKE', "%{$search}%")->orWhere('nombre', 'LIKE', "%{$search}%"))
                            ->orWhere('fecha_apertura', 'LIKE', "%{$search}%")
                            ->orWhere('estado', 'LIKE', "%{$search}%");
                    });
                }
            })
            ->toJson();
    }

    public function cajasDisponibles(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $cajas = $this->cajaClass->cajasDisponiblesParaApertura($empresaId);

        return response()->json([
            'success' => true,
            'data' => $cajas,
        ]);
    }

    public function cajerosDisponibles(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $cajeros = $this->cajaClass->cajerosDisponibles($empresaId);

        return response()->json([
            'success' => true,
            'data' => $cajeros,
        ]);
    }

    public function detalle(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $caja = $this->cajaClass->detalleCaja($id, $empresaId);

            return response()->json($caja);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Caja no encontrada',
            ], 404);
        }
    }

    public function guardar(CrearRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $caja = $this->cajaClass->guardarCaja($request->validated(), $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Caja registrada correctamente',
                'data' => $caja,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function actualizar(ActualizarRequest $request, int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $caja = $this->cajaClass->actualizarCaja($request->validated(), $id, $empresaId);

            return response()->json([
                'success' => true,
                'message' => 'Caja actualizada correctamente',
                'data' => $caja,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function eliminar(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $caja = $this->cajaClass->eliminarCaja($id, $empresaId);

            $mensaje = $caja->estado ? 'Caja reactivada correctamente' : 'Caja desactivada correctamente';

            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'data' => $caja,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function turnoActivo(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $turno = $this->cajaClass->obtenerTurnoActivoUsuario(Auth::id(), $empresaId);

        return response()->json([
            'success' => true,
            'data' => $turno,
        ]);
    }

    public function aperturarTurno(AperturarTurnoRequest $request): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $turno = $this->cajaClass->aperturarTurno($request->validated(), $empresaId, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Turno de caja aperturado exitosamente',
                'data' => $turno->load('caja'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function reporteX(int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $reporte = $this->cajaClass->calcularReporteTurno($id, $empresaId);

            return response()->json([
                'success' => true,
                'data' => $reporte,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function cerrarTurno(CerrarTurnoRequest $request, int $id): JsonResponse
    {
        try {
            $empresaId = $this->obtenerEmpresaId();
            $turno = $this->cajaClass->cerrarTurno($id, $request->validated(), $empresaId, Auth::id());

            return response()->json([
                'success' => true,
                'message' => 'Turno de caja cerrado exitosamente. Arqueo completado.',
                'data' => $turno,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function imprimirReporteX(int $id): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $reporte = $this->cajaClass->calcularReporteTurno($id, $empresaId);

        return view('Sistema.pages.empresa.reporte-corte-x-ticket', compact('reporte'));
    }

    public function imprimirReporteZ(int $id): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $reporte = $this->cajaClass->calcularReporteTurno($id, $empresaId);

        return view('Sistema.pages.empresa.reporte-cierre-z-ticket', compact('reporte'));
    }
}
