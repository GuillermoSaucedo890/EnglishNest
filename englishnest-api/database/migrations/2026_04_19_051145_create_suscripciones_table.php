<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Migración: Tabla suscripciones
|--------------------------------------------------------------------------
| Guarda los planes adquiridos por los usuarios
|
| Incluye:
| - Usuario
| - Plan
| - Método de pago
| - Fechas
| - Estado
*/
return new class extends Migration
{
    /**
     * Crear la tabla
     */
    public function up(): void
    {
        Schema::create('suscripciones', function (Blueprint $table) {
            $table->id();

            // 🔥 Usuario dueño de la suscripción
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // 🔥 Plan adquirido
            $table->foreignId('plan_id')
                ->constrained('planes')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // 🔥 Método de pago (Tarjeta, QR, etc)
            $table->string('metodo_pago');

            // 🔥 Estado de la suscripción
            $table->enum('estado', ['activa', 'vencida', 'cancelada'])
                ->default('activa');

            // 🔥 Fechas
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');

            // 🔥 Renovación automática
            $table->boolean('renovacion_automatica')->default(false);

            // 🔥 Timestamps
            $table->timestamps();
        });
    }

    /**
     * Eliminar la tabla
     */
    public function down(): void
    {
        Schema::dropIfExists('suscripciones');
    }
};
