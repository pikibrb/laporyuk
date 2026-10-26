# Lapor Yuk! — Technical Documentation

> **Lapor Yuk!** is a community reporting portal for **environmental problems** and **public-facility concerns**
> (pollution, waste, broken roads/lighting, neglected public facilities, utilities, etc.).
> It is **not** an emergency channel — fires, accidents and medical emergencies are redirected to an
> admin-managed "important phone numbers" page. Reports can be submitted **without an account**, and citizens can
> also send **feedback & suggestions** (optional anonymity) and read the officer responses from their dashboard.
> Admins (petugas) review reports and feedback from a dashboard, and manage categories and emergency numbers.
> The whole portal is available in **Indonesian and English** via a session-based language switcher.

This document is the deep-dive reference for the codebase: architecture, data model, every route, every component,
validation rules, the feature set, the test suite, conventions and known quirks.

**Companion documents**

| File | Purpose |
| --- | --- |
| `README.md` | **This file** — full application documentation. |
| `AGENTS.md` | Instructions for AI coding agents working in this repo (Laravel Boost rules, project conventions). |

---

## Table of contents

1. [Product overview](#1-product-overview)
2. [Tech stack](#2-tech-stack)
3. [Getting started](#3-getting-started)
4. [High-level architecture](#4-high-level-architecture)
5. [Directory structure](#5-directory-structure)
6. [Roles & permissions](#6-roles--permissions)
7. [Routing reference](#7-routing-reference)
8. [Database schema](#8-database-schema)
9. [Domain models](#9-domain-models)
10. [Features in depth](#10-features-in-depth)
11. [Livewire / Volt component reference](#11-livewire--volt-component-reference)
12. [Blade component reference](#12-blade-component-reference)
13. [Validation rules reference](#13-validation-rules-reference)
14. [Frontend, theming & assets](#14-frontend-theming--assets)
15. [Authentication & profile](#15-authentication--profile)
16. [Notifications & confirmation dialogs](#16-notifications--confirmation-dialogs)
17. [Seeders & factories](#17-seeders--factories)
18. [Testing](#18-testing)
19. [Conventions & code style](#19-conventions--code-style)
20. [Known constraints, quirks & troubleshooting](#20-known-constraints-quirks--troubleshooting)
21. [Deployment notes](#21-deployment-notes)

---

## 1. Product overview

### 1.1 Purpose

A public-facing, map-centric portal where citizens report non-emergency civic/environmental problems and staff
track them to resolution.

### 1.2 Core concepts

| Concept | Description |
| --- | --- |
| **Laporan (Report)** | A geo-tagged citizen report with a title, description, category, mandatory contact phone and mandatory photo evidence. Flows through a status lifecycle. |
| **Kategori (Category)** | Admin-managed taxonomy used to group reports (e.g. "Pencemaran Sungai & Air"). |
| **Masukan (Feedback)** | Free-form feedback/suggestion/complaint/appreciation. Supports **anonymous** submissions and an **officer response** (`response`, `responded_by`, `responded_at`). |
| **Tanggapan (Comment)** | Threaded public comment on a report (requires login). Every comment can receive **one level of replies** (`comments.parent_id`). |
| **Riwayat Status (Status History)** | Immutable audit trail of every status change, with optional staff notes. |
| **Nomor Penting (Hotlines)** | Emergency/service phone numbers stored in the `hotlines` table and maintained by admins. |
| **Petugas / Admin** | User with `role = admin`; reviews reports and feedback, manages categories and emergency numbers. |
| **Warga (Citizen)** | Regular user (`role = warga`); can report, comment/reply, and track their own reports plus the responses to their feedback. |
| **Tamu (Guest)** | Unauthenticated visitor; can browse, submit reports and submit feedback anonymously. |

### 1.3 Report status lifecycle

```
         ┌─────────┐   admin updates    ┌────────────┐   admin updates   ┌──────────┐
 new ──► │ pending │ ─────────────────► │ processing │ ────────────────► │ resolved │
         │Menunggu │                    │  Diproses  │                   │ Selesai  │
         └─────────┘                    └────────────┘                   └──────────┘
              │                                │                               ▲
              │                                └─────────── admin ────────────┘
              │
              └──────────────────────────► ┌──────────┐
                        admin updates     │ rejected │ (Ditolak)
                                          └──────────┘
```

* Every status change is allowed from any state (no transition machine) — the admin picks any value from
  `pending | processing | resolved | rejected`.
* Each change writes a row to `status_histories` (audit trail).
* Creating a report automatically writes the first `status_histories` row ("Laporan diterima dan menunggu
  verifikasi petugas.") via a model `created` hook.

### 1.4 Scope boundaries (product decisions)

* The portal is **explicitly not** an emergency service. `/darurat` lists the active rows of the `hotlines`
  table (seeded with 112, 110, 113, 119, 115 and 123) and calls out that the portal is for non-emergency reports only.
* Emergency-only concepts were removed: there is no `/sos` route and the `urgency_level` column was dropped from
  `categories`.
* Admins **cannot** submit reports or feedback through the UI: report/feedback submission actions are hidden from
  admin navigation and dashboard; admins instead see "Masukan Warga", "Kelola Kategori" and "Kelola Nomor Penting".
* Citizens cannot read other people's feedback: `/masukan-saya` is scoped to `feedback.user_id = auth()->id()`.
* Anonymous feedback is still accepted, but it cannot appear in `/masukan-saya` because there is no account to
  attach it to (the page states this explicitly).
* Language switching is **session-based** (`locale` key) with Indonesian as the default and English as the
  alternative; it is not persisted per user account.

---

## 2. Tech stack

### 2.1 Runtime & direct PHP dependencies

| Package | Constraint (`composer.json`) | Installed |
| --- | --- | --- |
| PHP | `^8.3` | 8.4.26 |
| `laravel/framework` | `^13.17` | 13.36.0 |
| `livewire/livewire` | `^3.6.4` | 3.8.10 |
| `livewire/volt` | `^1.7.0` | 1.11.2 |
| `laravel/breeze` | `^2.4` | 2.4.2 |
| `laravel/tinker` | `^3.0` | 3.0.2 |

**Dev dependencies:** `phpunit/phpunit ^12.5` (12.5.39), `laravel/pint ^1.27` (1.32.1), `mockery/mockery`,
`nunomaduro/collision`, `fakerphp/faker`, `laravel/boost`, `laravel/pail`, `laravel/pao`.

### 2.2 Frontend

| Tool | Version / note |
| --- | --- |
| Vite | `^8.0` via `laravel-vite-plugin ^3.1` |
| Tailwind CSS | `^3.1` (v3 config in `tailwind.config.js`) with `@tailwindcss/forms` |
| Alpine.js | Bundled with Livewire 3 (no separate npm package) |
| Leaflet | 1.9.4, loaded from unpkg CDN in `layouts/app.blade.php` (CSS + JS) |
| Fonts | Figtree via `fonts.bunny.net` (loaded in the **guest** layout only — see [quirks](#20-known-constraints-quirks--troubleshooting)) |
| JS entry | `resources/js/app.js` — intentionally empty; Alpine/Livewire ship from the Livewire package |

> **Note:** SweetAlert2 was previously loaded from a CDN but never used; it was removed. Toast notifications are
> implemented natively (Alpine + Tailwind) — see [§16](#16-notifications--confirmation-dialogs).

### 2.3 Application config defaults (`.env.example`)

| Key | Value | Meaning |
| --- | --- | --- |
| `APP_NAME` | `Lapor Yuk!` | Displayed in `<title>`. |
| `APP_URL` | `http://localhost:8000` | Used by `route()`/`url()` when building absolute links. |
| `APP_LOCALE` | `id` | Indonesian is the default UI language (source strings are Indonesian). |
| `APP_FALLBACK_LOCALE` | `en` | Fallback when a translation key is missing. |
| `app.supported_locales` | `id`, `en` | Switchable locales (`config/app.php`); anything else returns 404. |
| `DB_CONNECTION` | `sqlite` | Dev default; tests use in-memory sqlite. |
| `SESSION_DRIVER` | `database` | Sessions persisted in the `sessions` table. |
| `CACHE_STORE` | `database` | Cache in the `cache` table. |
| `QUEUE_CONNECTION` | `database` | Jobs in the `jobs` table (no jobs defined yet). |
| `FILESYSTEM_DISK` | `local` | Default disk; report photos use the explicit `public` disk. |
| `MAIL_MAILER` | `log` | Emails are written to logs (no real sending in dev). |
| `APP_TIMEZONE` | `UTC` (config default) | Timestamps rendered as-is. |

---

## 3. Getting started

### 3.1 One-shot setup (Composer script)

```bash
composer run setup
```

This runs, in order: `composer install` → copy `.env.example` to `.env` if missing → `php artisan key:generate`
→ `php artisan migrate --force` → `npm install --ignore-scripts` → `npm run build`.

### 3.2 Manual setup

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (sqlite by default)
touch database/database.sqlite          # only if DB_DATABASE points at a file
php artisan migrate

# 4. Public storage symlink (optional)
php artisan storage:link                # not needed for report photos (they are streamed by the app)

# 5. Seed demo data (optional)
php artisan db:seed                     # DatabaseSeeder: users, admin, categories, hotlines, demo reports
php artisan db:seed --class=HotlineSeeder  # only refresh the emergency numbers
php artisan db:seed --class=ReportSeeder   # only the demo reports

# 6. Frontend
npm run build        # production build
# or: npm run dev    # Vite dev server with HMR
```

### 3.3 Development

```bash
composer run dev     # php artisan dev (runs server, queue, pail, vite concurrently on Laravel 13)
```

`AGENTS.md` also notes: if a frontend change does not appear, run `npm run build` (or `npm run dev` / `composer run dev`).

### 3.4 Tests

```bash
composer run test            # config:clear + php artisan test
php artisan test --compact   # compact output
vendor/bin/phpunit --no-coverage   # direct runner
```

### 3.5 Default credentials (after seeding)

| Role | Email | Password |
| --- | --- | --- |
| Admin (petugas) | `admin@example.com` | `password` |
| Citizen | `test@example.com` | `password` |

> ⚠️ Change these before any real deployment.

---

## 4. High-level architecture

### 4.1 Request lifecycle

```
Browser
  │
  ├─ GET / , /lapor , /masukan , /darurat , /masukan-saya     → plain Blade view wrappers
  │  GET /laporan/{uuid} , /laporan/{uuid}/foto                (routes/web.php → Route::view / closure)
  │
  ├─ GET /laporan/peta?search=…&status=…&category=…           → JSON marker payload (ETag aware)
  ├─ GET /bahasa/{locale}                                     → stores locale in the session, redirects back
  │
  ├─ Livewire update request (POST /livewire/update)           → component method runs, then re-renders
  │
  └─ Any page renders <x-app-layout> (or layouts.guest for auth)
        └─ sidebar / bottom-nav / toast-stack (Livewire + Blade components)
        └─ page body renders <livewire:... /> Volt components
```

Key architectural choices:

* **Pages are thin wrappers.** Every page blade under `resources/views/*.blade.php` is just a layout + heading +
  one or more `<livewire:… />` components. All logic lives in Volt single-file components.
* **Volt class-based components.** Components live in `resources/views/livewire/**` and use the Volt **class API**
  (`new class extends Component { … }`) consistent with the Breeze starter kit. There is no separate `app/Livewire`
  component directory (except `Actions/Logout` and `Forms/LoginForm` helpers).
* **Server-rendered reactivity.** Livewire handles form state, validation, filters and polling. Alpine is used
  only for client-side interactions (theme toggle, modal, toasts, password visibility, Leaflet wiring).
* **One read-only JSON endpoint.** `GET /laporan/peta` returns the map markers so the Leaflet map can refresh
  without a Livewire round-trip. It supports `ETag`/`If-None-Match` and is polled only while the tab is visible.
  There is no broader API layer (Laravel's `shouldRenderJsonWhen` is configured for `api/*`/`expectsJson`).
* **Locale middleware.** `App\Http\Middleware\SetLocale` is appended to the `web` group and applies the
  `locale` session key on every request, including Livewire updates.

### 4.2 Where logic lives

| Concern | Location |
| --- | --- |
| HTTP routing | `routes/web.php`, `routes/auth.php` |
| Admin authorization middleware | `app/Http/Middleware/EnsureUserIsAdmin.php` (alias `admin`) |
| Domain rules (scopes, accessors, hooks) | `app/Models/*.php` |
| Locale resolution | `app/Http/Middleware/SetLocale.php` + `lang/*` |
| Feature logic (forms, filters, actions) | `resources/views/livewire/**/*.blade.php` (Volt) |
| Reusable UI | `resources/views/components/*.blade.php` |
| Global layout/chrome | `resources/views/layouts/app.blade.php`, `layouts/guest.blade.php` |
| Styling utilities | `resources/css/app.css` (Tailwind layers + Leaflet dark-mode tweaks) |

---

## 5. Directory structure

```
laporyuk/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/VerifyEmailController.php   # marks email verified (signed link)
│   │   │   └── Controller.php                   # empty base controller
│   │   ├── Middleware/EnsureUserIsAdmin.php     # alias: admin
│   │   └── Middleware/SetLocale.php             # applies session locale on every web request
│   ├── Livewire/
│   │   ├── Actions/Logout.php                   # invokable logout action
│   │   └── Forms/LoginForm.php                  # login form object + rate limiting
│   ├── Models/{User,Category,Report,Comment,StatusHistory,Feedback,Hotline}.php
│   ├── Providers/{AppServiceProvider,VoltServiceProvider}.php
│   └── View/Components/AppLayout.php            # <x-app-layout> → layouts.app
├── bootstrap/{app.php,providers.php}
├── config/                                      # standard Laravel config
├── database/
│   ├── factories/{User,Category,Report,Feedback,Hotline}Factory.php
│   ├── migrations/                              # see §8.3
│   └── seeders/{DatabaseSeeder,admin,CategorySeeder,HotlineSeeder,ReportSeeder}.php
├── lang/{en.json,id.json,id/{auth,passwords,pagination,validation}.php}
├── resources/
│   ├── css/app.css
│   ├── js/app.js
│   └── views/
│       ├── components/                          # Blade UI components (§12)
│       ├── layouts/{app,guest}.blade.php
│       ├── livewire/                            # Volt components (§11)
│       │   ├── pages/auth/*.blade.php
│       │   └── profile/*.blade.php
│       ├── welcome.blade.php                    # home / map
│       ├── lapor.blade.php                      # report submission page
│       ├── laporan-detail.blade.php             # report detail page
│       ├── feedback.blade.php                   # feedback submission page
│       ├── masukan.blade.php                    # admin feedback review page
│       ├── masukan-saya.blade.php               # citizen feedback inbox + officer responses
│       ├── kategori.blade.php                   # admin category management page
│       ├── hotline.blade.php                    # admin emergency-number management page
│       ├── dashboard.blade.php                  # dashboard
│       └── darurat.blade.php                    # hotlines
├── routes/{web.php,auth.php,console.php}
├── tests/
│   ├── Feature/{Auth/*, …}
│   └── Unit/
├── tailwind.config.js
├── vite.config.js
└── phpunit.xml
```

---

## 6. Roles & permissions

There are two roles stored in `users.role`: `admin` and `warga` (default). Logic is centralised in
`User::isAdmin()` (`$this->role === 'admin'`).

### 6.1 Capability matrix

| Capability | Guest | Warga | Admin |
| --- | :-: | :-: | :-: |
| Browse the public map (`/`) | ✅ | ✅ | ✅ |
| View report detail (`/laporan/{id}`) | ✅ | ✅ | ✅ |
| Submit a report (`/lapor`) | ✅ | ✅ | ❌ (hidden, not blocked server-side) |
| Submit feedback (`/masukan`) | ✅ | ✅ | ❌ (hidden, not blocked server-side) |
| Comment on a report | ❌ (login prompt) | ✅ | ✅ |
| Reply to a report comment | ❌ (login prompt) | ✅ | ✅ |
| View hotlines (`/darurat`) | ✅ | ✅ | ✅ |
| Read own feedback + officer responses (`/masukan-saya`) | ❌ → login | ✅ (own only) | ✅ (own only) |
| Dashboard (`/dashboard`) | ❌ → login | ✅ | ✅ |
| See **all** reports in dashboard list | – | ❌ (own only) | ✅ |
| Update report status | – | ❌ (`addError` + error toast) | ✅ |
| See reporter name + contact phone on detail | – | – | ✅ |
| Review + respond to feedback (`/admin/masukan`) | ❌ → login | ❌ 403 | ✅ |
| Manage categories (`/admin/kategori`) | ❌ → login | ❌ 403 | ✅ |
| Manage emergency numbers (`/admin/nomor-penting`) | ❌ → login | ❌ 403 | ✅ |

### 6.2 Enforcement layers

1. **Route middleware** — `/dashboard`, `/profile` and `/masukan-saya` require `auth` + `verified`; the whole
   `/admin/*` groups (`kategori`, `masukan`, `hotline`) require `auth`, `verified` and `admin`.
2. **`admin` middleware** (`EnsureUserIsAdmin`) — `abort_unless($request->user()?->isAdmin(), 403)`.
3. **In-component guards** — defense in depth:
   * `feedback-admin::with()` → `abort_unless(Auth::user()?->isAdmin(), 403)`; every mutating method calls
     `guardAdmin()`.
   * `kategori-admin::{create,update,delete}()` → private `guardAdmin()` → `abort_unless(...)`.
   * `hotline-admin::{save,toggleActive,confirmDelete,deleteHotline}()` → private `guardAdmin()`.
   * `report-detail::updateStatus()` → adds a validation error + error toast for non-admins.
   * `feedback-history` is scoped by `where('user_id', Auth::id())`, so it can never list another user's feedback.

> **Note:** admins are only *prevented by UI* from submitting reports/feedback — there is no server-side guard on
> `ReportForm` / `FeedbackForm`. This is a deliberate product decision (menu removal), not a security boundary.

---

## 7. Routing reference

All routes are defined in `routes/web.php` and `routes/auth.php` (`routes/auth.php` is `require`d at the bottom of
`web.php`). Full output of `php artisan route:list` (application routes; vendor routes omitted):

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET | `/` | `home` | `view:welcome` | web |
| GET | `/darurat` | `darurat` | closure → `view:darurat` with `Hotline::active()->ordered()` | web |
| GET | `/lapor` | `lapor` | `view:lapor` | web |
| GET | `/masukan` | `feedback` | `view:feedback` | web |
| GET | `/bahasa/{locale}` | `locale.switch` | closure → validates locale, stores it in the session, redirects to the previous local path | web |
| GET | `/laporan/peta` | `laporan.peta` | closure → JSON markers (`ETag`/304 aware) | web |
| GET | `/laporan/{report}` | `laporan.show` | closure → `view:laporan-detail` (UUID route-model binding) | web |
| GET | `/laporan/{report}/foto` | `laporan.foto` | closure → streams the evidence file from the `public` disk | web |
| POST | `/logout` | `logout` | closure → `Logout` action | web, **auth** |
| GET | `/dashboard` | `dashboard` | `view:dashboard` | web, auth, verified |
| GET | `/profile` | `profile` | `view:profile` | web, auth, verified |
| GET | `/masukan-saya` | `masukan.saya` | `view:masukan-saya` | web, auth, verified |
| GET | `/admin/kategori` | `kategori.index` | `view:kategori` | web, auth, verified, **admin** |
| GET | `/admin/masukan` | `masukan.index` | `view:masukan` | web, auth, verified, **admin** |
| GET | `/admin/nomor-penting` | `hotline.index` | `view:hotline` | web, auth, verified, **admin** |

Auth routes (`routes/auth.php`, provided by Breeze/Volt):

| Method | URI | Name | Guard group |
| --- | --- | --- | --- |
| GET | `/register` | `register` | guest |
| GET | `/login` | `login` | guest |
| GET | `/forgot-password` | `password.request` | guest |
| GET | `/reset-password/{token}` | `password.reset` | guest |
| GET | `/verify-email` | `verification.notice` | auth |
| GET | `/verify-email/{id}/{hash}` | `verification.verify` | auth, signed, `throttle:6,1` |
| GET | `/confirm-password` | `password.confirm` | auth |

Framework/package routes also registered: `POST /livewire/update`, `GET /livewire/livewire.js`,
`GET /livewire/preview-file/{filename}`, `POST /livewire/upload-file` (Livewire), `GET|PUT /storage/{path}`
(local storage serving), `GET /up` (health check), `POST /_boost/browser-logs` (Laravel Boost, dev only).

> **Route model binding:** `Report::getRouteKeyName()` returns `uuid`, so both `laporan.show` and `laporan.foto`
> bind by the unguessable public UUID instead of the sequential primary key. The numeric id returns 404.
>
> **Map data endpoint:** `laporan.peta` accepts `search`, `status` and `category`, validates them (unknown status or
> non-numeric category → 422), loads at most 50 reports and returns `{markers: [...], total: n}`. The response carries
> a content-derived `ETag` and answers `If-None-Match` with **304 Not Modified**, so idle polling costs no payload.

---

## 8. Database schema

### 8.1 Entity–relationship diagram

```
┌──────────┐            ┌────────────┐            ┌───────────┐
│  users   │1          *│  reports   │*          1│ categories│
│          ├───────────►│            │◄───────────┤           │
│ id (PK)  │            │ id (PK)    │            │ id (PK)   │
│ name     │            │ user_id FK │            │ name      │
│ email    │            │ category_id│            │ slug      │
│ role     │            │ ...        │            └───────────┘
└────┬─────┘            └─────┬──────┘
     │ 1                      │ 1
     │                        ├──────────────────►┌──────────────────┐
     │                        │ *                 │     comments     │
     │                        │                   │ id, report_id FK │
     │                        │                   │ user_id FK, body │
     │                        │                   └──────────────────┘
     │                        └──────────────────►┌──────────────────┐
     │ *                                          │ status_histories │
     └───────────────────────────────────────────►│ id, report_id FK │
                                                  │ user_id FK       │
                                                  │ status, notes    │
                                                  └──────────────────┘
     │ 1
     └──────────────►┌───────────────┐
                  *  │ feedback      │  (user_id nullable → SET NULL on user delete)
                     │ id            │
                     │ type          │
                     │ name          │
                     │ contact       │
                     │ message       │
                     │ response      │  (officer reply)
                     │ responded_by  │  → users.id, nullable
                     │ responded_at  │
                     └───────────────┘

┌────────────┐
│  hotlines  │  standalone admin-managed reference table
│ id         │
│ number     │
│ label      │
│ description│
│ sort_order │
│ is_active  │
└────────────┘
```

> `comments.parent_id` is a self-referencing FK, so a comment can receive replies. The application enforces a
> single reply level (a reply to a reply is stored under the same root comment).

### 8.2 Tables

#### `users`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `name` | string | |
| `email` | string unique | |
| `email_verified_at` | timestamp nullable | |
| `password` | string | hashed (`password` cast) |
| `role` | string default `warga` | `warga` \| `admin` |
| `remember_token` | string nullable | |
| `created_at`/`updated_at` | timestamps | |

#### `categories`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `name` | string | |
| `slug` | string unique | generated via `Str::slug()` + numeric suffix if taken |

> `categories` has **no timestamps** (`Category::$timestamps = false`).
> Historical: `urgency_level` enum was added then dropped (see migrations below).

#### `reports`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `user_id` | FK → `users.id`, **nullable**, cascade delete | null ⇒ anonymous report |
| `category_id` | FK → `categories.id`, restrict on delete | |
| `uuid` | uuid unique | public identifier used by every route (`getRouteKeyName()`) |
| `title` | string | |
| `phone` | string nullable | legacy rows may be null; **required by validation** |
| `description` | text | |
| `photo_path` | string nullable | legacy rows may be null; **required by validation** |
| `latitude` | decimal(10,8) | cast to `float` |
| `longitude` | decimal(11,8) | cast to `float` |
| `status` | enum `pending,processing,resolved,rejected` default `pending` | |
| `created_at`/`updated_at` | timestamps | |

Indexes: `user_id`, `category_id` (from FK constraints), `uuid` (unique). No dedicated index on
`status`/`created_at`.

#### `comments`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `report_id` | FK → `reports.id`, cascade delete | |
| `user_id` | FK → `users.id`, cascade delete | |
| `parent_id` | FK → `comments.id`, nullable, cascade delete | `null` ⇒ root comment; set ⇒ reply |
| `body` | text | |
| timestamps | | |

Index: `(report_id, parent_id)` for rendering a thread in one query.

#### `status_histories`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `report_id` | FK → `reports.id`, cascade delete | |
| `user_id` | FK → `users.id`, **nullable**, cascade delete | author of the change (null for system/first entry) |
| `status` | enum `pending,processing,resolved,rejected` | |
| `notes` | text nullable | |
| timestamps | | |

#### `feedback`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `user_id` | FK → `users.id`, **nullable**, `nullOnDelete` | null ⇒ anonymous |
| `type` | string default `saran` | `saran` \| `kritik` \| `apresiasi` \| `lainnya` |
| `name` | string nullable | optional display name |
| `contact` | string nullable | optional email/phone |
| `message` | text | 10–2000 chars (validated) |
| `response` | text nullable | officer reply (max 2000 by validation) |
| `responded_by` | FK → `users.id`, nullable, `nullOnDelete` | admin who wrote the response |
| `responded_at` | timestamp nullable | set together with `response`; used for the "answered" badge |
| timestamps | | |

> The model class `App\Models\Feedback` maps to the table `feedback` (the word "feedback" is uncountable, so
> Laravel's pluralizer leaves it unchanged). The table is named `feedback`, **not** `feedbacks`.

#### `hotlines`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `number` | string(30) | displayed and converted to a `tel:` link (`dialNumber()`) |
| `label` | string(120) | service name |
| `description` | text nullable | when to call |
| `sort_order` | unsigned int default 0 | lower numbers first |
| `is_active` | boolean default true | inactive rows stay in the admin list but are hidden publicly |
| timestamps | | |

Index: `(is_active, sort_order)` for the public listing query.

#### Infrastructure tables
`password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` — standard
Laravel scaffolding. `sessions` and `cache` are actively used because `SESSION_DRIVER=database` and
`CACHE_STORE=database`.

### 8.3 Migrations (in order)

| Migration | Purpose |
| --- | --- |
| `0001_01_01_000000_create_users_table` | `users` (+`role`), `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |
| `2026_10_14_133202_add_categories_tables` | `categories` |
| `2026_10_14_133222_add_reports_tables` | `reports` |
| `2026_10_14_133232_add_comments_tables` | `comments` |
| `2026_10_14_133255_add_status_histories_tables` | `status_histories` |
| `2026_10_14_150233_add_urgency_level_to_categories_table` | added `categories.urgency_level` |
| `2026_10_14_153627_make_user_id_nullable_on_reports_table` | allow anonymous reports |
| `2026_10_18_090000_make_user_id_nullable_on_status_histories_table` | allow system-authored status entries |
| `2026_10_18_100000_drop_urgency_level_from_categories_table` | **removed** urgency (concept revision) |
| `2026_10_18_110000_add_phone_to_reports_table` | added `reports.phone` (nullable) |
| `2026_10_18_120000_create_feedback_table` | created `feedback` |
| `2026_10_26_100000_add_uuid_to_reports_table` | added the public `reports.uuid` |
| `2026_10_26_100100_add_response_to_feedback_table` | added `response`, `responded_by`, `responded_at` |
| `2026_10_26_192030_add_parent_id_to_comments_table` | self-referencing `comments.parent_id` + `(report_id, parent_id)` index |
| `2026_10_26_192031_create_hotlines_table` | created `hotlines` (admin-managed emergency numbers) |

> `make_user_id_nullable` migrations use `->change()`, which requires Doctrine DBAL on older Laravel versions;
> Laravel 11+ supports it natively. On MySQL these are fine; the PHPUnit config still targets in-memory sqlite
> (see [§18.2](#182-coverage-map-test-files--focus) for the verification workaround used when `pdo_sqlite` is absent).

---

## 9. Domain models

### 9.1 `App\Models\User`

```php
#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable   // Illuminate\Foundation\Auth\User
{
    use HasFactory, Notifiable;

    protected function casts(): array { return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ]; }

    public function isAdmin(): bool;               // role === 'admin'
    public function reports(): HasMany;
    public function comments(): HasMany;
    public function statusHistories(): HasMany;
}
```

* Uses PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) instead of `$fillable`/`$hidden` properties.
* **Does not implement** `Illuminate\Contracts\Auth\MustVerifyEmail`, although the framework base class uses the
  `MustVerifyEmail` *trait*. Consequence: `$user instanceof MustVerifyEmail` is `false`, so the `verified`
  middleware is effectively a **no-op** and the "resend verification" UI never renders (see [§20](#20-known-constraints-quirks--troubleshooting)).

### 9.2 `App\Models\Category`

```php
#[Fillable(['name', 'slug'])]
class Category extends Model
{
    use HasFactory;
    public $timestamps = false;
    public function reports(): HasMany;
}
```

### 9.3 `App\Models\Report`

The richest model.

```php
#[Fillable(['user_id','category_id','title','description','phone','photo_path','latitude','longitude','status'])]
class Report extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_RESOLVED   = 'resolved';
    public const STATUS_REJECTED   = 'rejected';

    /** @var array<string,string> */
    public const STATUS_LABELS = [
        'pending'    => 'Menunggu',
        'processing' => 'Diproses',
        'resolved'   => 'Selesai',
        'rejected'   => 'Ditolak',
    ];

    protected function casts(): array;         // latitude, longitude → float
    protected static function booted(): void;  // on created → write first status history
    public function user(): BelongsTo;         // nullable (anonymous)
    public function category(): BelongsTo;
    public function comments(): HasMany;
    public function statusHistories(): HasMany;

    public function scopeFilter(Builder $q, array $filters): Builder; // search/status/category
    public function scopeOpen(Builder $q): Builder; // pending + processing
    protected function statusLabel(): Attribute;  // $report->status_label (translated)
    protected function photoUrl(): Attribute;     // $report->photo_url or null when the file is missing

    public function markerColor(): string;   // hex colour per status
    public function createdAgo(): string;    // "Baru saja" / "n menit lalu" / "n jam lalu" / "Kemarin" / "d M Y"
    public function reference(): string;     // first 8 chars of the uuid, uppercased
    public function toMapMarker(): array;    // serialised marker payload for Leaflet + /laporan/peta
}
```

**`photo_url` — streamed evidence photos**

Evidence images are served by the application, not by a public symlink:

```php
protected function photoUrl(): Attribute
{
    return Attribute::get(function (): ?string {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->exists($this->photo_path)
            ? route('laporan.foto', $this)
            : null;
    });
}
```

`GET /laporan/{uuid}/foto` re-checks `photo_path` and file existence (404 otherwise) and returns the file with its
content type. Because `photo_url` is `null` for a missing file, the detail page simply omits the preview instead of
rendering a broken image. A `public/storage` symlink is therefore **not** required for report photos.

**`booted()` — automatic first status entry**

```php
static::created(function (Report $report): void {
    $report->statusHistories()->create([
        'user_id' => $report->user_id,   // null for anonymous submissions
        'status'  => $report->status,
        'notes'   => 'Laporan diterima dan menunggu verifikasi petugas.',
    ]);
});
```

**`scopeFilter()`** — used by the public map:

```php
$query->when($filters['status'],   fn ($q, $v) => $q->where('status', $v))
      ->when($filters['category'], fn ($q, $v) => $q->where('category_id', $v))
      ->when($filters['search'],   function ($q) use ($search) {
          $q->where(function ($q) use ($search) {
              $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
          });
      });
```

**`toMapMarker()` shape** (consumed by the Leaflet `@script` in `report-map` and by `GET /laporan/peta`):

```php
[
  'title' => string, 'category' => string, 'status' => string,
  'status_label' => string (translated), 'latitude' => float, 'longitude' => float,
  'color' => string, 'url' => string (route laporan.show), 'created_ago' => string (translated),
]
```

### 9.4 `App\Models\Comment`

```php
#[Fillable(['report_id','user_id','parent_id','body'])]
class Comment extends Model
{
    public function report(): BelongsTo;
    public function user(): BelongsTo;
    public function parent(): BelongsTo;      // self, via parent_id
    public function replies(): HasMany;       // self, ordered oldest-first
    public function isReply(): bool;
}
```

### 9.5 `App\Models\StatusHistory`

`#[Fillable(['report_id','user_id','status','notes'])]`, `report()` and `user()` BelongsTo relations.
`user_id` is nullable so the automatic first entry (anonymous report) can be attributed to "Sistem".

### 9.6 `App\Models\Feedback`

```php
#[Fillable(['user_id','type','name','contact','message','response','responded_by','responded_at'])]
class Feedback extends Model
{
    use HasFactory;

    public const TYPE_SUGGESTION   = 'saran';
    public const TYPE_COMPLAINT    = 'kritik';
    public const TYPE_APPRECIATION = 'apresiasi';
    public const TYPE_OTHER        = 'lainnya';

    public const TYPE_LABELS = [
        'saran' => 'Saran', 'kritik' => 'Kritik',
        'apresiasi' => 'Apresiasi', 'lainnya' => 'Lainnya',
    ];

    protected function casts(): array;            // responded_at → datetime
    public function user(): BelongsTo;
    public function respondedBy(): BelongsTo;     // users.id via responded_by
    public function isResponded(): bool;          // responded_at !== null
    protected function typeLabel(): Attribute;    // translated type name
}
```

### 9.7 `App\Models\Hotline`

```php
#[Fillable(['number','label','description','sort_order','is_active'])]
class Hotline extends Model
{
    use HasFactory;

    protected function casts(): array;      // sort_order → int, is_active → bool
    public function scopeActive(Builder $q): Builder;
    public function scopeOrdered(Builder $q): Builder;   // sort_order, then label
    public function dialNumber(): string;                // strips spaces/punctuation for tel: links
}
```

---

## 10. Features in depth

### 10.1 Public report map — `/` (`welcome.blade.php` + `report-map` + `report-stats`)

* **Layout:** `x-app-layout` with a header, CTA buttons, `<livewire:report-stats />`, `<livewire:report-map />`
  and three informational cards.
* **Role-aware header CTAs:**
  * Guests/citizens: "Buat Laporan" (`route('lapor')`) and "Masukan" (`route('feedback')`).
  * Admins: "Masukan Warga" (`route('masukan.index')`).
  * Everyone: "Nomor Penting" (`route('darurat')`).
* **Map (Leaflet):**
  * Centered on `[3.5952, 98.6722]` (Medan) at zoom 13.
  * Renders up to **50** most recent reports (filtered) as `circleMarker`s coloured by status.
  * First render auto-fits bounds to the markers (`maxZoom: 14`); subsequent updates do not re-fit.
  * Popups are built with DOM APIs (no HTML injection) showing title (link to detail), category, status and age.
  * A "Lokasi saya" button uses the browser Geolocation API to drop a blue user marker.
  * Legend overlay: Menunggu (amber) / Diproses (blue) / Selesai (emerald).
* **Filters:** free-text `search` (debounced 400 ms, title + description), `status` select, `category` select,
  plus a "Reset filter" button. Filter changes push new markers to the map via the `report-map-updated` browser
  event (no page reload).
* **Live updates (low cost):** there is **no `wire:poll`** on the map any more. After the initial server render,
  the `@script` polls `GET /laporan/peta` every 30 seconds with `fetch()`, sending the current filters and the last
  `ETag` in `If-None-Match`. The endpoint answers **304** when nothing changed, the JSON body is compared by
  signature, and `draw()` only runs when the marker set actually differs. Polling is skipped while the tab is
  hidden (`document.visibilityState`) and while the browser is offline; it resumes on `visibilitychange`/`online`.
  The map container is `wire:ignore`, so Livewire never re-renders Leaflet markup.
* **Filter changes:** Livewire re-renders the recent list and dispatches `report-map-updated` with both `markers`
  and the active `filters`, so the next poll uses the same filter set.
* **Recent list:** the 6 newest matching reports with status badge and relative age.
* **Bottom CTA:** "Buat laporan baru" shown to non-admins only.
* **Query behaviour:** each filter change runs one report query for `with()`; the polling loop hits the JSON
  endpoint (max 50 rows, only the columns the marker needs, eager-loads `category:id,name`).

### 10.2 Report statistics — `report-stats`

* A single `GROUP BY status` aggregate query produces totals.
* Renders 4 cards: **Laporan masuk** (total), **Menunggu**, **Diproses**, **Selesai**.
* Accepts a `scope` mount parameter:
  * `scope="all"` (default, used by `/`) counts **every** report and labels the first card "Laporan masuk".
  * `scope="mine"` (used by the citizen dashboard) adds `where('user_id', Auth::id())` and labels the first card
    "Laporan saya". Admins keep `all` on their dashboard.
* `wire:poll.30s` keeps the numbers live.

### 10.3 Report submission — `/lapor` (`report-form`)

* Open to **guests and citizens**; admins are steered away via navigation only.
* **Fields:** title, category, photo evidence, description, phone, and an interactive Leaflet map to place a pin.
* **Geolocation:** click the map to drop a draggable pin, or press "Gunakan lokasi saya"; coordinates are written
  into the `latitude`/`longitude` Livewire properties via `$wire.set(...)` (8-decimal precision).
* **Photo evidence:** uses `Livewire\WithFileUploads`. When a previewable file is selected:
  * an inline **preview** (20×20 thumbnail + filename + size in KB + "Lihat ukuran penuh") is shown,
  * a "Hapus foto" button resets the upload (`removePhoto()`),
  * an upload spinner shows while the file transfers,
  * the stored filename is `<hash>.<ext>` under the `public` disk path `reports/`.
* **On success:** the component shows a confirmation card with the report number and links to the detail page or
  "Buat laporan lain". It also flashes a success **toast** and dispatches `report-submitted` so the map resets.
* **Anonymous:** when no user is logged in, `user_id` is `null` and the UI says the report is sent without an account.
* Validation rules are listed in [§13](#13-validation-rules-reference).

### 10.4 Report detail — `/laporan/{report}` (`report-detail`)

Sections:

1. **Summary card** — category chip, status badge, title, metadata (id, submitted time, reporter — "Anonim" for
   anonymous; **contact phone visible to admins only**), full description, photo (linked to full size),
   coordinates and an "Buka di OpenStreetMap" link.
2. **Tanggapan warga (comments)** — public, newest first, with **one-level replies**:
   * Logged-in users get a "Tulis tanggapan" form (`addComment`, max 1000 chars) and a "Balas" button on every
     comment. `startReply()` opens an inline reply form under that thread; `addReply()` validates and stores the
     row with `parent_id` = the root comment (replying to a reply re-attaches to the same root).
   * Replies render indented under the original comment with author, timestamp and body; the thread shows
     ":count balasan". Comments/replies written by an admin carry a blue **"Petugas"** badge so official answers
     stand out from citizen replies. Guests can read comments/replies and see a "Masuk untuk menanggapi" prompt.
   * The reply target must belong to the same report, otherwise a validation error is shown and nothing is stored.
   * `addComment` and `addReply` share a rate limit of **5 writes per user per 10 minutes**
     (`report-comment:{userId}`), surfacing a translated "try again in :seconds" error.
3. **Location mini-map** — a Leaflet map (`wire:ignore`) with a marker coloured by status, plus coordinates.
4. **Riwayat status (status history)** — timeline with coloured dots per status, timestamp, author (or "Sistem")
   and notes.
5. **Status update form (admins only)** — a status `<select>` (all four statuses) and optional notes (max 500).
   Submitting calls `updateStatus()`, which updates the report, appends the history entry (default note
   "Status diperbarui oleh {admin}.") and shows a success toast.
6. **Hotline CTA** — link to `/darurat`.

Query behaviour: `mount()` only stores the UUID; the report with `category`, `user`, root `comments`
(`whereNull('parent_id')`, newest first), `comments.replies.user` and `statusHistories.user` is loaded exactly
**once** in `with()`. `resolveReport()` resolves every mutation through the public UUID.

### 10.5 Dashboard — `/dashboard` (`report-stats` + `report-list`)

* **Role-aware header:** "Pusat Kendali Petugas / Pemantauan Laporan Warga" (admin) vs "Laporan Saya /
  Riwayat Laporan Anda" (citizen).
* Stats row (see §10.2) rendered with `scope="all"` for admins and `scope="mine"` for citizens, so a citizen's
  cards only count their own reports instead of the global map totals.
* **Report list (`report-list`)** — paginated (8 per page), `wire:poll.30s`:
  * Admins see **all** reports plus a "Pelapor" column (or "Anonim").
  * Citizens see **only their own** reports; the reporter column is hidden.
  * Empty states differ per role (admins: "Belum ada laporan yang masuk.").
* **Admin feedback card:** `<livewire:feedback-summary />` shows the number of unanswered feedback items, the
  three newest entries (with an "answered" marker) and a link to the inbox.
* **Citizen feedback card** (non-admin): "Masukan & saran saya" with a link to `/masukan-saya`, next to the
  latest-feedback responses.
* **Quick actions** (role-gated): citizens get "Laporkan masalah…", "Kirim masukan…", "Lihat tanggapan masukan
  saya", hotlines and map links; admins get "Tinjau masukan & saran warga", "Kelola kategori" and
  "Kelola nomor penting" instead of the submission shortcuts.
* **Status legend card** explaining pending/processing/resolved.
* The header action buttons are "Lihat peta" for everyone plus "Kotak masukan" (admin) or "Buat laporan"
  (citizen).

### 10.6 Feedback submission — `/masukan` (`feedback-form`)

* Open to guests and citizens; anonymous submission supported.
* **Fields:** type (`saran`/`kritik`/`apresiasi`/`lainnya`), message (10–2000 chars), optional name, optional contact.
* Blank name/contact are normalised to `null` (so anonymous rows stay clean).
* On success: a thank-you card with a "Kirim masukan lain" button (`resetForm()`), plus a success toast.
* Informational cards clarify anonymity, that it is *not* a report channel, and that all feedback is read.

### 10.7 Admin feedback review — `/admin/masukan` (`feedback-admin`)

* Admin-only; `with()` aborts with 403 for non-admins and every mutating method calls `guardAdmin()`.
* **Summary cards:** total feedback, not yet answered, already answered (counts from one aggregate query).
* **Filter select:** all feedback, "Belum ditanggapi", or one of the four types (`saran`, `kritik`, `apresiasi`,
  `lainnya`). The list is capped at 100 entries, newest first.
* Each card shows the translated type badge, answered/pending badge, timestamp, sender (name → linked user name →
  "Anonim"), contact ("Tidak diisi" fallback), whether it came from a linked account, the message and — when
  present — the existing officer response with author and time.
* **Reply workflow:** "Tanggapi" / "Ubah tanggapan" opens an inline form (`startResponding()` pre-fills the current
  response). `saveResponse()` validates a required 3–2000 char response, stores `response`, `responded_by`
  (`Auth::id()`) and `responded_at` (`now()`), closes the form and dispatches a success toast. "Batal" calls
  `cancelResponding()`.
* Feedback itself remains read-only (no edit/delete).

### 10.8 Category management — `/admin/kategori` (`kategori-admin`)

* Admin-only (`guardAdmin()` in every mutating method).
* **Create:** name required, ≤120 chars, unique. Slug is generated with `Str::slug()` and de-duplicated with a
  numeric suffix (`kategori`, `kategori-2`, …).
* **Rename:** inline row editing; name unique (ignoring the current row); slug regenerated & de-duplicated.
* **Delete:** opens a confirmation modal (`x-confirm-modal`); deletion is **blocked** if the category is used by
  any report (uses `withCount('reports')`), surfacing an error toast with the usage count.
* The table lists each category with its slug and report count.

### 10.9 Hotlines — `/darurat`

* Public page (no login required) driven by the database: the route loads `Hotline::query()->active()->ordered()`.
* Lists each active number with a `tel:` link built by `dialNumber()` (spaces/punctuation stripped), its label and
  optional description. When no active rows exist, an explanatory empty state is shown.
* Explains the portal is for non-emergency reports and includes a 3-step "when to use the portal" guide.
* Submission CTAs (report/feedback) are hidden from admins.
* Numbers are seeded by `HotlineSeeder` (112, 110, 113, 119, 115, 123) and are editable at
  `/admin/nomor-penting` — no source-code change is required to update them.

### 10.10 Role-aware navigation (`sidebar`, `bottom-nav`)

* Desktop: left `sidebar` (sticky, hidden below `lg`). Mobile: top header + fixed `bottom-nav` (hidden at `lg`+).
* Menu items are gated by `$user?->isAdmin()`:
  * Everyone: Peta Warga, Dashboard ("Pusat Kendali"/"Laporan Saya"), Nomor Penting.
  * Guests/citizens: Buat Laporan, Masukan & Saran, **Masukan Saya** (`/masukan-saya`).
  * Admins: Masukan Warga, Kelola Kategori, **Kelola Nomor Penting** (`/admin/nomor-penting`).
* Sidebar and guest header contain the ID/EN `x-locale-switcher`; the sidebar also has the theme toggle.
* Sidebar footer shows the avatar/initial, name, role label, Profil / Keluar buttons (or Masuk / Daftar for guests).

### 10.11 Citizen feedback inbox — `/masukan-saya` (`feedback-history`)

* Requires `auth` + `verified` (route `masukan.saya`); linked from the dashboard card, the quick actions and the
  sidebar for non-admins.
* The component queries `Feedback::where('user_id', Auth::id())` with `respondedBy:id,name`, newest first, so a
  citizen can only ever see their own feedback.
* Summary cards: total, awaiting a response, already answered. Each card shows the type badge, answered/pending
  status, timestamp, the message and — when answered — a green "Tanggapan petugas" block with the response text,
  the officer name and the response timestamp. Pending items show "Masukan Anda sedang menunggu tanggapan petugas."
* Empty state invites the citizen to send feedback. A footnote explains that anonymous submissions cannot be shown
  here because they are not linked to the account.

### 10.12 Language switching (ID / EN)

* `GET /bahasa/{locale}` (`locale.switch`) accepts only the keys of `config('app.supported_locales')` (`id`, `en`);
  anything else returns **404**. It stores the choice in the session (`locale`) and redirects back to the previous
  **local** path (external referrers and `/bahasa/*` fall back to `/`, so the switch cannot be used as an open
  redirect).
* `App\Http\Middleware\SetLocale` is appended to the `web` middleware group in `bootstrap/app.php` and calls
  `App::setLocale(session('locale', config('app.locale')))`, so the choice survives navigations, Livewire updates
  and redirects.
* `x-locale-switcher` is rendered in the desktop sidebar, the mobile header and the guest/auth header; it shows the
  active locale with `aria-pressed` states.
* Translation assets:
  * `lang/en.json` — Indonesian source strings → English (the majority of the UI).
  * `lang/id.json` — the English Breeze auth screens → Indonesian.
  * `lang/id/{auth,passwords,pagination,validation}.php` — framework messages in Indonesian.
* Dates/relative ages, status labels, feedback types and validation messages all follow the active locale.
* The locale is **session-scoped**; it is not stored on the user record, so it resets in a new browser session.

### 10.13 Emergency-number management — `/admin/nomor-penting` (`hotline-admin`)

* Admin-only (`hotline.index`, middleware `auth` + `verified` + `admin`), linked from the sidebar and the admin
  quick actions as "Kelola Nomor Penting".
* One shared form handles creation and editing: number (2–30 chars, digits/`+`/`-`/spaces/parentheses, unique),
  label (required, ≤120), description (optional, ≤500), sort order (0–999) and an "Aktif" checkbox.
  "Ubah" loads a row into the form (`editingId`), "Batal" resets it, and the submit label switches between
  "Tambah nomor" and "Simpan perubahan".
* Rows list the number, label, description, order, and an active/inactive badge, with actions to
  **activate/deactivate** (`toggleActive()`), edit and delete (confirmation modal `delete-hotline`).
* Inactive rows remain visible to admins but are filtered out of `/darurat`.
* Every mutation is guarded server-side and dispatches a success toast; deletions are permanent.

---

## 11. Livewire / Volt component reference

All components are **class-based Volt single-file components** under `resources/views/livewire/`.
Files start with a `<?php … ?>` block (`new class extends Component { … }`) followed by the Blade template,
using inline `with()` methods to pass render data.

### 11.1 Public components

| Component | Tag | Key state | Key methods | Notable behaviour |
| --- | --- | --- | --- | --- |
| `report-map` | `<livewire:report-map />` | `search`, `status`, `category` | `updated()`, `resetFilters()`, `reports()`, `filters()`, `dispatchMarkers()` | Leaflet map; JSON/ETag polling every 30 s while visible; pushes `report-map-updated` with `markers` + `filters` |
| `report-stats` | `<livewire:report-stats scope="all|mine" />` | `scope` | `mount()`, `with()` | grouped status counts, optionally scoped to the signed-in citizen, 30 s poll |
| `report-list` | `<livewire:report-list />` | pagination | `with()` | admin = all + reporter column; citizen = own only; 8/page; 30 s poll |
| `report-form` | `<livewire:report-form />` | `mapId`, `title`, `description`, `phone`, `category_id`, `$photo`, `latitude`, `longitude`, `submittedReportId` | `mount()`, `submit()`, `removePhoto()`, `resetForm()` | photo upload + preview, Leaflet pin, toast, anonymous support |
| `report-detail` | `<livewire:report-detail :report-uuid="$uuid" />` | `reportUuid`, `status`, `notes`, `comment`, `replyToId`, `reply` | `mount()`, `addComment()`, `startReply()`, `cancelReply()`, `addReply()`, `updateStatus()`, `with()` | threaded comments (one reply level), rate limiting, status history, admin status form, Leaflet mini-map |
| `feedback-form` | `<livewire:feedback-form />` | `type`, `message`, `name`, `contact`, `submitted` | `submit()`, `resetForm()` | anonymous-friendly |
| `feedback-summary` | `<livewire:feedback-summary />` | – | `with()` (403) | admin dashboard card: pending count, latest 3 feedback, link to the inbox |
| `feedback-admin` | `<livewire:feedback-admin />` | `filter`, `respondingId`, `response` | `with()` (403), `startResponding()`, `cancelResponding()`, `saveResponse()` | admin review, status/type filter, officer reply workflow, summary counts |
| `feedback-history` | `<livewire:feedback-history />` | – | `with()` | citizen inbox: own feedback only + officer responses + counts |
| `kategori-admin` | `<livewire:kategori-admin />` | `name`, `editingId`, `editName`, `confirmingDeleteId`, `confirmingDeleteName` | `createCategory()`, `startEditing()`, `cancelEditing()`, `updateCategory()`, `confirmDelete()`, `deleteCategory()` | CRUD + unique slug + delete guard |
| `hotline-admin` | `<livewire:hotline-admin />` | `number`, `label`, `description`, `sortOrder`, `isActive`, `editingId`, `confirmingDeleteId` | `save()`, `startEditing()`, `cancelEditing()`, `toggleActive()`, `confirmDelete()`, `deleteHotline()` | CRUD for emergency numbers, active toggle, confirm modal |
| `sidebar` | `<livewire:sidebar />` | – | `with()` | desktop nav, role gating, profile/logout |
| `bottom-nav` | `<livewire:bottom-nav />` | – | `with()` | mobile nav, role gating |

### 11.2 Profile components (`resources/views/livewire/profile/`)

| Component | Methods | Behaviour |
| --- | --- | --- |
| `update-profile-information-form` | `mount()`, `updateProfileInformation()`, `sendVerification()` | update name/email, reset `email_verified_at` when email changes, success toast |
| `update-password-form` | `updatePassword()` | requires current password, uses `Password::defaults()` + confirmation, success toast |
| `delete-user-form` | `deleteUser(Logout $logout)` | requires current password, logs out + deletes, flashes a toast then redirects `/` |

### 11.3 Auth components (`resources/views/livewire/pages/auth/`)

All use `#[Layout('layouts.guest')]`.

| Component | Route name | Methods |
| --- | --- | --- |
| `login` | `login` | `login()` (uses `LoginForm`) |
| `register` | `register` | `register()` |
| `forgot-password` | `password.request` | `sendPasswordResetLink()` |
| `reset-password` | `password.reset` | `mount(token)`, `resetPassword()` |
| `confirm-password` | `password.confirm` | `confirmPassword()` |
| `verify-email` | `verification.notice` | `sendVerification()`, `logout()` |

### 11.4 Action & form helpers (`app/Livewire/`)

* `Actions/Logout` — invokable: `Auth::guard('web')->logout()`, invalidate session, regenerate CSRF token. Used by
  the `POST /logout` route and by `verify-email`/`delete-user-form`.
* `Forms/LoginForm` — Livewire form object with `#[Validate]` rules on `email`/`password`/`remember`, an
  `authenticate()` method, and **rate limiting** (5 attempts per `email|ip` throttle key, with `Lockout` event).

---

## 12. Blade component reference

Anonymous Blade components (`resources/views/components/`):

| Component | Props | Purpose |
| --- | --- | --- |
| `x-brand` | `href` (default `route('home')`), `compact` | Logo + wordmark ("Lapor Yuk!" / "Portal pengaduan warga"); `compact` hides the text. |
| `x-theme-toggle` | `compact` | Dark/light toggle; reads/writes `localStorage.theme`, toggles `.dark` on `<html>`. |
| `x-status-badge` | `status` | Coloured pill for a report status; falls back to `pending` styling and `ucfirst($status)` label. |
| `x-toast-stack` | `initial` (array) | Fixed top-right toast stack (Alpine); see §16. |
| `x-confirm-modal` | `name`, `title`, `message`, `confirmLabel`, `cancelLabel`, `variant` (`danger`/`warning`/`primary`), `show`, `action`, `focusable`, `:confirm` slot | Animated confirmation dialog; see §16. |
| `x-auth-session-status` | `status` | Green session status message. |
| `x-input-label` | `value` / slot | Form label. |
| `x-input-error` | `messages` | Red validation error list. |
| `x-text-input` | `disabled` | Base text input. |
| `x-password-input` | `id`, plus any input attribute (`wire:model`, `name`, `required`, `autocomplete`, `class`) | Password field with an Alpine **show/hide** button (`showPassword`, `x-bind:type`, `aria-pressed`). Used by login (1 field) and register (password + confirmation). |
| `x-locale-switcher` | – | ID/EN links to `locale.switch`; highlights the active locale with `aria-pressed`. |
| `x-primary-button` | – | Dark indigo submit button. |
| `x-danger-button` | – | Red submit button. |

Layouts:

| Component | Resolves to | Used by |
| --- | --- | --- |
| `<x-app-layout>` | `App\View\Components\AppLayout` → `layouts.app` | all app pages |
| `#[Layout('layouts.guest')]` | `layouts.guest` (direct view name — **not** a component) | auth pages |

`layouts.app` includes the sidebar, mobile header, bottom nav, toast stack, `@livewireStyles`/`@livewireScripts`,
and the Leaflet CDN assets. `layouts.guest` is a centred card layout with the brand in the header.

> Some Breeze scaffolding components remain available even though the custom UI does not use them
> (`dropdown`, `dropdown-link`, `nav-link`, `responsive-nav-link`, `secondary-button`, `application-logo`,
> `action-message`). `x-modal` **is** used by `x-confirm-modal`'s internals.

---

## 13. Validation rules reference

### 13.1 Report submission (`report-form::submit`)

| Field | Rules |
| --- | --- |
| `title` | `required`, `string`, `max:255` |
| `description` | `required`, `string`, `max:2000` |
| `phone` | `required`, `string`, `max:25`, `regex:/^[0-9+\-\s().]+$/` |
| `category_id` | `required`, `integer`, `exists:categories,id` |
| `photo` | `required`, `image`, `max:2048` (KB ⇒ 2 MB) |
| `latitude` | `required`, `numeric`, `between:-90,90` |
| `longitude` | `required`, `numeric`, `between:-180,180` |

### 13.2 Feedback (`feedback-form::submit`)

| Field | Rules | Labels |
| --- | --- | --- |
| `type` | `required`, `in:saran,kritik,apresiasi,lainnya` | "Jenis masukan" |
| `message` | `required`, `string`, `min:10`, `max:2000` | "Isi masukan" |
| `name` | `nullable`, `string`, `max:100` | "Nama" |
| `contact` | `nullable`, `string`, `max:150` | "Kontak" |

### 13.3 Report detail (`report-detail`)

| Method | Field | Rules |
| --- | --- | --- |
| `addComment` | `comment` | `required`, `string`, `max:1000` |
| `addReply` | `reply` | `required`, `string`, `max:1000`; `replyToId` must point at a comment of the same report |
| `updateStatus` | `status` | `required`, `in:pending,processing,resolved,rejected` |
| `updateStatus` | `notes` | `nullable`, `string`, `max:500` |

> `addComment`/`addReply` share a **5 writes / 10 minutes** rate limit per user (`RateLimiter`), returning a
> translated validation error instead of writing the row.

### 13.4 Categories (`kategori-admin`)

| Method | Field | Rules |
| --- | --- | --- |
| `createCategory` | `name` | `required`, `string`, `max:120`, `unique:categories,name` |
| `updateCategory` | `editName` | `required`, `string`, `max:120`, `unique:categories,name` (ignoring current id) |

### 13.4b Emergency numbers (`hotline-admin`)

| Field | Rules |
| --- | --- |
| `number` | `required`, `string`, `max:30`, `regex:/^[0-9+()\-\s]{2,30}$/`, `unique:hotlines,number` (ignoring the edited row) |
| `label` | `required`, `string`, `max:120` |
| `description` | `nullable`, `string`, `max:500` |
| `sortOrder` | `required`, `integer`, `min:0`, `max:999` |
| `isActive` | `boolean` |

### 13.5 Profile (`profile/*`)

| Method | Field | Rules |
| --- | --- | --- |
| `updateProfileInformation` | `name` | `required`, `string`, `max:255` |
| `updateProfileInformation` | `email` | `required`, `lowercase`, `email`, `max:255`, `unique:users` (ignoring self) |
| `updatePassword` | `current_password` | `required`, `string`, `current_password` |
| `updatePassword` | `password` | `required`, `string`, `Password::defaults()`, `confirmed` |
| `deleteUser` | `password` | `required`, `string`, `current_password` |

### 13.6 Auth

| Component | Field | Rules |
| --- | --- | --- |
| Login | `form.email` | `required`, `string`, `email` |
| Login | `form.password` | `required`, `string` |
| Login | `form.remember` | `boolean` |
| Register | `name` | `required`, `string`, `max:255` |
| Register | `email` | `required`, `string`, `lowercase`, `email`, `max:255`, `unique:users` |
| Register | `password` | `required`, `string`, `confirmed`, `Password::defaults()` |
| Reset | `token` | `required` |
| Reset | `email` | `required`, `string`, `email` |
| Reset | `password` | `required`, `string`, `confirmed`, `Password::defaults()` |
| Confirm password | `password` | `required`, `string` |

---

## 14. Frontend, theming & assets

### 14.1 Tailwind

* **Version:** Tailwind CSS v3 with the classic `tailwind.config.js` (not the v4 CSS-first config),
  despite `@tailwindcss/vite` being present in `devDependencies`.
* **Dark mode:** `darkMode: 'class'`.
* **Content globs:** Laravel pagination views, compiled views, and `resources/views/**/*.blade.php`.
* **Plugin:** `@tailwindcss/forms`.
* **Font:** `fontFamily.sans = ['Figtree', ...defaultTheme.fontFamily.sans]`.
* **Entry CSS** (`resources/css/app.css`): the three Tailwind directives plus a `@layer base` block with
  `[x-cloak]`, dark-mode form control overrides, and Leaflet dark-mode styling (inverted tiles, popup colours).

### 14.2 Vite

```js
// vite.config.js
input: ['resources/css/app.css', 'resources/js/app.js']
```

Build output goes to `public/build/` (git-ignored). `npm run build` for production, `npm run dev` for HMR.

### 14.3 Dark/light theme

* A small **no-flash** inline script in both layouts runs before paint:

  ```js
  var stored = localStorage.getItem('theme');
  var dark = stored ? stored === 'dark' : matchMedia('(prefers-color-scheme: dark)').matches;
  document.documentElement.classList.toggle('dark', dark);
  ```

* `x-theme-toggle` (Alpine) flips the `dark` class and persists the choice to `localStorage`.
* All components ship `dark:` variants.

### 14.4 Leaflet usage

* Leaflet CSS + JS are loaded globally in `layouts.app` from unpkg with SRI hashes.
* Three maps exist, each initialised inside a `@script` block guarded by `container.dataset.initialized` to avoid
  double-initialisation across Livewire re-renders and to avoid duplicate event listeners:
  1. `report-map` → `#warga-map` (all reports, filters + JSON poll; container carries `data-map-url`).
  2. `report-detail` → `#report-location-map` (single report).
  3. `report-form` → dynamically named container (`report-map-<uniqid>`) for pin placement.
* Tiles: OpenStreetMap standard tiles.
* `resources/css/app.css` gives `.leaflet-container` `z-index: 0` so it sits under the app chrome.

### 14.5 Livewire ↔ JavaScript bridges

| Event (browser) | Dispatched by | Consumed by |
| --- | --- | --- |
| `notify` (`$event.detail` = `{type,title,message,duration}`) | any component via `$this->dispatch('notify', …)` | `x-toast-stack` |
| `report-map-updated` (`markers`, `filters`) | `report-map::dispatchMarkers()` | the `report-map` `@script` (`draw()` + filter cache for the next poll) |
| `report-submitted` | `report-form::submit()/resetForm()` | the `report-form` `@script` (removes pin, resets view) |
| `open-modal` / `close-modal` (`detail` = modal name) | components (e.g. `kategori-admin`, `hotline-admin`) | `x-confirm-modal` |

### 14.6 Real-time map data polling

The public map keeps itself current without a Livewire poll:

```
GET /laporan/peta?search=<q>&status=<status>&category=<id>
  → 200 {markers: [...], total: n}  + ETag
  → 304 (no body) when If-None-Match still matches
```

* The `@script` reads the endpoint from `data-map-url` (set by the component) so the URL is never hard-coded in JS.
* Every 30 s (and immediately when the tab becomes visible or the network reconnects) `refreshMarkers()` fetches
  the JSON with the cached filters and `If-None-Match`.
* 304 responses stop there; 200 responses are compared by `JSON.stringify(markers)` signature, and Leaflet is only
  redrawn when the markers actually changed. Network errors clear the cached ETag and retry on the next tick.
* Filters changed in Livewire are pushed into `currentFilters` via the `report-map-updated` event, so the poll always
  matches the visible filter set.

---

## 15. Authentication & profile

* **Stack:** Laravel Breeze (2.x) auth scaffolding implemented with **Volt** single-file components and the
  `guest` layout. Session-based (`web` guard).
* **Login:** `LoginForm` form object, rate-limited to 5 failed attempts per `email|ip` with a `Lockout` event.
  The password field uses `x-password-input` (show/hide toggle; `type` switches between `password`/`text`).
* **Registration:** creates a `warga` (default role) account and logs in, redirecting to the dashboard. Both the
  password and confirmation fields use `x-password-input`.
* **Language:** auth screens are English source strings translated through `lang/id.json`, so they render in
  Indonesian when the session locale is `id` (the default) and in English otherwise.
* **Password reset:** standard Laravel `Password::sendResetLink` / `Password::reset` flow (`MAIL_MAILER=log` in dev).
* **Email verification:** routes and views exist, but `User` does not implement `MustVerifyEmail`, so verification
  is not enforced and the resend UI is unreachable. `VerifyEmailController` still works if a signed link is used.
* **Password confirmation:** route/page exist (`password.confirm`); nothing currently applies the
  `password.confirm` middleware, so it is only reachable by visiting the URL directly.
* **Profile page** (`/profile`): three Livewire sections — profile info, password change, and account deletion.
  Deleting an account logs the user out, deletes the user, and flashes a toast. Because `reports.user_id` is
  nullable with **cascade delete**, deleting a user also deletes their reports; **feedback** uses `nullOnDelete`,
  so feedback survives as anonymous.

---

## 16. Notifications & confirmation dialogs

### 16.1 Toasts (`x-toast-stack`)

Rendered once per layout. It is an Alpine component that:

* Accepts an `initial` prop (used to replay `session('notify')` flashes after a redirect) and
  seeds them with a 150 ms stagger.
* Listens to the window event `notify` and pushes `$event.detail`.
* Supports four types: `success`, `error`, `warning`, `info` (anything else falls back to `info`),
  optional `title`, a `message`, and a `duration` (default 5000 ms).
* Animates in/out from the top-right (`translate-x`/opacity transitions), auto-dismisses, and has a close button.
* Container is `role="status"` / `aria-live="polite"`.

**Dispatch API**

```php
// Same-page (Livewire component)
$this->dispatch('notify', type: 'success', message: 'Kategori dihapus.');
$this->dispatch('notify', type: 'error', title: 'Tidak dapat dihapus', message: '…');

// Across a redirect
session()->flash('notify', ['type' => 'success', 'message' => 'Akun Anda telah dihapus.']);
```

### 16.2 Confirmation modal (`x-confirm-modal`)

An Alpine + Livewire modal:

* Props: `name` (required), `title`, `message`, `confirmLabel`, `cancelLabel`, `variant`, `show`, `action`,
  `focusable`, plus the `:confirm` slot for a custom confirm button.
* Opens/closes on the Livewire browser events `open-modal`/`close-modal` with matching `detail === name`, and
  closes on `Escape`, backdrop click or the cancel button.
* Locks body scroll while open and, with `focusable`, autofocuses the first field.
* Two usage styles:
  1. **Slot mode** (custom confirm button) — e.g. `kategori-admin` passes `wire:click="deleteCategory(id)"`.
  2. **Action mode** — pass `action="deleteUser"`; the modal wraps content in a Livewire form and submits it.
* `show` can force the dialog open (used by `delete-user-form` to reopen when validation errors exist).
* Variants `danger`/`warning`/`primary` exist; only `danger` is currently used.

---

## 17. Seeders & factories

### 17.1 Seeders (`database/seeders/`)

| Seeder | Registered in `DatabaseSeeder` | What it does |
| --- | :-: | --- |
| `DatabaseSeeder` | – | Creates `test@example.com` (citizen) if absent, then calls `admin` and `CategorySeeder`. |
| `admin` | ✅ | Creates `admin@example.com` (verified, `role=admin`) if absent. |
| `CategorySeeder` | ✅ | Truncates `categories` (FK checks disabled) and inserts the canonical 10 categories. |
| `HotlineSeeder` | ✅ | Upserts the six default emergency numbers by `number` (112, 110, 113, 119, 115, 123) without duplicating rows. |
| `ReportSeeder` | ✅ | If no reports exist, creates ~9 demo reports around Medan (no phone/photo). Can also be run alone with `php artisan db:seed --class=ReportSeeder`. |

Canonical categories: Pencemaran Sungai & Air · Pencemaran Udara · Sampah & Limbah Liar ·
Kerusakan Lingkungan & Ruang Terbuka Hijau · Infrastruktur Jalan & Jembatan Rusak · Penerangan Jalan Umum ·
Fasilitas Publik Terbengkalai · Masalah Utilitas (Listrik, Air, Gas) · Fasilitas Umum & Ruang Publik ·
Laporan Lainnya.

### 17.2 Factories (`database/factories/`)

| Factory | Default state | States |
| --- | --- | --- |
| `UserFactory` | random name/email, verified, password `password` | `unverified()` |
| `CategoryFactory` | title-cased 2-word name + unique slug | – |
| `ReportFactory` | sentence title, paragraph description, random Medan coords, `pending`, null phone/photo, auto UUID | `anonymous()`, `processing()`, `resolved()` |
| `FeedbackFactory` | random type, name + optional email contact | `anonymous()` |
| `HotlineFactory` | random 3-digit number, label, description, sort order, active | `inactive()` |

> There is **no** `CommentFactory` or `StatusHistoryFactory`; tests create those records directly via relations.

---

## 18. Testing

* **Framework:** PHPUnit 12 (`phpunit.xml`), with the `laravel/pao` agent output helper and Pail for log tailing.
* **Environment:** `APP_ENV=testing`, in-memory SQLite, array cache/session, array mail, sync queue,
  `BCRYPT_ROUNDS=4`.
* **Base class:** `Tests\TestCase` (thin) — feature tests use `RefreshDatabase`.
* **Suites:** `tests/Unit` and `tests/Feature`.

### 18.1 Running

```bash
php artisan test --compact
php artisan test --filter=ReportSubmissionTest
vendor/bin/phpunit --no-coverage
composer run test
```

### 18.2 Coverage map (test files → focus)

| Test file | Focus |
| --- | --- |
| `Feature/Auth/AuthenticationTest` | login screen, valid/invalid login, portal navigation renders, logout |
| `Feature/Auth/RegistrationTest` | register screen, new user can register |
| `Feature/Auth/PasswordResetTest` | request link, render reset screen, reset with valid token |
| `Feature/Auth/PasswordConfirmationTest` | confirm screen, confirm, reject wrong password |
| `Feature/Auth/EmailVerificationTest` | notice screen, verify, reject invalid hash |
| `Feature/Auth/PasswordUpdateTest` | password update, wrong current password rejected |
| `Feature/CategoryManagementTest` | admin CRUD, unique name, delete guard, confirm modal, notifications, link visibility (14 tests) |
| `Feature/FeedbackManagementTest` | admin review access control, listing, officer response workflow, pending filter, nav gating |
| `Feature/FeedbackSubmissionTest` | guest/anonymous submit, user linkage, min length, type whitelist, null normalisation (6 tests) |
| `Feature/HotlineManagementTest` | admin CRUD + guards, unique/required validation, active toggle, public page reads the DB, inactive rows hidden, seeder defaults, admin dashboard link |
| `Feature/HotlinePageTest` | public hotline page (seeded), auth requirement for dashboard, "not an emergency channel", `/sos` 404 |
| `Feature/LanguageSwitchingTest` | Indonesian default, switch to English and back, unsupported locale 404, authenticated pages, switcher rendering, redirect-back |
| `Feature/MapDataTest` | JSON marker payload, status/category/search filters, 422 filters, ETag/304, no `wire:poll` on the map |
| `Feature/NotificationTest` | toast container rendered, flashed notification replayed |
| `Feature/PasswordVisibilityTest` | show/hide control on login and both registration password fields |
| `Feature/ProfileTest` | profile page, update info, email verification state, account deletion + password check |
| `Feature/ReportDetailTest` | public detail, comment (auth/guest), replies (auth/guest, same-report guard, one-level depth), admin status update + history, non-admin blocked, rates, UUID URLs, photo streaming |
| `Feature/ReportMapTest` | public listing for guests, status filter, title search, reset filters |
| `Feature/ReportSubmissionTest` | guest/user submit, required fields/phone/photo, image rule, invalid phone chars, preview, remove preview, notification |
| `Feature/ResidentDashboardTest` | citizen stats scoped to own reports, public stats global, dashboard list scoping, feedback-inbox links, admin isolation |
| `Feature/ResidentFeedbackInboxTest` | auth requirement, own feedback + responses, other users hidden, pending state, empty state |
| `Feature/ExampleTest` | `/` returns 200 |
| `Unit/ExampleTest` | placeholder |

**Suite size (last run): 137 tests, 469 assertions, all passing.**

> **Verification note:** the PHPUnit config targets in-memory sqlite, but this workspace's PHP build has **no
> `pdo_sqlite`** extension (only `pdo_mysql`) and installing it needs root. The suite was therefore executed against
> MySQL by launching an isolated MariaDB instance with its own datadir/port (`3307`) and running
> `DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_PORT=3307 DB_USERNAME=root DB_PASSWORD= DB_DATABASE=laporyuk_test php artisan test`.
> Nothing was dropped from the development database. On a machine with `pdo_sqlite`, the plain
> `php artisan test` command works unchanged.

### 18.3 Testing conventions in this repo

* Feature tests use factories (e.g. `Report::factory()->processing()->create()`, `Hotline::factory()->inactive()->create()`).
* `assoc` models for admin: `User::factory()->create(['role' => 'admin'])` (there is no `admin()` state).
* Because the default locale is `id`, assertions use the Indonesian copy; English rendering is covered explicitly in
  `LanguageSwitchingTest` by switching the session locale.
* Livewire/Volt components are tested with `Livewire\Volt\Volt::test('component', [...])`.
* Because the test environment has **no GD extension**, photo tests use
  `UploadedFile::fake()->create('evidence.jpg', 500, 'image/jpeg')` rather than `->image()`.
* Photo uploads are isolated with `Storage::fake('public')`.
* `assertSee` HTML-escapes its argument — e.g. assert `"Masukan & Saran Warga"` un-escaped only when appropriate.

---

## 19. Conventions & code style

Project-specific rules (also captured in `AGENTS.md`):

* **PHP 8.4**, Laravel 13 APIs. Use constructor property promotion; explicit return types and parameter types.
* Use `php artisan make:*` to scaffold files (`--no-interaction`, correct options).
* **Pint** is the formatter: run `vendor/bin/pint --dirty --format agent` after editing PHP. Do **not** run
  `pint --test`.
* **Attributes over properties** for Eloquent config: `#[Fillable([...])]`, `#[Hidden([...])]`
  (Laravel 13 style used across models).
* Curly braces always, even for single statements.
* **No comments in source.** Every PHP/Blade/JS/CSS comment (including docblocks) was removed to keep the codebase
  clean; do not reintroduce comment lines. Express intent through descriptive names, explicit types and array-shape
  signatures on method returns instead.
* Descriptive names (`isRegisteredForDiscounts`, not `discount()`).
* Default locale is `id`; every new user-facing string must have an `lang/en.json` entry (Indonesian key → English
  value) and, for English-source Breeze screens, a `lang/id.json` entry.
* Prefer named routes + `route()` when generating links.
* Keep state server-side in Livewire; validate and authorise inside actions.
* Do not add dependencies or create new base folders without approval; do not create docs unless requested.
* If `.ai/rules` exists, read the rules whose globs cover the files you touch before editing (it does not exist
  in this repo at time of writing).

---

## 20. Known constraints, quirks & troubleshooting

### 20.1 Behavioural quirks (by design or leftover)

| # | Observation | Impact / mitigation |
| --- | --- | --- |
| 1 | `verified` middleware is a **no-op** because `User` does not implement `MustVerifyEmail` (it only inherits the trait). | Email verification is not enforced; the resend UI never renders. Add `implements MustVerifyEmail` if verification is ever required. |
| 2 | Admins are only kept out of report/feedback submission by **navigation/UI gating**, not server-side guards. | A determined admin could still POST to the components. Add explicit guards if this must be a hard rule. |
| 3 | The locale is **session-scoped** and not stored on the user. | Switching language does not follow the user to another device/session, and the redirect drops query strings on the current path. |
| 4 | `phone` and `photo_path` columns remain **nullable** (legacy rows) even though validation requires them. | Old/seeded reports may show no photo/phone. A backfill + `NOT NULL` migration would formalise it. |
| 5 | `ReportSeeder` **is** registered in `DatabaseSeeder` (after `HotlineSeeder`). | Fresh seeds include demo reports; remove it from the seeder list if production seeding should stay minimal. |
| 6 | `x-confirm-modal` `warning`/`primary` variants are unused. | Kept as component API surface. |
| 7 | The **app layout does not load Figtree**, while `tailwind.config.js` references it (only the guest layout links it). | App pages fall back to system sans-serif; guest pages use Figtree. Add the font link to `layouts/app` for consistency. |
| 8 | `hotlines(is_active, sort_order)` and `comments(report_id, parent_id)` are indexed, but `reports.status`, `reports.created_at`, `feedback.type` and `feedback.created_at` are not. | Fine at small scale; consider indexes/additive migration as data grows. |
| 9 | Validation translations are **partial**: `lang/id/validation.php` covers the rules the portal uses, but uncommon rules fall back to English. | Cosmetic; extend the file as new rules appear. |
| 10 | `x-text-input`/`x-primary-button` use **indigo** focus rings, whereas the custom UI uses **blue**. | Cosmetic inconsistency inherited from Breeze. |
| 11 | Anonymous feedback cannot be read back at `/masukan-saya` (no account link). | The page states this explicitly. Citizens must be signed in when submitting to track responses. |
| 12 | Comment replies are limited to **one level**; replying to a reply re-attaches to the root comment. | Keeps the thread query cheap and the UI simple. |
| 13 | Hotline labels/descriptions are stored in the database in **Indonesian only**. | Admin-managed content is not translated by the ID/EN switcher; page headings and helper copy are. |
| 14 | The public map and its JSON endpoint show at most **50** reports and do not paginate. | Older reports remain reachable from the dashboard/detail pages. |

### 20.2 Performance notes

* The map no longer re-renders through Livewire: the 30-second refresh is a small JSON request that returns
  **304** when nothing changed, is skipped while the tab is hidden/offline, and redraws Leaflet only when the
  marker signature changes. The initial server render still ships the markers inline (no extra request).
* `report-detail` loads the report (with category, user, root comments, replies and status history) exactly once,
  in `with()`; `mount()` only stores the UUID. Reply mutations resolve the report and parent through the UUID.
* Eager loading is column-scoped where possible: `category:id,name`, `user:id,name`, `respondedBy:id,name`.
* `report-list` only eager-loads `user` for admins (the reporter column is admin-only).
* Maps cap the marker set at 50 reports; stats use a single grouped aggregate; the admin feedback list is capped
  at 100 rows; the citizen inbox is a single `where('user_id')` query.

### 20.3 Troubleshooting

| Symptom | Likely cause / fix |
| --- | --- |
| "Unable to locate file in Vite manifest" | Assets not built — run `npm run build` (or `npm run dev` / `composer run dev`). |
| Report photos 404 / broken | The file must exist under `storage/app/public/<photo_path>`; `photo_url` returns `null` when it does not, so no preview is rendered. A `public/storage` symlink is **not** required (photos are streamed by `laporan.foto`). |
| Map markers stop refreshing | The Leaflet `@script` needs `data-map-url` and `GET /laporan/peta` to answer 200/304. Check the browser network tab; polling pauses while the tab is hidden and resumes on focus. |
| Language switcher seems to do nothing | The `locale` is stored in the session and applied by `SetLocale`; a `SESSION_DRIVER` misconfiguration or an expired session resets it to `APP_LOCALE` (`id`). |
| Maps render blank | Leaflet CDN blocked/unreachable, or the container was re-rendered without re-init (`wire:ignore` guards this). |
| Tests error about `sqlite3`/`pdo_sqlite` extension | The PHP CLI on the dev box may lack the sqlite extensions. Enable/install them, or point the two extensions at an extracted `.so` via `php -d extension=…`. |
| Dark mode flashes white on load | The no-flash script must stay **before** the stylesheet render; don't move it. |
| `assertSee` fails on strings with `&` | The helper escapes values; assert the escaped or raw form accordingly. |

---

## 21. Deployment notes

1. **Build steps**

   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan db:seed --class=HotlineSeeder    # emergency numbers (idempotent)
   npm ci && npm run build
   php artisan config:cache route:cache view:cache
   ```

2. **Environment** — set a real `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`,
   a production database (`DB_CONNECTION`/credentials), a durable `SESSION_DRIVER`/`CACHE_STORE`
   (`database` or `redis`), a real `MAIL_MAILER`, and a real `QUEUE_CONNECTION` if jobs are added.

3. **Storage** — report photos are **streamed** by `GET /laporan/{uuid}/foto` from the `public` disk, so
   `storage:link` is optional for them. Keep `storage/app/public/reports` on persistent/shared storage. For
   multi-server setups, switch report photo storage to S3 (the `s3` disk is pre-configured) and update
   `Report::photoUrl()`/the `laporan.foto` closure accordingly.

4. **Seeding** — run `HotlineSeeder` in production (it upserts the six defaults) or add the numbers manually at
   `/admin/nomor-penting`. Do **not** run `ReportSeeder` in production; ensure `admin`/`CategorySeeder` behaviour
   is intentional, and rotate the default credentials.

5. **Laravel Cloud** — the project includes `AGENTS.md` guidance to use the `deploying-to-cloud` skill / Cloud CLI
   when targeting Laravel Cloud.

6. **Health check** — `GET /up` is available for liveness probes.

---

## Appendix A — Route → View → Component map

| Route | View | Components rendered |
| --- | --- | --- |
| `/` `home` | `welcome` | `report-stats`, `report-map` |
| `/lapor` `lapor` | `lapor` | `report-form` |
| `/laporan/{report}` `laporan.show` | `laporan-detail` | `report-detail` |
| `/masukan` `feedback` | `feedback` | `feedback-form` |
| `/masukan-saya` `masukan.saya` | `masukan-saya` | `feedback-history` |
| `/darurat` `darurat` | `darurat` | – (hotlines from the database) |
| `/dashboard` `dashboard` | `dashboard` | `report-stats` (scope `all`/`mine`), `report-list`, `feedback-summary` (admin) |
| `/profile` `profile` | `profile` | `profile.update-profile-information-form`, `profile.update-password-form`, `profile.delete-user-form` |
| `/admin/kategori` `kategori.index` | `kategori` | `kategori-admin` |
| `/admin/masukan` `masukan.index` | `masukan` | `feedback-admin` |
| `/admin/nomor-penting` `hotline.index` | `hotline` | `hotline-admin` |
| `/laporan/{uuid}` `laporan.show` | `laporan-detail` | `report-detail` |
| `/laporan/peta` `laporan.peta` | – | JSON (`markers`, `total`), consumed by `report-map` JS |
| any app page | `layouts.app` | `sidebar`, `bottom-nav`, `toast-stack` |

## Appendix B — Status & type vocabularies

| Report status | Internal | Label | Marker colour |
| --- | --- | --- | --- |
| Menunggu | `pending` | `STATUS_PENDING` | `#d97706` (amber) |
| Diproses | `processing` | `STATUS_PROCESSING` | `#1d4ed8` (blue) |
| Selesai | `resolved` | `STATUS_RESOLVED` | `#059669` (emerald) |
| Ditolak | `rejected` | `STATUS_REJECTED` | `#6b7280` (gray) |

| Feedback type | Internal | Label | Badge colour |
| --- | --- | --- | --- |
| Saran | `saran` | `TYPE_SUGGESTION` | blue |
| Kritik | `kritik` | `TYPE_COMPLAINT` | amber |
| Apresiasi | `apresiasi` | `TYPE_APPRECIATION` | emerald |
| Lainnya | `lainnya` | `TYPE_OTHER` | gray |

| Feedback state | Detection | UI |
| --- | --- | --- |
| Menunggu tanggapan | `responded_at === null` | amber badge; admin "Tanggapi" button; citizen "sedang menunggu tanggapan petugas" |
| Sudah ditanggapi | `responded_at !== null` | emerald badge; admin "Ubah tanggapan"; citizen sees the officer reply + author/time |

| Locale | Session value | Source of UI copy |
| --- | --- | --- |
| Indonesian (default) | `id` | Indonesian literals + `lang/id/*` (Breeze screens via `lang/id.json`) |
| English | `en` | `lang/en.json` (Indonesian key → English value) |
