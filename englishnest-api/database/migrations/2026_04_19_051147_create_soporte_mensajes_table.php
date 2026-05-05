<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla soporte_mensajes
|--------------------------------------------------------------------------
| Aquí guardaremos los mensajes que pertenecen a una conversación
| de soporte.
|
| Ejemplo:
| - el usuario pregunta algo
| - el bot responde
| - luego el admin responde
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla soporte_mensajes
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('soporte_mensajes', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            conversacion_id:
            indica a qué conversación pertenece este mensaje
            */
            $table->foreignId('conversacion_id')
                ->constrained('soporte_conversaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            emisor_tipo:
            quién envió el mensaje
            - usuario
            - bot
            - admin
            - sistema
            */
            $table->enum('emisor_tipo', ['usuario', 'bot', 'admin', 'sistema']);

            // Contenido del mensaje
            $table->text('mensaje');

            // Fecha exacta en la que se envió
            $table->dateTime('fecha_envio');

            // Fechas automáticas
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('soporte_mensajes');
    }
};