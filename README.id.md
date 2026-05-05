# Laravel Architecture Patterns

> **Referensi arsitektur production-ready untuk aplikasi Laravel. Multi-tenant, type-safe, testable.**

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Laravel 13</strong> · <strong>PHP 8.4</strong> · <strong>Vue 3</strong> · <strong>Inertia.js v3</strong> · <strong>TailwindCSS 4</strong>
</p>

<p align="center">
  <a href="README.md">🇬🇧 English</a> · <a href="README.id.md">🇮🇩 Bahasa Indonesia</a>
</p>

---

## Tentang Repository Ini

Ini bukan boilerplate siap pakai. Ini adalah **kumpulan pattern dan keputusan arsitektur** yang saya gunakan berulang kali di setiap project Laravel.

Setiap pattern di sini lahir dari masalah nyata:
- Fat controller yang sulit di-test → **Action Pattern**
- Data tidak konsisten antar layer → **DTO Pattern**
- Lupa filter data tenant → **Multi-Tenancy via Global Scope**
- Logic tersebar di mana-mana → **Enum sebagai Business Logic Container**

Dokumentasi tersedia dalam **dua bahasa**:
- 🇮🇩 [`docs/patterns/`](docs/patterns/) — Bahasa Indonesia (dengan code examples lengkap)
- 🇬🇧 [`docs/patterns/en/`](docs/patterns/en/) — Versi Inggris

---

## Arsitektur Sekilas

```
Request → Middleware (auth + workspace + role)
       → Controller (thin orchestrator)
       → DTO (validate) → Action (write) / QueryBuilder (read) / Service (aggregate)
       → Model (scope + cast + business method)
       → Response (Inertia → Vue)
```

**Prinsip utama:**
- **Controller tidak berisi logic** — hanya delegasi
- **Model tidak bodoh** — berisi scopes, accessors, business methods
- **Enum bukan sekadar constant** — berisi rules, UI metadata, permission groups
- **Setiap query otomatis di-scope by organization** — tidak bisa lupa

---

## Keputusan Arsitektur

> Lihat [Architecture Decisions](docs/patterns/00-architecture-decisions.md) untuk penjelasan lengkap dan trade-off-nya.

| # | Keputusan | Satu Kalimat |
|---|-----------|--------------|
| 1 | **Action over Fat Controller** | Setiap write operation punya class sendiri |
| 2 | **DTO over Form Request** | Type-safe, reusable di luar HTTP context |
| 3 | **Enum as Logic Container** | Constants + rules + UI metadata dalam satu tempat |
| 4 | **Global Scope Multi-Tenancy** | Filter org_id otomatis, tidak bisa lupa |
| 5 | **QueryBuilder per View** | Filter/sort logic ter-encapsulate per halaman |
| 6 | **Policy + Role Dual Check** | Org membership dulu, baru role |
| 7 | **Service for Read Aggregation** | Write di Action, read complex di Service |
| 8 | **Rich Model, Bukan Anemic** | Scopes, accessors, business methods di Model |
| 9 | **UUID Primary Key** | Aman untuk multi-tenant dan public URLs |
| 10 | **Domain-Split Routes** | Route per fitur, auto-load dari directory |
| 11 | **Behavior Testing** | Test hasil, bukan implementasi |
| 12 | **Query-Driven Indexing** | Setiap index punya documented access pattern |
| 13 | **Thin Controller** | ≤ 10 baris, kalau lebih ada yang salah |

---

## Dokumentasi Pattern

### Core

| Pattern | Masalah | Solusi | [📖 Detail](docs/patterns/) |
|---------|---------|--------|------|
| **Action** | Fat controller, logic tersebar | Satu class, satu operasi | [01](docs/patterns/01-action-pattern.md) |
| **DTO** | Raw array, tidak type-safe | Spatie Data, auto-validation | [02](docs/patterns/02-data-transfer-object.md) |
| **Enum** | Hardcoded strings, logic di controller | Constants + behavior | [03](docs/patterns/03-enum-pattern.md) |
| **Query Builder** | Filter logic di controller | Encapsulated per view | [04](docs/patterns/04-query-builder-pattern.md) |

