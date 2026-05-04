<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request)
    {
        $pagos = Pago::where('usuario_id', $request->user()->id)->get();

        return response()->json([
            'ok' => true,
            'pagos' => $pagos
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'monto' => 'required|numeric|min:1',
            'metodo_pago' => 'required|string|max:50',
            'estado' => 'nullable|string|max:50',
            'referencia' => 'nullable|string|max:100',
        ]);

        $pago = Pago::create([
            'usuario_id' => $request->user()->id,
            'monto' => $data['monto'],
            'metodo_pago' => $data['metodo_pago'],
            'estado' => $data['estado'] ?? 'pendiente',
            'referencia' => $data['referencia'] ?? null,
            'fecha_pago' => now(),
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Pago registrado correctamente',
            'pago' => $pago
        ], 201);
    }
        public function estado(Request $request)
        {
            $usuario = $request->user();

            $tienePago = Pago::where('usuario_id', $usuario->id)
                ->where('estado', 'pagado')
                ->exists();

            return response()->json([
                'ok' => true,
                'tiene_acceso' => $tienePago
            ]);
        }
}

