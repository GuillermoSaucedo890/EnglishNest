<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea la tabla principal de cursos
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();

            // Docente asignado al curso
            $table->foreignId('docente_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Área o categoría profesional del curso
            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Datos principales del curso
            $table->string('titulo', 150);
            $table->text('descripcion');

            // Nivel del curso
            $table->enum('nivel', [
                'basico',
                'intermedio',
                'avanzado',
            ])->default('basico');

            // Estado simple del curso
            $table->enum('estado', [
                'borrador',
                'pendiente_revision',
                'publicado',
                'oculto',
                'rechazado',
            ])->default('borrador');

            // Indica si el docente pidió que se publique al aprobar la revisión
            $table->boolean('solicita_publicacion')->default(false);

            // Motivo cuando el admin rechaza el curso
            $table->text('motivo_rechazo')->nullable();

            // Precio visual opcional, aunque el acceso principal sea por plan
            $table->decimal('precio_referencia', 10, 2)->nullable();

            // Datos para ordenar cursos por calificación sin recalcular siempre
            $table->decimal('promedio_calificacion', 3, 2)->default(0);
            $table->integer('cantidad_calificaciones')->default(0);

            // Fecha en que se publicó
            $table->dateTime('fecha_publicacion')->nullable();

            $table->timestamps();
        });
    }

    // Borra la tabla si hacemos rollback
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};