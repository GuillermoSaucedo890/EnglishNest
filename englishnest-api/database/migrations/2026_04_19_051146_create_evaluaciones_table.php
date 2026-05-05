<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea evaluaciones para cuestionarios de lección y evaluación final del curso
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();

            // Curso al que pertenece la evaluación
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Si es cuestionario de lección, tiene leccion_id.
            // Si es evaluación final, queda null.
            $table->foreignId('leccion_id')
                ->nullable()
                ->constrained('lecciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Título visible para docente/admin/estudiante
            $table->string('titulo', 150);

            // quiz_leccion = cuestionario de una lección normal
            // examen_final = evaluación final del curso
            $table->enum('tipo', [
                'quiz_leccion',
                'examen_final',
            ]);

            // Porcentaje mínimo para aprobar esta evaluación individual
            $table->decimal('puntaje_minimo_aprobacion', 5, 2)->default(70);

            // borrador = todavía le faltan preguntas
            // activa = ya puede responderse
            $table->enum('estado', ['borrador', 'activa'])->default('borrador');

            $table->timestamps();

            // Solo una evaluación final por curso
            $table->unique(['curso_id', 'tipo', 'leccion_id'], 'evaluaciones_unicas_por_curso_leccion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};