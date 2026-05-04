<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla evaluaciones
|--------------------------------------------------------------------------
| Aquí guardaremos las evaluaciones de cada lección.
|
| Cada evaluación:
| - pertenece a una lección
| - tiene título
| - tiene tipo
| - tiene puntaje máximo
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla evaluaciones
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            leccion_id:
            indica a qué lección pertenece esta evaluación.
            */
            $table->foreignId('leccion_id')
                ->constrained('lecciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            // Nombre o título de la evaluación
            $table->string('titulo', 150);

            /*
            Tipo de evaluación.
            enum significa que solo puede tomar uno de estos valores.
            */
            $table->enum('tipo', ['quiz', 'examen', 'practica']);

            /*
            Puntaje máximo.
            Ejemplo: 100.00
            */
            $table->decimal('puntaje_maximo', 5, 2)->default(100);

            // Fechas automáticas
            $table->timestamps();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
};