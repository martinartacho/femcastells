<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>FemCastells — Novetats</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <style>
        body { font-family: system-ui, -apple-system, sans-serif; background:#f8fafc; color:#1e293b; margin:0; }
        * { box-sizing:border-box; }
        a { text-decoration:none; color:inherit; }
    </style>
</head>
<body>

    {{-- Capçalera --}}
    <header style="background:#1e293b; color:white;">
        <div style="max-width:860px; margin:0 auto; padding:24px; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
                        <rect width="32" height="32" rx="8" fill="#dc2626"/>
                        <rect x="14" y="4" width="4" height="24" rx="2" fill="white"/>
                        <rect x="6" y="10" width="20" height="4" rx="2" fill="white"/>
                    </svg>
                    <span style="font-size:22px; font-weight:700; letter-spacing:-0.5px;">FemCastells</span>
                </div>
                <p style="margin:4px 0 0 42px; font-size:13px; color:#94a3b8;">Gestió digital de colles castelleres</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="/admin/demo/"
                   style="background:#dc2626; color:white; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600;">
                    Accés Admin
                </a>
                <a href="/member/demo/"
                   style="background:#16a34a; color:white; padding:8px 18px; border-radius:8px; font-size:13px; font-weight:600;">
                    Accés Castellers
                </a>
            </div>
        </div>
    </header>

    {{-- Contingut --}}
    <main style="max-width:860px; margin:0 auto; padding:40px 24px 60px;">

        <h2 style="font-size:17px; font-weight:700; color:#0f172a; margin:0 0 24px; display:flex; align-items:center; gap:8px;">
            <span style="display:inline-block; width:4px; height:18px; background:#dc2626; border-radius:2px;"></span>
            Novetats
        </h2>

        {{-- v0.3 --}}
        <div style="background:white; border-radius:12px; border:1px solid #e2e8f0; padding:22px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span style="background:#fef2f2; color:#dc2626; font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; border:1px solid #fecaca;">v0.3</span>
                <span style="font-size:12px; color:#64748b;">Juny 2026</span>
            </div>
            <h3 style="font-size:15px; font-weight:700; margin:0 0 10px; color:#0f172a;">Editor de pissarra digital</h3>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:#374151; line-height:1.85;">
                <li>Canvas interactiu amb Konva.js — arrossega castellers a cada posició del castell</li>
                <li>Colors per zona: pinya, folre i tronc (estimació geomètrica)</li>
                <li>Indicadors d'assistència per casteller: ✓ confirmat / ✗ no ve / ? pendent</li>
                <li>Desar i carregar configuracions per event («Desa» i «Desa com...»)</li>
                <li>Dues seccions al panell: pissarres de l'event actual + totes les plantilles</li>
                <li>Exportació de la pissarra en format PNG</li>
                <li>Gestió de plantilles de castell: upload d'SVG i extracció automàtica de posicions</li>
                <li>Accés directe a la pissarra des del llistat d'events</li>
            </ul>
        </div>

        {{-- v0.2 --}}
        <div style="background:white; border-radius:12px; border:1px solid #e2e8f0; padding:22px; margin-bottom:14px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span style="background:#f0fdf4; color:#16a34a; font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; border:1px solid #bbf7d0;">v0.2</span>
                <span style="font-size:12px; color:#64748b;">Abril 2026</span>
            </div>
            <h3 style="font-size:15px; font-weight:700; margin:0 0 10px; color:#0f172a;">Portal membre i assistència</h3>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:#374151; line-height:1.85;">
                <li>Portal exclusiu per a castellers (panell independent, guard separat)</li>
                <li>Confirmació d'assistència: assisteixo / no assisteixo / pendent</li>
                <li>Importació de castellers i dades des de FemPinya 3</li>
                <li>Exportació de matriu d'assistència per periode</li>
            </ul>
        </div>

        {{-- v0.1 --}}
        <div style="background:white; border-radius:12px; border:1px solid #e2e8f0; padding:22px; margin-bottom:28px; box-shadow:0 1px 3px rgba(0,0,0,0.05);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:8px;">
                <span style="background:#eff6ff; color:#2563eb; font-size:11px; font-weight:700; padding:2px 8px; border-radius:20px; border:1px solid #bfdbfe;">v0.1</span>
                <span style="font-size:12px; color:#64748b;">Febrer 2026</span>
            </div>
            <h3 style="font-size:15px; font-weight:700; margin:0 0 10px; color:#0f172a;">Base del sistema</h3>
            <ul style="margin:0; padding-left:18px; font-size:13px; color:#374151; line-height:1.85;">
                <li>Panell d'administració (Filament v5) amb suport multi-colla</li>
                <li>Gestió de castellers: altes, baixes, etiquetes i posicions</li>
                <li>Gestió d'events: assaigs, actuacions i activitats</li>
                <li>Control de visibilitat i obertura d'assistència per event</li>
            </ul>
        </div>

        {{-- Properament --}}
        <div style="background:#f8fafc; border-radius:12px; border:1px dashed #cbd5e1; padding:18px;">
            <h3 style="font-size:13px; font-weight:600; color:#64748b; margin:0 0 8px;">Properament</h3>
            <ul style="margin:0; padding-left:18px; font-size:12px; color:#94a3b8; line-height:1.85;">
                <li>Events recurrents (assaigs setmanals automàtics)</li>
                <li>Filtre per posicions a la pissarra (baixos, agulles, enxanetes...)</li>
                <li>Historial de posicions per casteller</li>
                <li>Projecció de la pissarra en pantalla gran</li>
            </ul>
        </div>

    </main>

    {{-- Peu --}}
    <footer style="border-top:1px solid #e2e8f0; padding:16px 24px; text-align:center; font-size:11px; color:#94a3b8;">
        FemCastells &middot; H. Artacho &middot;
        <a href="https://github.com/martinartacho/femcastells" style="color:#64748b;">GitHub</a>
    </footer>

</body>
</html>
