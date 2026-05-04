<?php

use Illuminate\Support\Facades\Route;

// 🔐 Auth
use App\Http\Controllers\Api\AuthController;

// 👨‍🏫 Admin
use App\Http\Controllers\Api\AdminDocenteController;

// 📚 Cursos y contenido
use App\Http\Controllers\Api\CursoController;
use App\Http\Controllers\Api\LeccionController;
use App\Http\Controllers\Api\InscripcionController;
use App\Http\Controllers\Api\ProgresoController;

// 💳 Pagos
use App\Http\Controllers\Api\PagoController;

// 🤖 IA
use App\Http\Controllers\Api\IaController;

// 💎 Planes y suscripciones
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SuscripcionController;





/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/registro-estudiante', [AuthController::class, 'registrarEstudiante']);
    Route::post('/registro-docente', [AuthController::class, 'registrarDocente']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/reenviar-verificacion', [AuthController::class, 'reenviarVerificacion']);
});





/*
|--------------------------------------------------------------------------
| ADMIN DOCENTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('admin/docentes')->group(function () {
    Route::get('/pendientes', [AdminDocenteController::class, 'pendientes']);
    Route::post('/aprobar/{usuarioId}', [AdminDocenteController::class, 'aprobar']);
});





/*
|--------------------------------------------------------------------------
| CURSOS
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('cursos')->group(function () {
    Route::get('/', [CursoController::class, 'index']);
    Route::post('/', [CursoController::class, 'store']);
    Route::get('/{id}', [CursoController::class, 'show']);
});





/*
|--------------------------------------------------------------------------
| INSCRIPCIONES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('inscripciones')->group(function () {
    Route::get('/', [InscripcionController::class, 'index']);
    Route::post('/', [InscripcionController::class, 'store']);
});





/*
|--------------------------------------------------------------------------
| PROGRESO
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('progreso')->group(function () {
    Route::get('/', [ProgresoController::class, 'index']);
    Route::post('/', [ProgresoController::class, 'marcarLeccion']);
});





/*
|--------------------------------------------------------------------------
| LECCIONES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('lecciones')->group(function () {
    Route::get('/', [LeccionController::class, 'index']);
    Route::post('/', [LeccionController::class, 'store']);
});





/*
|--------------------------------------------------------------------------
| PAGOS
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('pagos')->group(function () {
    Route::get('/', [PagoController::class, 'index']);
    Route::post('/', [PagoController::class, 'store']);
    Route::get('/estado', [PagoController::class, 'estado']);
});





/*
|--------------------------------------------------------------------------
| IA
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('ia')->group(function () {
    Route::post('/tutor', [IaController::class, 'tutor']);
});





/*
|--------------------------------------------------------------------------
| PLANES Y SUSCRIPCIONES 🔥
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // 🔹 Planes
    Route::get('/planes', [PlanController::class, 'index']);
    Route::post('/planes/comprar', [PlanController::class, 'comprar']);
    Route::post('/planes/cancelar', [PlanController::class, 'cancelar']);
    Route::post('/planes/reembolso', [PlanController::class, 'reembolso']);
    Route::get('/planes/actual', [PlanController::class, 'actual']);

    // 🔹 Suscripciones (🔥 CLAVE)
    Route::post('/suscripciones', [SuscripcionController::class, 'store']);
    Route::get('/mi-suscripcion', [SuscripcionController::class, 'miSuscripcion']);
});
