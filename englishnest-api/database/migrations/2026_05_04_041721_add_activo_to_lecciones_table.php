<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Esta migración queda segura.
    // Si la columna activo ya existe, no intenta crearla otra vez.
    public function up(): void
    {
        if (!Schema::hasTable('lecciones')) {
            return;
        }

        if (!Schema::hasColumn('lecciones', 'activo')) {
            Schema::table('lecciones', function (Blueprint $table) {
                $table->boolean('activo')
                    ->default(true)
                    ->after('es_gratis');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('lecciones')) {
            return;
        }

        if (Schema::hasColumn('lecciones', 'activo')) {
            Schema::table('lecciones', function (Blueprint $table) {
                $table->dropColumn('activo');
            });
        }
    }
};