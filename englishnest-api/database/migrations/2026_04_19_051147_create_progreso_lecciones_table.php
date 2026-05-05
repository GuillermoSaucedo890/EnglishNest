<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla progreso_leccion
|--------------------------------------------------------------------------
| Aquí guardaremos el avance del usuario por lección.
|
| Ojo:
| No guardamos progreso por curso completo para evitar duplicar datos.
| El avance del curso se puede calcular viendo cuántas lecciones
| completó el usuario.
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla progreso_leccion
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('progreso_leccion', function (Blueprint $table) {
            /*
            usuario_id:
            usuario que está avanzando en la lección
            */
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            leccion_id:
            lección que está viendo o completó
            */
            $table->foreignId('leccion_id')
                ->constrained('lecciones')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            completado:
            true = ya terminó la lección
            false = aún no
            */
            $table->boolean('completado')->default(false);

            // Fecha en la que completó la lección
            $table->dateTime('fecha_completado')->nullable();

            // Última vez que vio esa lección
            $table->dateTime('ultima_visualizacion')->nullable();

            // Fechas automáticas
            $table->timestamps();

            /*
            Clave primaria compuesta:
            un mismo usuario solo puede tener un registro
            por cada lección.
            */
            $table->primary(['usuario_id', 'leccion_id']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('progreso_leccion');
    }
};