<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla soporte_conversaciones
|--------------------------------------------------------------------------
| Aquí guardaremos cada conversación de soporte.
|
| Ejemplo:
| - un estudiante abre una conversación para pedir ayuda
| - el sistema responde con bot
| - luego puede escalar a humano
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla soporte_conversaciones
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('soporte_conversaciones', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            usuario_id:
            usuario que abrió la conversación.
            Puede ser nulo si luego permites soporte sin iniciar sesión.
            */
            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            atendido_por:
            indica quién lleva la conversación
            - bot
            - humano
            - mixto
            */
            $table->enum('atendido_por', ['bot', 'humano', 'mixto'])
                ->default('bot');

            /*
            estado:
            - abierta
            - cerrada
            - escalada
            */
            $table->enum('estado', ['abierta', 'cerrada', 'escalada'])
                ->default('abierta');

            // Fecha en la que se cerró, si ya fue cerrada
            $table->dateTime('cerrada_en')->nullable();

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
        Schema::dropIfExists('soporte_conversaciones');
    }
};