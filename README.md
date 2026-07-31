# FemPinya 4

## Motivació

FemPinya 4 neix de l'auditoria de l'ecosistema existent (maig 2026):

- **FP3 backend** ([AssociacioFemPinya/backend](https://github.com/AssociacioFemPinya/backend), Laravel 8) és el repo més actiu, en producció, però tecnològicament desfasat. Migrar-lo a Laravel 11+ seria una reescriptura gairebé total: canvi de Blade tradicional, model de permisos, Livewire/Filament... Un risc alt si l'organització no hi participa activament.
- **FP3 api** (Laravel 11) té una arquitectura correcta com a pont entre backend i app Flutter, però l'activitat és escassa i els endpoints cobreixen poc domini.
- L'organització **AssociacioFemPinya** és semi-activa. Contribuir via PR requereix coordinació i cessió del codi (AGPL-3.0).

La decisió: **fork privat + reescriptura neta** amb stack modern (Laravel 13 + Filament 5), llibertat total de disseny, i opció d'oferir-lo upstream quan estigui madur.


## Referència FP3

Els repos de FP3 estan disponibles a [AssociacioFemPinya](https://github.com/AssociacioFemPinya/backend) per a consulta de domini. No se'n copia codi directament.


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
| API mòbil | Laravel Sanctum (token Bearer) |

---

## Funcionalitats

### Panell admin (`/admin/{shortname}/`)
- **Castellers** — CRUD complet, import CSV, grups, assignació de rols
- **Esdeveniments** — calendari, gestió d'assistència amb respostes personalitzades, export CSV
- **Pissarra** — editor visual de pinyes (Konva.js):
  - Assignació de castellers a posicions (clic, drag & drop)
  - Múltiples versions desades per event (`board_snapshots`), reanomenables
  - Nom per defecte al desar = tipologia del castell actual
  - Pissarra oficial (⭐) per event
  - Undo (Ctrl+Z), indicador de canvis no desats
  - **Projector** — URL permanent per colla per a TV/Raspberry Pi en mode quiosc
  - **Compartir** — URL pública per a un snapshot concret
  - **Canta la pinya** — vista d'impressió agrupada per posició
  - **Rondes** — gestió de l'ordre d'actuació per event:
    - Assignació de pinyes a rondes (Ronda 1, 2, 3…) amb reordenació ▲▼
    - Publicació amb URL pública per als castellers (sense login)
    - Mode pestanyes: vista interactiva amb canvas Konva.js integrat
    - Mode targetes: llista clicable cap a cada pinya individual
    - Configurable per colla (pestanyes / targetes)
- **Plantilles de castell** — generació i gestió de mapes de posicions:
  - **Wizard visual** (`/admin/{shortname}/wizard-plantilla`) — generació automàtica per tipus (Tres, Quatre…), pisos, nivell (base, tronc, folre, manilles) i anells; radis calibrats des de templates reals
  - CRUD de plantilles amb accions de reanomenar i eliminar
  - Camp `description` auto-generat (p.ex. "Tres de 8 amb folre") per identificar el tipus de canvas
- **Noticies** — tauler d'anuncis intern
- **Configuració** — gestió de la colla, GDPR, permisos per rol

### Portal membre (`/member/{shortname}/`)
- **Calendari** — events visibles amb confirmació d'assistència i respostes a preguntes
- **La meva pinya** — mostra la pinya projectada per la tècnica i destaca la posició del casteller
- **Rondes** — vista de les rondes de l'event més proper, en mode pestanyes o targetes

### API REST per a app mòbil (`/api-fempinya/`)

Endpoints autenticats via Sanctum (token Bearer), consumits per l'app Flutter FP3:

| Endpoint | Descripció |
|---|---|
| `POST /api/auth/login` | Login → retorna token |
| `GET /api-fempinya/mobile_user_context` | Dades del casteller actiu, colla i logo |
| `GET /api-fempinya/mobile_gdpr` | Estat GDPR del casteller |
| `GET /api-fempinya/noticies` | Tauler de notícies |
| `GET /api-fempinya/events` | Events de la colla |
| `GET /api-fempinya/rondes` | Rondes de l'event actiu |

### Pantalles públiques (sense login)
- `/display/{shortname}/{token}` — projector en temps real (auto-refresh 30s)
- `/share/{shortname}/{token}` — snapshot específic compartible
- `/display/{shortname}/rondes/{token}` — rondes publicades d'un event

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
SEEDER_USER_PASSWORD=<contrasenya-membres>
SEEDER_ADMIN_EMAIL=<email>
```

> **Casteller de demo:** el seeder crea `femcastells@artacho.org` (alias `DEMO`) a la colla demo, per a proves de l'app mòbil i revisió Play Store.

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

## Llicència

Actualment en desenvolupament privat. La intenció és publicar-lo sota llicència **AGPL-3.0**, la mateixa que [AssociacioFemPinya/backend](https://github.com/AssociacioFemPinya/backend), per facilitar una eventual integració o cessió a l'organització.

© H. Artacho
