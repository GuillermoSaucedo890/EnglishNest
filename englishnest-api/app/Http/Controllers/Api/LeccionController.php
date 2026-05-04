<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leccion;
use Illuminate\Http\Request;

class LeccionController extends Controller
{
    public function index()
    {
        return response()->json([
            'ok' => true,
            'lecciones' => Leccion::all()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'titulo' => 'required|string|max:150',
            'contenido' => 'required|string',
            'orden' => 'required|integer|min:1',
        ]);

        $leccion = Leccion::create($data);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección creada correctamente',
            'leccion' => $leccion
        ], 201);
    }
}
