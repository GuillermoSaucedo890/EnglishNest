<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\Evaluacion;
use App\Models\Inscripcion;
use App\Models\IntentoEvaluacion;
use App\Models\Leccion;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\ProgresoLeccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EvaluacionController extends Controller
{
    // Cada cuestionario o examen individual se aprueba con 70%.
    private int $notaMinimaEvaluacion = 51;

    // El curso completo se aprueba con 51 puntos finales.
    private int $notaMinimaCurso = 51;

    // Cada cuestionario y examen final tiene 2 intentos.
    // Si quiere mejorar después de esos 2, debe repetir el curso.
    private int $maxIntentosPorEvaluacion = 2;

    private function usuarioConRol(Request $request)
    {
        $usuario = $request->user();

        if ($usuario) {
            $usuario->load('rol');
        }

        return $usuario;
    }

    private function rolUsuario($usuario): string
    {
        if (!$usuario) {
            return '';
        }

        return strtolower((string) ($usuario->rol?->nombre ?? ''));
    }

    private function esAdmin($usuario): bool
    {
        return $this->rolUsuario($usuario) === 'admin';
    }

    private function esDocente($usuario): bool
    {
        return $this->rolUsuario($usuario) === 'docente';
    }

    private function esEstudiante($usuario): bool
    {
        return $this->rolUsuario($usuario) === 'estudiante';
    }

    private function puedeEditarCurso($usuario, Curso $curso): bool
    {
        if ($this->esAdmin($usuario)) {
            return true;
        }

        return $this->esDocente($usuario)
            && (int) $curso->docente_id === (int) $usuario->id;
    }

    private function puedeEditarEvaluacion($usuario, Evaluacion $evaluacion): bool
    {
        $evaluacion->loadMissing('curso');

        return $this->puedeEditarCurso($usuario, $evaluacion->curso);
    }

    private function esIntroduccion(Leccion $leccion): bool
    {
        return $leccion->tipo === 'introduccion' || (int) $leccion->orden === 1;
    }

    private function inscripcionDelEstudiante($usuario, int $cursoId): ?Inscripcion
    {
        if (!$this->esEstudiante($usuario)) {
            return null;
        }

        return Inscripcion::where('usuario_id', $usuario->id)
            ->where('curso_id', $cursoId)
            ->latest()
            ->first();
    }

    private function inscripcionActivaOCompletada($usuario, int $cursoId): ?Inscripcion
    {
        $inscripcion = $this->inscripcionDelEstudiante($usuario, $cursoId);

        if (!$inscripcion) {
            return null;
        }

        if (in_array($inscripcion->estado, ['activa', 'completada'], true)) {
            return $inscripcion;
        }

        return null;
    }

    private function ocultarRespuestasCorrectas(Evaluacion $evaluacion): array
    {
        $evaluacion->load([
            'curso:id,titulo',
            'leccion:id,curso_id,titulo,orden,tipo',
            'preguntas.opciones',
        ]);

        // Para estudiantes mezclamos el orden de preguntas y opciones.
        // Así no ven siempre el mismo orden.
        $preguntasAleatorias = $evaluacion->preguntas->shuffle()->values();

        return [
            'id' => $evaluacion->id,
            'curso_id' => $evaluacion->curso_id,
            'leccion_id' => $evaluacion->leccion_id,
            'titulo' => $evaluacion->titulo,
            'tipo' => $evaluacion->tipo,
            'puntaje_minimo_aprobacion' => $evaluacion->puntaje_minimo_aprobacion,
            'estado' => $evaluacion->estado,
            'curso' => $evaluacion->curso,
            'leccion' => $evaluacion->leccion,
            'preguntas' => $preguntasAleatorias->map(function ($pregunta) {
                return [
                    'id' => $pregunta->id,
                    'evaluacion_id' => $pregunta->evaluacion_id,
                    'pregunta' => $pregunta->pregunta,
                    'orden' => $pregunta->orden,
                    'opciones' => $pregunta->opciones->shuffle()->values()->map(function ($opcion) {
                        return [
                            'id' => $opcion->id,
                            'pregunta_id' => $opcion->pregunta_id,
                            'texto' => $opcion->texto,
                            'orden' => $opcion->orden,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    private function validarPreguntas(Request $request, string $tipoEvaluacion): array
    {
        $minPreguntas = $tipoEvaluacion === 'examen_final' ? 6 : 3;
        $maxPreguntas = $tipoEvaluacion === 'examen_final' ? 10 : 6;

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'preguntas' => ['required', 'array', 'min:' . $minPreguntas, 'max:' . $maxPreguntas],
            'preguntas.*.pregunta' => ['required', 'string', 'max:600'],

            // Dejamos 3 opciones exactas para que sea simple y estable.
            'preguntas.*.opciones' => ['required', 'array', 'size:3'],
            'preguntas.*.opciones.*.texto' => ['required', 'string', 'max:300'],
            'preguntas.*.opciones.*.es_correcta' => ['required', 'boolean'],
        ]);

        foreach ($data['preguntas'] as $pregunta) {
            $correctas = collect($pregunta['opciones'])
                ->where('es_correcta', true)
                ->count();

            if ($correctas !== 1) {
                throw ValidationException::withMessages([
                    'preguntas' => 'Cada pregunta debe tener exactamente una opción correcta.',
                ]);
            }
        }

        return $data;
    }

    public function porLeccion(Request $request, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);

        $leccion = Leccion::with('curso')->findOrFail($leccionId);

        if ($this->esIntroduccion($leccion)) {
            return response()->json([
                'ok' => true,
                'evaluacion' => null,
                'mensaje' => 'La introducción no tiene cuestionario.',
            ]);
        }

        $evaluacion = Evaluacion::firstOrCreate(
            [
                'leccion_id' => $leccion->id,
                'tipo' => 'quiz_leccion',
            ],
            [
                'curso_id' => $leccion->curso_id,
                'titulo' => 'Cuestionario de la lección',
                'puntaje_minimo_aprobacion' => $this->notaMinimaEvaluacion,
                'estado' => 'borrador',
            ]
        );

        $evaluacion->load('preguntas.opciones', 'leccion', 'curso');

        if ($this->puedeEditarEvaluacion($usuario, $evaluacion)) {
            return response()->json([
                'ok' => true,
                'modo' => 'edicion',
                'evaluacion' => $evaluacion,
            ]);
        }

        return response()->json([
            'ok' => true,
            'modo' => 'estudiante',
            'evaluacion' => $this->ocultarRespuestasCorrectas($evaluacion),
        ]);
    }

    public function guardarPorLeccion(Request $request, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);

        $leccion = Leccion::with('curso')->findOrFail($leccionId);

        if ($this->esIntroduccion($leccion)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'La introducción no debe tener cuestionario.',
            ], 422);
        }

        $evaluacion = Evaluacion::firstOrCreate(
            [
                'leccion_id' => $leccion->id,
                'tipo' => 'quiz_leccion',
            ],
            [
                'curso_id' => $leccion->curso_id,
                'titulo' => 'Cuestionario de la lección',
                'puntaje_minimo_aprobacion' => $this->notaMinimaEvaluacion,
                'estado' => 'borrador',
            ]
        );

        if (!$this->puedeEditarEvaluacion($usuario, $evaluacion)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este cuestionario.',
            ], 403);
        }

        $data = $this->validarPreguntas($request, 'quiz_leccion');

        $evaluacion = DB::transaction(function () use ($evaluacion, $data) {
            $evaluacion->titulo = $data['titulo'];
            $evaluacion->puntaje_minimo_aprobacion = $this->notaMinimaEvaluacion;
            $evaluacion->estado = 'activa';
            $evaluacion->save();

            $evaluacion->preguntas()->delete();

            foreach ($data['preguntas'] as $indicePregunta => $preguntaData) {
                $pregunta = Pregunta::create([
                    'evaluacion_id' => $evaluacion->id,
                    'pregunta' => $preguntaData['pregunta'],
                    'orden' => $indicePregunta + 1,
                ]);

                foreach ($preguntaData['opciones'] as $indiceOpcion => $opcionData) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'texto' => $opcionData['texto'],
                        'es_correcta' => (bool) $opcionData['es_correcta'],
                        'orden' => $indiceOpcion + 1,
                    ]);
                }
            }

            return $evaluacion->fresh('preguntas.opciones');
        });

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cuestionario guardado correctamente.',
            'evaluacion' => $evaluacion,
        ]);
    }

    public function finalPorCurso(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);

        $curso = Curso::findOrFail($cursoId);

        $evaluacion = Evaluacion::firstOrCreate(
            [
                'curso_id' => $curso->id,
                'tipo' => 'examen_final',
            ],
            [
                'leccion_id' => null,
                'titulo' => 'Evaluación final',
                'puntaje_minimo_aprobacion' => $this->notaMinimaEvaluacion,
                'estado' => 'borrador',
            ]
        );

        $evaluacion->load('preguntas.opciones', 'curso');

        if ($this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => true,
                'modo' => 'edicion',
                'evaluacion' => $evaluacion,
            ]);
        }

        return response()->json([
            'ok' => true,
            'modo' => 'estudiante',
            'evaluacion' => $this->ocultarRespuestasCorrectas($evaluacion),
        ]);
    }

    public function guardarFinalPorCurso(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);

        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar la evaluación final.',
            ], 403);
        }

        $data = $this->validarPreguntas($request, 'examen_final');

        $evaluacion = Evaluacion::firstOrCreate(
            [
                'curso_id' => $curso->id,
                'tipo' => 'examen_final',
            ],
            [
                'leccion_id' => null,
                'titulo' => 'Evaluación final',
                'puntaje_minimo_aprobacion' => $this->notaMinimaEvaluacion,
                'estado' => 'borrador',
            ]
        );

        $evaluacion = DB::transaction(function () use ($evaluacion, $data) {
            $evaluacion->titulo = $data['titulo'];
            $evaluacion->puntaje_minimo_aprobacion = $this->notaMinimaEvaluacion;
            $evaluacion->estado = 'activa';
            $evaluacion->save();

            $evaluacion->preguntas()->delete();

            foreach ($data['preguntas'] as $indicePregunta => $preguntaData) {
                $pregunta = Pregunta::create([
                    'evaluacion_id' => $evaluacion->id,
                    'pregunta' => $preguntaData['pregunta'],
                    'orden' => $indicePregunta + 1,
                ]);

                foreach ($preguntaData['opciones'] as $indiceOpcion => $opcionData) {
                    OpcionPregunta::create([
                        'pregunta_id' => $pregunta->id,
                        'texto' => $opcionData['texto'],
                        'es_correcta' => (bool) $opcionData['es_correcta'],
                        'orden' => $indiceOpcion + 1,
                    ]);
                }
            }

            return $evaluacion->fresh('preguntas.opciones');
        });

        return response()->json([
            'ok' => true,
            'mensaje' => 'Evaluación final guardada correctamente.',
            'evaluacion' => $evaluacion,
        ]);
    }

    public function estado(Request $request, $evaluacionId)
    {
        $usuario = $this->usuarioConRol($request);

        $evaluacion = Evaluacion::with('curso')->findOrFail($evaluacionId);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => true,
                'intentos_usados' => 0,
                'intentos_restantes' => $this->maxIntentosPorEvaluacion,
                'aprobado' => false,
                'ultimo_intento' => null,
                'mejor_intento' => null,
                'puede_repetir_curso' => false,
            ]);
        }

        $intentos = IntentoEvaluacion::where('usuario_id', $usuario->id)
            ->where('evaluacion_id', $evaluacion->id)
            ->orderByDesc('id')
            ->get();

        $mejorIntento = IntentoEvaluacion::where('usuario_id', $usuario->id)
            ->where('evaluacion_id', $evaluacion->id)
            ->orderByDesc('porcentaje')
            ->first();

        $aprobado = $intentos->where('aprobado', true)->count() > 0;
        $intentosUsados = $intentos->count();
        $intentosRestantes = max(0, $this->maxIntentosPorEvaluacion - $intentosUsados);

        return response()->json([
            'ok' => true,
            'intentos_usados' => $intentosUsados,
            'intentos_restantes' => $intentosRestantes,
            'aprobado' => $aprobado,
            'ultimo_intento' => $intentos->first(),
            'mejor_intento' => $mejorIntento,
            'puede_mejorar_nota' => $aprobado && $intentosRestantes > 0,
            'puede_repetir_curso' => $intentosUsados >= $this->maxIntentosPorEvaluacion,
            'mensaje' => $intentosUsados >= $this->maxIntentosPorEvaluacion
                ? 'Ya usaste tus intentos. Para mejorar o recuperar la nota debes repetir el curso.'
                : null,
        ]);
    }

    public function responder(Request $request, $evaluacionId)
    {
        $usuario = $this->usuarioConRol($request);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo los estudiantes pueden responder evaluaciones.',
            ], 403);
        }

        $data = $request->validate([
            'respuestas' => ['required', 'array'],
            'respuestas.*.pregunta_id' => ['required', 'integer', 'exists:preguntas,id'],
            'respuestas.*.opcion_id' => ['required', 'integer', 'exists:opciones_pregunta,id'],
        ]);

        $evaluacion = Evaluacion::with([
            'curso.lecciones',
            'leccion',
            'preguntas.opciones',
        ])->findOrFail($evaluacionId);

        if ($evaluacion->estado !== 'activa') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Esta evaluación todavía no está activa.',
            ], 422);
        }

        $inscripcion = $this->inscripcionActivaOCompletada($usuario, $evaluacion->curso_id);

        if (!$inscripcion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Necesitas una inscripción activa para responder esta evaluación.',
            ], 403);
        }

        if ($evaluacion->preguntas->count() <= 0) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Esta evaluación todavía no tiene preguntas.',
            ], 422);
        }

        $intentosUsados = IntentoEvaluacion::where('usuario_id', $usuario->id)
            ->where('evaluacion_id', $evaluacion->id)
            ->count();

        $yaAproboEvaluacion = IntentoEvaluacion::where('usuario_id', $usuario->id)
            ->where('evaluacion_id', $evaluacion->id)
            ->where('aprobado', true)
            ->exists();

        $mejorIntentoPrevio = IntentoEvaluacion::where('usuario_id', $usuario->id)
            ->where('evaluacion_id', $evaluacion->id)
            ->orderByDesc('porcentaje')
            ->first();

        if ($intentosUsados >= $this->maxIntentosPorEvaluacion) {
            $calificacionActual = $this->actualizarNotaCurso($usuario->id, $evaluacion->curso_id);

            if ($evaluacion->tipo === 'examen_final' && !$calificacionActual['aprobado_curso']) {
                $this->bloquearInscripcionPorReprobacion($inscripcion);
                $inscripcion = $inscripcion->fresh();
            }

            return response()->json([
                'ok' => false,
                'mensaje' => $evaluacion->tipo === 'examen_final'
                    ? 'Ya usaste tus 2 intentos de la evaluación final. Si tu nota final no alcanza, debes repetir el curso.'
                    : 'Ya usaste tus 2 intentos de este cuestionario. Puedes continuar con la siguiente lección o repetir el curso si quieres mejorar la nota.',
                'debe_repetir_curso' => $evaluacion->tipo === 'examen_final' && !$calificacionActual['aprobado_curso'],
                'aprobado' => $yaAproboEvaluacion,
                'intentos_usados' => $intentosUsados,
                'intentos_restantes' => 0,
                'mejor_intento' => $mejorIntentoPrevio,
                'calificacion' => $calificacionActual,
                'inscripcion' => $inscripcion,
            ], 422);
        }

        $respuestas = collect($data['respuestas']);

        $correctas = 0;
        $detalle = [];

        foreach ($evaluacion->preguntas as $pregunta) {
            $respuesta = $respuestas->firstWhere('pregunta_id', $pregunta->id);
            $opcionElegidaId = $respuesta['opcion_id'] ?? null;

            $opcionCorrecta = $pregunta->opciones->firstWhere('es_correcta', true);
            $esCorrecta = $opcionCorrecta && (int) $opcionCorrecta->id === (int) $opcionElegidaId;

            if ($esCorrecta) {
                $correctas++;
            }

            $detalle[] = [
                'pregunta_id' => $pregunta->id,
                'opcion_elegida_id' => $opcionElegidaId,
                'opcion_correcta_id' => $opcionCorrecta?->id,
                'correcta' => $esCorrecta,
            ];
        }

        $totalPreguntas = $evaluacion->preguntas->count();
        $porcentaje = round(($correctas / $totalPreguntas) * 100, 2);
        $aprobadoEvaluacion = $porcentaje >= $this->notaMinimaEvaluacion;
        $numeroIntento = $intentosUsados + 1;

        $resultado = DB::transaction(function () use (
            $usuario,
            $evaluacion,
            $inscripcion,
            $numeroIntento,
            $correctas,
            $porcentaje,
            $aprobadoEvaluacion,
            $detalle
        ) {
            $intento = IntentoEvaluacion::create([
                'usuario_id' => $usuario->id,
                'evaluacion_id' => $evaluacion->id,
                'intento_numero' => $numeroIntento,
                'puntaje' => $correctas,
                'porcentaje' => $porcentaje,
                'aprobado' => $aprobadoEvaluacion,
                'respuestas_json' => $detalle,
                'fecha_intento' => now(),
            ]);

            // Regla del proyecto:
            // resolver el cuestionario de una lección ya permite avanzar a la siguiente,
            // aunque la nota no haya alcanzado 70. La nota igual cuenta para el promedio.
            if ($evaluacion->tipo === 'quiz_leccion' && $evaluacion->leccion_id) {
                ProgresoLeccion::updateOrCreate(
                    [
                        'usuario_id' => $usuario->id,
                        'leccion_id' => $evaluacion->leccion_id,
                    ],
                    [
                        'completado' => true,
                        'fecha_completado' => now(),
                        'ultima_visualizacion' => now(),
                    ]
                );
            }

            $calificacion = $this->actualizarNotaCurso($usuario->id, $evaluacion->curso_id);

            $inscripcionActualizada = $inscripcion->fresh();

            if (
                $evaluacion->tipo === 'examen_final' &&
                $numeroIntento >= $this->maxIntentosPorEvaluacion &&
                !$calificacion['aprobado_curso']
            ) {
                $this->bloquearInscripcionPorReprobacion($inscripcionActualizada);
                $inscripcionActualizada = $inscripcionActualizada->fresh();
            }

            return [
                'intento' => $intento,
                'calificacion' => $calificacion,
                'inscripcion' => $inscripcionActualizada,
            ];
        });

        $intentosRestantes = max(0, $this->maxIntentosPorEvaluacion - $numeroIntento);
        $cursoAprobado = (bool) ($resultado['calificacion']['aprobado_curso'] ?? false);
        $debeRepetirCurso = $evaluacion->tipo === 'examen_final'
            && !$cursoAprobado
            && $numeroIntento >= $this->maxIntentosPorEvaluacion;

        return response()->json([
            'ok' => true,
            'mensaje' => $evaluacion->tipo === 'examen_final'
                ? ($cursoAprobado
                    ? 'Curso aprobado correctamente. Tu certificado ya está disponible.'
                    : ($debeRepetirCurso
                        ? 'Tu nota final no alcanza para aprobar. Debes repetir el curso si quieres intentarlo nuevamente.'
                        : 'Evaluación final revisada. Todavía puedes usar otro intento para mejorar.'))
                : ($porcentaje >= 100
                    ? 'Cuestionario perfecto. Se desbloqueó la siguiente lección.'
                    : ($intentosRestantes > 0
                        ? 'Cuestionario revisado. Se desbloqueó la siguiente lección y todavía tienes un intento para mejorar.'
                        : 'Cuestionario revisado. Ya usaste tus intentos, pero puedes continuar con la siguiente lección.')),
            'aprobado' => $aprobadoEvaluacion,
            'curso_aprobado' => $cursoAprobado,
            'nota_minima' => $this->notaMinimaEvaluacion,
            'porcentaje' => $porcentaje,
            'correctas' => $correctas,
            'total_preguntas' => $totalPreguntas,
            'intentos_usados' => $numeroIntento,
            'intentos_restantes' => $intentosRestantes,
            'debe_repetir_curso' => $debeRepetirCurso,
            'puede_mejorar_nota' => $porcentaje < 100 && $intentosRestantes > 0,
            'intento' => $resultado['intento'],
            'calificacion' => $resultado['calificacion'],
            'inscripcion' => $resultado['inscripcion'],
        ]);
    }

    private function actualizarNotaCurso(int $usuarioId, int $cursoId): array
    {
        $quizzes = Evaluacion::where('curso_id', $cursoId)
            ->where('tipo', 'quiz_leccion')
            ->get();

        $sumaPorcentajesQuizzes = 0;
        $quizzesRespondidos = 0;
        $totalQuizzes = $quizzes->count();

        foreach ($quizzes as $quiz) {
            $mejorIntento = IntentoEvaluacion::where('usuario_id', $usuarioId)
                ->where('evaluacion_id', $quiz->id)
                ->orderByDesc('porcentaje')
                ->first();

            // Cada cuestionario se calcula sobre 100.
            // Luego el promedio de TODOS los cuestionarios representa 70 puntos del curso.
            // Si todavía no respondió un cuestionario, cuenta como 0 hasta que lo resuelva.
            if ($mejorIntento) {
                $sumaPorcentajesQuizzes += (float) $mejorIntento->porcentaje;
                $quizzesRespondidos++;
            }
        }

        $promedioQuizzes = $totalQuizzes > 0
            ? $sumaPorcentajesQuizzes / $totalQuizzes
            : 0;

        $evaluacionFinal = Evaluacion::where('curso_id', $cursoId)
            ->where('tipo', 'examen_final')
            ->first();

        $mejorFinal = null;
        $finalRespondida = false;

        if ($evaluacionFinal) {
            $mejorFinal = IntentoEvaluacion::where('usuario_id', $usuarioId)
                ->where('evaluacion_id', $evaluacionFinal->id)
                ->orderByDesc('porcentaje')
                ->first();

            $finalRespondida = $mejorFinal !== null;
        }

        $porcentajeFinal = $mejorFinal ? (float) $mejorFinal->porcentaje : 0;

        // Fórmula final:
        // Promedio de cuestionarios sobre 100 * 70% = hasta 70 puntos.
        $notaCuestionarios = round($promedioQuizzes * 0.70, 2);

        // Evaluación final sobre 100 * 30% = hasta 30 puntos.
        $notaExamenFinal = round($porcentajeFinal * 0.30, 2);

        $notaFinal = round($notaCuestionarios + $notaExamenFinal, 2);

        // El progreso académico cuenta cuestionarios + evaluación final como pasos.
        // Por eso solo llega a 100% cuando ya respondió también la evaluación final.
        $totalPasos = max(1, $totalQuizzes + 1);
        $pasosCompletados = $quizzesRespondidos + ($finalRespondida ? 1 : 0);
        $progreso = round(($pasosCompletados / $totalPasos) * 100, 2);

        // El curso se aprueba por nota final total, no por aprobar cada cuestionario individual.
        $aprobadoCurso = $notaFinal >= $this->notaMinimaCurso && $finalRespondida;

        $inscripcion = Inscripcion::where('usuario_id', $usuarioId)
            ->where('curso_id', $cursoId)
            ->latest()
            ->first();

        if ($inscripcion) {
            $inscripcion->nota_cuestionarios = $notaCuestionarios;
            $inscripcion->nota_examen_final = $notaExamenFinal;
            $inscripcion->nota_final = $notaFinal;
            $inscripcion->progreso_porcentaje = $progreso;
            $inscripcion->aprobado = $aprobadoCurso;
            $inscripcion->certificado_disponible = $aprobadoCurso;

            if ($aprobadoCurso) {
                $inscripcion->estado = 'completada';
                $inscripcion->fecha_completado = now();
                $inscripcion->fecha_reprobacion = null;
            } elseif ($inscripcion->estado === 'completada') {
                $inscripcion->estado = 'activa';
                $inscripcion->fecha_completado = null;
            }

            $inscripcion->save();
        }

        return [
            'nota_cuestionarios' => $notaCuestionarios,
            'nota_examen_final' => $notaExamenFinal,
            'nota_final' => $notaFinal,
            'nota_minima_curso' => $this->notaMinimaCurso,
            'aprobado_curso' => $aprobadoCurso,
            'certificado_disponible' => $aprobadoCurso,
            'progreso_porcentaje' => $progreso,
            'promedio_cuestionarios' => round($promedioQuizzes, 2),
            'porcentaje_evaluacion_final' => round($porcentajeFinal, 2),
            'evaluacion_final_respondida' => $finalRespondida,
        ];
    }

    private function bloquearInscripcionPorReprobacion(Inscripcion $inscripcion): void
    {
        $inscripcion->estado = 'reprobada';
        $inscripcion->aprobado = false;
        $inscripcion->certificado_disponible = false;
        $inscripcion->fecha_reprobacion = now();
        $inscripcion->save();
    }

    public function repetirCurso(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);

        if (!$this->esEstudiante($usuario)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo estudiantes pueden repetir cursos.',
            ], 403);
        }

        $inscripcion = Inscripcion::where('usuario_id', $usuario->id)
            ->where('curso_id', $cursoId)
            ->latest()
            ->first();

        if (!$inscripcion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes una inscripción en este curso.',
            ], 404);
        }

        if ($inscripcion->estado === 'cancelada') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Tu inscripción está cancelada. Debes inscribirte nuevamente al curso.',
            ], 422);
        }

        $curso = Curso::with('lecciones')->findOrFail($cursoId);

        DB::transaction(function () use ($usuario, $curso, $inscripcion) {
            $evaluacionIds = Evaluacion::where('curso_id', $curso->id)->pluck('id');

            // Borra intentos anteriores para que pueda repetir infinitamente,
            // pero siempre comenzando el curso desde cero.
            IntentoEvaluacion::where('usuario_id', $usuario->id)
                ->whereIn('evaluacion_id', $evaluacionIds)
                ->delete();

            $idsLeccionesCurso = Leccion::where('curso_id', $curso->id)->pluck('id');

            // Limpia todo el progreso del curso, incluida la introducción.
            // Usamos los ids reales de la tabla lecciones para evitar que quede una marca vieja
            // si la relación del curso fue cargada antes del último cambio.
            ProgresoLeccion::where('usuario_id', $usuario->id)
                ->whereIn('leccion_id', $idsLeccionesCurso)
                ->delete();

            $inscripcion->estado = 'activa';
            $inscripcion->progreso_porcentaje = 0;
            $inscripcion->nota_cuestionarios = 0;
            $inscripcion->nota_examen_final = 0;
            $inscripcion->nota_final = 0;
            $inscripcion->aprobado = false;
            $inscripcion->certificado_disponible = false;
            $inscripcion->fecha_reprobacion = null;
            $inscripcion->fecha_completado = null;
            $inscripcion->save();
        });

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso reiniciado correctamente. Puedes repetirlo desde el inicio.',
            'inscripcion' => $inscripcion->fresh(),
        ]);
    }
}