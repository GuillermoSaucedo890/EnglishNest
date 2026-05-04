<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Rol;
use App\Models\User;
use App\Models\Suscripcion;
use App\Models\PerfilDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function mostrarRegistroEstudiante()
    {
        return view('auth.registro-estudiante');
    }

    public function mostrarRegistroDocente()
    {
        return view('auth.registro-docente');
    }

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

        Auth::login($usuario);

        return redirect()->route('verification.notice')
            ->with('ok', 'Registro exitoso. Revisa tu correo para verificar tu cuenta.');
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

        Auth::login($usuario);

        return redirect()->route('verification.notice')
            ->with('ok', 'Registro de docente enviado. Revisa tu correo y espera aprobación del administrador.');
    }

    public function iniciarSesion(Request $request)
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credenciales, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'Correo o contraseña incorrectos.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->route('panel');
    }

    public function cerrarSesion(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
