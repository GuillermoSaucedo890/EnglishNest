<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Curso;
use App\Models\User;
use App\Models\Area;
use Illuminate\Support\Facades\Hash;

class CursoSeeder extends Seeder
{
    public function run(): void
    {
        // 🔹 Crear o obtener docente
        $docente = User::firstOrCreate(
            ['email' => 'docente@englishnest.com'],
            [
                'rol_id' => 2,
                'nombres' => 'Gustavo',
                'apellidos' => 'Tantani',
                'password' => Hash::make('12345678'),
                'estado' => 'activo',
                'email_verified_at' => now(),
            ]
        );

        // 🔹 Crear o obtener área
        $area = Area::firstOrCreate(
            ['nombre' => 'Inglés Técnico'],
            [
                'descripcion' => 'Cursos de inglés técnico para tecnología.',
            ]
        );

        // 🔹 Curso 1
        Curso::updateOrCreate(
            ['titulo' => 'Inglés Técnico para Desarrollo de Software'],
            [
                'docente_id' => $docente->id,
                'area_id' => $area->id,
                'descripcion' => 'Aprende vocabulario técnico usado en programación, bases de datos, APIs y documentación.',
                'nivel' => 'basico',
                'estado' => 'publicado',
                'precio_referencia' => 0,
                'fecha_publicacion' => now(),
            ]
        );

        // 🔹 Curso 2
        Curso::updateOrCreate(
            ['titulo' => 'Inglés para Soporte Técnico'],
            [
                'docente_id' => $docente->id,
                'area_id' => $area->id,
                'descripcion' => 'Curso orientado a atención al cliente, tickets, errores técnicos y comunicación profesional.',
                'nivel' => 'intermedio',
                'estado' => 'publicado',
                'precio_referencia' => 0,
                'fecha_publicacion' => now(),
            ]
        );

        // 🔹 Curso 3
        Curso::updateOrCreate(
            ['titulo' => 'Inglés para Redes y Servidores'],
            [
                'docente_id' => $docente->id,
                'area_id' => $area->id,
                'descripcion' => 'Aprende términos técnicos sobre redes, servidores, dominios, IP, DNS y seguridad.',
                'nivel' => 'intermedio',
                'estado' => 'publicado',
                'precio_referencia' => 0,
                'fecha_publicacion' => now(),
            ]
        );
    }
}
