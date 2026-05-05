<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea las lecciones oficiales de cada curso
    public function up(): void
    {
        Schema::create('lecciones', function (Blueprint $table) {
            $table->id();

            // Curso al que pertenece la lección
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Título de la lección
            $table->string('titulo', 150);

            // Resumen corto visible antes de entrar
            $table->text('descripcion')->nullable();

            // Contenido escrito de la lección
            $table->longText('contenido_texto')->nullable();

            // Video de YouTube que se mostrará embebido en la plataforma
            $table->string('url_video')->nullable();

            // Orden dentro del curso
            $table->integer('orden')->default(1);

            // Introducción o lección normal
            $table->enum('tipo', ['introduccion', 'normal'])->default('normal');

            // Si es gratis, cualquiera puede verla
            $table->boolean('es_gratis')->default(false);

            // Activa/inactiva la lección sin borrarla físicamente
            $table->boolean('activo')->default(true);

            $table->timestamps();

            // Evita repetir dos lecciones con el mismo orden dentro del curso
            $table->unique(['curso_id', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecciones');
    }
};