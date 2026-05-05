<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Tabla curso_ediciones
|--------------------------------------------------------------------------
| Guarda el borrador seguro del curso.
|
| Nunca debe modificar directamente la tabla cursos/lecciones hasta que
| el administrador apruebe y publique.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curso_ediciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curso_id')
                ->constrained('cursos')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('creado_por_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->enum('estado', [
                'borrador',
                'pendiente_revision',
                'rechazado',
            ])->default('borrador');

            $table->boolean('solicita_publicacion')->default(false);
            $table->text('motivo_rechazo')->nullable();

            // Aquí se guarda la copia editable del curso:
            // título, descripción, área, nivel y lecciones.
            $table->json('datos_json');

            $table->timestamps();

            // Solo una edición activa por curso.
            $table->unique('curso_id');

            $table->index('creado_por_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curso_ediciones');
    }
};