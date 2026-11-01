<?php

namespace App\Http\Controllers;

use App\Service\Empresa\PanelPrincipalClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PanelPrincipalController extends Controller
{
    public function __construct(private PanelPrincipalClass $panelPrincipalClass) {}

    public function index(): View
    {
        $empresaId = $this->obtenerEmpresaId();
        $user = Auth::user();

        $dashboard = $this->panelPrincipalClass->obtenerDatosDashboard($empresaId, $user);

        return view('Sistema.dashboard', compact('dashboard'));
    }

    public function datos(): JsonResponse
    {
        $empresaId = $this->obtenerEmpresaId();
        $user = Auth::user();

        $datos = $this->panelPrincipalClass->obtenerDatosDashboard($empresaId, $user);

        return response()->json([
            'success' => true,
            'data' => $datos,
        ]);
    }
}
