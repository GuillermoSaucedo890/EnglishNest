<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Curso;
use App\Models\CursoEdicion;
use App\Models\Leccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CursoEdicionController extends Controller
{
    private function esAdmin($usuario): bool
    {
        return $usuario->rol?->nombre === 'admin';
    }

    private function esDocente($usuario): bool
    {
        return $usuario->rol?->nombre === 'docente';
    }

    private function validarPuedeEditarEdicion($usuario, Curso $curso): void
    {
        if ($this->esAdmin($usuario)) {
            return;
        }

        if (!$this->esDocente($usuario) || $curso->docente_id !== $usuario->id) {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para editar este curso.',
            ], 403));
        }

        if (!$usuario->hasVerifiedEmail()) {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'Debes verificar tu correo antes de editar cursos.',
            ], 403));
        }

        if (!$usuario->perfilDocente || $usuario->perfilDocente->estado_aprobacion !== 'aprobado') {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'Tu perfil docente debe estar aprobado por el administrador.',
            ], 403));
        }
    }

    private function bloquearSiDocenteYPendiente($usuario, CursoEdicion $edicion, string $mensaje): void
    {
        if ($this->esDocente($usuario) && $edicion->estado === 'pendiente_revision') {
            abort(response()->json([
                'ok' => false,
                'mensaje' => $mensaje,
            ], 422));
        }
    }

    private function validarAdmin($usuario): void
    {
        if (!$this->esAdmin($usuario)) {
            abort(response()->json([
                'ok' => false,
                'mensaje' => 'Solo el administrador puede realizar esta acción.',
            ], 403));
        }
    }

    private function obtenerEdicionActiva(Curso $curso): ?CursoEdicion
    {
        return CursoEdicion::where('curso_id', $curso->id)
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->first();
    }

    private function crearEdicionDesdeCurso(Curso $curso): CursoEdicion
    {
        $lecciones = $curso->lecciones()
            ->where('activo', true)
            ->orderBy('orden', 'asc')
            ->get()
            ->map(function ($leccion) {
                return [
                    'id' => $leccion->id,
                    'titulo' => $leccion->titulo,
                    'descripcion' => $leccion->descripcion,
                    'contenido_texto' => $leccion->contenido_texto,
                    'url_video' => $leccion->url_video,
                    'orden' => $leccion->orden,
                    'tipo' => $leccion->tipo,
                    'es_gratis' => (bool) $leccion->es_gratis,
                    'activo' => (bool) ($leccion->activo ?? true),
                    'accion' => 'mantener',
                ];
            })
            ->values()
            ->toArray();

        return CursoEdicion::create([
            'curso_id' => $curso->id,
            'docente_id' => $curso->docente_id,
            'estado' => 'borrador',
            'solicita_publicacion' => false,
            'datos_curso' => [
                'titulo' => $curso->titulo,
                'descripcion' => $curso->descripcion,
                'nivel' => $curso->nivel,
                'area_id' => $curso->area_id,
            ],
            'lecciones_json' => $lecciones,
            'motivo_rechazo' => null,
        ]);
    }

    private function obtenerOCrearEdicion(Curso $curso): CursoEdicion
    {
        $edicion = $this->obtenerEdicionActiva($curso);

        if ($edicion) {
            return $edicion;
        }

        return $this->crearEdicionDesdeCurso($curso);
    }

    private function responderCurso(Curso $curso, ?CursoEdicion $edicion = null)
    {
        $curso->load(['docente:id,nombres,apellidos,email', 'area:id,nombre']);

        if (!$edicion) {
            $curso->load(['lecciones' => function ($q) {
                $q->where('activo', true)->orderBy('orden', 'asc');
            }]);

            $cursoArray = $curso->toArray();
            $cursoArray['estado_oficial'] = $curso->estado;
            $cursoArray['tiene_edicion'] = false;
            $cursoArray['tiene_edicion_pendiente'] = false;
            $cursoArray['puede_enviar_revision'] = false;
            $cursoArray['mensaje_revision'] = 'Guarda una modificación para crear una edición pendiente.';

            return response()->json([
                'ok' => true,
                'curso' => $cursoArray,
                'edicion' => null,
            ]);
        }

        $datos = $edicion->datos_curso;

        $lecciones = collect($edicion->lecciones_json ?? [])
            ->filter(function ($leccion) {
                return ($leccion['accion'] ?? '') !== 'eliminar';
            })
            ->sortBy('orden')
            ->values()
            ->toArray();

        $cursoArray = $curso->toArray();

        $cursoArray['titulo'] = $datos['titulo'] ?? $curso->titulo;
        $cursoArray['descripcion'] = $datos['descripcion'] ?? $curso->descripcion;
        $cursoArray['nivel'] = $datos['nivel'] ?? $curso->nivel;
        $cursoArray['area_id'] = $datos['area_id'] ?? $curso->area_id;
        $cursoArray['lecciones'] = $lecciones;

        $cursoArray['estado'] = $edicion->estado;
        $cursoArray['estado_oficial'] = $curso->estado;
        $cursoArray['tiene_edicion'] = true;
        $cursoArray['tiene_edicion_pendiente'] = $edicion->estado === 'pendiente_revision';
        $cursoArray['solicita_publicacion'] = $edicion->solicita_publicacion;
        $cursoArray['motivo_rechazo'] = $edicion->motivo_rechazo;

        $tieneLecciones = count($lecciones) > 0;

        $cursoArray['puede_enviar_revision'] =
            $tieneLecciones &&
            in_array($edicion->estado, ['borrador', 'rechazado']);

        if (!$tieneLecciones) {
            $cursoArray['mensaje_revision'] = 'Agrega al menos una lección antes de enviar a revisión.';
        } elseif ($edicion->estado === 'pendiente_revision') {
            $cursoArray['mensaje_revision'] = 'Esta edición ya fue enviada. Espera la respuesta del administrador.';
        } elseif ($edicion->estado === 'rechazado') {
            $cursoArray['mensaje_revision'] = 'La edición fue rechazada. Corrige el contenido y vuelve a enviarla.';
        } else {
            $cursoArray['mensaje_revision'] = 'Hay cambios guardados. Puedes enviar esta edición a revisión.';
        }

        return response()->json([
            'ok' => true,
            'curso' => $cursoArray,
            'edicion' => $edicion,
        ]);
    }

    public function show(Request $request, $cursoId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::with(['lecciones', 'area', 'docente'])->findOrFail($cursoId);

        $puedeVer =
            $this->esAdmin($usuario) ||
            ($this->esDocente($usuario) && $curso->docente_id === $usuario->id);

        if (!$puedeVer) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No tienes permiso para ver esta edición.',
            ], 403);
        }

        $edicion = $this->obtenerEdicionActiva($curso);

        return $this->responderCurso($curso, $edicion);
    }

    public function guardarDatos(Request $request, $cursoId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::findOrFail($cursoId);

        $this->validarPuedeEditarEdicion($usuario, $curso);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['required', 'string'],
            'nivel' => ['required', 'in:basico,intermedio,avanzado'],
            'area_id' => ['required', 'exists:areas,id'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso);

        $this->bloquearSiDocenteYPendiente(
            $usuario,
            $edicion,
            'No puedes modificar una edición que ya está pendiente de revisión.'
        );

        $edicion->update([
            'estado' => $this->esAdmin($usuario) ? $edicion->estado : 'borrador',
            'datos_curso' => $data,
            'motivo_rechazo' => $this->esAdmin($usuario) ? $edicion->motivo_rechazo : null,
        ]);

        return $this->responderCurso($curso->fresh(), $edicion->fresh());
    }

    public function crearLeccion(Request $request, $cursoId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::findOrFail($cursoId);

        $this->validarPuedeEditarEdicion($usuario, $curso);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string'],
            'tipo' => ['required', 'in:introduccion,normal'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso);

        $this->bloquearSiDocenteYPendiente(
            $usuario,
            $edicion,
            'No puedes agregar lecciones mientras la edición está pendiente de revisión.'
        );

        $lecciones = $edicion->lecciones_json ?? [];
        $maxOrden = collect($lecciones)->max('orden') ?? 0;

        $nuevaLeccion = [
            'id' => 'tmp_' . Str::uuid()->toString(),
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'] ?? null,
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'url_video' => $data['url_video'] ?? null,
            'orden' => $maxOrden + 1,
            'tipo' => $data['tipo'],
            'es_gratis' => (bool) ($data['es_gratis'] ?? false),
            'activo' => true,
            'accion' => 'crear',
        ];

        if (count($lecciones) === 0) {
            $nuevaLeccion['tipo'] = 'introduccion';
            $nuevaLeccion['es_gratis'] = true;
        }

        $lecciones[] = $nuevaLeccion;

        $edicion->update([
            'estado' => $this->esAdmin($usuario) ? $edicion->estado : 'borrador',
            'lecciones_json' => array_values($lecciones),
            'motivo_rechazo' => $this->esAdmin($usuario) ? $edicion->motivo_rechazo : null,
        ]);

        return $this->responderCurso($curso->fresh(), $edicion->fresh());
    }

    public function actualizarLeccion(Request $request, $cursoId, $leccionId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::findOrFail($cursoId);

        $this->validarPuedeEditarEdicion($usuario, $curso);

        $data = $request->validate([
            'titulo' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'contenido_texto' => ['nullable', 'string'],
            'url_video' => ['nullable', 'string'],
            'tipo' => ['required', 'in:introduccion,normal'],
            'es_gratis' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso);

        $this->bloquearSiDocenteYPendiente(
            $usuario,
            $edicion,
            'No puedes editar lecciones mientras la edición está pendiente de revisión.'
        );

        $lecciones = $edicion->lecciones_json ?? [];
        $encontrada = false;

        foreach ($lecciones as &$leccion) {
            if ((string) $leccion['id'] === (string) $leccionId) {
                $leccion['titulo'] = $data['titulo'];
                $leccion['descripcion'] = $data['descripcion'] ?? null;
                $leccion['contenido_texto'] = $data['contenido_texto'] ?? null;
                $leccion['url_video'] = $data['url_video'] ?? null;
                $leccion['tipo'] = $data['tipo'];
                $leccion['es_gratis'] = (bool) ($data['es_gratis'] ?? false);

                if (($leccion['accion'] ?? '') !== 'crear') {
                    $leccion['accion'] = 'actualizar';
                }

                $encontrada = true;
                break;
            }
        }

        if (!$encontrada) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No se encontró la lección en la edición.',
            ], 404);
        }

        $edicion->update([
            'estado' => $this->esAdmin($usuario) ? $edicion->estado : 'borrador',
            'lecciones_json' => array_values($lecciones),
            'motivo_rechazo' => $this->esAdmin($usuario) ? $edicion->motivo_rechazo : null,
        ]);

        return $this->responderCurso($curso->fresh(), $edicion->fresh());
    }

    public function eliminarLeccion(Request $request, $cursoId, $leccionId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::findOrFail($cursoId);

        $this->validarPuedeEditarEdicion($usuario, $curso);

        $edicion = $this->obtenerOCrearEdicion($curso);

        $this->bloquearSiDocenteYPendiente(
            $usuario,
            $edicion,
            'No puedes eliminar lecciones mientras la edición está pendiente de revisión.'
        );

        $lecciones = $edicion->lecciones_json ?? [];

        foreach ($lecciones as $index => &$leccion) {
            if ((string) $leccion['id'] === (string) $leccionId) {
                if (($leccion['accion'] ?? '') === 'crear') {
                    unset($lecciones[$index]);
                } else {
                    $leccion['accion'] = 'eliminar';
                    $leccion['activo'] = false;
                }

                break;
            }
        }

        $edicion->update([
            'estado' => $this->esAdmin($usuario) ? $edicion->estado : 'borrador',
            'lecciones_json' => array_values($lecciones),
            'motivo_rechazo' => $this->esAdmin($usuario) ? $edicion->motivo_rechazo : null,
        ]);

        return $this->responderCurso($curso->fresh(), $edicion->fresh());
    }

    public function enviarRevision(Request $request, $cursoId)
    {
        $usuario = $request->user()->load(['rol', 'perfilDocente']);
        $curso = Curso::findOrFail($cursoId);

        if (!$this->esDocente($usuario) || $curso->docente_id !== $usuario->id) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Solo el docente asignado puede enviar esta edición a revisión.',
            ], 403);
        }

        $this->validarPuedeEditarEdicion($usuario, $curso);

        $data = $request->validate([
            'solicita_publicacion' => ['nullable', 'boolean'],
        ]);

        $edicion = $this->obtenerOCrearEdicion($curso);

        if ($edicion->estado === 'pendiente_revision') {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Esta edición ya fue enviada a revisión.',
            ], 422);
        }

        $leccionesVisibles = collect($edicion->lecciones_json ?? [])
            ->filter(fn ($l) => ($l['accion'] ?? '') !== 'eliminar')
            ->count();

        if ($leccionesVisibles < 1) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'Agrega al menos una lección antes de enviar a revisión.',
            ], 422);
        }

        $edicion->update([
            'estado' => 'pendiente_revision',
            'solicita_publicacion' => $data['solicita_publicacion'] ?? false,
            'motivo_rechazo' => null,
            'fecha_envio_revision' => now(),
        ]);

        return $this->responderCurso($curso->fresh(), $edicion->fresh());
    }

    public function aprobar(Request $request, $cursoId)
    {
        $usuario = $request->user()->load('rol');
        $this->validarAdmin($usuario);

        $data = $request->validate([
            'publicar' => ['nullable', 'boolean'],
        ]);

        $curso = Curso::with('docente')->findOrFail($cursoId);

        $edicion = CursoEdicion::where('curso_id', $curso->id)
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->first();

        if (!$edicion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No hay una edición activa para aprobar.',
            ], 404);
        }

        $forzarPublicacion = (bool) ($data['publicar'] ?? false);

        DB::transaction(function () use ($curso, $edicion, $usuario, $forzarPublicacion) {
            $datos = $edicion->datos_curso;

            $estadoFinal = $curso->estado;

            if ($forzarPublicacion) {
                $estadoFinal = 'publicado';
            } elseif ($curso->estado === 'publicado') {
                $estadoFinal = 'publicado';
            }

            $curso->update([
                'titulo' => $datos['titulo'] ?? $curso->titulo,
                'descripcion' => $datos['descripcion'] ?? $curso->descripcion,
                'nivel' => $datos['nivel'] ?? $curso->nivel,
                'area_id' => $datos['area_id'] ?? $curso->area_id,
                'estado' => $estadoFinal,
                'fecha_publicacion' => $estadoFinal === 'publicado'
                    ? ($curso->fecha_publicacion ?? now())
                    : $curso->fecha_publicacion,
                'motivo_rechazo' => null,
            ]);

            // Importante:
            // Primero movemos TODAS las lecciones actuales a órdenes temporales.
            // Esto evita el error Duplicate entry curso_id-orden cuando se aplican cambios.
            $leccionesActuales = Leccion::where('curso_id', $curso->id)->get();

            foreach ($leccionesActuales as $leccionActual) {
                $leccionActual->update([
                    'activo' => false,
                    'orden' => 1000000 + $leccionActual->id,
                ]);
            }

            $leccionesEdicion = collect($edicion->lecciones_json ?? [])
                ->sortBy('orden')
                ->values();

            $ordenVisible = 1;

            foreach ($leccionesEdicion as $item) {
                $accion = $item['accion'] ?? 'mantener';
                $esIdReal = is_numeric($item['id']);

                if ($accion === 'eliminar') {
                    continue;
                }

                $datosLeccion = [
                    'curso_id' => $curso->id,
                    'titulo' => $item['titulo'],
                    'descripcion' => $item['descripcion'] ?? null,
                    'contenido_texto' => $item['contenido_texto'] ?? null,
                    'url_video' => $item['url_video'] ?? null,
                    'orden' => $ordenVisible,
                    'tipo' => $item['tipo'] ?? 'normal',
                    'es_gratis' => (bool) ($item['es_gratis'] ?? false),
                    'activo' => true,
                ];

                if ($ordenVisible === 1) {
                    $datosLeccion['tipo'] = 'introduccion';
                    $datosLeccion['es_gratis'] = true;
                }

                if ($esIdReal) {
                    Leccion::where('id', $item['id'])
                        ->where('curso_id', $curso->id)
                        ->update($datosLeccion);
                } else {
                    Leccion::create($datosLeccion);
                }

                $ordenVisible++;
            }

            $edicion->update([
                'estado' => 'aprobado',
                'revisado_por' => $usuario->id,
                'fecha_revision' => now(),
                'motivo_rechazo' => null,
            ]);
        });

        $this->enviarCorreoSimple(
            $curso->docente->email,
            'Edición de curso aceptada',
            'Hola ' . $curso->docente->nombres . ', los cambios del curso "' . $curso->titulo . '" fueron aceptados.'
        );

        return $this->responderCurso($curso->fresh(['lecciones', 'area', 'docente']), null);
    }

    public function rechazar(Request $request, $cursoId)
    {
        $usuario = $request->user()->load('rol');
        $this->validarAdmin($usuario);

        $request->validate([
            'motivo_rechazo' => ['required', 'string', 'min:5'],
        ]);

        $curso = Curso::with('docente')->findOrFail($cursoId);

        $edicion = CursoEdicion::where('curso_id', $curso->id)
            ->whereIn('estado', ['borrador', 'pendiente_revision', 'rechazado'])
            ->latest()
            ->first();

        if (!$edicion) {
            return response()->json([
                'ok' => false,
                'mensaje' => 'No hay una edición activa para rechazar.',
            ], 404);
        }

        $edicion->update([
            'estado' => 'rechazado',
            'revisado_por' => $usuario->id,
            'fecha_revision' => now(),
            'motivo_rechazo' => $request->motivo_rechazo,
        ]);

        $this->enviarCorreoSimple(
            $curso->docente->email,
            'Edición de curso rechazada',
            'Hola ' . $curso->docente->nombres . ', los cambios del curso "' . $curso->titulo . '" fueron rechazados. Motivo: ' . $request->motivo_rechazo
        );

        return $this->responderCurso($curso->fresh(['lecciones', 'area', 'docente']), $edicion->fresh());
    }

    private function enviarCorreoSimple(string $destino, string $asunto, string $mensaje): void
    {
        try {
            Mail::raw($mensaje, function ($mail) use ($destino, $asunto) {
                $mail->to($destino)->subject($asunto);
            });
        } catch (\Throwable $e) {
            // No detenemos el flujo si falla el correo
        }
    }
}