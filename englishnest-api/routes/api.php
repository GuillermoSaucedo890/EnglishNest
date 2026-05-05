<?php

use App\Http\Controllers\Api\AdminDocenteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\DocentePerfilController;
use App\Http\Controllers\Api\EvaluacionController;
use App\Http\Controllers\Api\IaController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\LeccionController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ProgresoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RUTAS PÚBLICAS
|--------------------------------------------------------------------------
*/

Route::prefix('auth')->group(function () {
    Route::post('/registro-estudiante', [AuthController::class, 'registrarEstudiante']);
    Route::post('/registro-docente', [AuthController::class, 'registrarDocente']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/olvide-mi-contrasena', [AuthController::class, 'enviarEnlaceRecuperacion']);
    Route::post('/restablecer-contrasena', [AuthController::class, 'restablecerContrasena']);

    Route::get('/restablecer-password/{token}', function ($token) {
        return response()->json([
            'ok' => true,
            'mensaje' => 'Usa el frontend para cambiar tu clave.',
            'token' => $token,
        ]);
    })->name('password.reset');
});

// Cursos públicos oficiales.
Route::prefix('public')->group(function () {
    Route::get('/cursos', [CursoController::class, 'publicoIndex']);
    Route::get('/cursos/{id}', [CursoController::class, 'publicoShow']);
});

// Alias en español para pantallas antiguas.
Route::prefix('publico')->group(function () {
    Route::get('/cursos', [CursoController::class, 'publicoIndex']);
    Route::get('/cursos/{id}', [CursoController::class, 'publicoShow']);
});

// Alias anterior.
Route::get('/cursos-publicos', [CursoController::class, 'publicoIndex']);
Route::get('/cursos-publicos/{id}', [CursoController::class, 'publicoShow']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/reenviar-verificacion', [AuthController::class, 'reenviarVerificacion']);
    });

    Route::prefix('docente/perfil')->group(function () {
        Route::get('/', [DocentePerfilController::class, 'miPerfil']);
        Route::put('/', [DocentePerfilController::class, 'actualizar']);
        Route::post('/reenviar-solicitud', [DocentePerfilController::class, 'reenviarSolicitud']);
    });

    Route::get('/areas', [CursoController::class, 'obtenerAreas']);

    Route::prefix('admin/docentes')->group(function () {
        Route::get('/pendientes', [AdminDocenteController::class, 'pendientes']);
        Route::get('/aprobados', [AdminDocenteController::class, 'aprobados']);
        Route::get('/{usuarioId}/detalle', [AdminDocenteController::class, 'detalle']);
        Route::post('/aprobar/{usuarioId}', [AdminDocenteController::class, 'aprobar']);
        Route::post('/rechazar/{usuarioId}', [AdminDocenteController::class, 'rechazar']);
    });

    Route::prefix('cursos')->group(function () {
        Route::get('/', [CursoController::class, 'index']);
        Route::post('/', [CursoController::class, 'store']);

        /*
        |--------------------------------------------------------------------------
        | Edición segura de curso
        |--------------------------------------------------------------------------
        */

        Route::get('/{id}/edicion', [CursoController::class, 'obtenerCursoEdicion']);
        Route::put('/{id}/edicion', [CursoController::class, 'actualizarCursoEdicion']);

        Route::post('/{id}/edicion/enviar-revision', [CursoController::class, 'enviarRevision']);

        // Compatibilidad con nombres viejos y nuevos.
        Route::post('/{id}/edicion/aprobar', [CursoController::class, 'aprobarRevision']);
        Route::post('/{id}/edicion/aprobar-revision', [CursoController::class, 'aprobarRevision']);

        Route::post('/{id}/edicion/rechazar', [CursoController::class, 'rechazarRevision']);
        Route::post('/{id}/edicion/rechazar-revision', [CursoController::class, 'rechazarRevision']);

        Route::post('/{cursoId}/edicion/lecciones', [CursoController::class, 'crearLeccion']);

        Route::put('/lecciones/{leccionId}/edicion', [CursoController::class, 'actualizarLeccion']);
        Route::delete('/lecciones/{leccionId}/edicion', [CursoController::class, 'eliminarLeccion']);

        // Evaluaciones dentro de la edición segura del curso.
        // No se publican hasta que el admin apruebe la edición.
        Route::get('/{cursoId}/edicion/evaluacion-final', [CursoController::class, 'obtenerEvaluacionFinalEdicion']);
        Route::post('/{cursoId}/edicion/evaluacion-final', [CursoController::class, 'guardarEvaluacionFinalEdicion']);
        Route::get('/{cursoId}/edicion/lecciones/{leccionId}/evaluacion', [CursoController::class, 'obtenerEvaluacionLeccionEdicion']);
        Route::post('/{cursoId}/edicion/lecciones/{leccionId}/evaluacion', [CursoController::class, 'guardarEvaluacionLeccionEdicion']);

        /*
        |--------------------------------------------------------------------------
        | Curso privado/oficial
        |--------------------------------------------------------------------------
        */

        Route::get('/{id}', [CursoController::class, 'show']);
        Route::put('/{id}', [CursoController::class, 'update']);

        Route::post('/{id}/enviar-revision', [CursoController::class, 'enviarRevision']);
        Route::post('/{id}/aprobar-revision', [CursoController::class, 'aprobarRevision']);
        Route::post('/{id}/rechazar-revision', [CursoController::class, 'rechazarRevision']);

        Route::post('/{id}/publicar', [CursoController::class, 'publicar']);
        Route::post('/{id}/ocultar', [CursoController::class, 'ocultar']);

        Route::get('/{cursoId}/evaluacion-final', [EvaluacionController::class, 'finalPorCurso']);
        Route::post('/{cursoId}/evaluacion-final', [EvaluacionController::class, 'guardarFinalPorCurso']);
        Route::post('/{cursoId}/repetir', [EvaluacionController::class, 'repetirCurso']);
    });

    Route::prefix('lecciones')->group(function () {
        Route::get('/', [LeccionController::class, 'index']);
        Route::get('/{id}/contenido', [LeccionController::class, 'mostrarContenido']);
        Route::put('/{id}', [LeccionController::class, 'update']);
        Route::delete('/{id}', [LeccionController::class, 'destroy']);

        Route::get('/{leccionId}/evaluacion', [EvaluacionController::class, 'porLeccion']);
        Route::post('/{leccionId}/evaluacion', [EvaluacionController::class, 'guardarPorLeccion']);
    });

    Route::prefix('evaluaciones')->group(function () {
        Route::get('/{evaluacionId}/estado', [EvaluacionController::class, 'estado']);
        Route::post('/{evaluacionId}/responder', [EvaluacionController::class, 'responder']);
    });

    Route::prefix('inscripciones')->group(function () {
        Route::get('/', [InscripcionController::class, 'index']);
        Route::post('/', [InscripcionController::class, 'store']);
        Route::post('/{cursoId}/cancelar', [InscripcionController::class, 'cancelar']);
    });

    Route::get('/mis-cursos', [InscripcionController::class, 'misCursos']);

    Route::prefix('progreso')->group(function () {
        Route::get('/', [ProgresoController::class, 'index']);
        Route::post('/', [ProgresoController::class, 'marcarLeccion']);
        Route::get('/curso/{cursoId}/porcentaje', [ProgresoController::class, 'calcularPorcentajeCurso']);
        Route::get('/curso/{cursoId}/estado', [ProgresoController::class, 'estadoCurso']);
    });

    Route::prefix('pagos')->group(function () {
        Route::get('/', [PagoController::class, 'index']);
        Route::post('/', [PagoController::class, 'store']);
        Route::get('/estado', [PagoController::class, 'estado']);
    });

    Route::prefix('ia')->group(function () {
        Route::post('/tutor', [IaController::class, 'tutor']);
    });
});