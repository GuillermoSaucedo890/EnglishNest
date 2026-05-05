<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\Suscripcion;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    // Lista pagos del usuario autenticado
    public function index(Request $request)
    {
        $pagos = Pago::where('usuario_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'ok' => true,
            'pagos' => $pagos,
        ]);
    }

    // Registro manual de pago por ahora. Stripe lo hacemos después.
    public function store(Request $request)
    {
        $data = $request->validate([
            'suscripcion_id' => ['nullable', 'exists:suscripciones,id'],
            'monto' => ['required', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'in:stripe,paypal,manual'],
            'referencia_externa' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'in:pendiente,pagado,fallido,reembolsado'],
        ]);

        $pago = Pago::create([
            'usuario_id' => $request->user()->id,
            'suscripcion_id' => $data['suscripcion_id'] ?? null,
            'monto' => $data['monto'],
            'proveedor' => $data['proveedor'] ?? 'manual',
            'referencia_externa' => $data['referencia_externa'] ?? null,
            'estado' => $data['estado'] ?? 'pendiente',
            'fecha_pago' => ($data['estado'] ?? 'pendiente') === 'pagado' ? now() : null,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Pago registrado correctamente.',
            'pago' => $pago,
        ], 201);
    }

    // Estado de acceso del estudiante
    public function estado(Request $request)
    {
        $usuario = $request->user();

        $suscripcion = Suscripcion::where('usuario_id', $usuario->id)
            ->where('estado', 'activa')
            ->with('plan')
            ->latest('fecha_fin')
            ->first();

        if (!$suscripcion) {
            return response()->json([
                'ok' => true,
                'tiene_acceso' => false,
                'mensaje' => 'No tienes una suscripción activa.',
                'suscripcion' => null,
            ]);
        }

        $estaVigente = $suscripcion->fecha_fin > now();

        if (!$estaVigente) {
            $suscripcion->update(['estado' => 'vencida']);

            return response()->json([
                'ok' => true,
                'tiene_acceso' => false,
                'mensaje' => 'Tu suscripción venció. Tu progreso se mantiene guardado.',
                'suscripcion' => $suscripcion->fresh('plan'),
            ]);
        }

        $diasRestantes = now()->diffInDays($suscripcion->fecha_fin, false);

        return response()->json([
            'ok' => true,
            'tiene_acceso' => true,
            'mensaje' => 'Suscripción activa.',
            'mostrar_recordatorio' => $diasRestantes <= 2,
            'dias_restantes' => $diasRestantes,
            'suscripcion' => $suscripcion,
        ]);
    }
}