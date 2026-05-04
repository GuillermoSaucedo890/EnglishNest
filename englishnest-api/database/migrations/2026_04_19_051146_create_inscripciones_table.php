<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla inscripciones
|--------------------------------------------------------------------------
| Aquí guardaremos a qué cursos se inscribió cada usuario.
|
| Ejemplo:
| - Juan se inscribió al curso "Inglés para programación"
| - María se inscribió al curso "Networking básico"
|
| También guardamos:
| - con qué suscripción entró
| - el estado de esa inscripción
| - la fecha en la que se inscribió
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla inscripciones
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            usuario_id:
            indica qué usuario se inscribió.
            */
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            curso_id:
            indica a qué curso se inscribió.
            */
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            suscripcion_id:
            indica con qué suscripción obtuvo acceso.
            Puede quedar vacío si en algún caso entra por otro medio.
            */
            $table->foreignId('suscripcion_id')
                ->nullable()
                ->constrained('suscripciones')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            /*
            Estado de la inscripción:
            - activa: el curso está en progreso
            - completada: el usuario terminó el curso
            - cancelada: dejó o perdió la inscripción
            */
            $table->enum('estado', ['activa', 'completada', 'cancelada'])
                ->default('activa');

            // Fecha en la que el usuario se inscribió
            $table->dateTime('fecha_inscripcion');

            // Fechas automáticas
            $table->timestamps();

            /*
            Evita que el mismo usuario se inscriba dos veces
            al mismo curso.
            */
            $table->unique(['usuario_id', 'curso_id']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};