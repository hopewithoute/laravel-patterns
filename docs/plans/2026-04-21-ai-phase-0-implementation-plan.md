# AI Phase 0 Implementation Plan

**Goal:** Deliver satu vertical slice AI yang nyata, aman, dan bisa diuji: user workspace mengirim satu instruksi, sistem memprosesnya secara async, memilih satu tool yang diizinkan, memvalidasi argumen, mengeksekusi business action, lalu menyimpan hasilnya sebagai audit log internal.

**Tech Stack:** Laravel 13, PHP 8.4, Inertia.js 3, Vue 3, SQLite

**Target Use Case:** `create_task`

---

## Success Criteria

- User yang sudah login dan sudah memilih workspace dapat mengirim satu AI instruction melalui endpoint internal.
- Sistem menyimpan satu record interaksi AI dengan status yang jelas: `queued`, `processing`, `completed`, atau `failed`.
- Eksekusi AI berjalan melalui queue, bukan langsung di request HTTP.
- Tool yang dipanggil hanya berasal dari registry yang diizinkan.
- Input tool divalidasi secara eksplisit sebelum `TaskCreateAction` dijalankan.
- Task yang dibuat selalu terikat ke `organization_id` aktif milik user, bukan bergantung pada session di worker queue.
- Ada feature test untuk happy path, invalid argument path, dan cross-tenant rejection path.

---

## Non-Goals

- Belum ada public AI chatbox / omnibar.
- Belum ada integrasi RAG, vector store, semantic audit log, atau summary komentar.
- Belum ada multi-tool orchestration.
- Belum ada dependency AI SDK baru sebagai keharusan Phase 0.

---

## Architectural Decisions

### 1. Reuse existing project structure

Karena repo ini menghindari penambahan base folder baru tanpa alasan kuat, implementasi Phase 0 harus memanfaatkan struktur yang sudah ada:

- `app/Models` untuk persistence model
- `app/Enums` untuk status
- `app/Data/AI` untuk DTO input AI
- `app/Actions/AI` untuk write operations terkait lifecycle interaksi AI
- `app/AI` untuk runtime, registry, tool, dan model adapter
- `app/Http/Controllers` untuk endpoint trigger
- `routes/web` untuk route entry point

### 2. Queue worker tidak boleh bergantung pada session

Current web flow masih mengandalkan helper active organization di beberapa DTO. Itu tidak aman untuk eksekusi di queue worker. Pada Phase 0, `organization_id` dan `user_id` harus dipersist sejak request awal, lalu dibawa eksplisit ke job/runtime.

### 3. Tool input AI dipisah dari DTO HTTP form

Jangan memakai `TaskData` langsung sebagai shape output model. Buat DTO khusus AI, lalu map ke `TaskData` setelah tenant context dan default business values ditentukan. Ini menjaga boundary AI tetap sempit dan mudah diaudit.

### 4. Default model adapter harus deterministic

Phase 0 sebaiknya tidak bergantung pada provider eksternal untuk lolos test. Buat adapter `fake` sebagai default untuk local/test. Provider nyata bisa ditambahkan di belakang contract yang sama pada iterasi berikutnya.

### 5. Satu tool saja untuk vertical slice pertama

Tool pertama cukup `create_task`. Jangan menambahkan `create_project`, `assign_task`, atau tool lain sebelum jalur pertama stabil.

---

## Proposed Data Flow

1. `POST /ai/runs` menerima instruction dari user terautentikasi.
2. Controller membuat record `ai_interactions` dengan status `queued`.
3. Controller dispatch job `RunAiInteractionJob`.
4. Job memuat interaction record, mengubah status menjadi `processing`.
5. Runtime memanggil model adapter dan menerima hasil dalam bentuk tool call tunggal.
6. Tool registry mencocokkan `tool_name`.
7. Tool DTO memvalidasi argumen model.
8. Tool mengeksekusi business action yang sudah ada.
9. Interaction record diperbarui menjadi `completed` atau `failed`.
10. Endpoint atau consumer lain dapat membaca hasil eksekusi dari record tersebut.

---

## Task 1: Add AI Interaction Persistence

**Files:**

- Create: `database/migrations/<timestamp>_create_ai_interactions_table.php`
- Create: `app/Models/AiInteraction.php`
- Create: `app/Enums/AiInteractionStatus.php`

**Implementation Notes:**

- Gunakan UUID primary key agar konsisten dengan model lain.
- Simpan `organization_id` dan `user_id` sejak awal request.
- Simpan payload mentah yang cukup untuk audit, tetapi jangan menyimpan secret.

