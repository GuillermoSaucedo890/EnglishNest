<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titulo ?? 'EnglishNest' }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            padding: 0;
        }

        .contenedor {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        h1, h2, h3 {
            margin-top: 0;
        }

        .menu {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .menu a, .menu button {
            padding: 10px 14px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            background: #2563eb;
            color: white;
            cursor: pointer;
            font-size: 14px;
        }

        .menu a.secundario {
            background: #64748b;
        }

        .campo {
            margin-bottom: 15px;
        }

        .campo label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        .campo input,
        .campo textarea,
        .campo select {
            width: 100%;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
        }

        .boton {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            background: #16a34a;
            color: white;
            cursor: pointer;
        }

        .boton-rojo {
            background: #dc2626;
        }

        .alerta-ok {
            background: #dcfce7;
            color: #166534;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .alerta-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .texto-pequeno {
            font-size: 14px;
            color: #475569;
        }

        .tarjeta {
            background: #f8fafc;
            padding: 16px;
            border-radius: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="contenedor">

        <div class="menu">
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('registro.estudiante') }}">Registro estudiante</a>
            <a href="{{ route('registro.docente') }}">Registro docente</a>

            @auth
                <a href="{{ route('panel') }}" class="secundario">Panel</a>

                <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                    @csrf
                    <button type="submit" class="boton-rojo">Cerrar sesión</button>
                </form>
            @endauth
        </div>

        {{-- Mensaje de éxito --}}
        @if(session('ok'))
            <div class="alerta-ok">
                {{ session('ok') }}
            </div>
        @endif

        {{-- Errores de validación --}}
        @if($errors->any())
            <div class="alerta-error">
                <strong>Hay errores en el formulario:</strong>
                <ul style="margin-bottom:0;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('contenido')
    </div>
</body>
</html>