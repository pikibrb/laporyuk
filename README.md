# Lapor Yuk!

Community reporting portal for **environmental problems** and **public-facility concerns** (pollution, waste,
broken roads/lighting, neglected facilities, utilities). It is **not** an emergency channel — emergencies are
redirected to an admin-managed hotline page.

- Reports can be submitted **without an account**, with photo evidence and a map pin.
- Citizens can send **feedback & suggestions** (optionally anonymously) and read officer responses.
- Admins (petugas) review reports/feedback and manage categories and emergency numbers.
- The UI is available in **Indonesian (default) and English** via a session-based switcher.

Detailed agent instructions live in `AGENTS.md`.

---

## Tech stack

| Layer | Stack |
| --- | --- |
| Backend | PHP 8.4, Laravel 13 |
| UI | Livewire 3 + Volt (class-based single-file components), Blade, Alpine.js |
| Styling | Tailwind CSS 3 (`@tailwindcss/forms`), dark mode via class |
| Maps | Leaflet 1.9.4 (unpkg CDN), OpenStreetMap tiles |
| Build | Vite 8 |
| Tests | PHPUnit 12 (in-memory SQLite), Pint for formatting |

---

## Getting started

### One-shot setup

```bash
composer run setup
```

Runs `composer install` → `.env` creation → `key:generate` → `migrate` → `npm install` → `npm run build`.

