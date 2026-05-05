<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Curso;
use App\Models\CursoEdicion;
use App\Models\Evaluacion;
use App\Models\Leccion;
use App\Models\OpcionPregunta;
use App\Models\Pregunta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CursoController extends Controller
{
    public function obtenerAreas()
    {
        return response()->json([
            'ok' => true,
            'areas' => Area::orderBy('nombre')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $usuario = $this->usuarioConRol($request);
        $rol = $this->rolUsuario($usuario);

        $query = Curso::query()
            ->with(['area'])
            ->orderBy('created_at', 'desc');

        if ($rol === 'docente') {
            $query->where('docente_id', $usuario->id);
        }

        if ($rol !== 'admin' && $rol !== 'docente') {
            $query->where('estado', 'publicado');
        }

        $cursos = $query->get()->map(function ($curso) {
            return $this->respuestaCursoResumen($curso);
        });

        return response()->json([
            'ok' => true,
            'cursos' => $cursos,
        ]);
    }

    public function publicoIndex(Request $request)
    {
        $cursos = Curso::query()
            ->with(['area'])
            ->where('estado', 'publicado')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($curso) {
                return $this->respuestaCursoResumenPublico($curso);
            });

        return response()->json([
            'ok' => true,
            'cursos' => $cursos,
        ]);
    }

    public function show(Request $request, $id)
    {
        return $this->publicoShow($request, $id);
    }

    public function publicoShow(Request $request, $id)
    {
        $usuario = $request->user();

        if ($usuario) {
            $usuario->load('rol');
        }

        $rol = $this->rolUsuario($usuario);

        $curso = Curso::query()
            ->with(['area'])
            ->findOrFail($id);

        $puedeVerOficialAunqueEsteOculto =
            $rol === 'admin' ||
            ($rol === 'docente' && (int) $curso->docente_id === (int) $usuario?->id);

        if (!$puedeVerOficialAunqueEsteOculto && $curso->estado !== 'publicado') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Este curso no está disponible públicamente.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'curso' => $this->respuestaCursoOficial($curso),
        ]);
    }

    public function store(Request $request)
    {
        return $this->crearCurso($request);
    }

    public function crearCurso(Request $request)
    {
        $usuario = $this->usuarioConRol($request);

        if ($this->rolUsuario($usuario) !== 'admin') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede crear cursos.',
            ], 403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'docente_id' => ['required', 'integer', 'exists:users,id'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'nivel' => ['required', Rule::in(['basico', 'intermedio', 'avanzado'])],
        ]);

        $curso = new Curso();
        $curso->titulo = $data['titulo'];
        $curso->descripcion = $data['descripcion'];
        $curso->docente_id = $data['docente_id'];
        $curso->area_id = $data['area_id'];
        $curso->nivel = $data['nivel'];
        $curso->estado = 'borrador';
        $curso->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso creado correctamente.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function obtenerCursoEdicion(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        $curso = Curso::query()
            ->with(['area'])
            ->findOrFail($id);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'curso' => $this->respuestaCursoEdicion($curso),
        ]);
    }

    public function actualizarCursoEdicion(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        $curso = Curso::query()
            ->with(['area'])
            ->findOrFail($id);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['required', 'string'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'nivel' => ['required', Rule::in(['basico', 'intermedio', 'avanzado'])],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        $datos['titulo'] = $data['titulo'];
        $datos['descripcion'] = $data['descripcion'];
        $datos['area_id'] = (int) $data['area_id'];
        $datos['nivel'] = $data['nivel'];

        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cambios guardados en edición segura.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function crearLeccion(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        $lecciones = $datos['lecciones'] ?? [];
        $nuevoId = 'tmp_' . uniqid();

        $lecciones[] = [
            'id' => $nuevoId,
            'uid' => $nuevoId,
            'origen_id' => null,
            'curso_id' => (int) $curso->id,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'url_video' => $data['url_video'] ?? null,
            'orden' => count($lecciones) + 1,
            'tipo' => $data['tipo'] ?? 'normal',
            'es_gratis' => (bool) ($data['es_gratis'] ?? false),
            'activo' => true,
            'evaluacion' => ($data['tipo'] ?? 'normal') === 'introduccion' ? null : $this->evaluacionVacia('quiz_leccion'),
            '_estado_edicion' => 'nueva',
        ];

        $datos['lecciones'] = $this->reordenarLeccionesArray($lecciones);

        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección guardada en edición segura.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function actualizarLeccion(Request $request, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = $this->buscarCursoPorLeccionEnBorradorOOficial($leccionId);

        if (!$curso) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección.',
            ], 404);
        }

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar esta lección.',
            ], 403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string', 'max:1000'],
            'tipo' => ['nullable', 'string', 'max:50'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        $lecciones = $datos['lecciones'] ?? [];
        $indice = $this->buscarIndiceLeccion($lecciones, $leccionId);

        if ($indice === null) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección dentro de la edición.',
            ], 404);
        }

        $estadoActual = $lecciones[$indice]['_estado_edicion'] ?? 'oficial';

        $lecciones[$indice]['titulo'] = $data['titulo'];
        $lecciones[$indice]['descripcion'] = $data['descripcion'] ?? null;
        $lecciones[$indice]['contenido_texto'] = $data['contenido_texto'] ?? null;
        $lecciones[$indice]['url_video'] = $data['url_video'] ?? null;
        $lecciones[$indice]['tipo'] = $data['tipo'] ?? 'normal';
        $lecciones[$indice]['es_gratis'] = (bool) ($data['es_gratis'] ?? false);

        if ($estadoActual !== 'nueva') {
            $lecciones[$indice]['_estado_edicion'] = 'modificada';
        }

        $datos['lecciones'] = $this->reordenarLeccionesArray($lecciones);

        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección actualizada en edición segura.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function eliminarLeccion(Request $request, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = $this->buscarCursoPorLeccionEnBorradorOOficial($leccionId);

        if (!$curso) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección.',
            ], 404);
        }

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para quitar esta lección.',
            ], 403);
        }

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        $lecciones = $datos['lecciones'] ?? [];
        $indice = $this->buscarIndiceLeccion($lecciones, $leccionId);

        if ($indice === null) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección dentro de la edición.',
            ], 404);
        }

        unset($lecciones[$indice]);

        $datos['lecciones'] = $this->reordenarLeccionesArray(array_values($lecciones));

        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección quitada de la edición segura.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }


    public function obtenerEvaluacionLeccionEdicion(Request $request, $cursoId, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);
        $lecciones = $datos['lecciones'] ?? [];
        $indice = $this->buscarIndiceLeccion($lecciones, $leccionId);

        if ($indice === null) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección dentro de la edición segura.',
            ], 404);
        }

        $leccion = $lecciones[$indice];

        if ($this->esIntroduccionArray($leccion)) {
            return response()->json([
                'ok' => true,
                'evaluacion' => null,
                'mensaje' => 'La introducción no tiene cuestionario.',
            ]);
        }

        return response()->json([
            'ok' => true,
            'evaluacion' => $this->normalizarEvaluacionBorrador($leccion['evaluacion'] ?? null, 'quiz_leccion'),
        ]);
    }

    public function guardarEvaluacionLeccionEdicion(Request $request, $cursoId, $leccionId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);
        $lecciones = $datos['lecciones'] ?? [];
        $indice = $this->buscarIndiceLeccion($lecciones, $leccionId);

        if ($indice === null) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección dentro de la edición segura.',
            ], 404);
        }

        if ($this->esIntroduccionArray($lecciones[$indice])) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'La introducción no debe tener cuestionario.',
            ], 422);
        }

        $evaluacion = $this->validarEvaluacionBorrador($request, 'quiz_leccion');
        $lecciones[$indice]['evaluacion'] = $evaluacion;

        if (($lecciones[$indice]['_estado_edicion'] ?? 'oficial') !== 'nueva') {
            $lecciones[$indice]['_estado_edicion'] = 'modificada';
        }

        $datos['lecciones'] = $this->reordenarLeccionesArray($lecciones);
        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Cuestionario guardado en edición segura. Se publicará cuando el admin apruebe la edición.',
            'evaluacion' => $evaluacion,
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function obtenerEvaluacionFinalEdicion(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        return response()->json([
            'ok' => true,
            'evaluacion' => $this->normalizarEvaluacionBorrador($datos['evaluacion_final'] ?? null, 'examen_final'),
        ]);
    }

    public function guardarEvaluacionFinalEdicion(Request $request, $cursoId)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403);
        }

        $evaluacion = $this->validarEvaluacionBorrador($request, 'examen_final');

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);
        $datos['evaluacion_final'] = $evaluacion;

        $edicion->datos_json = $datos;
        $this->marcarEdicionComoBorradorSiCorresponde($edicion, $usuario);
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Evaluación final guardada en edición segura. Se publicará cuando el admin apruebe la edición.',
            'evaluacion' => $evaluacion,
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function enviarRevision(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);
        $curso = Curso::findOrFail($id);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para enviar este curso a revisión.',
            ], 403);
        }

        $data = $request->validate([
            'solicita_publicacion' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $this->datosEdicionOSnapshot($edicion, $curso);
        $errores = $this->erroresCursoListoParaRevision($datos);

        if (count($errores) > 0) {
            return response()->json([
                'ok' => false,
                'mensaje' => "Antes de enviar a revisión debes corregir:
- " . implode("
- ", $errores),
                'errores' => $errores,
                'curso' => $this->respuestaCursoEdicion($curso->fresh()),
            ], 422);
        }

        $edicion->estado = 'pendiente_revision';
        $edicion->solicita_publicacion = (bool) ($data['solicita_publicacion'] ?? true);
        $edicion->motivo_rechazo = null;
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso enviado a revisión.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function aprobarRevision(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        if ($this->rolUsuario($usuario) !== 'admin') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede aprobar ediciones.',
            ], 403);
        }

        $curso = Curso::findOrFail($id);
        $edicion = $this->edicionActiva($curso->id);

        if (!$edicion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Este curso no tiene una edición pendiente.',
            ], 422);
        }

        $datosRevision = $this->datosEdicionOSnapshot($edicion, $curso);
        $errores = $this->erroresCursoListoParaRevision($datosRevision);

        if (count($errores) > 0) {
            return response()->json([
                'ok' => false,
                'mensaje' => "No se puede aprobar/publicar todavía:
- " . implode("
- ", $errores),
                'errores' => $errores,
                'curso' => $this->respuestaCursoEdicion($curso->fresh()),
            ], 422);
        }

        DB::transaction(function () use ($curso, $edicion) {
            $datos = $this->normalizarDatosJson($edicion->datos_json);

            $curso->titulo = $datos['titulo'] ?? $curso->titulo;
            $curso->descripcion = $datos['descripcion'] ?? $curso->descripcion;
            $curso->area_id = $datos['area_id'] ?? $curso->area_id;
            $curso->nivel = $datos['nivel'] ?? $curso->nivel;
            $curso->estado = 'publicado';
            $curso->save();

            $mapaLecciones = $this->sincronizarLeccionesOficiales($curso, $datos['lecciones'] ?? []);
            $this->sincronizarEvaluacionesOficiales($curso, $datos, $mapaLecciones);

            $edicion->delete();
        });

        return response()->json([
            'ok' => true,
            'mensaje' => 'Edición aprobada y publicada correctamente.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function rechazarRevision(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        if ($this->rolUsuario($usuario) !== 'admin') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede rechazar ediciones.',
            ], 403);
        }

        $data = $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:5'],
        ]);

        $curso = Curso::findOrFail($id);
        $edicion = $this->edicionActiva($curso->id);

        if (!$edicion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Este curso no tiene una edición pendiente.',
            ], 422);
        }

        $edicion->estado = 'rechazado';
        $edicion->motivo_rechazo = $data['motivo_rechazo'];
        $edicion->solicita_publicacion = false;
        $edicion->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Edición rechazada correctamente.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function publicar(Request $request, $id)
    {
        return $this->publicarCurso($request, $id);
    }

    public function publicarCurso(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        if ($this->rolUsuario($usuario) !== 'admin') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede publicar cursos.',
            ], 403);
        }

        $curso = Curso::findOrFail($id);
        $errores = $this->erroresCursoOficialListoParaPublicar($curso);

        if (count($errores) > 0) {
            return response()->json([
                'ok' => false,
                'mensaje' => "No se puede publicar este curso todavía:
- " . implode("
- ", $errores),
                'errores' => $errores,
                'curso' => $this->respuestaCursoEdicion($curso->fresh()),
            ], 422);
        }

        $curso->estado = 'publicado';
        $curso->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso publicado correctamente.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    public function ocultar(Request $request, $id)
    {
        return $this->ocultarCurso($request, $id);
    }

    public function ocultarCurso(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        if ($this->rolUsuario($usuario) !== 'admin') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede ocultar cursos.',
            ], 403);
        }

        $curso = Curso::findOrFail($id);
        $curso->estado = 'oculto';
        $curso->save();

        return response()->json([
            'ok' => true,
            'mensaje' => 'Curso ocultado correctamente.',
            'curso' => $this->respuestaCursoEdicion($curso->fresh()),
        ]);
    }

    private function respuestaCursoResumen(Curso $curso): array
    {
        $curso->loadMissing(['area']);

        $edicion = $this->edicionActiva($curso->id);

        $estadoOficial = strtolower((string) $curso->estado);
        $estadoActual = $estadoOficial;
        $estadoEdicion = null;
        $tieneEdicion = false;
        $solicitaPublicacion = false;
        $motivoRechazo = null;

        if ($edicion) {
            $tieneEdicion = true;
            $estadoActual = strtolower((string) $edicion->estado);
            $estadoEdicion = strtolower((string) $edicion->estado);
            $solicitaPublicacion = (bool) $edicion->solicita_publicacion;
            $motivoRechazo = $edicion->motivo_rechazo;
        }

        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'nivel' => $curso->nivel,
            'area_id' => $curso->area_id,
            'area' => $curso->area,
            'docente_id' => $curso->docente_id,
            'docente_nombre' => $this->nombreDocenteAsignado($curso),

            // mantenemos estado por compatibilidad
            'estado' => $estadoOficial,

            // oficial
            'estado_oficial' => $estadoOficial,

            // actual / edición
            'estado_actual' => $estadoActual,
            'estado_edicion' => $estadoEdicion,
            'edicion_estado' => $estadoEdicion,

            'tiene_edicion' => $tieneEdicion,
            'solicita_publicacion' => $solicitaPublicacion,
            'motivo_rechazo' => $motivoRechazo,
        ];
    }

    private function respuestaCursoResumenPublico(Curso $curso): array
    {
        $curso->loadMissing(['area']);

        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'nivel' => $curso->nivel,
            'area_id' => $curso->area_id,
            'area' => $curso->area,
            'docente_id' => $curso->docente_id,
            'docente_nombre' => $this->nombreDocenteAsignado($curso),
            'estado' => $curso->estado,
            'lecciones_count' => $this->leccionesOficialesQuery($curso->id)->count(),
        ];
    }

    private function respuestaCursoOficial(Curso $curso): array
    {
        $curso->loadMissing(['area']);

        $lecciones = $this->leccionesOficialesQuery($curso->id)
            ->get()
            ->map(function ($leccion) {
                return $this->mapearLeccionOficial($leccion);
            })
            ->values()
            ->toArray();

        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'nivel' => $curso->nivel,
            'area_id' => $curso->area_id,
            'area' => $curso->area,
            'docente_id' => $curso->docente_id,
            'docente_nombre' => $this->nombreDocenteAsignado($curso),
            'estado' => $curso->estado,
            'estado_oficial' => $curso->estado,
            'tiene_edicion' => false,
            'estado_edicion' => null,
            'lecciones' => $lecciones,
        ];
    }

    private function respuestaCursoEdicion(Curso $curso): array
    {
        $curso->loadMissing(['area']);

        $edicion = $this->edicionActiva($curso->id);

        if (!$edicion) {
            $datos = $this->snapshotOficial($curso);

            $datos['estado'] = $curso->estado;
            $datos['estado_oficial'] = $curso->estado;
            $datos['estado_actual'] = $curso->estado;
            $datos['tiene_edicion'] = false;
            $datos['estado_edicion'] = null;
            $datos['edicion_estado'] = null;
            $datos['solicita_publicacion'] = false;
            $datos['motivo_rechazo'] = null;

            return $datos;
        }

        $datos = $this->datosEdicionOSnapshot($edicion, $curso);

        $area = null;

        if (!empty($datos['area_id'])) {
            $area = Area::find($datos['area_id']);
        }

        return [
            'id' => $curso->id,
            'titulo' => $datos['titulo'] ?? $curso->titulo,
            'descripcion' => $datos['descripcion'] ?? $curso->descripcion,
            'nivel' => $datos['nivel'] ?? $curso->nivel,
            'area_id' => $datos['area_id'] ?? $curso->area_id,
            'area' => $area ?? $curso->area,
            'docente_id' => $curso->docente_id,
            'docente_nombre' => $this->nombreDocenteAsignado($curso),
            'estado' => $edicion->estado,
            'estado_oficial' => $curso->estado,
            'estado_actual' => $edicion->estado,
            'tiene_edicion' => true,
            'estado_edicion' => $edicion->estado,
            'edicion_estado' => $edicion->estado,
            'solicita_publicacion' => (bool) $edicion->solicita_publicacion,
            'motivo_rechazo' => $edicion->motivo_rechazo,
            'lecciones' => $this->reordenarLeccionesArray($datos['lecciones'] ?? []),
            'evaluacion_final' => $this->normalizarEvaluacionBorrador($datos['evaluacion_final'] ?? null, 'examen_final'),
        ];
    }

    private function snapshotOficial(Curso $curso): array
    {
        $curso->loadMissing(['area']);

        $lecciones = $this->leccionesOficialesQuery($curso->id)
            ->get()
            ->map(function ($leccion) {
                return $this->mapearLeccionOficial($leccion);
            })
            ->values()
            ->toArray();

        return [
            'id' => $curso->id,
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'nivel' => $curso->nivel,
            'area_id' => $curso->area_id,
            'area' => $curso->area,
            'docente_id' => $curso->docente_id,
            'docente_nombre' => $this->nombreDocenteAsignado($curso),
            'lecciones' => $lecciones,
            'evaluacion_final' => $this->mapearEvaluacionOficial(
                Evaluacion::where('curso_id', $curso->id)->where('tipo', 'examen_final')->first(),
                'examen_final'
            ),
        ];
    }

    private function mapearLeccionOficial(Leccion $leccion): array
    {
        return [
            'id' => $leccion->id,
            'uid' => 'official_' . $leccion->id,
            'origen_id' => $leccion->id,
            'curso_id' => $leccion->curso_id,
            'titulo' => $leccion->titulo,
            'descripcion' => $leccion->descripcion,
            'contenido_texto' => $leccion->contenido_texto,
            'url_video' => $leccion->url_video,
            'orden' => $leccion->orden,
            'tipo' => $leccion->tipo ?? 'normal',
            'es_gratis' => (bool) $leccion->es_gratis,
            'activo' => Schema::hasColumn('lecciones', 'activo') ? (bool) $leccion->activo : true,
            'evaluacion' => ($leccion->tipo ?? 'normal') === 'introduccion'
                ? null
                : $this->mapearEvaluacionOficial(
                    Evaluacion::where('leccion_id', $leccion->id)->where('tipo', 'quiz_leccion')->first(),
                    'quiz_leccion'
                ),
            '_estado_edicion' => 'oficial',
        ];
    }

    private function sincronizarLeccionesOficiales(Curso $curso, array $leccionesBorrador): array
    {
        $leccionesBorrador = $this->reordenarLeccionesArray($leccionesBorrador);

        $idsQueQuedan = [];
        $mapaIdsBorradorAOficial = [];

        $existentes = Leccion::where('curso_id', $curso->id)->get();

        foreach ($existentes as $existente) {
            $existente->orden = 10000 + (int) $existente->id;
            $existente->save();
        }

        foreach ($leccionesBorrador as $index => $item) {
            $origenId = $item['origen_id'] ?? null;

            if (!$origenId && is_numeric($item['id'] ?? null)) {
                $origenId = (int) $item['id'];
            }

            $leccion = null;

            if ($origenId) {
                $leccion = Leccion::where('curso_id', $curso->id)
                    ->where('id', $origenId)
                    ->first();
            }

            if (!$leccion) {
                $leccion = new Leccion();
                $leccion->curso_id = $curso->id;
            }

            $leccion->titulo = $item['titulo'] ?? 'Lección sin título';
            $leccion->descripcion = $item['descripcion'] ?? null;
            $leccion->contenido_texto = $item['contenido_texto'] ?? null;
            $leccion->url_video = $item['url_video'] ?? null;
            $leccion->orden = $index + 1;
            $leccion->tipo = $item['tipo'] ?? 'normal';
            $leccion->es_gratis = (bool) ($item['es_gratis'] ?? false);

            if (Schema::hasColumn('lecciones', 'activo')) {
                $leccion->activo = true;
            }

            $leccion->save();

            $idsQueQuedan[] = $leccion->id;
            $mapaIdsBorradorAOficial[(string) ($item['id'] ?? $leccion->id)] = $leccion->id;
            $mapaIdsBorradorAOficial[(string) ($item['uid'] ?? $leccion->id)] = $leccion->id;
            $mapaIdsBorradorAOficial[(string) ($item['origen_id'] ?? $leccion->id)] = $leccion->id;
        }

        Leccion::where('curso_id', $curso->id)
            ->whereNotIn('id', $idsQueQuedan)
            ->delete();

        return $mapaIdsBorradorAOficial;
    }


    private function mapearEvaluacionOficial(?Evaluacion $evaluacion, string $tipo): array
    {
        if (!$evaluacion) {
            return $this->evaluacionVacia($tipo);
        }

        $evaluacion->loadMissing('preguntas.opciones');

        return [
            'id' => $evaluacion->id,
            'titulo' => $evaluacion->titulo,
            'tipo' => $evaluacion->tipo,
            'estado' => $evaluacion->estado,
            'puntaje_minimo_aprobacion' => (float) $evaluacion->puntaje_minimo_aprobacion,
            'preguntas' => $evaluacion->preguntas->map(function ($pregunta) {
                return [
                    'id' => $pregunta->id,
                    'pregunta' => $pregunta->pregunta,
                    'orden' => (int) $pregunta->orden,
                    'opciones' => $pregunta->opciones->map(function ($opcion) {
                        return [
                            'id' => $opcion->id,
                            'texto' => $opcion->texto,
                            'es_correcta' => (bool) $opcion->es_correcta,
                            'orden' => (int) $opcion->orden,
                        ];
                    })->values()->toArray(),
                ];
            })->values()->toArray(),
        ];
    }

    private function evaluacionVacia(string $tipo): array
    {
        return [
            'id' => null,
            'titulo' => $tipo === 'examen_final' ? 'Evaluación final' : 'Cuestionario de la lección',
            'tipo' => $tipo,
            'estado' => 'borrador',
            'puntaje_minimo_aprobacion' => 70,
            'preguntas' => [],
        ];
    }

    private function normalizarEvaluacionBorrador($evaluacion, string $tipo): array
    {
        $base = $this->evaluacionVacia($tipo);

        if (!is_array($evaluacion)) {
            return $base;
        }

        $base['id'] = $evaluacion['id'] ?? null;
        $base['titulo'] = $evaluacion['titulo'] ?? $base['titulo'];
        $base['tipo'] = $tipo;
        $base['estado'] = $evaluacion['estado'] ?? 'borrador';
        $base['puntaje_minimo_aprobacion'] = $evaluacion['puntaje_minimo_aprobacion'] ?? 70;
        $base['preguntas'] = array_values($evaluacion['preguntas'] ?? []);

        return $base;
    }

    private function validarEvaluacionBorrador(Request $request, string $tipo): array
    {
        $minimo = $tipo === 'examen_final' ? 6 : 3;
        $maximo = $tipo === 'examen_final' ? 10 : 6;

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'preguntas' => ['required', 'array', 'min:' . $minimo, 'max:' . $maximo],
            'preguntas.*.pregunta' => ['required', 'string', 'max:600'],
            'preguntas.*.opciones' => ['required', 'array', 'size:3'],
            'preguntas.*.opciones.*.texto' => ['required', 'string', 'max:300'],
            'preguntas.*.opciones.*.es_correcta' => ['required', 'boolean'],
        ]);

        foreach ($data['preguntas'] as $pregunta) {
            $correctas = collect($pregunta['opciones'])->where('es_correcta', true)->count();

            if ($correctas !== 1) {
                abort(response()->json([
                    'ok' => false,
                    'mensaje' => 'Cada pregunta debe tener exactamente una opción correcta.',
                ], 422));
            }
        }

        return [
            'id' => null,
            'titulo' => $data['titulo'],
            'tipo' => $tipo,
            'estado' => 'activa',
            'puntaje_minimo_aprobacion' => 70,
            'preguntas' => collect($data['preguntas'])->values()->map(function ($pregunta, $indicePregunta) {
                return [
                    'id' => null,
                    'pregunta' => $pregunta['pregunta'],
                    'orden' => $indicePregunta + 1,
                    'opciones' => collect($pregunta['opciones'])->values()->map(function ($opcion, $indiceOpcion) {
                        return [
                            'id' => null,
                            'texto' => $opcion['texto'],
                            'es_correcta' => (bool) $opcion['es_correcta'],
                            'orden' => $indiceOpcion + 1,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    private function erroresCursoListoParaRevision(array $datos): array
    {
        $errores = [];
        $lecciones = $this->reordenarLeccionesArray($datos['lecciones'] ?? []);

        if (count($lecciones) === 0) {
            $errores[] = 'Agrega al menos una introducción y una lección normal.';
            return $errores;
        }

        $primera = $lecciones[0] ?? null;

        if (!$primera || !$this->esIntroduccionArray($primera)) {
            $errores[] = 'La primera lección debe ser una introducción gratuita.';
        }

        $leccionesNormales = array_values(array_filter($lecciones, function ($leccion) {
            return !$this->esIntroduccionArray($leccion);
        }));

        if (count($leccionesNormales) === 0) {
            $errores[] = 'Agrega al menos una lección normal además de la introducción.';
        }

        foreach ($leccionesNormales as $leccion) {
            $erroresEvaluacion = $this->erroresEvaluacionArray($leccion['evaluacion'] ?? null, 'quiz_leccion', 'cuestionario de ' . ($leccion['titulo'] ?? 'la lección'));
            $errores = array_merge($errores, $erroresEvaluacion);
        }

        $erroresFinal = $this->erroresEvaluacionArray($datos['evaluacion_final'] ?? null, 'examen_final', 'evaluación final');
        $errores = array_merge($errores, $erroresFinal);

        return $errores;
    }

    private function erroresCursoOficialListoParaPublicar(Curso $curso): array
    {
        $datos = $this->snapshotOficial($curso);
        return $this->erroresCursoListoParaRevision($datos);
    }

    private function erroresEvaluacionArray($evaluacion, string $tipo, string $nombre): array
    {
        $errores = [];
        $minimo = $tipo === 'examen_final' ? 6 : 3;
        $maximo = $tipo === 'examen_final' ? 10 : 6;

        if (!is_array($evaluacion)) {
            return ["Falta configurar la {$nombre}."];
        }

        if (empty(trim((string) ($evaluacion['titulo'] ?? '')))) {
            $errores[] = "Falta el título de la {$nombre}.";
        }

        $preguntas = array_values($evaluacion['preguntas'] ?? []);

        if (count($preguntas) < $minimo || count($preguntas) > $maximo) {
            $errores[] = "La {$nombre} debe tener entre {$minimo} y {$maximo} preguntas.";
            return $errores;
        }

        foreach ($preguntas as $indicePregunta => $pregunta) {
            $numero = $indicePregunta + 1;

            if (empty(trim((string) ($pregunta['pregunta'] ?? '')))) {
                $errores[] = "La pregunta {$numero} de la {$nombre} no tiene texto.";
            }

            $opciones = array_values($pregunta['opciones'] ?? []);

            if (count($opciones) !== 3) {
                $errores[] = "La pregunta {$numero} de la {$nombre} debe tener exactamente 3 opciones.";
                continue;
            }

            $correctas = 0;

            foreach ($opciones as $indiceOpcion => $opcion) {
                if (empty(trim((string) ($opcion['texto'] ?? '')))) {
                    $errores[] = 'Completa la opción ' . ($indiceOpcion + 1) . " de la pregunta {$numero} de la {$nombre}.";
                }

                if (($opcion['es_correcta'] ?? false) === true || (int) ($opcion['es_correcta'] ?? 0) === 1) {
                    $correctas++;
                }
            }

            if ($correctas !== 1) {
                $errores[] = "La pregunta {$numero} de la {$nombre} debe tener exactamente una opción correcta.";
            }
        }

        return $errores;
    }

    private function esIntroduccionArray(array $leccion): bool
    {
        return ($leccion['tipo'] ?? 'normal') === 'introduccion' || (int) ($leccion['orden'] ?? 0) === 1;
    }

    private function sincronizarEvaluacionesOficiales(Curso $curso, array $datos, array $mapaLecciones): void
    {
        Evaluacion::where('curso_id', $curso->id)->delete();

        $evaluacionFinal = $this->normalizarEvaluacionBorrador($datos['evaluacion_final'] ?? null, 'examen_final');
        $this->crearEvaluacionOficial($curso->id, null, $evaluacionFinal, 'examen_final');

        foreach ($this->reordenarLeccionesArray($datos['lecciones'] ?? []) as $leccionBorrador) {
            if ($this->esIntroduccionArray($leccionBorrador)) {
                continue;
            }

            $clave = (string) ($leccionBorrador['id'] ?? '');
            $leccionOficialId = $mapaLecciones[$clave]
                ?? $mapaLecciones[(string) ($leccionBorrador['uid'] ?? '')]
                ?? $mapaLecciones[(string) ($leccionBorrador['origen_id'] ?? '')]
                ?? null;

            if (!$leccionOficialId) {
                continue;
            }

            $evaluacion = $this->normalizarEvaluacionBorrador($leccionBorrador['evaluacion'] ?? null, 'quiz_leccion');
            $this->crearEvaluacionOficial($curso->id, (int) $leccionOficialId, $evaluacion, 'quiz_leccion');
        }
    }

    private function crearEvaluacionOficial(int $cursoId, ?int $leccionId, array $evaluacionBorrador, string $tipo): void
    {
        $evaluacion = Evaluacion::create([
            'curso_id' => $cursoId,
            'leccion_id' => $leccionId,
            'titulo' => $evaluacionBorrador['titulo'] ?? ($tipo === 'examen_final' ? 'Evaluación final' : 'Cuestionario de la lección'),
            'tipo' => $tipo,
            'puntaje_minimo_aprobacion' => 70,
            'estado' => 'activa',
        ]);

        foreach (array_values($evaluacionBorrador['preguntas'] ?? []) as $indicePregunta => $preguntaBorrador) {
            $pregunta = Pregunta::create([
                'evaluacion_id' => $evaluacion->id,
                'pregunta' => $preguntaBorrador['pregunta'] ?? '',
                'orden' => $indicePregunta + 1,
            ]);

            foreach (array_values($preguntaBorrador['opciones'] ?? []) as $indiceOpcion => $opcionBorrador) {
                OpcionPregunta::create([
                    'pregunta_id' => $pregunta->id,
                    'texto' => $opcionBorrador['texto'] ?? '',
                    'es_correcta' => (bool) ($opcionBorrador['es_correcta'] ?? false),
                    'orden' => $indiceOpcion + 1,
                ]);
            }
        }
    }

    private function edicionActiva(int $cursoId): ?CursoEdicion
    {
        return CursoEdicion::where('curso_id', $cursoId)
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->first();
    }

    private function obtenerOCrearEdicion(Curso $curso, $usuario): CursoEdicion
    {
        $edicion = $this->edicionActiva($curso->id);

        if ($edicion) {
            return $edicion;
        }

        $edicion = new CursoEdicion();
        $edicion->curso_id = $curso->id;
        $edicion->creado_por_id = $usuario?->id;
        $edicion->estado = 'borrador';
        $edicion->solicita_publicacion = false;
        $edicion->motivo_rechazo = null;
        $edicion->datos_json = $this->snapshotOficial($curso);
        $edicion->save();

        return $edicion;
    }

    private function marcarEdicionComoBorradorSiCorresponde(CursoEdicion $edicion, $usuario): void
    {
        $rol = $this->rolUsuario($usuario);

        if ($edicion->estado === 'rechazado') {
            $edicion->estado = 'borrador';
            $edicion->motivo_rechazo = null;
            $edicion->solicita_publicacion = false;
            return;
        }

        if ($edicion->estado === 'pendiente_revision' && $rol !== 'admin') {
            $edicion->estado = 'borrador';
            $edicion->solicita_publicacion = false;
        }
    }

    private function datosEdicionOSnapshot(CursoEdicion $edicion, Curso $curso): array
    {
        $datos = $this->normalizarDatosJson($edicion->datos_json);

        if (!$datos) {
            return $this->snapshotOficial($curso);
        }

        if (!isset($datos['lecciones'])) {
            $datos['lecciones'] = [];
        }

        if (!isset($datos['evaluacion_final'])) {
            $datos['evaluacion_final'] = $this->mapearEvaluacionOficial(
                Evaluacion::where('curso_id', $curso->id)->where('tipo', 'examen_final')->first(),
                'examen_final'
            );
        }

        return $datos;
    }

    private function normalizarDatosJson($valor): array
    {
        if (is_array($valor)) {
            return $valor;
        }

        if (is_string($valor)) {
            $decode = json_decode($valor, true);

            return is_array($decode) ? $decode : [];
        }

        return [];
    }

    private function buscarIndiceLeccion(array $lecciones, $leccionId): ?int
    {
        foreach ($lecciones as $i => $leccion) {
            if ((string) ($leccion['id'] ?? '') === (string) $leccionId) {
                return $i;
            }

            if ((string) ($leccion['uid'] ?? '') === (string) $leccionId) {
                return $i;
            }

            if ((string) ($leccion['origen_id'] ?? '') === (string) $leccionId) {
                return $i;
            }
        }

        return null;
    }

    private function buscarCursoPorLeccionEnBorradorOOficial($leccionId): ?Curso
    {
        if (is_numeric($leccionId)) {
            $leccion = Leccion::find($leccionId);

            if ($leccion) {
                return Curso::find($leccion->curso_id);
            }
        }

        $ediciones = CursoEdicion::whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->get();

        foreach ($ediciones as $edicion) {
            $datos = $this->normalizarDatosJson($edicion->datos_json);
            $lecciones = $datos['lecciones'] ?? [];

            if ($this->buscarIndiceLeccion($lecciones, $leccionId) !== null) {
                return Curso::find($edicion->curso_id);
            }
        }

        return null;
    }

    private function reordenarLeccionesArray(array $lecciones): array
    {
        $lecciones = array_values($lecciones);

        usort($lecciones, function ($a, $b) {
            return ((int) ($a['orden'] ?? 0)) <=> ((int) ($b['orden'] ?? 0));
        });

        foreach ($lecciones as $i => &$leccion) {
            $leccion['orden'] = $i + 1;

            if (!isset($leccion['uid'])) {
                $leccion['uid'] = isset($leccion['origen_id'])
                    ? 'official_' . $leccion['origen_id']
                    : 'tmp_' . uniqid();
            }

            if (!isset($leccion['_estado_edicion'])) {
                $leccion['_estado_edicion'] = 'oficial';
            }

            if (!isset($leccion['activo'])) {
                $leccion['activo'] = true;
            }

            if ($this->esIntroduccionArray($leccion)) {
                $leccion['tipo'] = 'introduccion';
                $leccion['es_gratis'] = true;
                $leccion['evaluacion'] = null;
            } else {
                $leccion['tipo'] = $leccion['tipo'] ?? 'normal';
                if (!array_key_exists('evaluacion', $leccion)) {
                    $leccion['evaluacion'] = $this->evaluacionVacia('quiz_leccion');
                }
            }
        }

        return $lecciones;
    }

    private function leccionesOficialesQuery(int $cursoId)
    {
        $query = Leccion::where('curso_id', $cursoId)
            ->orderBy('orden');

        if (Schema::hasColumn('lecciones', 'activo')) {
            $query->where('activo', true);
        }

        return $query;
    }

    private function nombreDocenteAsignado(Curso $curso): string
    {
        if (!$curso->docente_id) {
            return 'Docente por asignar';
        }

        $docente = User::find($curso->docente_id);

        if (!$docente) {
            return 'Docente por asignar';
        }

        return trim(($docente->nombres ?? '') . ' ' . ($docente->apellidos ?? '')) ?: $docente->email;
    }

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

        $rol = $usuario->rol->nombre ?? $usuario->rol_nombre ?? $usuario->rol ?? '';

        if (is_object($rol)) {
            return strtolower((string) ($rol->nombre ?? ''));
        }

        return strtolower((string) $rol);
    }

    private function puedeEditarCurso($usuario, Curso $curso): bool
    {
        $rol = $this->rolUsuario($usuario);

        if ($rol === 'admin') {
            return true;
        }

        if ($rol === 'docente' && (int) $curso->docente_id === (int) $usuario?->id) {
            return true;
        }

        return false;
    }
}