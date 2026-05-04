<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PerfilDocente;
use App\Models\Plan;
use App\Models\Rol;
use App\Models\Suscripcion;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function registrarEstudiante(Request $request)
    {
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
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

            Suscripcion::create([
                'user_id' => $usuario->id,
                'plan_id' => $planTrial->id,
                'metodo_pago' => 'TRIAL',
                'estado' => 'activa',
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addDays($planTrial->duracion_dias),
                'renovacion_automatica' => false,
            ]);

            return $usuario;
        });

        event(new Registered($usuario));

        $token = $usuario->createToken('angular-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Estudiante registrado correctamente. Revisa tu correo para verificar tu cuenta.',
            'token' => $token,
        ], 201);
    }

    public function registrarDocente(Request $request)
    {
        $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
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

            PerfilDocente::create([
                'usuario_id' => $usuario->id,
                'estudios' => $request->estudios,
                'especialidad' => $request->especialidad,
                'biografia' => $request->biografia,
                'estado_aprobacion' => 'pendiente',
            ]);

            return $usuario;
        });

        event(new Registered($usuario));

        $token = $usuario->createToken('angular-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Docente registrado correctamente. Revisa tu correo y espera aprobación del administrador.',
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credenciales)) {
            throw ValidationException::withMessages([
                'email' => ['Correo o contraseña incorrectos.'],
            ]);
        }

        $usuario = User::where('email', $request->email)
            ->with(['rol', 'perfilDocente'])
            ->firstOrFail();

        if ($usuario->estado !== 'activo') {
            throw ValidationException::withMessages([
                'email' => ['Tu cuenta no está activa.'],
            ]);
        }

        $token = $usuario->createToken('angular-token')->plainTextToken;

        return response()->json([
            'ok' => true,
            'mensaje' => 'Inicio de sesión correcto.',
            'token' => $token,
            'usuario' => [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'estado' => $usuario->estado,
                'correo_verificado' => $usuario->email_verified_at ? true : false,
                'rol' => $usuario->rol?->nombre,
                'perfil_docente' => $usuario->perfilDocente,
            ],
        ]);
    }

    public function me(Request $request)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);

        return response()->json([
            'ok' => true,
            'usuario' => [
                'id' => $usuario->id,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'email' => $usuario->email,
                'estado' => $usuario->estado,
                'correo_verificado' => $usuario->email_verified_at ? true : false,
                'rol' => $usuario->rol?->nombre,
                'perfil_docente' => $usuario->perfilDocente,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function reenviarVerificacion(Request $request)
    {
        $usuario = $request->user();

        if ($usuario->hasVerifiedEmail()) {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Tu correo ya estaba verificado.',
            ]);
        }

        $usuario->sendEmailVerificationNotification();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Correo de verificación reenviado correctamente.',
        ]);
    }
}
