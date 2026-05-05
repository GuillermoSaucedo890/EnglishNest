<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea las preguntas de cada evaluación
    public function up(): void
    {
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_id')
                ->constrained('evaluaciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Usamos pregunta porque tu modelo y controller trabajan con ese nombre
            $table->text('pregunta');

            $table->integer('orden')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('preguntas');
    }
};