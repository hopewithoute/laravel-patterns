# Laravel Architecture Patterns

> **Production-ready reference architecture for Laravel applications. Multi-tenant, type-safe, testable.**

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

## About This Repository

This is not a boilerplate. This is a **collection of architecture patterns and decisions** that I use repeatedly across Laravel projects.

Each pattern here was born from real problems:
- Fat controllers that are hard to test → **Action Pattern**
- Inconsistent data between layers → **DTO Pattern**
- Forgetting to filter tenant data → **Multi-Tenancy via Global Scope**
- Logic scattered everywhere → **Enum as Business Logic Container**

Documentation is available in **two languages**:
- 🇮🇩 [`docs/patterns/`](docs/patterns/) — Bahasa Indonesia (with full code examples)
- 🇬🇧 [`docs/patterns/en/`](docs/patterns/en/) — English version

---

## Architecture at a Glance

```
Request → Middleware (auth + workspace + role)
       → Controller (thin orchestrator)
       → DTO (validate) → Action (write) / QueryBuilder (read) / Service (aggregate)
       → Model (scope + cast + business method)
       → Response (Inertia → Vue)
```

**Core principles:**
- **Controllers contain no logic** — only delegation
- **Models are not anemic** — they hold scopes, accessors, business methods
- **Enums are not just constants** — they hold rules, UI metadata, permission groups
- **Every query is auto-scoped by organization** — it's impossible to forget

---

## Architecture Decisions

> See [Architecture Decisions](docs/patterns/en/00-architecture-decisions.md) for full rationale and trade-offs.

| # | Decision | One-liner |
|---|----------|-----------|
| 1 | **Action over Fat Controller** | Every write operation has its own class |
| 2 | **DTO over Form Request** | Type-safe, reusable outside HTTP context |
| 3 | **Enum as Logic Container** | Constants + rules + UI metadata in one place |
| 4 | **Global Scope Multi-Tenancy** | org_id filtered automatically, impossible to forget |
| 5 | **QueryBuilder per View** | Filter/sort logic encapsulated per page |
| 6 | **Policy + Role Dual Check** | Org membership first, then role |
| 7 | **Service for Read Aggregation** | Write in Action, complex read in Service |
| 8 | **Rich Model, Not Anemic** | Scopes, accessors, business methods in Model |
| 9 | **UUID Primary Key** | Safe for multi-tenant and public URLs |
| 10 | **Domain-Split Routes** | Routes per feature, auto-loaded from directory |
| 11 | **Behavior Testing** | Test outcomes, not implementations |
| 12 | **Query-Driven Indexing** | Every index has a documented access pattern |
| 13 | **Thin Controller** | ≤ 10 lines, if more something's wrong |

---

## Pattern Documentation

### Core

| Pattern | Problem | Solution | [📖 Detail](docs/patterns/en/) |
|---------|---------|----------|------|
| **Action** | Fat controller, scattered logic | One class, one operation | [01](docs/patterns/en/01-action-pattern.md) |
| **DTO** | Raw arrays, not type-safe | Spatie Data, auto-validation | [02](docs/patterns/en/02-data-transfer-object.md) |
| **Enum** | Hardcoded strings, logic in controller | Constants + behavior | [03](docs/patterns/en/03-enum-pattern.md) |
| **Query Builder** | Filter logic in controller | Encapsulated per view | [04](docs/patterns/en/04-query-builder-pattern.md) |

### Architecture

| Pattern | Problem | Solution | [📖 Detail](docs/patterns/en/) |
|---------|---------|----------|------|
| **Multi-Tenancy** | Data leakage between orgs | Global Scope + Trait | [05](docs/patterns/en/05-multi-tenancy.md) |
| **Policy Auth** | Hardcoded permission checks | Org + Role dual check | [06](docs/patterns/en/06-policy-authorization.md) |
| **Service** | Aggregation in controller | Read-only service + memoization | [07](docs/patterns/en/07-service-pattern.md) |
| **Support Helper** | Utility logic scattered | Stateless static helpers | [08](docs/patterns/en/08-support-helper.md) |

### Infrastructure

| Pattern | Problem | Solution | [📖 Detail](docs/patterns/en/) |
|---------|---------|----------|------|
| **Rich Model** | Anemic model, logic everywhere | Scopes + accessors + business methods | [09](docs/patterns/en/09-model-pattern.md) |
| **Route Org** | routes/web.php at 500 lines | Split per domain + auto-load | [10](docs/patterns/en/10-route-organization.md) |
| **Testing** | Tests forget tenant context | Workspace helper + Inertia assertions | [11](docs/patterns/en/11-testing-pattern.md) |
| **Indexing** | Indexes without purpose | Documented access patterns | [12](docs/patterns/en/12-performance-indexing.md) |
| **Thin Controller** | Controllers at 200 lines | Orchestrator pattern, delegation map | [13](docs/patterns/en/13-thin-controller.md) |

---

## Tech Stack

### Backend

| Package | Version | Purpose |
|---------|---------|---------|
| Laravel Framework | ^13.0 | Core |
| Inertia.js Laravel | ^3.0 | SPA bridge |
| Spatie Laravel Data | ^4.21 | DTO + Validation |
| Spatie Laravel Permission | ^7.3 | Role management |
| Spatie Laravel Query Builder | ^7.2 | API filtering |
| BenSampo Laravel Enum | ^6.14 | Type-safe enums |
| Laravel Sanctum | ^4.3 | API auth |

### Frontend

| Package | Version | Purpose |
|---------|---------|---------|
| Vue 3 | ^3.5 | UI framework |
| Inertia.js Vue 3 | ^3.0 | SPA adapter |
| TailwindCSS | ^4.0 | Styling |
| Radix Vue / Reka UI | ^1.9 / ^2.9 | Headless UI |
| Vue Draggable Plus | ^0.6 | Drag-and-drop |

---

## Project Structure

```
app/
├── Actions/           → Write operations (one class = one operation)
├── Data/              → DTOs (validation + transformation)
├── Enums/             → Constants + business rules + UI metadata
├── Http/
│   ├── Controllers/   → Thin orchestrators (≤ 10 lines per method)
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
└── Feature/           → HTTP integration tests with workspace context

docs/
├── patterns/          → 13 pattern docs (Indonesian + English)
│   └── en/            → English versions
└── adr/               → Architecture Decision Records (if needed)
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
php artisan test                    # All tests
php artisan test --filter=Task      # Filter by domain
php artisan test --compact          # Compact output
```

---

## License

MIT
