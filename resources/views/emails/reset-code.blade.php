<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 480px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 32px; }
        h2 { color: #15803d; margin-top: 0; }
        .code {
            display: block; font-size: 2.4rem; font-weight: bold; letter-spacing: .2em;
            color: #15803d; text-align: center;
            background: #f0fdf4; border: 2px dashed #86efac;
            border-radius: 8px; padding: 16px; margin: 24px 0;
        }
        p { color: #374151; line-height: 1.6; }
        .note { color: #9ca3af; font-size: .85rem; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Restablir contrasenya</h2>
        <p>Has sol·licitat restablir la contrasenya de FemCastells. Introdueix aquest codi a l'app:</p>

        <span class="code">{{ $code }}</span>

        <p>El codi caduca en <strong>15 minuts</strong>.</p>
        <p class="note">Si no has sol·licitat cap canvi de contrasenya, ignora aquest correu.</p>
    </div>
</body>
</html>