### Manual setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # if using the default sqlite connection
php artisan migrate
php artisan db:seed              # optional demo data
npm run build                    # or: npm run dev
```

### Development

```bash
composer run dev    # server + queue + pail + vite
```

If a frontend change does not appear, run `npm run build` (or `npm run dev`).

### Tests

```bash
composer run test
php artisan test --compact
php artisan test --filter=ReportSubmissionTest
```

### Default seeded credentials

| Role | Email | Password |
| --- | --- | --- |
| Admin (petugas) | `admin@example.com` | `password` |
| Citizen | `test@example.com` | `password` |

> Change these before deploying.

---

## Roles & permissions

Roles are stored in `users.role`: `admin` or `warga` (default). `User::isAdmin()` is the single check.

| Capability | Guest | Warga | Admin |
| --- | :-: | :-: | :-: |
| Browse map, report detail, hotlines | ✅ | ✅ | ✅ |
| Submit report / feedback | ✅ | ✅ | ❌ (UI-hidden only) |
| Comment and reply on reports | ❌ | ✅ | ✅ |
| Read own feedback + responses (`/masukan-saya`) | ❌ | ✅ (own) | ✅ (own) |
| Dashboard | ❌ | ✅ (own reports) | ✅ (all reports) |
| Update report status | ❌ | ❌ | ✅ |
| Manage feedback, categories, hotlines (`/admin/*`) | ❌ | ❌ | ✅ |

Enforcement: `auth`/`verified` route middleware, the `admin` middleware (`EnsureUserIsAdmin` → 403), and
in-component `guardAdmin()` checks. Admins are only **prevented by the UI** from submitting reports/feedback;
there is no server-side guard on those forms.

---

## Routes

| Method | URI | Name | Notes |
| --- | --- | --- | --- |
| GET | `/` | `home` | Public map |
| GET | `/lapor` | `lapor` | Report submission |
| GET | `/laporan/{report}` | `laporan.show` | Detail (UUID binding) |
| GET | `/laporan/{report}/foto` | `laporan.foto` | Streams evidence photo |
| GET | `/laporan/peta` | `laporan.peta` | JSON markers (ETag/304) |
| GET | `/masukan` | `feedback` | Feedback form |
| GET | `/masukan-saya` | `masukan.saya` | Citizen inbox (auth) |
| GET | `/darurat` | `darurat` | Active hotlines |
| GET | `/bahasa/{locale}` | `locale.switch` | Stores locale in session |
| GET | `/dashboard` | `dashboard` | Auth |
| GET | `/profile` | `profile` | Auth |
| GET | `/admin/kategori` | `kategori.index` | Admin |
| GET | `/admin/masukan` | `masukan.index` | Admin |
| GET | `/admin/nomor-penting` | `hotline.index` | Admin |

Auth routes (Breeze/Volt): `/register`, `/login`, `/forgot-password`, `/reset-password/{token}`, `/verify-email`,
`/confirm-password`, plus `POST /logout`. Framework routes: `/livewire/*`, `/storage/{path}`, `GET /up`.

`Report::getRouteKeyName()` returns `uuid`, so reports are addressed by an unguessable public ID.

---

## Data model

| Table | Purpose / key columns |
| --- | --- |
| `users` | `name`, `email`, `password`, `role` (`warga`/`admin`) |
| `categories` | `name`, unique `slug`; no timestamps |
| `reports` | `uuid` (public ID), nullable `user_id` (anonymous), `category_id`, `title`, `description`, `phone`, `photo_path`, `latitude`, `longitude`, `status` |
| `comments` | `report_id`, `user_id`, `parent_id` (one reply level), `body` |
| `status_histories` | `report_id`, nullable `user_id`, `status`, `notes` — immutable audit trail |
| `feedback` | nullable `user_id`, `type` (`saran`/`kritik`/`apresiasi`/`lainnya`), `name`, `contact`, `message`, `response`, `responded_by`, `responded_at` |
| `hotlines` | `number`, `label`, `description`, `sort_order`, `is_active` |

Report statuses: `pending` (Menunggu) → `processing` (Diproses) → `resolved` (Selesai), or `rejected` (Ditolak).
Any status can be set from any state; every change writes a `status_histories` row. Creating a report writes the
first history entry automatically via a model `created` hook.

Feedback responses are written by admins (`response`, `responded_by`, `responded_at`); feedback itself is
read-only and cannot be edited or deleted.

---

## How it works

- **Thin pages, logic in Volt.** Page blades are layout + heading + `<livewire:… />`; feature logic lives in
  `resources/views/livewire/**` as class-based Volt components.
- **Public map** (`report-map`): Leaflet, max 50 most recent reports, coloured by status, filters for
  search/status/category. After the initial render it polls `GET /laporan/peta` every 30 s with `fetch()` and
  `If-None-Match`; a 304 or an unchanged marker signature means no redraw. Polling pauses while the tab is
  hidden or offline.
- **Report submission** (`report-form`): title, category, description, phone, required photo (max 2 MB,
  preview + remove), and a Leaflet map pin or browser geolocation.
- **Report detail** (`report-detail`): summary, photo, comments with one-level replies (rate limited to
  5 writes / 10 min per user), location mini-map, status history, and an admin-only status form.
- **Dashboard**: role-aware stats (`scope="all"` vs `scope="mine"`), paginated report list (admins see all +
  reporter, citizens see their own), feedback summary card, role-gated quick actions.
- **Feedback**: anonymous-friendly submission; admins reply inline from `/admin/masukan`; citizens track replies
  at `/masukan-saya`.
- **Categories / hotlines**: admin CRUD. Categories with reports cannot be deleted. Inactive hotlines stay in
  admin but are hidden from `/darurat`.
- **Photos** are streamed by `GET /laporan/{uuid}/foto` from the `public` disk, so a `public/storage` symlink is
  not required. `photo_url` returns `null` when the file is missing.
- **Locale**: `SetLocale` middleware applies the `locale` session key on every web request (including Livewire
  updates). Supported locales come from `config('app.supported_locales')` (`id`, `en`); anything else 404s.

---

## Conventions

- PHP 8.4 / Laravel 13 APIs. Constructor property promotion, explicit parameter and return types, curly braces
  always.
- Eloquent config via attributes: `#[Fillable([...])]`, `#[Hidden([...])]`.
- Scaffold with `php artisan make:*` (`--no-interaction`).
- Run `vendor/bin/pint --dirty --format agent` after editing PHP.
- **No comments in source** (PHP/Blade/JS/CSS) — use descriptive names and types instead.
- New user-facing strings need a `lang/en.json` entry (Indonesian key → English value).
- Prefer named routes and `route()`.
- Use factories in tests; `Livewire\Volt\Volt::test(...)` for components.
- Don't add dependencies or new base folders without approval.

---

## Known quirks

| Quirk | Impact |
| --- | --- |
| `User` does not `implement MustVerifyEmail` | `verified` middleware is a no-op; resend-verification UI never renders. |
| Admins are blocked from submitting only in the UI | No server-side guard on report/feedback forms. |
| Locale is session-scoped | Not tied to the account; resets in a new session. |
| `phone` / `photo_path` are nullable in the DB | Legacy and seeded reports may lack them even though validation requires them. |
| `ReportSeeder` runs in `DatabaseSeeder` | Fresh seeds include demo reports; don't run it in production. |
| App layout does not load Figtree | App pages use system sans-serif; only guest pages load the font. |
| Map/JSON endpoint caps at 50 reports | Older reports are reachable from dashboard/detail pages. |
| Replies are one level deep | Replying to a reply attaches to the root comment. |
| Hotline text is stored in Indonesian only | Admin-managed content is not translated by the switcher. |
| Validation translations are partial | Uncommon rules fall back to English. |

### Troubleshooting

| Symptom | Fix |
| --- | --- |
| "Unable to locate file in Vite manifest" | Run `npm run build` (or `npm run dev`). |
| Photos 404 / no preview | File must exist under `storage/app/public/<photo_path>`; no symlink needed. |
| Map not refreshing | Check `data-map-url` and `GET /laporan/peta` in the network tab; polling pauses when hidden. |
| Language switch seems ignored | `locale` lives in the session; a broken `SESSION_DRIVER` resets it to `id`. |
| Blank map | Leaflet CDN blocked, or container re-rendered without `wire:ignore`. |
| `pdo_sqlite` missing | Install/enable the extension or point PHPUnit at MySQL. |

---

## Deployment

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --class=HotlineSeeder   # idempotent emergency numbers
npm ci && npm run build
php artisan config:cache route:cache view:cache
```

- Set a real `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, database credentials, durable
  `SESSION_DRIVER`/`CACHE_STORE`, and real mail/queue drivers.
- Keep `storage/app/public/reports` on persistent/shared storage (or switch to S3 and update
  `Report::photoUrl()` / the `laporan.foto` route).
- Do **not** run `ReportSeeder` in production; rotate the default credentials.
- `GET /up` is available for health checks.
