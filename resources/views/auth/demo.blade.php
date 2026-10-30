<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Demostración local - LAB DEMO</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #edf4fc;
            color: #183153;
            font-family: Arial, sans-serif;
        }
        main {
            width: min(420px, 90%);
            padding: 36px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 45px #153c681c;
            text-align: center;
        }
        h1 { font-size: 26px; margin-bottom: 12px; }
        p { line-height: 1.6; color: #526981; }
        button {
            width: 100%;
            padding: 15px;
            margin-top: 15px;
            border: 0;
            border-radius: 10px;
            background: #1264df;
            color: white;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
        }
        a {
            display: inline-block;
            margin-top: 22px;
            color: #1264df;
        }
    </style>
</head>
<body>
    <main>
        <h1>LAB DEMO · Demostración</h1>
        <p>
            Acceso local con usuario ficticio.
            No utiliza credenciales ni datos clínicos reales.
        </p>

        <form method="POST" action="{{ route('demo.enter') }}">
            @csrf
            <button type="submit">Entrar a la demostración</button>
        </form>

        <a href="{{ route('login') }}">Volver al inicio de sesión</a>
    </main>
</body>
</html>