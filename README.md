# Lapor Yuk! — Technical Documentation

> **Lapor Yuk!** is a community reporting portal for **environmental problems** and **public-facility concerns**
> (pollution, waste, broken roads/lighting, neglected public facilities, utilities, etc.).
> It is **not** an emergency channel — fires, accidents and medical emergencies are redirected to a static
> "important phone numbers" page. Reports can be submitted **without an account**, and citizens can also send
> **feedback & suggestions** (optional anonymity). Admins (petugas) review reports and feedback from a dashboard.

This document is the deep-dive reference for the codebase: architecture, data model, every route, every component,
validation rules, the feature set, the test suite, conventions and known quirks.

**Companion documents**

| File | Purpose |
| --- | --- |
| `README.md` | Boilerplate Laravel readme (framework intro). |
| `AGENTS.md` | Instructions for AI coding agents working in this repo (Laravel Boost rules, project conventions). |
| `DOCUMENTATION.md` | **This file** — full application documentation. |

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
| **Masukan (Feedback)** | Free-form feedback/suggestion/complaint/appreciation. Supports **anonymous** submissions. |
| **Tanggapan (Comment)** | Threaded public comment on a report (requires login). |
| **Riwayat Status (Status History)** | Immutable audit trail of every status change, with optional staff notes. |
| **Nomor Penting (Hotlines)** | Static reference page of emergency/service phone numbers. |
| **Petugas / Admin** | User with `role = admin`; reviews reports and feedback, manages categories. |
| **Warga (Citizen)** | Regular user (`role = warga`); can report, comment and track their own reports. |
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

* The portal is **explicitly not** an emergency service. `/darurat` lists hotlines (112, 110, 113, 119, 115, 123)
  and calls out that the portal is for non-emergency reports only.
* Emergency-only concepts were removed: there is no `/sos` route and the `urgency_level` column was dropped from
  `categories`.
* Admins **cannot** submit reports or feedback through the UI: report/feedback submission actions are hidden from
  admin navigation and dashboard; admins instead see "Masukan Warga" and "Kelola Kategori".

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
| JS entry | `resources/js/app.js` — intentionally empty (`//`); Alpine/Livewire ship from the Livewire package |

