@extends('layouts.app')

@section('contenido')
    <h1>Iniciar sesión</h1>
    <p class="texto-pequeno">Ingresa con tu correo y contraseña.</p>

    <form action="{{ route('login.post') }}" method="POST">
        @csrf

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required>
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input type="password" name="password" id="password" required>
        </div>

        <div class="campo">
            <label>
                <input type="checkbox" name="remember">
                Recordarme
            </label>
        </div>

        <button type="submit" class="boton">Entrar</button>
    </form>
@endsection