### Arsitektur

| Pattern | Masalah | Solusi | [📖 Detail](docs/patterns/) |
|---------|---------|--------|------|
| **Multi-Tenancy** | Data leakage antar org | Global Scope + Trait | [05](docs/patterns/05-multi-tenancy.md) |
| **Policy Auth** | Hardcode permission check | Org + Role dual check | [06](docs/patterns/06-policy-authorization.md) |
| **Service** | Aggregation di controller | Read-only service + memoization | [07](docs/patterns/07-service-pattern.md) |
| **Support Helper** | Utility logic bertebaran | Stateless static helpers | [08](docs/patterns/08-support-helper.md) |

### Infrastruktur

| Pattern | Masalah | Solusi | [📖 Detail](docs/patterns/) |
|---------|---------|--------|------|
| **Rich Model** | Anemic model, logic di mana-mana | Scopes + accessors + business methods | [09](docs/patterns/09-model-pattern.md) |
| **Route Org** | routes/web.php 500 baris | Split per domain + auto-load | [10](docs/patterns/10-route-organization.md) |
| **Testing** | Test lupa setup tenant context | Workspace helper + Inertia assertions | [11](docs/patterns/11-testing-pattern.md) |
| **Indexing** | Index tanpa tujuan | Documented access patterns | [12](docs/patterns/12-performance-indexing.md) |
| **Thin Controller** | Controller 200 baris | Orchestrator pattern, delegation map | [13](docs/patterns/13-thin-controller.md) |

---

## Tech Stack

### Backend

| Package | Versi | Kegunaan |
|---------|-------|----------|
| Laravel Framework | ^13.0 | Core |
| Inertia.js Laravel | ^3.0 | SPA bridge |
| Spatie Laravel Data | ^4.21 | DTO + Validation |
| Spatie Laravel Permission | ^7.3 | Role management |
| Spatie Laravel Query Builder | ^7.2 | API filtering |
| BenSampo Laravel Enum | ^6.14 | Type-safe enums |
| Laravel Sanctum | ^4.3 | API auth |

### Frontend

| Package | Versi | Kegunaan |
|---------|-------|----------|
| Vue 3 | ^3.5 | UI framework |
| Inertia.js Vue 3 | ^3.0 | SPA adapter |
| TailwindCSS | ^4.0 | Styling |
| Radix Vue / Reka UI | ^1.9 / ^2.9 | Headless UI |
| Vue Draggable Plus | ^0.6 | Drag-and-drop |

---

## Struktur Aplikasi

```
app/
├── Actions/           → Write operations (satu class = satu operasi)
├── Data/              → DTOs (validasi + transformasi)
├── Enums/             → Constants + business rules + UI metadata
├── Http/
│   ├── Controllers/   → Thin orchestrators (≤ 10 baris per method)
│   └── Middleware/     → Multi-tenancy & auth gates
├── Models/            → Rich domain models (scopes, accessors, business methods)
├── Policies/          → Per-model authorization
├── QueryBuilders/     → Per-view query logic (filter, sort, paginate)
├── Services/          → Read-only aggregation
├── Supports/          → Stateless utility helpers
└── Traits/            → Reusable model behaviors (HasOrganization)

routes/
└── web/               → Domain-split route files (auto-loaded)

tests/
└── Feature/           → HTTP integration tests dengan workspace context

docs/
├── patterns/          → 13 pattern docs (Indonesia + English)
│   └── en/            → Versi Inggris
└── adr/               → Architecture Decision Records (jika diperlukan)
```

---

## Quick Start

```bash
git clone <repo-url> && cd laravel-boilerplate
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
composer dev
```

---

## Testing

```bash
php artisan test                    # Semua test
php artisan test --filter=Task      # Filter per domain
php artisan test --compact          # Compact output
```

---

## License

MIT
