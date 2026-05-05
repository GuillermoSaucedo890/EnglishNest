<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InscripcionController extends Controller
{
    private function usuarioConRol(Request $request)
    {
        $usuario = $request->user();

        if ($usuario) {
            $usuario->load('rol');
        }

        return $usuario;
    }

    private function esEstudiante($usuario): bool
    {
        return $usuario && $usuario->rol?->nombre === 'estudiante';
    }

    private function estadosActivos(): array
    {
        // Estados que siguen contando como curso suscrito.
        // Un curso completado/aprobado todavía pertenece al estudiante hasta que se desuscriba.
        return ['activa', 'activo', 'vigente', 'completada', 'aprobada', 'reprobada', 'bloqueada'];
    }

    private function obtenerPlanUsuario($usuario): string
    {
        $plan = $usuario->plan
            ?? $usuario->plan_actual
            ?? $usuario->tipo_plan
            ?? 'trial';

        return strtolower((string) $plan);
    }

    private function limiteCursosPorPlan($usuario): int
    {
        $plan = $this->obtenerPlanUsuario($usuario);

        return match ($plan) {
            'trial' => 1,
            'basico', 'básico' => 5,
            'intermedio' => 10,
            'premium' => 999999,
            default => 1,
        };
    }

    private function consultaInscripcionesUsuario($usuario)
    {
        return Inscripcion::where('usuario_id', $usuario->id);
    }

    private function cargarRelacionesInscripcion($consulta)
    {
        return $consulta->with([
            'curso.area:id,nombre',
            'curso.docente:id,nombres,apellidos,email',
        ]);
    }

    public function index(Request $request)
    {
        return $this->misCursos($request);
    }

    public function misCursos(Request $request)
    {
        $usuario = $this->usuarioConRol($request);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo los estudiantes pueden ver sus cursos.',
            ], 403);
        }

        $inscripciones = $this->cargarRelacionesInscripcion(
                $this->consultaInscripcionesUsuario($usuario)
            )
            ->latest()
            ->get();

        return response()->json([
            'ok' => true,
            'inscripciones' => $inscripciones,
        ]);
    }

    public function store(Request $request, $cursoId = null)
    {
        $cursoIdFinal = $cursoId ?? $request->input('curso_id');

        return $this->inscribirse($request, $cursoIdFinal);
    }

    public function inscribirse(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo una cuenta de estudiante puede inscribirse a cursos.',
            ], 403);
        }

        $curso = Curso::where('estado', 'publicado')->findOrFail($cursoId);

        $inscripcionExistente = Inscripcion::where('usuario_id', $usuario->id)
            ->where('curso_id', $curso->id)
            ->latest()
            ->first();

        if ($inscripcionExistente && in_array($inscripcionExistente->estado, $this->estadosActivos(), true)) {
            return response()->json([
                'ok' => true,
                'mensaje' => 'Ya estás inscrito en este curso.',
                'inscripcion' => $inscripcionExistente->load([
                    'curso.area:id,nombre',
                    'curso.docente:id,nombres,apellidos,email',
                ]),
            ]);
        }

        $cantidadActivas = Inscripcion::where('usuario_id', $usuario->id)
            ->whereIn('estado', $this->estadosActivos())
            ->count();

        $limite = $this->limiteCursosPorPlan($usuario);

        if ($cantidadActivas >= $limite) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Ya alcanzaste el límite de cursos de tu plan. Puedes mejorar tu plan o desuscribirte de otro curso.',
            ], 422);
        }

        if ($inscripcionExistente) {
            $inscripcionExistente->estado = 'activa';

            if (Schema::hasColumn('inscripciones', 'fecha_cancelacion')) {
                $inscripcionExistente->fecha_cancelacion = null;
            }

            if (Schema::hasColumn('inscripciones', 'fecha_inscripcion') && !$inscripcionExistente->fecha_inscripcion) {
                $inscripcionExistente->fecha_inscripcion = now();
            }

            $inscripcionExistente->save();

            return response()->json([
                'ok' => true,
                'mensaje' => 'Te reinscribiste correctamente al curso.',
                'inscripcion' => $inscripcionExistente->fresh([
                    'curso.area:id,nombre',
                    'curso.docente:id,nombres,apellidos,email',
                ]),
            ]);
        }

        $inscripcion = new Inscripcion();
        $inscripcion->usuario_id = $usuario->id;
        $inscripcion->curso_id = $curso->id;
        $inscripcion->estado = 'activa';

        if (Schema::hasColumn('inscripciones', 'progreso_porcentaje')) {
            $inscripcion->progreso_porcentaje = 0;
        }

        if (Schema::hasColumn('inscripciones', 'fecha_inscripcion')) {
            $inscripcion->fecha_inscripcion = now();
        }

        $inscripcion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Te inscribiste correctamente al curso.',
            'inscripcion' => $inscripcion->load([
                'curso.area:id,nombre',
                'curso.docente:id,nombres,apellidos,email',
            ]),
        ]);
    }

    public function cancelarInscripcion(Request $request, $cursoId)
    {
        return $this->cancelar($request, $cursoId);
    }

    public function cancelar(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo una cuenta de estudiante puede desuscribirse de cursos.',
            ], 403);
        }

        $inscripcion = Inscripcion::where('usuario_id', $usuario->id)
            ->where('curso_id', $cursoId)
            ->whereNotIn('estado', ['cancelada', 'cancelado'])
            ->latest()
            ->first();

        if (!$inscripcion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes una inscripción activa o completada en este curso.',
            ], 404);
        }

        $inscripcion->estado = 'cancelada';

        if (Schema::hasColumn('inscripciones', 'fecha_cancelacion')) {
            $inscripcion->fecha_cancelacion = now();
        }

        $inscripcion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Te desuscribiste del curso. Tu progreso se conserva para retomarlo después.',
            'inscripcion' => $inscripcion->fresh([
                'curso.area:id,nombre',
                'curso.docente:id,nombres,apellidos,email',
            ]),
        ]);
    }

    public function destroy(Request $request, $cursoId)
    {
        return $this->cancelar($request, $cursoId);
    }
}