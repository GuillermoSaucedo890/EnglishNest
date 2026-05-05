<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerfilDocente;
use Illuminate\Http\Request;

class DocentePerfilController extends Controller
{
    private function usuarioDocente(Request $request)
    {
        $usuario = $request->user();

        if (!$usuario) {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'No autenticado.',
            ], 401));
        }

        $usuario->load('rol');

        if ($usuario->rol?->nombre !== 'docente') {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'Solo los docentes pueden usar esta sección.',
            ], 403));
        }

        return $usuario;
    }

    private function obtenerOCrearPerfil($usuario): PerfilDocente
    {
        return PerfilDocente::firstOrCreate(
            [
                'usuario_id' => $usuario->id,
            ],
            [
                'estudios' => null,
                'especialidad' => null,
                'biografia' => null,
                'estado_aprobacion' => 'pendiente',
                'motivo_rechazo' => null,
                'cambios_pendientes_json' => null,
                'estado_revision_cambios' => null,
                'motivo_rechazo_cambios' => null,
                'fecha_solicitud_cambios' => null,
            ]
        );
    }

    public function miPerfil(Request $request)
    {
        $usuario = $this->usuarioDocente($request);

        $perfil = $this->obtenerOCrearPerfil($usuario);

        $perfil->load('usuario:id,nombres,apellidos,email,email_verified_at');

        return response()->json([
            'ok' => true,
            'perfil' => $perfil,
        ]);
    }

    public function actualizar(Request $request)
    {
        $usuario = $this->usuarioDocente($request);

        $data = $request->validate([
            'especialidad' => ['required', 'string', 'min:3', 'max:255'],
            'estudios' => ['nullable', 'string', 'max:1000'],
            'biografia' => ['nullable', 'string', 'max:3000'],
        ]);

        $perfil = $this->obtenerOCrearPerfil($usuario);

        $estudios = $data['estudios'] ?? null;
        $biografia = $data['biografia'] ?? null;

        if ($perfil->estado_aprobacion === 'aprobado') {
            $perfil->update([
                'cambios_pendientes_json' => [
                    'especialidad' => $data['especialidad'],
                    'estudios' => $estudios,
                    'biografia' => $biografia,
                ],
                'estado_revision_cambios' => 'pendiente',
                'motivo_rechazo_cambios' => null,
                'fecha_solicitud_cambios' => now(),
            ]);

            $perfil->load('usuario:id,nombres,apellidos,email,email_verified_at');

            return response()->json([
                'ok' => true,
                'mensaje' => 'Tus cambios fueron enviados a revisión. Tu perfil oficial seguirá igual hasta que el administrador los apruebe.',
                'perfil' => $perfil,
            ]);
        }

        $perfil->update([
            'especialidad' => $data['especialidad'],
            'estudios' => $estudios,
            'biografia' => $biografia,
        ]);

        $perfil->load('usuario:id,nombres,apellidos,email,email_verified_at');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Perfil docente actualizado correctamente.',
            'perfil' => $perfil,
        ]);
    }

    public function reenviarSolicitud(Request $request)
    {
        $usuario = $this->usuarioDocente($request);

        $perfil = $this->obtenerOCrearPerfil($usuario);

        if ($perfil->estado_aprobacion === 'aprobado') {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Tu perfil docente ya está aprobado. Si editas tu perfil, los cambios se enviarán a revisión automáticamente.',
                'perfil' => $perfil->load('usuario:id,nombres,apellidos,email,email_verified_at'),
            ]);
        }

        if (empty(trim((string) $perfil->especialidad))) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Completa al menos tu especialidad antes de volver a solicitar aprobación.',
            ], 422);
        }

        $perfil->update([
            'estado_aprobacion' => 'pendiente',
            'motivo_rechazo' => null,
        ]);

        $perfil->load('usuario:id,nombres,apellidos,email,email_verified_at');

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitud reenviada correctamente. El administrador volverá a revisar tu perfil.',
            'perfil' => $perfil,
        ]);
    }
}