<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Guarda los intentos de un estudiante en una evaluación
    public function up(): void
    {
        Schema::create('intentos_evaluacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('evaluacion_id')
                ->constrained('evaluaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->integer('intento_numero')->default(1);

            // Puntaje en cantidad de correctas
            $table->decimal('puntaje', 5, 2)->default(0);

            // Porcentaje sobre 100
            $table->decimal('porcentaje', 5, 2)->default(0);

            $table->boolean('aprobado')->default(false);

            // Guarda detalle de respuestas, correctas, incorrectas
            $table->json('respuestas_json')->nullable();

            $table->dateTime('fecha_intento');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intentos_evaluacion');
    }
};