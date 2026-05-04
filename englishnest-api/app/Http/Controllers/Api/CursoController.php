<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'cursos' => Curso::all()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'docente_id' => 'required|exists:users,id',
            'area_id' => 'required|exists:areas,id',
            'titulo' => 'required|string|max:150',
            'descripcion' => 'required|string',
            'nivel' => 'required|in:basico,intermedio,avanzado',
            'estado' => 'nullable|in:borrador,publicado,archivado',
            'precio_referencia' => 'nullable|numeric|min:0',
        ]);

        $curso = Curso::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso creado correctamente',
            'curso' => $curso
        ], 201);
    }

    public function show($id)
    {
        return response()->json([
            'ok' => true,
            'curso' => Curso::findOrFail($id)
        ]);
    }
}
