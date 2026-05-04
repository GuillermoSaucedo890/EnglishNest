<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla cursos
|--------------------------------------------------------------------------
| Aquí guardaremos los cursos creados por los docentes.
|
| Cada curso:
| - pertenece a un docente
| - pertenece a un área
| - tiene título
| - tiene descripción
| - tiene nivel
| - tiene estado
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla cursos
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('cursos', function (Blueprint $table) {
            // Clave primaria
            $table->id();

            /*
            docente_id:
            usuario que creó o imparte el curso.
            Apunta a la tabla users.
            */
            $table->foreignId('docente_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
            area_id:
            área a la que pertenece el curso.
            Ejemplo: Tecnología, Redes, etc.
            */
            $table->foreignId('area_id')
                ->constrained('areas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            // Título del curso
            $table->string('titulo', 150);

            // Descripción general del curso
            $table->text('descripcion');

            /*
            Nivel del curso:
            basico, intermedio o avanzado
            */
            $table->enum('nivel', ['basico', 'intermedio', 'avanzado']);

            /*
            Estado del curso:
            - borrador: aún no visible
            - publicado: visible para usuarios
            - archivado: ya no activo
            */
            $table->enum('estado', ['borrador', 'publicado', 'archivado'])
                ->default('borrador');

            /*
            Precio solo como referencia.
            Puede ser null si el acceso es solo por plan.
            */
            $table->decimal('precio_referencia', 10, 2)->nullable();

            // Fecha en la que el curso fue publicado
            $table->dateTime('fecha_publicacion')->nullable();

            // Fechas automáticas
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};