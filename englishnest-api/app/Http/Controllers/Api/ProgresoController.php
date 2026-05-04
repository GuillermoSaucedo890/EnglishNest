<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProgresoLeccion;
use Illuminate\Http\Request;

class ProgresoController extends Controller
{
    public function index(Request $request)
    {
        $progreso = ProgresoLeccion::where('usuario_id', $request->user()->id)->get();

        return response()->json([
            'ok' => true,
            'progreso' => $progreso
        ]);
    }

    public function marcarLeccion(Request $request)
    {
        $data = $request->validate([
            'leccion_id' => 'required|exists:lecciones,id',
            'completado' => 'required|boolean',
        ]);

        $progreso = ProgresoLeccion::updateOrCreate(
            [
                'usuario_id' => $request->user()->id,
                'leccion_id' => $data['leccion_id'],
            ],
            [
                'completado' => $data['completado'],
                'fecha_completado' => $data['completado'] ? now() : null,
                'ultima_visualizacion' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'mensaje' => 'Progreso actualizado correctamente',
            'progreso' => $progreso
        ]);
    }
}
