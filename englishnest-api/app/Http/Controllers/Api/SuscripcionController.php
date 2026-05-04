<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Suscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SuscripcionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:planes,id',
            'metodo_pago' => 'required|string|max:100',
        ]);

        $user = Auth::user();

        $existe = Suscripcion::where('user_id', $user->id)
            ->where('estado', 'activa')
            ->first();

        if ($existe) {
            return response()->json([
                'message' => 'Ya tienes una suscripción activa.'
            ], 409);
        }

        $plan = Plan::findOrFail($request->plan_id);

        $suscripcion = Suscripcion::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'metodo_pago' => $request->metodo_pago,
            'estado' => 'activa',
            'fecha_inicio' => now(),
            'fecha_fin' => Carbon::now()->addDays($plan->duracion_dias),
            'renovacion_automatica' => false,
        ]);

        return response()->json([
            'message' => 'Plan adquirido correctamente.',
            'suscripcion' => $suscripcion
        ], 201);
    }

    public function miSuscripcion()
    {
        $user = Auth::user();

        $suscripcion = Suscripcion::with('plan')
            ->where('user_id', $user->id)
            ->where('estado', 'activa')
            ->latest()
            ->first();

        return response()->json($suscripcion);
    }
}
