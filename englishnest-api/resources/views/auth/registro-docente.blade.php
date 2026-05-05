@extends('layouts.app')

@section('contenido')
    <h1>Registro de docente</h1>
    <p class="texto-pequeno">
        Completa tus datos. Tu perfil quedará pendiente de aprobación por un administrador.
    </p>

    <form action="{{ route('registro.docente.post') }}" method="POST">
        @csrf

        <div class="campo">
            <label for="nombres">Nombres</label>
            <input type="text" name="nombres" id="nombres" value="{{ old('nombres') }}" required>
        </div>

        <div class="campo">
            <label for="apellidos">Apellidos</label>
            <input type="text" name="apellidos" id="apellidos" value="{{ old('apellidos') }}" required>
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required>
        </div>

        <div class="campo">
            <label for="password_confirmation">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required>
        </div>

        <div class="campo">
            <label for="estudios">Estudios</label>
            <textarea name="estudios" id="estudios" rows="4" required>{{ old('estudios') }}</textarea>
        </div>

        <div class="campo">
            <label for="especialidad">Especialidad</label>
            <input type="text" name="especialidad" id="especialidad" value="{{ old('especialidad') }}">
        </div>

        <div class="campo">
            <label for="biografia">Biografía</label>
            <textarea name="biografia" id="biografia" rows="4">{{ old('biografia') }}</textarea>
        </div>

        <button type="submit" class="boton">Registrarme como docente</button>
    </form>
@endsection