> **Note:** SweetAlert2 was previously loaded from a CDN but never used; it was removed. Toast notifications are
> implemented natively (Alpine + Tailwind) — see [§16](#16-notifications--confirmation-dialogs).

### 2.3 Application config defaults (`.env.example`)

| Key | Value | Meaning |
| --- | --- | --- |
| `APP_NAME` | `Lapor Yuk!` | Displayed in `<title>`. |
| `APP_URL` | `http://localhost:8000` | Also used to build public photo URLs. |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `en` | Some auth strings remain English. |
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

# 4. Public storage symlink — REQUIRED for report photos
php artisan storage:link

# 5. Seed demo data (optional)
php artisan db:seed                     # DatabaseSeeder: test user + admin + categories
php artisan db:seed --class=ReportSeeder # optional: 9 demo reports (manual only)

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
  ├─ GET / , /lapor , /masukan , /darurat , /laporan/{id}      → plain Blade view wrappers
  │                                                            (routes/web.php → Route::view / closure)
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
  only for client-side interactions (theme toggle, modal, toasts, Leaflet wiring).
* **No API layer.** Everything is server-rendered; there are no JSON/API routes (Laravel's `shouldRenderJsonWhen`
  is configured for `api/*`/`expectsJson` but no API routes exist).

### 4.2 Where logic lives

| Concern | Location |
| --- | --- |
| HTTP routing | `routes/web.php`, `routes/auth.php` |
| Admin authorization middleware | `app/Http/Middleware/EnsureUserIsAdmin.php` (alias `admin`) |
| Domain rules (scopes, accessors, hooks) | `app/Models/*.php` |
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
│   │   └── Middleware/EnsureUserIsAdmin.php     # alias: admin
│   ├── Livewire/
│   │   ├── Actions/Logout.php                   # invokable logout action
│   │   └── Forms/LoginForm.php                  # login form object + rate limiting
│   ├── Models/{User,Category,Report,Comment,StatusHistory,Feedback}.php
│   ├── Providers/{AppServiceProvider,VoltServiceProvider}.php
│   └── View/Components/AppLayout.php            # <x-app-layout> → layouts.app
├── bootstrap/{app.php,providers.php}
├── config/                                      # standard Laravel config
├── database/
│   ├── factories/{User,Category,Report,Feedback}Factory.php
│   ├── migrations/                              # see §8.3
│   └── seeders/{DatabaseSeeder,admin,CategorySeeder,ReportSeeder}.php
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
│       ├── kategori.blade.php                   # admin category management page
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
| View hotlines (`/darurat`) | ✅ | ✅ | ✅ |
| Dashboard (`/dashboard`) | ❌ → login | ✅ | ✅ |
| See **all** reports in dashboard list | – | ❌ (own only) | ✅ |
| Update report status | – | ❌ (`addError` + error toast) | ✅ |
| See reporter name + contact phone on detail | – | – | ✅ |
| Review feedback (`/admin/masukan`) | ❌ → login | ❌ 403 | ✅ |
| Manage categories (`/admin/kategori`) | ❌ → login | ❌ 403 | ✅ |

### 6.2 Enforcement layers

1. **Route middleware** — `/dashboard` and `/profile` require `auth` + `verified`; the whole `/admin/*` group
   requires `auth`, `verified` and `admin`.
2. **`admin` middleware** (`EnsureUserIsAdmin`) — `abort_unless($request->user()?->isAdmin(), 403)`.
3. **In-component guards** — defense in depth:
   * `masukan-admin::mount()` → `abort_unless(Auth::user()?->isAdmin(), 403)`.
   * `kategori-admin::{create,update,delete}()` → private `guardAdmin()` → `abort_unless(...)`.
   * `report-detail::updateStatus()` → adds a validation error + error toast for non-admins.

> **Note:** admins are only *prevented by UI* from submitting reports/feedback — there is no server-side guard on
> `ReportForm` / `FeedbackForm`. This is a deliberate product decision (menu removal), not a security boundary.

---

## 7. Routing reference

All routes are defined in `routes/web.php` and `routes/auth.php` (`routes/auth.php` is `require`d at the bottom of
`web.php`). Full output of `php artisan route:list` (application routes; vendor routes omitted):

| Method | URI | Name | Action | Middleware |
| --- | --- | --- | --- | --- |
| GET | `/` | `home` | `view:welcome` | web |
| GET | `/darurat` | `darurat` | `view:darurat` | web |
| GET | `/lapor` | `lapor` | `view:lapor` | web |
| GET | `/masukan` | `feedback` | `view:feedback` | web |
| GET | `/laporan/{report}` | `laporan.show` | closure → `view:laporan-detail` (route-model binding) | web |
| POST | `/logout` | `logout` | closure → `Logout` action | web, **auth** |
| GET | `/dashboard` | `dashboard` | `view:dashboard` | web, auth, verified |
| GET | `/profile` | `profile` | `view:profile` | web, auth, verified |
| GET | `/admin/kategori` | `kategori.index` | `view:kategori` | web, auth, verified, **admin** |
| GET | `/admin/masukan` | `masukan.index` | `view:masukan` | web, auth, verified, **admin** |

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

> **Route model binding:** `laporan.show` binds `Report` by its primary key; the closure passes `$report` into the
> `laporan-detail` view.

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
     └──────────────►┌───────────┐
                  *  │ feedback  │  (user_id nullable → SET NULL on user delete)
                     │ id        │
                     │ type      │
                     │ name      │
                     │ contact   │
                     │ message   │
                     └───────────┘
```

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
| `title` | string | |
| `phone` | string nullable | legacy rows may be null; **required by validation** |
| `description` | text | |
| `photo_path` | string nullable | legacy rows may be null; **required by validation** |
| `latitude` | decimal(10,8) | cast to `float` |
| `longitude` | decimal(11,8) | cast to `float` |
| `status` | enum `pending,processing,resolved,rejected` default `pending` | |
| `created_at`/`updated_at` | timestamps | |

Indexes: `user_id`, `category_id` (from FK constraints). No dedicated index on `status`/`created_at`.

#### `comments`
| Column | Type | Notes |
| --- | --- | --- |
| `id` | bigint PK | |
| `report_id` | FK → `reports.id`, cascade delete | |
| `user_id` | FK → `users.id`, cascade delete | |
| `body` | text | |
| timestamps | | |

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
| timestamps | | |

> The model class `App\Models\Feedback` maps to the table `feedback` (the word "feedback" is uncountable, so
> Laravel's pluralizer leaves it unchanged). The table is named `feedback`, **not** `feedbacks`.

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

> `make_user_id_nullable` migrations use `->change()`, which requires Doctrine DBAL on older Laravel versions;
> Laravel 11+ supports it natively. On MySQL these are fine; sqlite tests run against `:memory:`.

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
    protected function statusLabel(): Attribute;  // $report->status_label
    protected function photoUrl(): Attribute;     // $report->photo_url (public disk URL)

    public function markerColor(): string;   // hex colour per status
    public function createdAgo(): string;    // "Baru saja" / "n menit lalu" / "n jam lalu" / "Kemarin" / "d M Y"
    public function toMapMarker(): array;    // serialised marker payload for Leaflet
}
```

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

**`toMapMarker()` shape** (consumed by the Leaflet `@script` in `report-map`):

```php
[
  'id' => int, 'title' => string, 'category' => string, 'status' => string,
  'status_label' => string, 'latitude' => float, 'longitude' => float,
  'color' => string, 'url' => string (route laporan.show), 'created_ago' => string,
]
```

### 9.4 `App\Models\Comment`

`#[Fillable(['report_id','user_id','body'])]`, `report()` and `user()` BelongsTo relations.

### 9.5 `App\Models\StatusHistory`

`#[Fillable(['report_id','user_id','status','notes'])]`, `report()` and `user()` BelongsTo relations.
`user_id` is nullable so the automatic first entry (anonymous report) can be attributed to "Sistem".

### 9.6 `App\Models\Feedback`

```php
#[Fillable(['user_id','type','name','contact','message'])]
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

    public function user(): BelongsTo;
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
* **Live updates:** `wire:poll.30s="refresh"` re-queries and re-pushes markers every 30 seconds.
* **Recent list:** the 6 newest matching reports with status badge and relative age.
* **Bottom CTA:** "Buat laporan baru" shown to non-admins only.
* **Query behaviour:** the reports query is **memoised per request** in a private cache property, so the poll/update
  cycle performs a single query instead of two (see [§20](#20-known-constraints-quirks--troubleshooting)).

### 10.2 Report statistics — `report-stats`

* A single `GROUP BY status` aggregate query produces totals.
* Renders 4 cards: **Laporan masuk** (total), **Menunggu**, **Diproses**, **Selesai**.
* `wire:poll.30s` keeps the numbers live.
* Note: statistics are **global**, even for citizens viewing their dashboard.

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
2. **Tanggapan warga (comments)** — public, newest first. Logged-in users get a "Tulis tanggapan" form
   (`addComment`, max 1000 chars). Guests see a "Masuk untuk menanggapi" prompt.
3. **Location mini-map** — a Leaflet map (`wire:ignore`) with a marker coloured by status, plus coordinates.
4. **Riwayat status (status history)** — timeline with coloured dots per status, timestamp, author (or "Sistem")
   and notes.
5. **Status update form (admins only)** — a status `<select>` (all four statuses) and optional notes (max 500).
   Submitting calls `updateStatus()`, which updates the report, appends the history entry (default note
   "Status diperbarui oleh {admin}.") and shows a success toast.
6. **Hotline CTA** — link to `/darurat`.

Query behaviour: `mount()` only stores the id; the report with `category`, `user`, `comments.user` and
`statusHistories.user` is loaded exactly **once** in `with()`.

### 10.5 Dashboard — `/dashboard` (`report-stats` + `report-list`)

* **Role-aware header:** "Pusat Kendali Petugas / Pemantauan Laporan Warga" (admin) vs "Laporan Saya /
  Riwayat Laporan Anda" (citizen).
* Stats row (see §10.2).
* **Report list (`report-list`)** — paginated (8 per page), `wire:poll.30s`:
  * Admins see **all** reports plus a "Pelapor" column (or "Anonim").
  * Citizens see **only their own** reports; the reporter column is hidden.
  * Empty states differ per role (admins: "Belum ada laporan yang masuk.").
* **Quick actions** (role-gated): citizens get "Laporkan masalah…" and "Kirim masukan…"; admins get
  "Lihat masukan warga" and "Kelola kategori". Everyone gets hotlines + map links.
* **Status legend card** explaining pending/processing/resolved.

### 10.6 Feedback submission — `/masukan` (`feedback-form`)

* Open to guests and citizens; anonymous submission supported.
* **Fields:** type (`saran`/`kritik`/`apresiasi`/`lainnya`), message (10–2000 chars), optional name, optional contact.
* Blank name/contact are normalised to `null` (so anonymous rows stay clean).
* On success: a thank-you card with a "Kirim masukan lain" button (`resetForm()`), plus a success toast.
* Informational cards clarify anonymity, that it is *not* a report channel, and that all feedback is read.

### 10.7 Admin feedback review — `/admin/masukan` (`masukan-admin`)

* Admin-only; component `mount()` aborts with 403 for non-admins.
* **Type tabs** with counts: `Semua (n)` plus one tab per type (counts from a grouped aggregate query).
* **Live search** across `message`, `name` and `contact` (debounced 300 ms), with pagination (10 per page).
* Each entry card shows the type badge, timestamp, the message (preserving line breaks), an avatar initial
  (name → linked user name → "A"), the contact ("Tanpa kontak") and whether it came from a registered user
  ("Warga terdaftar") or an anonymous sender ("Tanpa akun").
* Read-only — there are no edit/delete actions.

### 10.8 Category management — `/admin/kategori` (`kategori-admin`)

* Admin-only (`guardAdmin()` in every mutating method).
* **Create:** name required, ≤120 chars, unique. Slug is generated with `Str::slug()` and de-duplicated with a
  numeric suffix (`kategori`, `kategori-2`, …).
* **Rename:** inline row editing; name unique (ignoring the current row); slug regenerated & de-duplicated.
* **Delete:** opens a confirmation modal (`x-confirm-modal`); deletion is **blocked** if the category is used by
  any report (uses `withCount('reports')`), surfacing an error toast with the usage count.
* The table lists each category with its slug and report count.

### 10.9 Hotlines — `/darurat`

* Static page (no login required). Lists six numbers with `tel:` links:
  112 (darurat nasional), 110 (kepolisian), 113 (pemadam kebakaran), 119 (ambulans & medis),
  115 (Basarnas/SAR), 123 (PLN).
* Explains the portal is for non-emergency reports and includes a 3-step "when to use the portal" guide.
* Submission CTAs (report/feedback) are hidden from admins.

### 10.10 Role-aware navigation (`sidebar`, `bottom-nav`)

* Desktop: left `sidebar` (sticky, hidden below `lg`). Mobile: top header + fixed `bottom-nav` (hidden at `lg`+).
* Menu items are gated by `$user?->isAdmin()`:
  * Everyone: Peta Warga, Dashboard ("Pusat Kendali"/"Laporan Saya"), Nomor Penting.
  * Guests/citizens: Buat Laporan, Masukan & Saran.
  * Admins: Masukan Warga, Kelola Kategori.
* Sidebar footer shows the avatar/initial, name, role label, Profil / Keluar buttons (or Masuk / Daftar for guests).

---

## 11. Livewire / Volt component reference

All components are **class-based Volt single-file components** under `resources/views/livewire/`.
Files start with a `<?php … ?>` block (`new class extends Component { … }`) followed by the Blade template,
using inline `with()` methods to pass render data.

### 11.1 Public components

| Component | Tag | Key state | Key methods | Notable behaviour |
| --- | --- | --- | --- | --- |
| `report-map` | `<livewire:report-map />` | `search`, `status`, `category`, private `$reportsCache` | `updated()`, `refresh()`, `resetFilters()`, `reports()`, `dispatchMarkers()` | Leaflet map, 30 s poll, memoised query, pushes `report-map-updated` |
| `report-stats` | `<livewire:report-stats />` | – | `with()` | grouped status counts, 30 s poll |
| `report-list` | `<livewire:report-list />` | pagination | `with()` | admin = all + reporter column; citizen = own only; 8/page; 30 s poll |
| `report-form` | `<livewire:report-form />` | `mapId`, `title`, `description`, `phone`, `category_id`, `$photo`, `latitude`, `longitude`, `submittedReportId` | `mount()`, `submit()`, `removePhoto()`, `resetForm()` | photo upload + preview, Leaflet pin, toast, anonymous support |
| `report-detail` | `<livewire:report-detail :report-id="$id" />` | `reportId`, `status`, `notes`, `comment` | `mount()`, `addComment()`, `updateStatus()`, `with()` | comments, status history, admin status form, Leaflet mini-map |
| `feedback-form` | `<livewire:feedback-form />` | `type`, `message`, `name`, `contact`, `submitted` | `submit()`, `resetForm()` | anonymous-friendly |
| `masukan-admin` | `<livewire:masukan-admin />` | `type`, `search` + pagination | `mount()` (403), `setType()`, `updatedSearch()`, `with()` | admin-only review, tabs + counts, search, 10/page |
| `kategori-admin` | `<livewire:kategori-admin />` | `name`, `editingId`, `editName`, `confirmingDeleteId`, `confirmingDeleteName` | `createCategory()`, `startEditing()`, `cancelEditing()`, `updateCategory()`, `confirmDelete()`, `deleteCategory()` | CRUD + unique slug + delete guard |
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
| `x-primary-button` | – | Dark indigo submit button. |
| `x-danger-button` | – | Red submit button. |

Layouts:

| Component | Resolves to | Used by |
| --- | --- | --- |
| `<x-app-layout>` | `App\View\Components\AppLayout` → `layouts.app` | all app pages |
| `#[Layout('layouts.guest')]` | `layouts.guest` (direct view name — **not** a component) | auth pages |

`layouts.app` includes the sidebar, mobile header, bottom nav, toast stack, `@livewireStyles`/`@livewireScripts`,
and the Leaflet CDN assets. `layouts.guest` is a centred card layout with the brand in the header.

> The Breeze scaffolding components that were unused (`dropdown`, `dropdown-link`, `modal`, `nav-link`,
> `responsive-nav-link`, `secondary-button`, `application-logo`, `action-message`) and the unused
> `App\View\Components\GuestLayout` class were removed during the cleanup pass.

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
| `updateStatus` | `status` | `required`, `in:pending,processing,resolved,rejected` |
| `updateStatus` | `notes` | `nullable`, `string`, `max:500` |

### 13.4 Categories (`kategori-admin`)

| Method | Field | Rules |
| --- | --- | --- |
| `createCategory` | `name` | `required`, `string`, `max:120`, `unique:categories,name` |
| `updateCategory` | `editName` | `required`, `string`, `max:120`, `unique:categories,name` (ignoring current id) |

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
  1. `report-map` → `#warga-map` (all reports, filters + poll).
  2. `report-detail` → `#report-location-map` (single report).
  3. `report-form` → dynamically named container (`report-map-<uniqid>`) for pin placement.
* Tiles: OpenStreetMap standard tiles.
* `resources/css/app.css` gives `.leaflet-container` `z-index: 0` so it sits under the app chrome.

### 14.5 Livewire ↔ JavaScript bridges

| Event (browser) | Dispatched by | Consumed by |
| --- | --- | --- |
| `notify` (`$event.detail` = `{type,title,message,duration}`) | any component via `$this->dispatch('notify', …)` | `x-toast-stack` |
| `report-map-updated` (`markers`) | `report-map::dispatchMarkers()` | the `report-map` `@script` (`draw()`) |
| `report-submitted` | `report-form::submit()/resetForm()` | the `report-form` `@script` (removes pin, resets view) |
| `open-modal` / `close-modal` (`detail` = modal name) | components (e.g. `kategori-admin`) | `x-confirm-modal` |

---

## 15. Authentication & profile

* **Stack:** Laravel Breeze (2.x) auth scaffolding implemented with **Volt** single-file components and the
  `guest` layout. Session-based (`web` guard).
* **Login:** `LoginForm` form object, rate-limited to 5 failed attempts per `email|ip` with a `Lockout` event.
* **Registration:** creates a `warga` (default role) account and logs in, redirecting to the dashboard.
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
| `ReportSeeder` | ❌ (manual) | If no reports exist, creates ~9 demo reports around Medan (no phone/photo). Run explicitly with `php artisan db:seed --class=ReportSeeder`. |

Canonical categories: Pencemaran Sungai & Air · Pencemaran Udara · Sampah & Limbah Liar ·
Kerusakan Lingkungan & Ruang Terbuka Hijau · Infrastruktur Jalan & Jembatan Rusak · Penerangan Jalan Umum ·
Fasilitas Publik Terbengkalai · Masalah Utilitas (Listrik, Air, Gas) · Fasilitas Umum & Ruang Publik ·
Laporan Lainnya.

### 17.2 Factories (`database/factories/`)

| Factory | Default state | States |
| --- | --- | --- |
| `UserFactory` | random name/email, verified, password `password` | `unverified()` |
| `CategoryFactory` | title-cased 2-word name + unique slug | – |
| `ReportFactory` | sentence title, paragraph description, random Medan coords, `pending`, null phone/photo | `anonymous()`, `processing()`, `resolved()` |
| `FeedbackFactory` | random type, name + optional email contact | `anonymous()` |

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
| `Feature/FeedbackManagementTest` | admin review access control, listing, anonymity label, type filter, search, nav gating (11 tests) |
| `Feature/FeedbackSubmissionTest` | guest/anonymous submit, user linkage, min length, type whitelist, null normalisation (6 tests) |
| `Feature/HotlinePageTest` | public hotline page, auth requirement for dashboard, "not an emergency channel", `/sos` 404 |
| `Feature/NotificationTest` | toast container rendered, flashed notification replayed |
| `Feature/ProfileTest` | profile page, update info, email verification state, account deletion + password check |
| `Feature/ReportDetailTest` | public detail, comment (auth/guest), admin status update + history, non-admin blocked, auto history |
| `Feature/ReportMapTest` | public listing for guests, status filter, title search, reset filters |
| `Feature/ReportSubmissionTest` | guest/user submit, required fields/phone/photo, image rule, invalid phone chars, preview, remove preview, notification |
| `Feature/ExampleTest` | `/` returns 200 |
| `Unit/ExampleTest` | placeholder |

**Suite size (last run): 83 tests, 264 assertions, all passing.**

### 18.3 Testing conventions in this repo

* Feature tests use factories (e.g. `Report::factory()->processing()->create()`).
* `assoc` models for admin: `User::factory()->create(['role' => 'admin'])` (there is no `admin()` state).
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
* PHPDoc/array-shape annotations over inline comments for non-obvious types.
* Descriptive names (`isRegisteredForDiscounts`, not `discount()`).
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
| 3 | `report-stats` shows **global** counts even on a citizen's dashboard. | Citizens see totals across all reports. Pass a scoped query if per-user stats are desired. |
| 4 | `phone` and `photo_path` columns remain **nullable** (legacy rows) even though validation requires them. | Old/seeded reports may show no photo/phone. A backfill + `NOT NULL` migration would formalise it. |
| 5 | `ReportSeeder` is **not** registered in `DatabaseSeeder`. | It is a manual dev utility (`--class=ReportSeeder`); intentionally retained. |
| 6 | `x-confirm-modal` `warning`/`primary` variants are unused. | Kept as component API surface. |
| 7 | The **app layout does not load Figtree**, while `tailwind.config.js` references it (only the guest layout links it). | App pages fall back to system sans-serif; guest pages use Figtree. Add the font link to `layouts/app` for consistency. |
| 8 | No dedicated DB indexes on `reports.status`, `reports.created_at`, `feedback.type`, `feedback.created_at`. | Fine at small scale; consider indexes/additive migration as data grows. |
| 9 | Some auth UI strings remain in **English** (`__('Email')`, `__('Password')`, forgot-password copy) while the rest of the app is Indonesian. | Cosmetic; localise if desired. |
| 10 | `x-text-input`/`x-primary-button` use **indigo** focus rings, whereas the custom UI uses **blue**. | Cosmetic inconsistency inherited from Breeze. |

### 20.2 Performance notes

* `report-map` **memoises** the filtered reports query in a private `$reportsCache` property so the 30-second poll
  and filter changes run a single query per request instead of two.
* `report-detail` loads the report (with all relations) exactly once, in `with()`; `mount()` only stores the id.
* Eager loading is column-scoped where possible: `category:id,name`, `user:id,name`.
* `report-list` only eager-loads `user` for admins (the reporter column is admin-only).
* Maps cap the marker set at 50 reports; stats use a single grouped aggregate; the feedback list paginates at 10.

### 20.3 Troubleshooting

| Symptom | Likely cause / fix |
| --- | --- |
| "Unable to locate file in Vite manifest" | Assets not built — run `npm run build` (or `npm run dev` / `composer run dev`). |
| Report photos 404 / broken | Missing `php artisan storage:link`, or `APP_URL` is wrong (photo URLs derive from it). |
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
   php artisan storage:link
   npm ci && npm run build
   php artisan config:cache route:cache view:cache
   ```

2. **Environment** — set a real `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, a production `APP_URL`,
   a production database (`DB_CONNECTION`/credentials), a durable `SESSION_DRIVER`/`CACHE_STORE`
   (`database` or `redis`), a real `MAIL_MAILER`, and a real `QUEUE_CONNECTION` if jobs are added.

3. **Storage** — `storage:link` is mandatory for report photos. For multi-server setups, switch report photo
   storage to S3 (the `s3` disk is pre-configured) and update `Report::photoUrl()`/storage calls accordingly.

4. **Seeding** — do **not** run `ReportSeeder` in production; ensure `admin`/`CategorySeeder` behaviour is
   intentional, and rotate the default credentials.

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
| `/darurat` `darurat` | `darurat` | – (static) |
| `/dashboard` `dashboard` | `dashboard` | `report-stats`, `report-list` |
| `/profile` `profile` | `profile` | `profile.update-profile-information-form`, `profile.update-password-form`, `profile.delete-user-form` |
| `/admin/kategori` `kategori.index` | `kategori` | `kategori-admin` |
| `/admin/masukan` `masukan.index` | `masukan` | `masukan-admin` |
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
