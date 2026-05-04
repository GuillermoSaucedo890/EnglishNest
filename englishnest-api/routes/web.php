<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'ok' => true,
        'mensaje' => 'EnglishNest API funcionando'
    ]);
});

Route::get('/correo/verificar/{id}/{hash}', function ($id, $hash) {
    $usuario = User::findOrFail($id);

    if (!hash_equals((string) $hash, sha1($usuario->getEmailForVerification()))) {
        abort(403, 'Hash de verificación inválido.');
    }

    if (!$usuario->hasVerifiedEmail()) {
        $usuario->markEmailAsVerified();
    }

    return response('
        <html>
            <head>
                <title>Correo verificado</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        background: #f4f7fb;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        text-align: center;
                    }
                    .card {
                        background: white;
                        padding: 35px;
                        border-radius: 20px;
                        box-shadow: 0 10px 30px rgba(0,0,0,0.12);
                    }
                    h1 {
                        color: #16a34a;
                    }
                </style>
            </head>
            <body>
                <div class="card">
                    <h1>✅ Correo verificado</h1>
                    <p>Tu cuenta fue verificada correctamente.</p>
                    <p>Ahora puedes volver a EnglishNest e iniciar sesión.</p>
                </div>
            </body>
        </html>
    ');
})->name('verification.verify');
