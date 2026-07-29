<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de privacitat — FemCastells</title>
    <style>
        body { font-family: sans-serif; max-width: 720px; margin: 40px auto; padding: 0 24px; color: #1f2937; line-height: 1.7; }
        h1 { font-size: 28px; color: #111827; border-bottom: 2px solid #e5e7eb; padding-bottom: 12px; }
        h2 { font-size: 18px; color: #374151; margin-top: 32px; }
        p, ul { color: #4b5563; }
        ul { padding-left: 20px; }
        footer { margin-top: 48px; font-size: 13px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 16px; }
    </style>
</head>
<body>
    <h1>Política de privacitat de FemCastells</h1>
    <p>Última actualització: {{ now()->format('d/m/Y') }}</p>

    <h2>1. Responsable del tractament</h2>
    <p>Pepe Martín Artacho<br>
    Correu de contacte: <a href="mailto:se.pepemartin@gmail.com">se.pepemartin@gmail.com</a></p>

    <h2>2. Dades que recollim</h2>
    <ul>
        <li>Nom, cognom i adreça electrònica (compte de l'usuari)</li>
        <li>Dades d'assistència a esdeveniments de la colla</li>
        <li>Posicions assignades a la pinya (castell humà)</li>
        <li>Dades tècniques del dispositiu per a l'enviament de notificacions push (token Firebase)</li>
    </ul>

    <h2>3. Finalitat del tractament</h2>
    <p>Les dades es fan servir exclusivament per gestionar les activitats de la colla castellera: confirmar assistència a assajos i actuacions, visualitzar la pinya assignada i rebre notificacions de l'aplicació.</p>

    <h2>4. Base legal</h2>
    <p>El tractament es basa en el consentiment de l'usuari (acceptació dels termes en donar-se d'alta) i en l'interès legítim de la colla per organitzar les seves activitats.</p>

    <h2>5. Tercers</h2>
    <p>L'aplicació utilitza <strong>Firebase</strong> (Google LLC) per a l'enviament de notificacions push i analítica bàsica. Google pot emmagatzemar dades tècniques als seus servidors d'acord amb la seva pròpia política de privacitat: <a href="https://policies.google.com/privacy" target="_blank">policies.google.com/privacy</a>.</p>

    <h2>6. Conservació de les dades</h2>
    <p>Les dades es conserven mentre l'usuari formi part de la colla. En cas de baixa, les dades s'eliminen a petició de l'usuari o del responsable de la colla.</p>

    <h2>7. Drets de l'usuari</h2>
    <p>L'usuari pot exercir els drets d'accés, rectificació, supressió i portabilitat de les dades contactant per correu electrònic a <a href="mailto:se.pepemartin@gmail.com">se.pepemartin@gmail.com</a>.</p>

    <h2>8. Seguretat</h2>
    <p>Les dades es transmeten xifrades (HTTPS) i s'emmagatzemen en servidors protegits. Les contrasenyes es guarden amb hash irreversible.</p>

    <footer>
        FemCastells · Gestió de colles castelleres · <a href="mailto:se.pepemartin@gmail.com">se.pepemartin@gmail.com</a>
    </footer>
</body>
</html>
