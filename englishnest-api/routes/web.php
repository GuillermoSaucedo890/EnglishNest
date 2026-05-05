<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

$frontendUrl = env('FRONTEND_URL', 'http://localhost:4200');

Route::get('/', function () use ($frontendUrl) {
    return redirect($frontendUrl . '/login');
});

Route::get('/correo/verificar/{id}/{hash}', function (Request $request, $id, $hash) use ($frontendUrl) {
    $usuario = User::findOrFail($id);

    if (!URL::hasValidSignature($request)) {
        abort(403, 'Enlace no válido o expirado.');
    }

    if (!hash_equals((string) $hash, sha1($usuario->getEmailForVerification()))) {
        abort(403, 'Hash de verificación inválido.');
    }

    if (!$usuario->hasVerifiedEmail()) {
        $usuario->markEmailAsVerified();
    }

    return redirect($frontendUrl . '/verificar-correo?estado=ok');
})->name('verification.verify');