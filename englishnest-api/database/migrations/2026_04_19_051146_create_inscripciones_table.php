<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Guarda los cursos a los que se inscribe un estudiante
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('suscripcion_id')
                ->nullable()
                ->constrained('suscripciones')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // activa: cursando
            // completada: aprobó el curso
            // cancelada: se desuscribió
            // bloqueada/reprobada: falló intentos y debe repetir curso
            $table->enum('estado', [
                'activa',
                'completada',
                'cancelada',
                'bloqueada',
                'reprobada',
            ])->default('activa');

            $table->decimal('progreso_porcentaje', 5, 2)->default(0);

            // 70 puntos del curso
            $table->decimal('nota_cuestionarios', 5, 2)->default(0);

            // 30 puntos del curso
            $table->decimal('nota_examen_final', 5, 2)->default(0);

            // nota_cuestionarios + nota_examen_final
            $table->decimal('nota_final', 5, 2)->default(0);

            $table->boolean('aprobado')->default(false);

            $table->boolean('certificado_disponible')->default(false);

            $table->dateTime('fecha_inscripcion');

            $table->dateTime('fecha_cancelacion')->nullable();

            $table->dateTime('fecha_completado')->nullable();

            $table->dateTime('fecha_reprobacion')->nullable();

            $table->timestamps();

            $table->unique(['usuario_id', 'curso_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};