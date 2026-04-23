# Laravel AI SDK Migration Plan

Dokumen ini menjelaskan migrasi dari runtime AI custom Phase 0 ke **Laravel AI SDK** tanpa mengubah kontrak produk yang sudah ada.

Yang dipertahankan:

- endpoint `POST /ai/runs`
- endpoint polling `GET /ai/runs/{interactionId}`
- tabel `ai_interactions`
- lifecycle status `queued`, `processing`, `completed`, `failed`
- action guardrail `TaskCreateFromAiAction`
- DTO validation backend

Yang dimigrasikan:

- engine pemilihan model
- tool registry
- runtime orchestration
- fake/testing strategy AI

---

## Goal

Mengganti layer AI custom dengan **Laravel AI SDK** sebagai execution engine, sambil menjaga boundary arsitektur yang sudah benar:

1. request tetap masuk lewat controller internal,
2. eksekusi tetap async lewat queue job,
3. tool call tetap tunduk pada action pattern existing,
4. tenant safety tetap dipaksakan oleh backend,
5. hasil tetap disimpan ke `ai_interactions`.

---

## Non-Goals

- Tidak memindahkan lifecycle run ke conversation storage bawaan SDK.
- Tidak menghapus polling UI atau endpoint JSON yang sudah ada.
- Tidak menambah chat memory publik di fase ini.
- Tidak menambah RAG, embeddings, vector stores, atau multi-tool workflow.
- Tidak mengganti business action existing dengan tool logic langsung.

---

## Architecture Target

### Before

```text
Controller
  -> AiInteractionCreateAction
  -> RunAiInteractionJob
     -> AiRuntime
        -> ChatModel
        -> ToolRegistry
        -> CreateTaskTool
           -> TaskCreateFromAiAction
```

### After

```text
Controller
  -> AiInteractionCreateAction
  -> RunAiInteractionJob
     -> AiAgentRuntime
        -> Laravel AI SDK Agent
           -> Laravel AI SDK Tool
              -> TaskCreateFromAiAction
```

Perubahan penting:

- `TaskCreateFromAiAction` tetap menjadi business safety boundary.
- `RunAiInteractionJob` tetap menjadi async orchestration boundary.
- Laravel AI SDK hanya menggantikan **engine**, bukan **product contract**.

---

## Replace Matrix

| Current piece                 | Target replacement                                            |
| ----------------------------- | ------------------------------------------------------------- |
| `App\AI\Contracts\ChatModel`  | Laravel AI SDK Agent                                          |
| `App\AI\Models\FakeChatModel` | Laravel AI SDK testing fake                                   |
| `App\AI\ToolRegistry`         | `tools()` declaration pada agent                              |
| `App\AI\AiRuntime`            | adapter tipis ke Laravel AI SDK                               |
| `App\AI\Tools\CreateTaskTool` | Laravel AI SDK Tool implementation                            |
| `config/ai.php` custom        | merged config between app runtime settings and package config |

Yang tidak diganti:

| Current piece            | Reason                                                |
| ------------------------ | ----------------------------------------------------- |
| `AiInteraction`          | source of truth untuk polling dan audit               |
| `RunAiInteractionJob`    | queue lifecycle dan failure handling product-specific |
| `TaskCreateFromAiAction` | tenant-safe domain execution                          |
| `CreateTaskToolData`     | backend validation source of truth                    |
| `AiInteraction*Action`   | explicit status transition dan persistence            |

---

## Migration Steps

### Step 1. Install Laravel AI SDK

- Tambahkan dependency `laravel/ai`.
- Pastikan versi kompatibel dengan Laravel 13 dan PHP 8.4.
- Publish config/migrations package bila memang dibutuhkan.

### Step 2. Introduce SDK-backed runtime adapter

- Tambahkan runtime baru yang memanggil Laravel AI SDK Agent.
- Pertahankan signature hasil yang kompatibel dengan `AiRuntimeResultData`.
- Jangan ubah `RunAiInteractionJob` lebih dari yang diperlukan.

### Step 3. Port tool `create_task`

- Buat tool SDK untuk `create_task`.
- Handler tool tetap memanggil `TaskCreateFromAiAction`.
- DTO `CreateTaskToolData` tetap dipakai di backend.

### Step 4. Keep interaction lifecycle intact

- `AiInteractionCreateAction` tetap membuat record awal.
- `RunAiInteractionJob` tetap bertanggung jawab untuk:
    - mark processing
    - mark completed
    - mark failed
- polling endpoint tidak berubah.

### Step 5. Update tests

- Endpoint tests tetap memverifikasi:
    - request bisa queue interaction
    - queued job memuat tenant context yang sudah dipersist
    - polling access dibatasi oleh user + organization
- Job execution tests diganti agar memakai fake dari SDK atau stub agent runtime baru.

### Step 6. Remove obsolete custom engine

- Hapus `ChatModel` contract custom.
- Hapus `FakeChatModel`.
- Hapus `ToolRegistry`.
- Sederhanakan `AiRuntime` menjadi adapter SDK atau rename ke runtime baru.

---

## Config Strategy

Project ini **sudah punya** `config/ai.php`, sementara Laravel AI SDK juga memakai config file dengan nama yang sama.

Aturan migrasi:

1. jangan overwrite file existing secara buta,
2. merge key custom yang masih dibutuhkan,
3. pindahkan app-specific key ke namespace yang tidak bentrok jika perlu,
4. pastikan `config:cache` tetap aman.

Preferensi implementasi:

- gunakan key package SDK untuk provider/model/tooling,
- pertahankan queue name app bila masih relevan,
- simpan testing knobs custom hanya jika tidak disediakan langsung oleh SDK.

---

## Risk Areas

### 1. Config collision

`config/ai.php` adalah area risiko paling besar. Konflik nama key bisa membuat runtime diam-diam memakai config yang salah.

### 2. Namespace ambiguity

Codebase saat ini memakai `App\AI`, sementara ekosistem Laravel biasanya memakai `App\Ai`. Kita harus konsisten dan tidak membuat dua pohon namespace yang membingungkan.

### 3. Over-migrating to SDK conversations

Conversation memory SDK tidak sama dengan `ai_interactions`. Jika dipaksa mengganti, polling/status model saat ini bisa rusak.

### 4. Losing tenant guardrails

Schema/tool support dari SDK membantu model, tetapi tidak menggantikan validasi tenant. `TaskCreateFromAiAction` tidak boleh dihapus dari flow.

---

## Success Criteria

Migrasi dianggap selesai jika:

1. package `laravel/ai` sudah terpasang dan dipakai oleh runtime,
2. `POST /ai/runs` masih mengembalikan `202 Accepted`,
3. `GET /ai/runs/{interactionId}` masih dapat dipoll seperti sebelumnya,
4. tool `create_task` masih membuat task melalui `TaskCreateFromAiAction`,
5. cross-tenant dan invalid argument path tetap gagal dengan aman,
6. focused tests tetap hijau.

---

## Rollback Strategy

Jika migrasi engine gagal:

1. revert adapter runtime ke implementasi custom lama,
2. pertahankan `AiInteraction`, job, dan controller apa adanya,
3. biarkan dependency `laravel/ai` tetap terpasang sementara bila rollback cepat dibutuhkan,
4. hapus wiring SDK hanya setelah runtime lama kembali stabil.

Karena boundary produk dipertahankan, rollback cukup dilakukan di layer engine, bukan di endpoint atau persistence model.
