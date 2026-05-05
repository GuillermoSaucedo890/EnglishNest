<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\CursoEdicion;
use App\Models\Leccion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LeccionController extends Controller
{
    private function usuarioConRol(Request $request)
    {
        $usuario = $request->user();

        if ($usuario) {
            $usuario->load(['rol', 'perfilDocente']);
        }

        return $usuario;
    }

    private function rol($usuario): string
    {
        return strtolower((string) ($usuario?->rol?->nombre ?? ''));
    }

    private function esAdmin($usuario): bool
    {
        return $this->rol($usuario) === 'admin';
    }

    private function esDocente($usuario): bool
    {
        return $this->rol($usuario) === 'docente';
    }

    private function docenteAprobado($usuario): bool
    {
        return ($usuario->perfilDocente?->estado_aprobacion ?? '') === 'aprobado';
    }

    private function puedeEditarCurso($usuario, Curso $curso): bool
    {
        if ($this->esAdmin($usuario)) {
            return true;
        }

        return $this->esDocente($usuario)
            && (int) $curso->docente_id === (int) $usuario->id
            && $this->docenteAprobado($usuario);
    }

    private function datosDesdeCursoOficial(Curso $curso): array
    {
        $curso->load([
            'lecciones' => function ($q) {
                $q->orderBy('orden', 'asc');
            },
        ]);

        return [
            'titulo' => $curso->titulo,
            'descripcion' => $curso->descripcion,
            'nivel' => $curso->nivel,
            'area_id' => $curso->area_id,
            'lecciones' => $curso->lecciones->map(function ($leccion) {
                return [
                    'id' => $leccion->id,
                    'uid' => 'oficial_' . $leccion->id,
                    'origen_id' => $leccion->id,
                    'curso_id' => $leccion->curso_id,
                    'titulo' => $leccion->titulo,
                    'descripcion' => $leccion->descripcion,
                    'contenido_texto' => $leccion->contenido_texto,
                    'url_video' => $leccion->url_video,
                    'orden' => $leccion->orden,
                    'tipo' => $leccion->tipo,
                    'es_gratis' => (bool) $leccion->es_gratis,
                    'activo' => (bool) $leccion->activo,
                    '_estado_edicion' => 'oficial',
                ];
            })->values()->toArray(),
        ];
    }

    private function edicionActiva(Curso $curso): ?CursoEdicion
    {
        return CursoEdicion::where('curso_id', $curso->id)
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->first();
    }

    private function obtenerOCrearEdicion(Curso $curso, $usuario): CursoEdicion
    {
        $edicion = $this->edicionActiva($curso);

        if ($edicion) {
            if ($edicion->estado === 'pendiente_revision' && !$this->esAdmin($usuario)) {
                abort(response()->json([
                    'ok' => false,
                    'mensaje' => 'El curso ya fue enviado a revisión. Espera la respuesta del administrador.',
                ], 422));
            }

            if ($edicion->estado === 'rechazado') {
                $edicion->update([
                    'estado' => 'borrador',
                    'solicita_publicacion' => false,
                ]);
            }

            return $edicion->fresh();
        }

        return CursoEdicion::create([
            'curso_id' => $curso->id,
            'creado_por_id' => $usuario?->id,
            'estado' => 'borrador',
            'solicita_publicacion' => false,
            'motivo_rechazo' => null,
            'datos_json' => $this->datosDesdeCursoOficial($curso),
        ]);
    }

    private function respuestaCursoEdicion(Curso $curso, CursoEdicion $edicion): array
    {
        $curso->load(['area:id,nombre', 'docente:id,nombres,apellidos,email']);

        $datos = $edicion->datos_json ?? [];

        $array = $curso->toArray();

        $array['titulo'] = $datos['titulo'] ?? $curso->titulo;
        $array['descripcion'] = $datos['descripcion'] ?? $curso->descripcion;
        $array['nivel'] = $datos['nivel'] ?? $curso->nivel;
        $array['area_id'] = $datos['area_id'] ?? $curso->area_id;
        $array['estado'] = $edicion->estado;
        $array['estado_oficial'] = $curso->estado;
        $array['tiene_edicion'] = true;
        $array['edicion_estado'] = $edicion->estado;
        $array['solicita_publicacion'] = (bool) $edicion->solicita_publicacion;
        $array['motivo_rechazo'] = $edicion->motivo_rechazo;
        $array['lecciones'] = collect($datos['lecciones'] ?? [])
            ->sortBy('orden')
            ->values()
            ->toArray();

        return $array;
    }

    private function normalizarLecciones(array $lecciones): array
    {
        return collect($lecciones)
            ->values()
            ->map(function ($leccion, $index) {
                $leccion['orden'] = $index + 1;

                if ($index === 0) {
                    $leccion['tipo'] = 'introduccion';
                    $leccion['es_gratis'] = true;
                }

                return $leccion;
            })
            ->toArray();
    }

    public function index()
    {
        return response()->json([
            'ok' => true,
            'lecciones' => Leccion::orderBy('orden')->get(),
        ]);
    }

    public function mostrarContenido($id)
    {
        $leccion = Leccion::findOrFail($id);

        return response()->json([
            'ok' => true,
            'leccion' => $leccion,
        ]);
    }

    public function store(Request $request, $curso_id)
    {
        $usuario = $this->usuarioConRol($request);

        $curso = Curso::findOrFail($curso_id);

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar lecciones de este curso.',
            ], 403);
        }

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string', 'max:500'],
            'tipo' => ['nullable', 'in:introduccion,normal'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $edicion->datos_json ?? $this->datosDesdeCursoOficial($curso);

        $lecciones = $datos['lecciones'] ?? [];

        $lecciones[] = [
            'id' => 'tmp_' . Str::uuid()->toString(),
            'uid' => 'tmp_' . Str::uuid()->toString(),
            'origen_id' => null,
            'curso_id' => $curso->id,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'url_video' => $data['url_video'] ?? null,
            'orden' => count($lecciones) + 1,
            'tipo' => $data['tipo'] ?? 'normal',
            'es_gratis' => (bool) ($data['es_gratis'] ?? false),
            'activo' => true,
            '_estado_edicion' => 'nueva',
        ];

        $datos['lecciones'] = $this->normalizarLecciones($lecciones);

        $edicion->update([
            'estado' => 'borrador',
            'motivo_rechazo' => null,
            'datos_json' => $datos,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección guardada en borrador. No será visible para estudiantes hasta publicar.',
            'curso' => $this->respuestaCursoEdicion($curso, $edicion->fresh()),
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string', 'max:500'],
            'tipo' => ['nullable', 'in:introduccion,normal'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $leccionOficial = is_numeric($id) ? Leccion::find($id) : null;

        if ($leccionOficial) {
            $curso = Curso::findOrFail($leccionOficial->curso_id);
        } else {
            $edicionTmp = CursoEdicion::whereIn('estado', ['borrador', 'rechazado'])
                ->whereJsonContains('datos_json->lecciones', [['id' => $id]])
                ->latest()
                ->first();

            $curso = $edicionTmp ? Curso::findOrFail($edicionTmp->curso_id) : null;
        }

        if (!$curso) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección en la edición.',
            ], 404);
        }

        if (!$this->puedeEditarCurso($usuario, $curso)) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar esta lección.',
            ], 403);
        }

        $edicion = $this->obtenerOCrearEdicion($curso, $usuario);
        $datos = $edicion->datos_json ?? $this->datosDesdeCursoOficial($curso);

        $lecciones = collect($datos['lecciones'] ?? [])->map(function ($leccion) use ($id, $data) {
            $coincide =
                (string) ($leccion['id'] ?? '') === (string) $id ||
                (string) ($leccion['origen_id'] ?? '') === (string) $id;

            if (!$coincide) {
                return $leccion;
            }

            $estadoAnterior = $leccion['_estado_edicion'] ?? 'oficial';

            return [
                ...$leccion,
                'titulo' => $data['titulo'],
                'descripcion' => $data['descripcion'] ?? null,
                'contenido_texto' => $data['contenido_texto'] ?? null,
                'url_video' => $data['url_video'] ?? null,
                'tipo' => $data['tipo'] ?? 'normal',
                'es_gratis' => (bool) ($data['es_gratis'] ?? false),
                '_estado_edicion' => $estadoAnterior === 'nueva' ? 'nueva' : 'modificada',
            ];
        })->toArray();

        $datos['lecciones'] = $this->normalizarLecciones($lecciones);

        $edicion->update([
            'estado' => 'borrador',
            'motivo_rechazo' => null,
            'datos_json' => $datos,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección actualizada en borrador.',
            'curso' => $this->respuestaCursoEdicion($curso, $edicion->fresh()),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $usuario = $this->usuarioConRol($request);

        $leccionOficial = is_numeric($id) ? Leccion::find($id) : null;

        if ($leccionOficial) {
            $curso = Curso::findOrFail($leccionOficial->curso_id);
        } else {
            $edicion = CursoEdicion::whereIn('estado', ['borrador', 'rechazado'])
                ->latest()
                ->get()
                ->first(function ($edicion) use ($id) {
                    return collect($edicion->datos_json['lecciones'] ?? [])
                        ->contains(fn ($l) => (string) ($l['id'] ?? '') === (string) $id);
                });

            $curso = $edicion ? Curso::findOrFail($edicion->curso_id) : null;
        }

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
        $datos = $edicion->datos_json ?? $this->datosDesdeCursoOficial($curso);

        $datos['lecciones'] = collect($datos['lecciones'] ?? [])
            ->reject(function ($leccion) use ($id) {
                return (string) ($leccion['id'] ?? '') === (string) $id
                    || (string) ($leccion['origen_id'] ?? '') === (string) $id;
            })
            ->values()
            ->toArray();

        $datos['lecciones'] = $this->normalizarLecciones($datos['lecciones']);

        $edicion->update([
            'estado' => 'borrador',
            'motivo_rechazo' => null,
            'datos_json' => $datos,
        ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Lección quitada del borrador. El curso oficial no cambia hasta publicar.',
            'curso' => $this->respuestaCursoEdicion($curso, $edicion->fresh()),
        ]);
    }
}