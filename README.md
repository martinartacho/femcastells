# FemCastells — Backend

Aplicació de gestió per a colles castelleres. Substitut de [FemPinya 3](https://github.com/AssociacioFemPinya/backend) (Laravel 8), reescrit des de zero amb Laravel 13 i Filament 5.

> Projecte personal de [H. Artacho](https://github.com/martinartacho). Intenció futura d'oferir-lo a l'organització AssociacioFemPinya.

---

## Motivació

FemCastells neix de l'auditoria de l'ecosistema existent (maig 2026):

- **FP3 backend** ([AssociacioFemPinya/backend](https://github.com/AssociacioFemPinya/backend), Laravel 8) és el repo més actiu, en producció, però tecnològicament desfasat. Migrar-lo a Laravel 11+ seria una reescriptura gairebé total.
- L'organització **AssociacioFemPinya** és semi-activa. Contribuir via PR requereix coordinació i cessió del codi (AGPL-3.0).

La decisió: **reescriptura neta** amb stack modern (Laravel 13 + Filament 5), llibertat total de disseny, i opció d'oferir-lo upstream quan estigui madur.

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
- **Pissarra** — editor visual de pinyes (Konva.js): assignació de castellers a posicions, múltiples versions desades, projector per TV/Raspberry Pi, URL pública per snapshot
- **Rondes** — gestió de l'ordre d'actuació per event amb URL pública per als castellers
- **Plantilles de castell** — wizard visual per generar mapes de posicions
- **Noticies** — tauler d'anuncis intern
- **Configuració** — gestió de la colla, GDPR, permisos per rol

### Portal membre (`/member/{shortname}/`)
- **Calendari** — events visibles amb confirmació d'assistència
- **La meva pinya** — mostra la posició del casteller a la pinya projectada
- **Rondes** — vista de les rondes de l'event més proper

### API REST per a app mòbil (`/api-fempinya/`)

Endpoints autenticats via Sanctum, consumits per [l'app FemCastells](https://github.com/martinartacho/femcastells-app):

| Endpoint | Descripció |
|---|---|
| `POST /api/auth/login` | Login → retorna token |
| `POST /api/forgot-password` | Envia codi de reset per email |
| `POST /api/reset-password` | Verifica codi i actualitza contrasenya |
| `POST /api/demo/register` | Accés demo sense registre |
| `GET /api-fempinya/mobile_home` | Home: notícies i events |
| `GET /api-fempinya/mobile_events` | Events de la colla |
| `GET /api-fempinya/mobile_rondas` | Rondes de l'event actiu |

### Pantalles públiques (sense login)
- `/display/{shortname}/{token}` — projector en temps real (auto-refresh 30s)
- `/share/{shortname}/{token}` — snapshot específic compartible
- `/display/{shortname}/rondes/{token}` — rondes publicades d'un event

---

## Instal·lació en local

### Requisits
- PHP 8.3+, Composer, Node.js 20+, MySQL

### Passos

```bash
git clone git@github.com:martinartacho/femcastells.git
cd femcastells

composer install && npm install

cp .env.example .env
php artisan key:generate
```

Edita `.env`:
```
APP_URL=http://fempinya4.test
DB_DATABASE=fempinya4
SEEDER_ADMIN_PASSWORD=<contrasenya-segura>
SEEDER_USER_PASSWORD=<contrasenya-membres>
SEEDER_ADMIN_EMAIL=<email>
```

```bash
php artisan migrate --seed
npm run build
```

Accés: `http://fempinya4.test/admin`

---

## Repos relacionats

| Repo | Descripció |
|---|---|
| [martinartacho/femcastells](https://github.com/martinartacho/femcastells) | Aquest repo — backend Laravel |
| [martinartacho/femcastells-app](https://github.com/martinartacho/femcastells-app) | App mòbil Flutter |

---

## Llicència

La intenció és publicar-lo sota llicència **AGPL-3.0**, la mateixa que [AssociacioFemPinya/backend](https://github.com/AssociacioFemPinya/backend).

© H. Artacho
