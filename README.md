# Laravel Clean Architecture Boilerplate

> **Showcase project yang menerapkan best practices, clean code, dan design patterns di Laravel 13 — dibangun sebagai referensi arsitektur untuk memulai project baru.**

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="320" alt="Laravel Logo">
</p>

<p align="center">
  <strong>Laravel 13</strong> · <strong>PHP 8.3</strong> · <strong>Vue 3</strong> · <strong>Inertia.js</strong> · <strong>TailwindCSS 4</strong>
</p>

---

## 📋 Table of Contents

- [About](#-about)
- [Tech Stack](#-tech-stack)
- [Architecture Overview](#-architecture-overview)
- [Design Patterns](#-design-patterns)
- [Project Structure](#-project-structure)
- [Quick Start](#-quick-start)
- [Development Commands](#-development-commands)
- [Testing](#-testing)

---

## 🎯 About

Project ini adalah **Task Management SaaS** yang berfungsi sebagai showcase penerapan clean code dan design patterns di ekosistem Laravel. Setiap pattern didokumentasikan secara detail dengan code examples, rationale, dan best practices.

### Fitur Utama

- **Multi-Tenancy** — Workspace-based data isolation (Organization → Projects → Tasks)
- **Kanban Board** — Drag-and-drop task management dengan date-based columns
- **Role-Based Access** — Global (Super Admin) + Contextual (Owner/Admin/Member) roles
- **Team Management** — Invite system dengan invite code
- **Dashboard Analytics** — Real-time task & project statistics

---

## 🛠 Tech Stack

### Backend

| Package                       | Versi    | Kegunaan                        |
|-------------------------------|----------|---------------------------------|
| **Laravel Framework**         | ^13.0    | Core framework                  |
| **Inertia.js Laravel**        | ^3.0     | SPA bridge (server-driven)      |
| **Spatie Laravel Data**       | ^4.21    | DTO + Validation                |
| **Spatie Laravel Permission** | ^7.3     | Role & permission management    |
| **Spatie Laravel Query Builder** | ^7.2  | API filtering, sorting          |
| **Spatie Laravel Settings**   | ^3.7     | Persistent app settings         |
| **BenSampo Laravel Enum**     | ^6.14    | Type-safe enum constants        |
| **Laravel Sanctum**           | ^4.3     | API authentication              |
| **Laravel Pint**              | ^1.27    | Code style fixer                |

### Frontend

| Package                | Versi    | Kegunaan                          |
|------------------------|----------|-----------------------------------|
| **Vue 3**              | ^3.5     | UI framework                      |
| **Inertia.js Vue 3**   | ^3.0     | SPA adapter                       |
| **TailwindCSS**        | ^4.0     | Utility-first CSS                 |
| **Radix Vue / Reka UI**| ^1.9/^2.9| Headless UI primitives           |
| **Lucide Vue Next**    | ^1.0     | Icon library                      |
| **VueUse**             | ^14.2    | Composable utilities              |
| **Vue Draggable Plus** | ^0.6     | Drag-and-drop                     |
| **Vue Sonner**         | ^2.0     | Toast notifications               |

---

## 🏗 Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP Request                             │
├──────────┬──────────────────────────────────────────────────────┤
│          │  Middleware Layer                                     │
│          │  ├── EnsureWorkspaceSelected (multi-tenancy gate)    │
│          │  ├── ContextualRoleMiddleware (authorization)        │
│          │  └── HandleInertiaRequests (shared data)             │
├──────────┼──────────────────────────────────────────────────────┤
│          │  Controller (Thin Orchestrator)                       │
│          │  ├── Auto-resolved DTO (validation)                  │
│          │  ├── Auto-resolved QueryBuilder (filtering)          │
│          │  └── Injected Action (business logic)                │
├──────────┼──────────────────────────────────────────────────────┤
│          ▼                                                      │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────────────┐   │
│  │   Action     │  │ QueryBuilder │  │    Service           │   │
│  │  (Write)     │  │  (Read)      │  │  (Aggregation)      │   │
│  │             │  │              │  │                     │   │
│  │ • Create    │  │ • Filter     │  │ • Dashboard stats   │   │
│  │ • Update    │  │ • Sort       │  │ • Complex queries   │   │
│  │ • Delete    │  │ • Paginate   │  │ • Memoized results  │   │
│  └──────┬──────┘  └──────┬───────┘  └──────────┬──────────┘   │
│         │                │                      │              │
│         ▼                ▼                      ▼              │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                      Model Layer                         │  │
│  │  • HasOrganization Trait (Global Scope → data isolation) │  │
│  │  • Enum Casting (TaskStatus, Priority, RoleAuth)         │  │
│  │  • Scopes, Accessors, Business Methods                   │  │
│  │  • UUID Primary Keys                                     │  │
│  └──────────────────────────────────────────────────────────┘  │
│                                                                 │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │                    Support Layer                          │  │
│  │  • GetActiveOrganization (session workspace)              │  │
│  │  • UserRoleContext (org-aware role checks)                │  │
│  │  • RouteHelper (auto-load route files)                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

### Request Lifecycle

```
1. Request masuk
2. Middleware: Cek workspace selected → Cek role
3. Controller: DTO auto-validate → Action/QueryBuilder execute
4. Model: Global Scope filter by organization_id
5. Response: Inertia render Vue component + shared data
```

---

## 📚 Design Patterns

Setiap pattern didokumentasikan secara detail di `docs/patterns/`. Klik link untuk membaca penjelasan lengkap, code examples, dan best practices.

### Core Patterns

| #  | Pattern | Deskripsi | Dokumentasi |
|----|---------|-----------|-------------|
| 01 | **Action Pattern** | Single-responsibility class untuk setiap write operation. Readonly, transactional, DTO-driven. | [📖 Detail](docs/patterns/01-action-pattern.md) |
| 02 | **Data Transfer Object** | Type-safe validation & transformation menggunakan Spatie Laravel Data. Menggantikan Form Request. | [📖 Detail](docs/patterns/02-data-transfer-object.md) |
| 03 | **Enum Pattern** | Type-safe constants dengan business logic, UI metadata, dan permission groups. | [📖 Detail](docs/patterns/03-enum-pattern.md) |
| 04 | **Query Builder Pattern** | Encapsulated filter/sort/pagination logic menggunakan Spatie Query Builder. | [📖 Detail](docs/patterns/04-query-builder-pattern.md) |

### Architecture Patterns

| #  | Pattern | Deskripsi | Dokumentasi |
|----|---------|-----------|-------------|
| 05 | **Multi-Tenancy** | Organization-scoped data isolation via Global Scope + Trait. Session-based workspace switching. | [📖 Detail](docs/patterns/05-multi-tenancy.md) |
| 06 | **Policy Authorization** | Model-based access control dengan dual check (organization + role). | [📖 Detail](docs/patterns/06-policy-authorization.md) |
| 07 | **Service Pattern** | Complex read-only aggregation dengan memoization (`once()`). | [📖 Detail](docs/patterns/07-service-pattern.md) |
| 08 | **Support Helper** | Stateless utility classes untuk cross-cutting concerns. | [📖 Detail](docs/patterns/08-support-helper.md) |

### Infrastructure Patterns

| #  | Pattern | Deskripsi | Dokumentasi |
|----|---------|-----------|-------------|
| 09 | **Rich Model** | Domain models dengan scopes, accessors, casts, dan business methods. | [📖 Detail](docs/patterns/09-model-pattern.md) |
| 10 | **Route Organization** | Domain-split routes dengan auto-loading dari directory. | [📖 Detail](docs/patterns/10-route-organization.md) |
| 11 | **Testing Pattern** | Feature tests dengan workspace context, factory helpers, dan Inertia assertions. | [📖 Detail](docs/patterns/11-testing-pattern.md) |
| 12 | **Performance Indexing** | Query-driven index design dengan documented access patterns. | [📖 Detail](docs/patterns/12-performance-indexing.md) |
| 13 | **Thin Controller** | Controller sebagai orchestrator — delegates ke Action, QueryBuilder, dan Service. | [📖 Detail](docs/patterns/13-thin-controller.md) |

---

## 📁 Project Structure

```
app/
├── Actions/           → Single-responsibility write operations
│   ├── TaskCreateAction.php
│   ├── TaskUpdateAction.php
│   └── ...
├── Data/              → DTOs with validation & transformation
│   ├── TaskData.php
│   ├── ProjectData.php
│   └── ...
├── Enums/             → Type-safe constants with behavior
│   ├── TaskStatus.php
│   ├── Priority.php
│   └── RoleAuth.php
├── Http/
│   ├── Controllers/   → Thin orchestrators
│   └── Middleware/     → Multi-tenancy & auth gates
├── Models/            → Rich domain models
│   ├── Task.php
│   ├── Project.php
│   ├── Organization.php
│   └── ...
├── Policies/          → Model-based authorization
├── Providers/         → Service providers
├── QueryBuilders/     → Encapsulated query logic
│   ├── TaskIndexQuery.php
│   └── TaskKanbanQuery.php
├── Services/          → Read-only aggregation
├── Settings/          → Persistent app settings
├── Supports/          → Utility helpers
│   ├── GetActiveOrganization.php
│   ├── RouteHelper.php
│   └── UserRoleContext.php
└── Traits/            → Reusable model behaviors
    └── HasOrganization.php

routes/
├── web.php            → Global routes + auto-loader
└── web/               → Domain-split route files
    ├── auth.php
    ├── dashboard.php
    ├── projects.php
    ├── tasks.php
    └── ...

tests/
├── TestCase.php       → Base test with workspace helper
├── Feature/           → HTTP integration tests
└── Unit/              → isolated unit tests

docs/
└── patterns/          → Pattern documentation (13 articles)
```

---

## 🚀 Quick Start

### Prerequisites

- PHP >= 8.3
- Composer
- Node.js >= 18
- SQLite / MySQL / PostgreSQL

### Installation

```bash
# Clone the repo
git clone <repo-url> laravel-boilerplate
cd laravel-boilerplate

# Install all dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations + seed
php artisan migrate --seed

# Start development servers
composer dev
```

> `composer dev` menjalankan **4 proses** secara bersamaan: Laravel server, Queue worker, Log watcher, dan Vite dev server.

### Default Credentials

```
Email:    admin@example.com
Password: password
```

---

## ⚡ Development Commands

Project ini menggunakan **justfile** sebagai task runner:

```bash
just dev             # Start all dev servers
just test            # Run PHPUnit tests
just test-coverage   # Run tests with coverage
just migrate-fresh   # Fresh migration + seed
just pint            # PHP code style fixer
just lint            # Run all linters (Pint + ESLint)
just clear-cache     # Clear all Laravel caches
just tinker          # Laravel REPL
```

Atau menggunakan **Composer scripts**:

```bash
composer dev         # Start all servers (with Pail logs)
composer test        # Run tests
composer setup       # Full setup from scratch
```

---

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test
php artisan test --filter=TaskControllerTest

# Run with coverage
php artisan test --coverage
```

### Test Coverage

| Domain     | Tests |
|------------|-------|
| Tasks      | CRUD + Kanban + Move + Status + Assign |
| Projects   | CRUD + Policy |
| Comments   | Create + Delete |
| Workspaces | Create + Switch |
| Team       | Invite + Remove |

---

## 📄 License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
