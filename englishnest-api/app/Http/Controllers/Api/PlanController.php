<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;

class PlanController extends Controller
{
    // ✅ ESTE MÉTODO FALTA
    public function index()
    {
        $planes = Plan::all();

        return response()->json($planes);
    }
}
