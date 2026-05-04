@extends('layouts.app')

@section('contenido')
    <h1>Panel principal</h1>

    <p>Bienvenido, <strong>{{ $usuario->nombres }} {{ $usuario->apellidos }}</strong>.</p>
    <p class="texto-pequeno">Tu correo es: {{ $usuario->email }}</p>

    <div class="tarjeta">
        <h3>Información de cuenta</h3>
        <p><strong>ID de usuario:</strong> {{ $usuario->id }}</p>
        <p><strong>Correo verificado:</strong> {{ $usuario->email_verified_at ? 'Sí' : 'No' }}</p>
        <p><strong>Estado:</strong> {{ $usuario->estado }}</p>
        <p><strong>Rol:</strong> {{ $usuario->rol?->nombre ?? 'Sin rol' }}</p>
    </div>

    @if($usuario->rol && $usuario->rol->nombre === 'docente')
        <div class="tarjeta">
            <h3>Perfil docente</h3>

            @if($usuario->perfilDocente)
                <p><strong>Estudios:</strong> {{ $usuario->perfilDocente->estudios }}</p>
                <p><strong>Especialidad:</strong> {{ $usuario->perfilDocente->especialidad ?? 'No definida' }}</p>
                <p><strong>Biografía:</strong> {{ $usuario->perfilDocente->biografia ?? 'No registrada' }}</p>
                <p><strong>Estado de aprobación:</strong> {{ $usuario->perfilDocente->estado_aprobacion }}</p>
            @else
                <p>No existe perfil docente asociado.</p>
            @endif
        </div>
    @endif
@endsection