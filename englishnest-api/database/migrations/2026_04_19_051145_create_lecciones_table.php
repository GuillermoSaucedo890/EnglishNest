<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla lecciones
|--------------------------------------------------------------------------
| Aquí guardaremos las lecciones que pertenecen a cada curso.
|
| Cada lección:
| - pertenece a un curso
| - tiene título
| - tiene contenido
| - tiene un orden dentro del curso
| - puede ser gratuita o no
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla lecciones
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('lecciones', function (Blueprint $table) {
            // Clave principal de la tabla
            $table->id();

            /*
            curso_id:
            esta columna apunta a la tabla cursos.
            Significa: esta lección pertenece a un curso.
            */
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Título de la lección
            $table->string('titulo', 150);

            /*
            Contenido de la lección.
            LONGTEXT sirve para guardar bastante texto.
            */
            $table->longText('contenido');

            /*
            Orden de la lección dentro del curso.
            Ejemplo:
            1 = primera lección
            2 = segunda lección
            3 = tercera lección
            */
            $table->integer('orden');

            /*
            es_gratis:
            true = la pueden ver sin pago o como demo
            false = requiere acceso normal
            */
            $table->boolean('es_gratis')->default(false);

            // Fechas automáticas
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    | Si deshaces esta migración, Laravel borra la tabla lecciones.
    */
    public function down(): void
    {
        Schema::dropIfExists('lecciones');
    }
};