**Recommended columns:**

- `id`
- `organization_id`
- `user_id`
- `instruction`
- `requested_tool` nullable
- `status`
- `model`
- `tool_name` nullable
- `tool_arguments` nullable JSON
- `tool_result` nullable JSON
- `error_message` nullable text
- `started_at` nullable timestamp
- `completed_at` nullable timestamp
- timestamps

**Why first:** seluruh lifecycle Phase 0 bergantung pada satu source of truth yang bisa diobservasi dan diuji.

---

## Task 2: Define AI Runtime Contracts and Config

**Files:**

- Create: `config/ai.php`
- Create: `app/AI/Contracts/ChatModel.php`
- Create: `app/AI/Contracts/Tool.php`
- Create: `app/AI/Models/FakeChatModel.php`
- Modify: `app/Providers/AppServiceProvider.php`

**Implementation Notes:**

- `ChatModel` cukup punya satu method, misalnya `respond(AiInteraction $interaction, array $tools): array`.
- `Tool` harus mendeklarasikan nama tool, deskripsi singkat, schema/shape argument, dan method execute.
- `config/ai.php` menentukan driver default, model name, dan toggle logging minimum.
- Binding service dilakukan di `AppServiceProvider`.

**Important constraint:**

- Jangan tambahkan package AI baru di Phase 0.
- Jika nanti butuh provider nyata, gunakan contract yang sama dan pasang lewat config binding.

---

## Task 3: Build the Tool Registry and First Tool

**Files:**

- Create: `app/AI/ToolRegistry.php`
- Create: `app/AI/Tools/CreateTaskTool.php`
- Create: `app/Data/AI/CreateTaskToolData.php`

**Implementation Notes:**

- `ToolRegistry` mengembalikan daftar tool yang diizinkan untuk Phase 0.
- `CreateTaskToolData` memegang input minimal yang memang boleh dihasilkan model:
  - `project_id`
  - `title`
  - `description`
  - `priority`
  - `due_date`
  - `assigned_to`
- `CreateTaskTool` harus:
  - menerima `AiInteraction`
  - memvalidasi argumen via `CreateTaskToolData`
  - memastikan `project_id` masih milik `organization_id` interaction
  - memastikan `assigned_to`, jika ada, masih anggota organization yang sama
  - memetakan input ke `TaskData`
  - meng-inject `organization_id` dari interaction, bukan dari session
  - menjalankan `TaskCreateAction`

**Reasoning:**

Ini adalah guardrail utama terhadap hallucination dan tenant escape. Validasi `exists` global saja tidak cukup untuk tool calling.

---

## Task 4: Add Runtime Orchestration and Job Execution

**Files:**

- Create: `app/AI/AiRuntime.php`
- Create: `app/Jobs/RunAiInteractionJob.php`
- Create: `app/Actions/AI/AiInteractionCreateAction.php`
- Create: `app/Actions/AI/AiInteractionMarkProcessingAction.php`
- Create: `app/Actions/AI/AiInteractionCompleteAction.php`
- Create: `app/Actions/AI/AiInteractionFailAction.php`

**Implementation Notes:**

- `AiInteractionCreateAction` membuat interaction record dengan status `queued`.
- `RunAiInteractionJob` memuat interaction row fresh dari database.
- Gunakan status transition eksplisit:
  - `queued` -> `processing`
  - `processing` -> `completed`
  - `processing` -> `failed`
- `AiRuntime` bertugas:
  - memanggil `ChatModel`
  - resolve tool via registry
  - menjalankan tool
  - mengembalikan payload hasil standar untuk disimpan

**Queue guidance:**

- Gunakan queue async normal untuk jalur aplikasi.
- Untuk test job behavior, kombinasikan `Queue::fake()` pada endpoint test dan `dispatchSync()` atau direct `handle()` untuk execution test sesuai dokumentasi Laravel 13.

**Important constraint:**

- Runtime AI tidak ditempatkan di `app/Services` karena pada repo ini `Service` didedikasikan untuk read-only aggregation.
- Job class ditempatkan di `app/Jobs` agar konsisten dengan primitive queue Laravel.

---

## Task 5: Add the Internal Trigger Endpoint

**Files:**

- Create: `app/Data/AI/AiRunData.php`
- Create: `app/Http/Controllers/AiRunController.php`
- Create: `routes/web/ai.php`
- Modify: `routes/web.php`

**Implementation Notes:**

- Endpoint awal cukup `POST /ai/runs`.
- Middleware wajib:
  - `auth`
  - `ensure_workspace_selected`
