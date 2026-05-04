@extends('layouts.app')

@section('contenido')
    <h1>Verifica tu correo</h1>

    <p>
        Antes de continuar, revisa tu correo electrónico y haz clic en el enlace de verificación.
    </p>

    <p class="texto-pequeno">
        Si no encuentras el mensaje, revisa también tu carpeta de spam o correo no deseado.
    </p>

    <div class="tarjeta">
        <form action="{{ route('verification.send') }}" method="POST">
            @csrf
            <button type="submit" class="boton">Reenviar correo de verificación</button>
        </form>
    </div>
@endsection