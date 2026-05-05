<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla suscripciones
|--------------------------------------------------------------------------
| Aquí guardaremos los planes contratados por cada usuario.
|
| Esta tabla sirve para manejar:
| - TRIAL
| - BASICO
| - PRO
| - PREMIUM
|
| También guarda:
| - cuándo empezó
| - cuándo termina
| - si está activa, vencida o cancelada
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla suscripciones
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            usuario_id:
            usuario dueño de esta suscripción.
            */
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            plan_id:
            plan asociado a esta suscripción.
            Ejemplo: TRIAL, BASICO, PRO, PREMIUM
            */
            $table->foreignId('plan_id')
                ->constrained('planes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
            Estado de la suscripción:
            - activa: se puede usar
            - vencida: ya terminó
            - cancelada: fue cancelada antes
            */
            $table->enum('estado', ['activa', 'vencida', 'cancelada'])
                ->default('activa');

            // Fecha de inicio de la suscripción
            $table->dateTime('fecha_inicio');

            // Fecha de fin de la suscripción
            $table->dateTime('fecha_fin');

            /*
            renovacion_automatica:
            true = se renueva automáticamente
            false = no se renueva sola
            */
            $table->boolean('renovacion_automatica')->default(false);

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
        Schema::dropIfExists('suscripciones');
    }
};