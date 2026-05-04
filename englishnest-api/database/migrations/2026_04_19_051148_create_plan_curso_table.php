<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla intermedia plan_curso
|--------------------------------------------------------------------------
| Esta tabla dice qué cursos puede ver cada plan.
|
| Ejemplo:
| - el plan TRIAL puede ver el curso 1
| - el plan BASICO puede ver cursos 1, 2 y 3
| - el plan PREMIUM puede ver todos
|
| Es una relación muchos a muchos:
| - un plan puede tener muchos cursos
| - un curso puede pertenecer a varios planes
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla plan_curso
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('plan_curso', function (Blueprint $table) {
            /*
            plan_id:
            referencia al plan
            */
            $table->foreignId('plan_id')
                ->constrained('planes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            curso_id:
            referencia al curso
            */
            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            Clave principal compuesta:
            evita repetir la misma combinación
            plan + curso
            */
            $table->primary(['plan_id', 'curso_id']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Deshacer la migración
    |--------------------------------------------------------------------------
    */
    public function down(): void
    {
        Schema::dropIfExists('plan_curso');
    }
};