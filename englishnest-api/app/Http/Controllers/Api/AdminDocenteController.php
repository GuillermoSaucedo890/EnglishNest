<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDocenteController extends Controller
{
    private function validarAdmin(Request $request): void
    {
        $usuario = $request->user()->load('rol');

        if (!$usuario->rol || $usuario->rol->nombre !== 'admin') {
            abort(403, 'No tienes permisos para esta acción.');
        }
    }

    public function pendientes(Request $request)
    {
        $this->validarAdmin($request);

        $docentes = User::with(['rol', 'perfilDocente'])
            ->whereHas('rol', function ($q) {
                $q->where('nombre', 'docente');
            })
            ->whereHas('perfilDocente', function ($q) {
                $q->where('estado_aprobacion', 'pendiente');
            })
            ->get();

        return response()->json([
            'ok' => true,
            'docentes' => $docentes,
        ]);
    }

    public function aprobar(Request $request, int $usuarioId)
    {
        $this->validarAdmin($request);

        $usuario = User::with('perfilDocente')->findOrFail($usuarioId);

        if (!$usuario->perfilDocente) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'El usuario no tiene perfil docente.',
            ], 422);
        }

        $usuario->perfilDocente->update([
            'estado_aprobacion' => 'aprobado',
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Docente aprobado correctamente.',
        ]);
    }
}