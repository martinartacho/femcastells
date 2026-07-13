# FemPinya 4

Aplicació de gestió per a colles castelleres. Substitut de [FemPinya 3](https://github.com/AssociacioFemPinya/backend) (Laravel 8), reescrit des de zero amb Laravel 13 i Filament 5.

> Projecte personal de [H. Artacho](https://github.com/martinartacho). Intenció futura d'oferir-lo a l'organització AssociacioFemPinya.

---

## Stack

| Capa | Tecnologia |
|---|---|
| Framework | Laravel 13 · PHP 8.3 |
| Panell admin | Filament 5 |
| Portal membre | Filament 5 (guard `member`, model `Casteller`) |
| Multi-tenancy | Colla com a tenant (slug `shortname`) |
| Base de dades | MySQL |
| Frontend | Blade + Alpine.js + Tailwind CSS |
| Canvas interactiu | Konva.js (via Vite) |
| Assets | Vite |
| Permisos | spatie/laravel-permission |

---

## Funcionalitats

### Panell admin (`/admin/{shortname}/`)
- **Castellers** — CRUD complet, import CSV, grups, assignació de rols
- **Esdeveniments** — calendari, gestió d'assistència amb respostes personalitzades, export CSV
- **Pissarra** — editor visual de pinyes (Konva.js):
  - Assignació de castellers a posicions (clic, drag & drop)
  - Múltiples versions desades per event (`board_snapshots`)
  - Pissarra oficial (⭐) per event
  - Undo (Ctrl+Z), indicador de canvis no desats
  - **Projector** — URL permanent per colla per a TV/Raspberry Pi en mode quiosc
  - **Compartir** — URL pública per a un snapshot concret
  - **Canta la pinya** — vista d'impressió agrupada per posició
- **Plantilles de castell** — generació i gestió de mapes de posicions (Cinc de 6, Set de X, 3d6…)
- **Noticies** — tauler d'anuncis intern
- **Configuració** — gestió de la colla, GDPR, permisos per rol

### Portal membre (`/member/{shortname}/`)
- **Calendari** — events visibles amb confirmació d'assistència i respostes a preguntes
- **La meva pinya** — mostra la pinya projectada per la tècnica i destaca la posició del casteller

### Pantalles públiques (sense login)
- `/display/{shortname}/{token}` — projector en temps real (auto-refresh 30s)
- `/share/{shortname}/{token}` — snapshot específic compartible

---

## Instal·lació en local

### Requisits
- PHP 8.3+, Composer
- Node.js 20+, npm
- MySQL
- Laragon (Windows) o equivalent

### Passos

```bash
git clone git@github.com:martinartacho/fempinya4.git
cd fempinya4

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Edita `.env`:
```
APP_URL=http://fempinya4.test
DB_DATABASE=fempinya4
DB_USERNAME=root
DB_PASSWORD=
SEEDER_ADMIN_PASSWORD=<contrasenya-segura>
SEEDER_ADMIN_EMAIL=<email>
```

```bash
php artisan migrate --seed
npm run build
```

Accés: `http://fempinya4.test/admin`

---

## Estructura clau

```
app/
├── Filament/
│   ├── Pages/BoardEditorPage.php        ← Editor de pissarres (Livewire)
│   ├── Resources/EventResource.php
│   ├── Resources/CastellerResource.php
│   └── Member/                          ← Recursos del portal membre
├── Http/Controllers/
│   ├── ProjectorDisplayController.php   ← Pantalla TV/Raspberry
│   └── DisplayPinyaController.php       ← Snapshot compartit
├── Models/
│   ├── Colla.php                        ← Tenant (display_token, projected_snapshot_id)
│   ├── BoardSnapshot.php                ← Versions de pinya desades
│   ├── CastleTemplate.php               ← Mapes de posicions
│   └── Casteller.php                    ← Autenticable (guard member)
resources/
├── js/board-editor.js                   ← Canvas Konva.js (assignacions, undo)
└── views/
    ├── filament/pages/board-editor.blade.php
    ├── filament/member/pages/pinya.blade.php
    └── display/                         ← Vistes públiques del projector
```

---

## Motivació

FemPinya 4 neix de l'auditoria de l'ecosistema existent (juny 2026):

- **FP3 backend** ([AssociacioFemPinya/backend](https://github.com/AssociacioFemPinya/backend), Laravel 8) és el repo més actiu, en producció, però tecnològicament desfasat. Migrar-lo a Laravel 11+ seria una reescriptura gairebé total: canvi de Blade tradicional, model de permisos, Livewire/Filament... Un risc alt si l'organització no hi participa activament.
- **FP3 api** (Laravel 11) té una arquitectura correcta com a pont entre backend i app Flutter, però l'activitat és escassa i els endpoints cobreixen poc domini.
- L'organització **AssociacioFemPinya** és semi-activa. Contribuir via PR requereix coordinació i cessió del codi (AGPL-3.0).

La decisió: **fork privat + reescriptura neta** amb stack modern (Laravel 13 + Filament 5), llibertat total de disseny, i opció d'oferir-lo upstream quan estigui madur.

L'autor va crear FemPinya v1 i v2 (primera app del mercat, usada pel 50% de colles) i va impulsar la creació de l'AssociacioFemPinya i el grup dev que va desenvolupar FP3.

## Referència FP3

Els repos de FP3 estan disponibles a [AssociacioFemPinya](https://github.com/AssociacioFemPinya/backend) per a consulta de domini. No se'n copia codi directament.

---

## Llicència

Projecte privat. Tots els drets reservats © H. Artacho.
