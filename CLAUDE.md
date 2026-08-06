# CLAUDE.md — femcastells (backend)

FemCastells — Gestió de colles castelleres. Projecte independent de Pepe Martín Artacho,
creat per substituir FP3 (AssociacioFemPinya/backend, Laravel 8). Intenció futura d'oferir-lo
a l'organització.

## Stack

| Capa | Tecnologia |
|---|---|
| Framework | Laravel 13.x (PHP 8.3) |
| Admin panel | Filament 5.x |
| Multi-tenancy | Filament Teams — Colla com a tenant |
| Auth admin | Filament auth (email + password) |
| Auth API | Laravel Sanctum (app Flutter) |
| BD | MySQL — base de dades `fempinya4` |
| Frontend | Blade + Tailwind (Filament) |
| Assets | Vite |
| Permisos | spatie/laravel-permission |

## Entorn local

- Domini: `http://fempinya4.test`
- DB: `fempinya4` (MySQL, root sense password)
- GitHub: `martinartacho/femcastells` (públic)
- Admin panel: `http://fempinya4.test/admin`
- Producció: `https://femcastells.artacho.org`

## Domini del negoci

```
Colla (tenant)
 ├── User (admins de la colla)
 ├── Casteller (membres)
 │    ├── Attendance (event_id, status)
 │    └── BoardPosition (board_event_id, position JSON)
 ├── Tag (tipus: CASTELLERS, EVENTS, POSITIONS, BOARDS)
 ├── Event
 │    ├── Attendance
 │    ├── BoardEvent (pivot board↔event)
 │    └── Ronda
 ├── Board (plantilles de pinya)
 └── Period (períodes temporals)
```

## Regles

- Sempre MySQL, mai SQLite
- `declare(strict_types=1)` a totes les classes noves
- Codi font en anglès, UI en català (ca) o castellà (es) segons context
- Un pas a la vegada — confirmar que funciona abans de continuar
- Sense `Co-Authored-By` als commits
- PRs sempre via web GitHub: `https://github.com/martinartacho/femcastells/compare/master...{rama}`

## Referència FP3

Els repos de FP3 estan clonats a `c:/laragon/www/FemPinya/`:
- `backend/` — Laravel 8 (referència de domini, NO copiar codi)
- `api/` — Laravel 11 (capa API REST)
- `app/` — Flutter (app mòbil, ara a `martinartacho/femcastells-app`)
