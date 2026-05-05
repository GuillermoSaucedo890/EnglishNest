<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

// Modelo de usuario
use App\Models\User;

// Modelo de rol
use App\Models\Rol;

// Facade para cifrar la contraseña
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Crea el usuario administrador inicial.
     */
    public function run(): void
    {
        /*
        Primero buscamos el rol admin.
        Si no existe, no seguimos.
        */
        $rolAdmin = Rol::where('nombre', 'admin')->first();

        if (!$rolAdmin) {
            return;
        }

        /*
        Creamos o actualizamos el admin principal.

        IMPORTANTE:
        - email_verified_at = now() para que ya quede confirmado
        - password va cifrada con Hash::make
        */
        User::updateOrCreate(
            ['email' => 'admin@englishnest.com'],
            [
                'rol_id' => $rolAdmin->id,
                'nombres' => 'Administrador',
                'apellidos' => 'Principal',
                'password' => Hash::make('Admin12345*'),
                'estado' => 'activo',
                'fecha_prueba_usada' => null,
                'email_verified_at' => now(),
            ]
        );
    }
}