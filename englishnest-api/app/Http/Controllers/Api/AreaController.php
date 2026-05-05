<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    // Lista las áreas/categorías disponibles para cursos
    public function index(Request $request)
    {
        $areas = Area::orderBy('nombre', 'asc')->get();

        return response()->json([
            'ok' => true,
            'areas' => $areas,
        ]);
    }
}