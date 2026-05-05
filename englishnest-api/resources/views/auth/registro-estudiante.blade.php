@extends('layouts.app')

@section('contenido')
    <h1>Registro de estudiante</h1>
    <p class="texto-pequeno">Completa tus datos para crear tu cuenta como estudiante.</p>

    <form action="{{ route('registro.estudiante.post') }}" method="POST">
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

        <button type="submit" class="boton">Registrarme como estudiante</button>
    </form>
@endsection