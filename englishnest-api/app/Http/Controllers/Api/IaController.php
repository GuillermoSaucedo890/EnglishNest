<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IaController extends Controller
{
    public function tutor(Request $request)
    {
        $data = $request->validate([
            'mensaje' => 'required|string|max:500',
        ]);

        $mensaje = strtolower($data['mensaje']);

        $respuesta = 'No entendí bien tu pregunta. Puedes preguntarme sobre cursos, redes, backend, soporte o progreso.';

        if (str_contains($mensaje, 'hola')) {
            $respuesta = 'Hola 👋 soy tu Tutor IA de EnglishNest. Puedo ayudarte a elegir cursos y mejorar tu aprendizaje.';
        }

        if (str_contains($mensaje, 'curso')) {
            $respuesta = 'Te recomiendo revisar los cursos de inglés técnico según tu área: redes, soporte, backend o reuniones IT.';
        }

        if (str_contains($mensaje, 'redes')) {
            $respuesta = 'Para redes, te recomiendo el curso "Inglés Técnico para Redes". Aprenderás vocabulario sobre routers, switches y soporte.';
        }

        if (str_contains($mensaje, 'backend')) {
            $respuesta = 'Para backend, te recomiendo "Inglés para Programadores Backend". Es ideal para APIs, bases de datos y servidores.';
        }

        if (str_contains($mensaje, 'soporte')) {
            $respuesta = 'Para soporte técnico, te recomiendo practicar frases para tickets, atención a usuarios y solución de problemas.';
        }

        if (str_contains($mensaje, 'progreso')) {
            $respuesta = 'Tu progreso se calcula según las lecciones completadas. Si completas todas, podrás generar tu certificado.';
        }

        return response()->json([
            'ok' => true,
            'respuesta' => $respuesta
        ]);
    }
}
