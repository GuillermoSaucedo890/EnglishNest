<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Esta migración crea la tabla pagos
|--------------------------------------------------------------------------
| Aquí guardaremos los pagos realizados por los usuarios.
|
| Nos servirá para:
| - registrar pagos de planes
| - saber si el pago fue exitoso o no
| - guardar una referencia externa si luego usas Stripe o PayPal
*/
return new class extends Migration
{
    /*
    |--------------------------------------------------------------------------
    | Crear la tabla pagos
    |--------------------------------------------------------------------------
    */
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            // Clave principal
            $table->id();

            /*
            usuario_id:
            usuario que realizó el pago
            */
            $table->foreignId('usuario_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
            suscripcion_id:
            pago asociado a una suscripción.
            Puede quedar vacío si luego manejas otro tipo de cobro.
            */
            $table->foreignId('suscripcion_id')
                ->nullable()
                ->constrained('suscripciones')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            // Monto del pago
            $table->decimal('monto', 10, 2);

            /*
            proveedor:
            quién procesó el pago
            */
            $table->enum('proveedor', ['stripe', 'paypal', 'manual'])
                ->default('manual');

            /*
            referencia_externa:
            para guardar un código, id o referencia del pago
            en una pasarela externa.
            */
            $table->string('referencia_externa', 100)->nullable();

            /*
            Estado del pago:
            - pendiente
            - pagado
            - fallido
            - reembolsado
            */
            $table->enum('estado', ['pendiente', 'pagado', 'fallido', 'reembolsado'])
                ->default('pendiente');

            // Fecha en la que se registró el pago como realizado
            $table->dateTime('fecha_pago')->nullable();

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
        Schema::dropIfExists('pagos');
    }
};