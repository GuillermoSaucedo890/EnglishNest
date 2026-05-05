<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Crea las opciones de cada pregunta
    public function up(): void
    {
        Schema::create('opciones_pregunta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pregunta_id')
                ->constrained('preguntas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->text('texto');

            $table->boolean('es_correcta')->default(false);

            $table->integer('orden')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opciones_pregunta');
    }
};