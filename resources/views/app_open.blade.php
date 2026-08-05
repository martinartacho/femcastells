<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrasenya actualitzada — FemCastells</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f0fdf4;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .card {
            background: #fff;
            border-radius: 1rem;
            padding: 2.5rem 2rem;
            text-align: center;
            max-width: 360px;
            width: 100%;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
        }
        .icon { font-size: 3.5rem; margin-bottom: 1rem; }
        h1 { color: #15803d; font-size: 1.4rem; margin-bottom: .75rem; }
        p  { color: #4b5563; font-size: .95rem; line-height: 1.6; margin-bottom: 1.5rem; }
        .btn {
            display: inline-block;
            background: #16a34a;
            color: #fff;
            text-decoration: none;
            padding: .75rem 2rem;
            border-radius: .625rem;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: .75rem;
        }
        .note { color: #9ca3af; font-size: .8rem; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon">🔑</div>
        <h1>Contrasenya actualitzada!</h1>
        <p>La teva contrasenya s'ha canviat correctament. Ja pots entrar a l'app FemCastells.</p>
        <a class="btn" href="femcastells://">Obrir FemCastells</a>
        <p class="note">Si l'app no s'obre, inicia sessió manualment.</p>
    </div>
    <script>
        // Intent automàtic just al carregar la pàgina
        window.location.href = 'femcastells://';
    </script>
</body>
</html>
