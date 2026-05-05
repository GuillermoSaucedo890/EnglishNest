<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Leccion;
use App\Models\ProgresoLeccion;
use Illuminate\Http\Request;

class ProgresoController extends Controller
{
    public function index(Request $request)
    {
        $progreso = ProgresoLeccion::where('usuario_id', $request->user()->id)->get();

        return response()->json([
            'ok' => true,
            'progreso' => $progreso,
        ]);
    }

    public function marcarLeccion(Request $request)
    {
        $data = $request->validate([
            'leccion_id' => ['required', 'exists:lecciones,id'],
            'completado' => ['required', 'boolean'],
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
            'mensaje' => 'Progreso actualizado correctamente.',
            'progreso' => $progreso,
        ]);
    }

    public function calcularPorcentajeCurso(Request $request, $cursoId)
    {
        $curso = Curso::with([
                'lecciones' => function ($q) {
                    $q->where('activo', true)->orderBy('orden', 'asc');
                },
            ])
            ->findOrFail($cursoId);

        $lecciones = $curso->lecciones;

        if ($lecciones->count() <= 0) {
            return response()->json([
                'ok' => true,
                'porcentaje' => 0,
                'total_lecciones' => 0,
                'lecciones_completadas' => 0,
            ]);
        }

        $idsLecciones = $lecciones->pluck('id');

        $completadas = ProgresoLeccion::where('usuario_id', $request->user()->id)
            ->whereIn('leccion_id', $idsLecciones)
            ->where('completado', true)
            ->count();

        $porcentaje = round(($completadas / $lecciones->count()) * 100, 2);

        return response()->json([
            'ok' => true,
            'porcentaje' => $porcentaje,
            'total_lecciones' => $lecciones->count(),
            'lecciones_completadas' => $completadas,
        ]);
    }

    public function estadoCurso(Request $request, $cursoId)
    {
        $curso = Curso::with([
                'lecciones' => function ($q) {
                    $q->where('activo', true)->orderBy('orden', 'asc');
                },
                'lecciones.evaluacion',
            ])
            ->findOrFail($cursoId);

        $idsLecciones = $curso->lecciones->pluck('id');

        $progresos = ProgresoLeccion::where('usuario_id', $request->user()->id)
            ->whereIn('leccion_id', $idsLecciones)
            ->get()
            ->keyBy('leccion_id');

        $lecciones = $curso->lecciones->values()->map(function ($leccion, $index) use ($progresos) {
            $esIntroduccion = $index === 0 || $leccion->tipo === 'introduccion' || (bool) $leccion->es_gratis;

            $progreso = $progresos->get($leccion->id);

            return [
                'leccion_id' => $leccion->id,
                'orden' => $leccion->orden,
                'titulo' => $leccion->titulo,
                'es_introduccion' => $esIntroduccion,
                'tiene_evaluacion' => $leccion->evaluacion !== null,
                'completado' => (bool) ($progreso?->completado ?? false),
                'fecha_completado' => $progreso?->fecha_completado,
            ];
        });

        return response()->json([
            'ok' => true,
            'curso_id' => $curso->id,
            'lecciones' => $lecciones,
        ]);
    }
}