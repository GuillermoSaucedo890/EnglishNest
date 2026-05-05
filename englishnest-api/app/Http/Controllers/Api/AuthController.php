<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContrasenaHistorial;
use App\Models\PerfilDocente;
use App\Models\Plan;
use App\Models\Rol;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password as PasswordFacade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    // Reglas de contraseña reutilizables
    private function reglasPassword(): array
    {
        return [
            'required',
            'confirmed',
            Password::min(10)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols(),
        ];
    }

    // Registrar estudiante y darle plan TRIAL automáticamente
    public function registrarEstudiante(Request $request)
    {
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->reglasPassword(),
        ]);

        $rolEstudiante = Rol::where('nombre', 'estudiante')->firstOrFail();
        $planTrial = Plan::where('nombre', 'TRIAL')->firstOrFail();

        $usuario = DB::transaction(function () use ($request, $rolEstudiante, $planTrial) {
            $usuario = User::create([
                'rol_id' => $rolEstudiante->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'estado' => 'activo',
                'fecha_prueba_usada' => now(),
            ]);

            // Guardamos la primera contraseña en historial
            ContrasenaHistorial::create([
                'usuario_id' => $usuario->id,
                'password_hash' => $usuario->password,
            ]);

            // Creamos trial inicial
            Suscripcion::create([
                'usuario_id' => $usuario->id,
                'plan_id' => $planTrial->id,
                'estado' => 'activa',
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addDays($planTrial->duracion_dias),
                'renovacion_automatica' => false,
            ]);

            return $usuario;
        });

        // Envía correo de verificación
        event(new Registered($usuario));

        $token = $usuario->createToken('angular-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Estudiante registrado correctamente. Revisa tu correo para verificar tu cuenta.',
            'token' => $token,
            'usuario' => $usuario->load('rol'),
        ], 201);
    }

    // Registrar docente como solicitud pendiente
    public function registrarDocente(Request $request)
    {
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => $this->reglasPassword(),
            'estudios' => ['required', 'string'],
            'especialidad' => ['nullable', 'string', 'max:120'],
            'biografia' => ['nullable', 'string'],
        ]);

        $rolDocente = Rol::where('nombre', 'docente')->firstOrFail();

        $usuario = DB::transaction(function () use ($request, $rolDocente) {
            $usuario = User::create([
                'rol_id' => $rolDocente->id,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'estado' => 'activo',
                'fecha_prueba_usada' => null,
            ]);

            ContrasenaHistorial::create([
                'usuario_id' => $usuario->id,
                'password_hash' => $usuario->password,
            ]);

            PerfilDocente::create([
                'usuario_id' => $usuario->id,
                'estudios' => $request->estudios,
                'especialidad' => $request->especialidad,
                'biografia' => $request->biografia,
                'estado_aprobacion' => 'pendiente',
                'motivo_rechazo' => null,
            ]);

            return $usuario;
        });

        event(new Registered($usuario));

        $token = $usuario->createToken('angular-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Solicitud enviada correctamente. Revisa tu correo y espera aprobación del administrador.',
            'token' => $token,
            'usuario' => $usuario->load(['rol', 'perfilDocente']),
        ], 201);
    }

    // Login con límite de dispositivos según plan
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $usuario = User::where('email', $request->email)
            ->with(['rol', 'perfilDocente'])
            ->first();

        if (!$usuario || !Hash::check($request->password, $usuario->password)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Credenciales incorrectas.',
            ], 401);
        }

        if ($usuario->estado !== 'activo') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Tu cuenta está bloqueada. Contacta con soporte.',
            ], 403);
        }

        $limiteDispositivos = 1;

        if ($usuario->rol?->nombre === 'estudiante') {
            $suscripcion = Suscripcion::where('usuario_id', $usuario->id)
                ->where('estado', 'activa')
                ->where('fecha_fin', '>', now())
                ->with('plan')
                ->latest('fecha_fin')
                ->first();

            if ($suscripcion?->plan) {
                $limiteDispositivos = $suscripcion->plan->limite_dispositivos;
            }
        }

        // Si supera el límite, borra sesiones antiguas
        $tokensActuales = $usuario->tokens()->orderBy('created_at', 'asc')->get();

        if ($tokensActuales->count() >= $limiteDispositivos) {
            $cantidadABorrar = ($tokensActuales->count() - $limiteDispositivos) + 1;

            for ($i = 0; $i < $cantidadABorrar; $i++) {
                $tokensActuales[$i]->delete();
            }
        }

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Sesión iniciada correctamente.',
            'token' => $token,
            'usuario' => $this->formatearUsuario($usuario),
        ]);
    }

    // Datos del usuario autenticado
    public function me(Request $request)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente', 'suscripcionActiva.plan']);

        return response()->json([
            'ok' => true,
            'usuario' => $this->formatearUsuario($usuario),
        ]);
    }

    // Cerrar sesión actual
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    // Reenviar correo de verificación
    public function reenviarVerificacion(Request $request)
    {
        $usuario = $request->user();

        if ($usuario->hasVerifiedEmail()) {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Tu correo ya está verificado.',
            ]);
        }

        $usuario->sendEmailVerificationNotification();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Correo de verificación reenviado correctamente.',
        ]);
    }

    // Envía enlace de recuperación de contraseña
    public function enviarEnlaceRecuperacion(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $estado = PasswordFacade::broker()->sendResetLink(
            $request->only('email')
        );

        if ($estado === PasswordFacade::RESET_LINK_SENT) {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Te enviamos un correo con el enlace para recuperar tu contraseña.',
            ]);
        }

        return response()->json([
            'ok' => false,
            'mensaje' => 'No se pudo enviar el correo de recuperación.',
        ], 500);
    }

    // Restablece contraseña usando token real de Laravel
    public function restablecerContrasena(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => $this->reglasPassword(),
        ]);

        $usuario = User::where('email', $request->email)->firstOrFail();

        // Validar si ya usó esa contraseña antes
        $historial = ContrasenaHistorial::where('usuario_id', $usuario->id)->get();

        foreach ($historial as $registro) {
            if (Hash::check($request->password, $registro->password_hash)) {
                return response()->json([
                    'ok' => false,
                    'mensaje' => 'Ya usaste esta contraseña el ' . $registro->created_at->format('d/m/Y H:i') . '. Por seguridad elige una contraseña diferente.',
                ], 422);
            }
        }

        $estado = PasswordFacade::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($usuario) use ($request) {
                $usuario->password = Hash::make($request->password);
                $usuario->save();

                ContrasenaHistorial::create([
                    'usuario_id' => $usuario->id,
                    'password_hash' => $usuario->password,
                ]);

                // Cerramos sesiones anteriores por seguridad
                $usuario->tokens()->delete();
            }
        );

        if ($estado === PasswordFacade::PASSWORD_RESET) {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Contraseña actualizada correctamente.',
            ]);
        }

        return response()->json([
            'ok' => false,
            'mensaje' => 'El enlace de recuperación es inválido o ya expiró.',
        ], 422);
    }

    // Respuesta limpia para Angular
    private function formatearUsuario(User $usuario): array
    {
        return [
            'id' => $usuario->id,
            'nombres' => $usuario->nombres,
            'apellidos' => $usuario->apellidos,
            'email' => $usuario->email,
            'estado' => $usuario->estado,
            'correo_verificado' => $usuario->email_verified_at ? true : false,
            'email_verified_at' => $usuario->email_verified_at,
            'rol' => $usuario->rol?->nombre,
            'perfil_docente' => $usuario->perfilDocente,
            'suscripcion_activa' => $usuario->suscripcionActiva,
        ];
    }
}