- `AiRunData` cukup memuat:
  - `instruction`
  - `requested_tool` nullable
- Controller flow:
  - ambil `organization_id` aktif dari request context
  - panggil `AiInteractionCreateAction`
  - dispatch `RunAiInteractionJob`
  - return JSON berisi `interaction_id` dan status awal

**Why JSON first:**

Phase 0 fokus pada runtime boundary, bukan UX. Endpoint JSON lebih mudah diuji dan tidak mengunci desain frontend terlalu cepat.

---

## Task 6: Harden Tenant and Validation Boundaries

**Files to inspect and likely modify:**

- Modify: `app/Data/TaskData.php`
- Modify: `app/Data/ProjectData.php` only if reuse pattern is needed
- Inspect: `app/Traits/HasOrganization.php`
- Inspect: `app/Supports/GetActiveOrganization.php`

**Implementation Notes:**

- Jangan mengandalkan fallback `GetActiveOrganization::getSelected()` untuk AI path.
- Jika `TaskData` tetap mempertahankan fallback untuk web flow, tool layer tetap wajib mengirim `organization_id` secara eksplisit.
- Pastikan validasi tenant-sensitive dilakukan sebelum action write:
  - project harus berada di organization yang sama
  - assigned user harus menjadi member organization yang sama

**Expected outcome:**

Business execution di queue tetap benar meskipun tidak ada session, request header, atau active singleton dari HTTP request.

---

## Task 7: Add Test Coverage

**Files:**

- Create: `tests/Feature/AiRunControllerTest.php`
- Create: `tests/Feature/RunAiInteractionJobTest.php`

**Create with:**

```bash
php artisan make:test --phpunit AiRunControllerTest --no-interaction
php artisan make:test --phpunit RunAiInteractionJobTest --no-interaction
```

**Required scenarios:**

- `AiRunControllerTest`
  - authenticated workspace user can queue AI interaction
  - request without workspace context is rejected
  - queued job receives persisted `organization_id` and `user_id`

- `RunAiInteractionJobTest`
  - fake model calling `create_task` creates task successfully
  - invalid tool name marks interaction as failed
  - invalid arguments mark interaction as failed and do not create task
  - project from another organization is rejected
  - assigned user from another organization is rejected

**Testing guidance:**

- Reuse `createWorkspaceUser()` helper dari `tests/TestCase.php`
- Gunakan `Queue::fake()` untuk assertion dispatch
- Gunakan `RefreshDatabase`
- Untuk execution tests, fake model harus deterministic agar test tidak flaky

---

## Task 8: Verification Sequence

**Recommended command order:**

```bash
php artisan test --compact tests/Feature/AiRunControllerTest.php
php artisan test --compact tests/Feature/RunAiInteractionJobTest.php
```

Jika ada file PHP yang berubah saat implementasi:

```bash
vendor/bin/pint --dirty --format agent
```

---

## Suggested Delivery Order

1. Persistence layer (`ai_interactions`, model, enum)
2. Config + contracts + fake model
3. Tool registry + `create_task` tool
4. Interaction actions + runtime + queue job
5. Internal trigger endpoint
6. Tenant boundary hardening
7. Feature tests
8. Pint + targeted test rerun

---

## Risks and Mitigations

### Risk 1: Queue worker loses tenant context

**Mitigation:** persist `organization_id` on interaction row and pass it explicitly to tool execution.

### Risk 2: AI generates valid UUID from wrong tenant

**Mitigation:** validate project membership and assignee membership against `organization_id`, bukan hanya rule `exists`.

### Risk 3: Phase 0 terkunci ke provider AI tertentu

**Mitigation:** mulai dari contract + fake adapter. Provider nyata hanya implement contract yang sama.

### Risk 4: Tool contract terlalu longgar

**Mitigation:** mulai dari satu tool, satu DTO AI input, satu runtime path.

---

## Definition of Done

Phase 0 selesai jika:

- repo memiliki runtime AI minimal yang bisa dipanggil lewat endpoint internal
- satu tool `create_task` berjalan end-to-end via queue
- interaction lifecycle tersimpan di database
- tenant boundary tidak bergantung pada session saat job dieksekusi
- seluruh test Phase 0 pass

---

## Optional Follow-Up After Phase 0

- Tambahkan provider nyata pertama di balik `ChatModel` contract
- Tambahkan polling endpoint untuk membaca status interaction
- Tambahkan UI internal sederhana sebelum masuk ke omnibar/chatbox Phase 1
- Tambahkan tool kedua hanya setelah metrics Phase 0 stabil
