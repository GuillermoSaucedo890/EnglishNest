<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerfilDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class AdminDocenteController extends Controller
{
    private function usuarioEsAdmin(Request $request): bool
    {
        $usuario = $request->user();

        if (!$usuario) {
            return false;
        }

        $usuario->load('rol');

        return $usuario->rol?->nombre === 'admin';
    }

    private function respuestaNoAutorizado()
    {
        return response()->json([
            'ok' => false,
            'mensaje' => 'Solo el administrador puede realizar esta acción.',
        ], 403);
    }

    private function agregarTipoRevision($perfil)
    {
        $tipo = 'solicitud_inicial';

        if (
            $perfil->estado_aprobacion === 'aprobado' &&
            $perfil->estado_revision_cambios === 'pendiente'
        ) {
            $tipo = 'cambios_perfil';
        }

        $perfil->setAttribute('tipo_revision', $tipo);

        return $perfil;
    }

    public function pendientes(Request $request)
    {
        if (!$this->usuarioEsAdmin($request)) {
            return $this->respuestaNoAutorizado();
        }

        $docentes = PerfilDocente::with('usuario:id,nombres,apellidos,email,email_verified_at,estado')
            ->where(function ($q) {
                $q->where('estado_aprobacion', 'pendiente')
                  ->orWhere('estado_revision_cambios', 'pendiente');
            })
            ->latest()
            ->get()
            ->map(function ($perfil) {
                return $this->agregarTipoRevision($perfil);
            });

        return response()->json([
            'ok' => true,
            'docentes' => $docentes,
        ]);
    }

    public function aprobados(Request $request)
    {
        if (!$this->usuarioEsAdmin($request)) {
            return $this->respuestaNoAutorizado();
        }

        $docentes = PerfilDocente::with('usuario:id,nombres,apellidos,email,email_verified_at,estado')
            ->where('estado_aprobacion', 'aprobado')
            ->latest()
            ->get()
            ->map(function ($perfil) {
                return $this->agregarTipoRevision($perfil);
            });

        return response()->json([
            'ok' => true,
            'docentes' => $docentes,
        ]);
    }

    public function detalle(Request $request, $usuarioId)
    {
        if (!$this->usuarioEsAdmin($request)) {
            return $this->respuestaNoAutorizado();
        }

        $perfil = PerfilDocente::with('usuario:id,nombres,apellidos,email,email_verified_at,estado')
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();

        $perfil = $this->agregarTipoRevision($perfil);

        return response()->json([
            'ok' => true,
            'docente' => $perfil,
        ]);
    }

    public function aprobar(Request $request, $usuarioId)
    {
        if (!$this->usuarioEsAdmin($request)) {
            return $this->respuestaNoAutorizado();
        }

        $perfil = PerfilDocente::with('usuario')
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Aprobar cambios de perfil de un docente ya aprobado
        |--------------------------------------------------------------------------
        */
        if (
            $perfil->estado_aprobacion === 'aprobado' &&
            $perfil->estado_revision_cambios === 'pendiente'
        ) {
            $cambios = $perfil->cambios_pendientes_json ?? [];

            $perfil->update([
                'estudios' => $cambios['estudios'] ?? $perfil->estudios,
                'especialidad' => $cambios['especialidad'] ?? $perfil->especialidad,
                'biografia' => $cambios['biografia'] ?? $perfil->biografia,
                'cambios_pendientes_json' => null,
                'estado_revision_cambios' => null,
                'motivo_rechazo_cambios' => null,
                'fecha_solicitud_cambios' => null,
            ]);

            $this->enviarCorreoSeguro(
                $perfil->usuario->email,
                'Cambios de perfil docente aprobados',
                "Hola {$perfil->usuario->nombres}, los cambios de tu perfil docente fueron aprobados y ya están activos en EnglishNest."
            );

            return response()->json([
                'ok' => true,
                'mensaje' => 'Cambios del perfil docente aprobados correctamente.',
                'docente' => $perfil->fresh('usuario'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Aprobar solicitud inicial
        |--------------------------------------------------------------------------
        */
        $perfil->update([
            'estado_aprobacion' => 'aprobado',
            'motivo_rechazo' => null,
        ]);

        $this->enviarCorreoSeguro(
            $perfil->usuario->email,
            'Solicitud docente aprobada',
            "Hola {$perfil->usuario->nombres}, tu solicitud como docente fue aprobada. Ya puedes gestionar cursos en EnglishNest."
        );

        return response()->json([
            'ok' => true,
            'mensaje' => 'Docente aprobado correctamente.',
            'docente' => $perfil->fresh('usuario'),
        ]);
    }

    public function rechazar(Request $request, $usuarioId)
    {
        if (!$this->usuarioEsAdmin($request)) {
            return $this->respuestaNoAutorizado();
        }

        $data = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:5'],
        ]);

        $perfil = PerfilDocente::with('usuario')
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Rechazar cambios posteriores del perfil
        |--------------------------------------------------------------------------
        | Se mantiene el perfil oficial anterior.
        | El docente verá el motivo y podrá editar esos cambios otra vez.
        */
        if (
            $perfil->estado_aprobacion === 'aprobado' &&
            $perfil->estado_revision_cambios === 'pendiente'
        ) {
            $perfil->update([
                'estado_revision_cambios' => 'rechazado',
                'motivo_rechazo_cambios' => $data['motivo_rechazo'],
            ]);

            $this->enviarCorreoSeguro(
                $perfil->usuario->email,
                'Cambios de perfil docente rechazados',
                "Hola {$perfil->usuario->nombres}, los cambios de tu perfil docente fueron rechazados.\n\nMotivo:\n{$data['motivo_rechazo']}\n\nTu perfil oficial anterior sigue activo. Puedes corregir los cambios y enviarlos nuevamente."
            );

            return response()->json([
                'ok' => true,
                'mensaje' => 'Cambios del perfil docente rechazados correctamente.',
                'docente' => $perfil->fresh('usuario'),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Rechazar solicitud inicial
        |--------------------------------------------------------------------------
        */
        $perfil->update([
            'estado_aprobacion' => 'rechazado',
            'motivo_rechazo' => $data['motivo_rechazo'],
        ]);

        $this->enviarCorreoSeguro(
            $perfil->usuario->email,
            'Solicitud docente rechazada',
            "Hola {$perfil->usuario->nombres}, tu solicitud como docente fue rechazada.\n\nMotivo:\n{$data['motivo_rechazo']}\n\nPuedes corregir la información y volver a solicitar aprobación."
        );

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitud docente rechazada correctamente.',
            'docente' => $perfil->fresh('usuario'),
        ]);
    }

    private function enviarCorreoSeguro(string $correo, string $asunto, string $mensaje): void
    {
        try {
            Mail::raw($mensaje, function ($mail) use ($correo, $asunto) {
                $mail->to($correo)->subject($asunto);
            });
        } catch (\Throwable $e) {
            // No rompemos la acción si falla el correo.
        }
    }
}