<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Tabla perfiles_docentes
|--------------------------------------------------------------------------
| Guarda el perfil profesional del docente.
|
| Flujo:
| - Un docente nuevo queda pendiente.
| - Si el admin lo rechaza, se guarda motivo_rechazo.
| - Si el docente ya fue aprobado y edita su perfil, los cambios no pisan
|   el perfil oficial. Quedan en cambios_pendientes_json hasta que el admin
|   los apruebe o rechace.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perfiles_docentes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('especialidad')->nullable();
            $table->text('estudios')->nullable();
            $table->text('biografia')->nullable();

            $table->enum('estado_aprobacion', [
                'pendiente',
                'aprobado',
                'rechazado',
            ])->default('pendiente');

            $table->text('motivo_rechazo')->nullable();

            // Cambios nuevos cuando el docente ya fue aprobado.
            $table->json('cambios_pendientes_json')->nullable();

            $table->enum('estado_revision_cambios', [
                'pendiente',
                'rechazado',
            ])->nullable();

            $table->text('motivo_rechazo_cambios')->nullable();

            $table->timestamp('fecha_solicitud_cambios')->nullable();

            $table->timestamps();

            $table->unique('usuario_id');
            $table->index('estado_aprobacion');
            $table->index('estado_revision_cambios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles_docentes');
    }
};