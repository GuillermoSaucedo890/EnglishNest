<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscripcion;
use Illuminate\Http\Request;

class InscripcionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $inscripciones = Inscripcion::with('curso')
            ->where('usuario_id', $user->id)
            ->get();

        return response()->json([
            'inscripciones' => $inscripciones
        ]);
}

    public function store(Request $request)
    {
        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
        ]);

        $user = $request->user();

        $existe = Inscripcion::where('usuario_id', $user->id)
            ->where('curso_id', $request->curso_id)
            ->first();

        if ($existe) {
            return response()->json([
                'mensaje' => 'Ya estás inscrito en este curso.'
            ], 409);
        }

        $inscripcion = Inscripcion::create([
            'usuario_id' => $user->id,
            'curso_id' => $request->curso_id,
            'estado' => 'activa',
            'fecha_inscripcion' => now(),
        ]);

        return response()->json([
            'mensaje' => 'Inscripción realizada correctamente.',
            'inscripcion' => $inscripcion
        ], 201);
    }
}
