# Keputusan Arsitektur

> Keputusan teknis utama di balik codebase ini dan alasan di balik masing-masing.

---

## 1. Action over Fat Controller

**Keputusan:** Setiap operasi write tinggal di class `Action` terpisah, bukan di controller.

**Mengapa:**
- Single responsibility — satu class, satu tugas
- Bisa di-test tanpa HTTP layer
- Bisa dipakai ulang di controller, command, dan job

**Trade-off:** Lebih banyak file. Diterima karena kejelasan > kenyamanan.

```
Controller → menerima request
Action     → menjalankan business logic
Model      → menyimpan data
```

---

## 2. DTO over Form Request

**Keputusan:** Pakai Spatie Laravel Data DTO, bukan Form Request untuk validasi.

**Mengapa:**
- Type-safe properties (IDE autocomplete works)
- Bisa dipakai di luar konteks HTTP (job, command)
- Transformasi built-in via `toModelData()`

**Trade-off:** External dependency. Diterima karena DX improvement signifikan.

**Konvensi:**
- `{Domain}Data` untuk CRUD
- `{Domain}{Operation}Data` untuk operasi spesifik (misal: `TaskMoveData`)

---

## 3. Enum sebagai Container Business Logic

**Keputusan:** Enum tidak hanya menyimpan value — mereka encapsulate business rules, UI metadata, dan authorization groups.

**Mengapa:**
- Single source of truth untuk status colors, priority weights, role permissions
- Validasi rules otomatis derive dari enum values
- UI options (`asOptions()`) se-locate dengan constants

**Contoh:** `TaskStatus::openStatuses()` dipakai di queries, validasi, dan UI filters — didefinisikan sekali.

---

## 4. Multi-Tenancy via Global Scope

**Keputusan:** Isolasi organisasi melalui trait `HasOrganization` dengan global scope otomatis.

**Mengapa:**
- Zero chance lupa `WHERE organization_id = ?`
- Transparan untuk developer — `Task::all()` langsung jalan
- Escape hatch via `withoutOrganizationScope()` untuk admin views

**Trade-off:** Implicit behavior bisa mengejutkan developer baru. Dimitasi dengan dokumentasi dan naming.

**Session over URL:** Workspace aktif disimpan di session, bukan di route. URL lebih bersih, switching lebih mudah.

---

## 5. QueryBuilder over Raw Query Logic

**Keputusan:** Extend Spatie QueryBuilder ke class domain-specific (`TaskIndexQuery`, `TaskKanbanQuery`).

**Mengapa:**
- Controller tetap bersih — cukup `$query->paginate(15)`
- Filter/sort logic reusable dan testable
- Scope delegation jaga model sebagai single source of truth

**Konvensi:** `{Domain}{View}Query` — misal: `TaskIndexQuery` untuk halaman list, `TaskKanbanQuery` untuk board.

---

## 6. Policy dengan Org + Role Dual Check

**Keputusan:** Setiap policy check adalah `belongsToOrganization() && hasRole()`.

**Mengapa:**
- Organization membership selalu diverifikasi dulu
- Permission roles didefinisikan di `RoleAuth` enum, bukan hardcoded di policies
- Assignee override untuk operasi spesifik (member bisa update task sendiri)

**Authorization ada di dua tempat:**
- `Policy` — per-model authorization
- `ContextualRoleMiddleware` — per-route authorization

---

## 7. Service untuk Read-Only Aggregation

**Keputusan:** Class `Service` terpisah untuk operasi read yang kompleks (dashboard stats, reports).

**Mengapa:**
- Action handle writes, Service handle reads — pemisahan jelas
- `once()` memoization mencegah duplikasi query dalam satu request
- Single-query aggregation via `CASE` expressions

**Kapan pakai Service vs Action:**
- Mutasi database → Action
- Read dan aggregate → Service

---

## 8. Support Classes untuk Cross-Cutting Concerns

**Keputusan:** Utility class stateless di `app/Supports/` untuk hal-hal yang bukan domain manapun.

**Mengapa:**
- `GetActiveOrganization` — session management, dipakai dimana-mana
- `RouteHelper` — auto-loading route files per domain
- `UserRoleContext` — role checking tanpa model dependency

**Konvensi:** Static methods only. Tidak ada state. Tidak perlu instantiation.

---

## 9. Rich Models, Bukan Anemic Models

**Keputusan:** Model berisi accessors, scopes, dan business methods — bukan hanya relationships dan casts.

**Mengapa:**
- `$task->is_overdue` dihitung sekali, tersedia dimana-mana
- Scopes bisa di-chain dan didelegasikan ke QueryBuilder
- Business methods seperti `markAsCompleted()` jaga domain logic dekat dengan data

**Yang masuk Model vs Action:**
- Operasi single-record → Model (`$task->markAsCompleted()`)
- Multi-step atau external concerns → Action

---

## 10. UUID over Auto-Increment

**Keputusan:** Semua primary key adalah UUID via trait `HasUuids`.

**Mengapa:**
- Aman untuk multi-tenant (tidak bisa di-enumerate)
- Aman untuk URL publik (tidak bisa ditebak sequential)
- Tidak ada ID collision antar environment

**Trade-off:** Index sedikit lebih besar. Negligible untuk skala ini.

---

## 11. Route Split per Domain

**Keputusan:** Route dipisah per fitur di `routes/web/`, auto-load via `RouteHelper`.

**Mengapa:**
- `tasks.php` 100 baris, bukan 500
- Mudah cari definisi route
- Middleware groups per domain (auth + workspace)

**Konvensi:** Custom endpoints sebelum `Route::resource()` untuk hindari konflik parameter.

---

## 12. Index berdasarkan Query Pattern

**Keputusan:** Setiap migration index mendokumentasikan access pattern yang dilayaninya.

**Mengapa:**
- Tidak ada index misterius — masing-masing punya tujuan
- Composite index dimulai dengan `organization_id` (setiap query di-scope by org)
- Named index untuk readability (`tasks_org_due_date_idx`)

**Konvensi:** Migration terpisah untuk index. Perubahan schema ≠ perubahan performance.

---

## 13. Behavior Testing, Bukan Implementation Testing

**Keputusan:** Test memverifikasi outcome, bukan method calls.

**Mengapa:**
- `$task->fresh()->status === TaskStatus::Done` — test hasilnya
- Refactoring internal tidak memecah test
- Helper `createWorkspaceUser()` kurangi boilerplate

**Konvensi:** Setiap test setup workspace context (user + org + session). Multi-tenancy bukan opsional.

---

## 14. Thin Controller sebagai Orchestrator

**Keputusan:** Controller tidak berisi business logic — hanya routing, delegation, dan response.

**Mengapa:**
- Method injection, bukan constructor injection (hanya resolve yang dibutuhkan)
- Delegation map jelas siapa mengerjakan apa
- Flash message convention untuk UX konsisten

**Sebuah controller method harus ≤ 10 baris.** Kalau lebih, ada yang masuk ke Action atau Service.

---

## Ringkasan

| Prinsip | Keputusan |
|---------|-----------|
| Separation of Concerns | Action (write), Service (read), Controller (orchestrate) |
| Type Safety | DTO + Enum dimana-mana |
| Multi-Tenancy | Global Scope, otomatis, transparan |
| Reusability | QueryBuilder + Scope delegation |
| Testability | Behavior-focused, workspace helper |
| Dokumentasi | Access patterns, delegation maps, tabel perbandingan |
