# AI Runtime Resumption Snapshot

Dokumen ini adalah **source of truth operasional** untuk melanjutkan pekerjaan AI setelah snapshot **2026-04-22**.

Status basis verifikasi terakhir:

- `vendor/bin/pint --dirty --format agent`
- `php artisan test --compact`
- `node --test tests/js/*.mjs`
- hasil terakhir:
  - **87 passed, 542 assertions** untuk PHP
  - **26 passed** untuk JS

Dokumen ini menggantikan asumsi lama bahwa retrieval dan citation belum ada.

---

## 1. Executive Summary

Implementasi AI sekarang sudah berada pada tahap:

- chat-first,
- SSE streaming,
- tool-enabled,
- artifact-aware,
- retrieval-enabled,
- citation-ready.

Yang sudah ada:

- custom provider `cliproxyapi`
- single-lane chat UI
- Laravel AI SDK conversation flow
- runtime layer sampai retrieval abstraction
- first RAG path dengan source citations
- typed artifact rendering
- inline citation markers `[1]`, `[2]` yang terhubung ke source cards

Yang belum ada:

- preflight classifier / jailbreak guardrail layer
- human approval checkpoint
- orchestration multi-step yang matang
- observability and governance lengkap

Bottom line:

> Sistem sudah bukan proof of concept. Ini sudah menjadi runtime AI yang layak diteruskan sebagai fondasi produk.

---

## 2. Goal Akhir

Target arsitektur tetap sama:

1. Laravel AI SDK = primitive layer
2. application runtime = orchestration lifecycle
3. UI = consumer typed artifacts
4. retrieval, guardrail, approvals, governance = extension points

Target dekat:

- harden runtime hooks
- tambah guardrail/classifier layer
- perdalam retrieval quality

Target menengah:

- approvals
- follow-up jobs
- multi-step orchestration

Target akhir:

- production-grade AI subsystem yang observable dan governable

---

## 3. Current State

### 3.1 Frontend / UX

Sudah selesai:

- halaman chat tunggal:
  - [Index.vue](/var/www/laravel_boilerplate/resources/js/pages/Ai/Index.vue)
- SSE streaming
- character-by-character rendering
- markdown renderer battle-tested:
  - `markdown-it`
  - `dompurify`
- fullscreen chat layout
- artifact renderer registry
- source-linked citation rendering

Artifact frontend yang aktif:

- `task_summary`
- `table`
- `checklist`
- `key_value`
- `stats_card`
- `approval_card`
- `markdown`
- `json_fallback`
- `source_list`
- `answer_with_sources`

Status:

- **done** untuk product chat slice sekarang

### 3.2 Backend Chat Runtime

Sudah selesai:

- chat index controller
- session creation
- stream endpoint
- conversation persistence
- artifact sync ke assistant message meta

File utama:

- [AiChatController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatController.php)
- [AiChatSessionController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatSessionController.php)
- [AiChatMessageStreamController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatMessageStreamController.php)
- [AiChatSessionSyncAction.php](/var/www/laravel_boilerplate/app/Actions/AI/AiChatSessionSyncAction.php)

Status:

- **done**

### 3.3 Provider Layer

Sudah selesai:

- custom OpenAI-compatible provider `cliproxyapi`
- endpoint `chat/completions`
- text response
- structured output
- tool loop
- streaming

File utama:

- [config/ai.php](/var/www/laravel_boilerplate/config/ai.php)
- [CliProxyApiGateway.php](/var/www/laravel_boilerplate/app/AI/Gateway/CliProxyApiGateway.php)
- [AppServiceProvider.php](/var/www/laravel_boilerplate/app/Providers/AppServiceProvider.php)

Status:

- **done**

### 3.4 Runtime Layer

Folder utama:

- [app/AI/Runtime](/var/www/laravel_boilerplate/app/AI/Runtime)

Phase status:

- **Phase 1: Runtime Contracts**: done
- **Phase 2: Preflight + Prompt Pipeline**: done
- **Phase 3: Artifact Resolver Layer**: done
- **Phase 4: Tool Policy Layer**: done as baseline
- **Phase 5: Retrieval Abstraction**: done
- **Phase 6: First RAG Path**: partially done but live

### 3.5 Retrieval and RAG Baseline

Yang sudah ada:

- `WorkspaceRetrievalPlanner`
- `WorkspaceCompositeKnowledgeSource`
- `WorkspaceDatabaseKnowledgeSource`
- `WorkspaceVectorKnowledgeSource`
- `SdkEmbeddingProvider`
- `DatabaseVectorStore`
- observer-based ingestion pipeline
- `CitationPayload`

File penting:

- [WorkspaceArtifactResolver.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/WorkspaceArtifactResolver.php)
- [AnswerWithSourcesFormatter.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/AnswerWithSourcesFormatter.php)
- [WorkspaceCompositeKnowledgeSource.php](/var/www/laravel_boilerplate/app/AI/Runtime/Retrieval/WorkspaceCompositeKnowledgeSource.php)
- [WorkspaceVectorKnowledgeSource.php](/var/www/laravel_boilerplate/app/AI/Runtime/Retrieval/WorkspaceVectorKnowledgeSource.php)
- [WorkspaceDatabaseKnowledgeSource.php](/var/www/laravel_boilerplate/app/AI/Runtime/Retrieval/WorkspaceDatabaseKnowledgeSource.php)

Status:

- **done untuk baseline**
- **partial** untuk tuning/routing/quality

---

## 4. Capability Matrix Saat Ini

### Sudah Berjalan

- custom provider `cliproxyapi`
- SSE chat streaming
- persisted chat session
- Laravel AI SDK conversation support
- tool calling untuk `CreateTaskTool`
- typed artifact rendering
- runtime context + preflight + prompt middleware
- artifact resolver
- tool execution policy baseline
- retrieval planner dan knowledge source nyata
- vector-backed retrieval path
- source citations
- `answer_with_sources`
- inline citation markers + source card anchors

### Sebagian Berjalan

- guardrail:
  - ada workspace scope enforcement
  - belum ada pre-classifier / intent classifier / jailbreak classifier
- artifact strategy:
  - typed artifact sudah ada
  - taxonomy masih terbatas
- observability:
  - tool execution logging sudah ada
  - belum ada stage tracing/metrics penuh
- RAG:
  - retrieval path pertama sudah ada
  - ranking/routing belum matang

### Belum Mulai

- HITL approval checkpoints
- async follow-up jobs via post-run hooks
- enterprise governance lengkap

---

## 5. Review Implementasi Dari Phase Awal

### Phase Awal: `ai.runs` + polling vertical slice

Yang berhasil:

- membuktikan boundary AI pertama
- membuktikan tool call domain pertama
- membuktikan tenancy + validation guardrails

Kenapa tidak dipertahankan:

- UX tidak cocok dengan target produk
- terlalu jauh dari conversation model Laravel AI SDK

Status:

- **retired**

### Migrasi ke Laravel AI SDK

Yang berhasil:

- provider custom dibuat kompatibel
- chat session dan persistence disejajarkan dengan SDK
- streaming dan tool loop bekerja dalam flow chat

Status:

- **done**

### Runtime Refactor

Yang berhasil:

- orchestration dipindahkan ke runtime layer
- controller diperkecil
- artifact menjadi concern first-class
- retrieval bisa masuk tanpa rewrite besar

Status:

- **done untuk baseline**

---

## 6. Review Per Phase Saat Ini

### Phase 1

Review:

- contracts sudah cukup bersih
- boundary lifecycle sudah jelas

### Phase 2

Review:

- prompt/preflight logic sudah keluar dari controller
- siap menerima classifier dan policy routing

### Phase 3

Review:

- artifact resolver menjadi boundary yang benar
- frontend tidak perlu infer struktur dari text

Tambahan terbaru:

- retrieval-backed `answer_with_sources`
- inline citation markers

### Phase 4

Review:

- tool execution policy sudah menjadi concern terpisah
- journal execution menyatukan stream emission dan persistence

Keterbatasan:

- retry/fallback belum matang
- domain tool wrapper masih sedikit

### Phase 5

Review:

- retrieval abstraction berhasil diwujudkan
- knowledge source tidak lagi placeholder

### Phase 6

Review:

- first RAG path sudah hidup
- local vector path dan ingestion sudah nyata
- citation shaping sudah usable untuk UI

Keterbatasan:

- ranking, routing, dan summarization masih bisa diperdalam

---

## 7. Known Gaps dan Technical Debt

Yang paling penting untuk diingat:

1. Guardrail classifier belum ada.
   - saat ini masih bertumpu pada prompt constraints dan workspace scope policy

2. Tool policy belum kaya.
   - authz dan logging ada
   - retry, fallback, timeout, rate limiting belum matang

3. Post-run hooks belum dipakai secara nyata.
   - contract ada
   - behavior production belum banyak

4. Observability belum lengkap.
   - belum ada trace per stage
   - belum ada token/tool/retrieval metrics
   - belum ada audit taxonomy formal

5. Retrieval quality masih baseline.
   - source sudah ada
   - ranking/routing belum optimal

6. Artifact taxonomy masih terbatas.
   - sudah cukup untuk current product slice
   - belum kaya untuk semua mode output ke depan

---

## 8. Recommended Next Step

Langkah berikut yang paling tepat adalah **hardening runtime hooks**, bukan membangun ulang chat.

Urutan rekomendasi:

1. **Guardrail / Preflight Classifier Layer**
   - keyword intent classifier
   - whitelist / blacklist rules
   - optional lightweight model sebelum main call

2. **Retrieval Quality Refinement**
   - improve planner heuristics
   - ranking
   - summarization
   - citation selection quality

3. **Advanced Control Flow**
   - approvals
   - follow-up jobs
   - multi-step orchestration

4. **Observability and Governance**
   - tracing
   - metrics
   - redaction
   - audit taxonomy

---

## 9. Practical Resume Checklist

Kalau ingin lanjut besok tanpa context recovery panjang:

1. Baca dokumen ini dulu.
2. Baca file runtime utama:
   - [PrepareWorkspaceAssistantRunAction.php](/var/www/laravel_boilerplate/app/Actions/AI/PrepareWorkspaceAssistantRunAction.php)
   - [WorkspaceArtifactResolver.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/WorkspaceArtifactResolver.php)
   - [WorkspaceToolExecutionPolicy.php](/var/www/laravel_boilerplate/app/AI/Runtime/Tools/WorkspaceToolExecutionPolicy.php)
   - [AiChatMessageStreamController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatMessageStreamController.php)
3. Anggap chat/SSE/runtime retrieval baseline sudah stabil.
4. Jangan kembali ke pola controller-heavy atau polling.
5. Lanjutkan lewat runtime extension points yang sudah ada.

---

## 10. File Landmarks

File orientasi tercepat:

- chat page:
  - [Index.vue](/var/www/laravel_boilerplate/resources/js/pages/Ai/Index.vue)
- markdown renderer:
  - [renderMarkdown.js](/var/www/laravel_boilerplate/resources/js/components/ai/renderMarkdown.js)
- answer-with-sources renderer:
  - [AiArtifactAnswerWithSources.vue](/var/www/laravel_boilerplate/resources/js/components/ai/AiArtifactAnswerWithSources.vue)
- provider config:
  - [ai.php](/var/www/laravel_boilerplate/config/ai.php)
- provider gateway:
  - [CliProxyApiGateway.php](/var/www/laravel_boilerplate/app/AI/Gateway/CliProxyApiGateway.php)
- runtime preparation:
  - [PrepareWorkspaceAssistantRunAction.php](/var/www/laravel_boilerplate/app/Actions/AI/PrepareWorkspaceAssistantRunAction.php)
- runtime contracts:
  - [app/AI/Runtime](/var/www/laravel_boilerplate/app/AI/Runtime)
- artifact resolver:
  - [WorkspaceArtifactResolver.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/WorkspaceArtifactResolver.php)
- tool policy:
  - [WorkspaceToolExecutionPolicy.php](/var/www/laravel_boilerplate/app/AI/Runtime/Tools/WorkspaceToolExecutionPolicy.php)
- stream controller:
  - [AiChatMessageStreamController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatMessageStreamController.php)
- persistence sync:
  - [AiChatSessionSyncAction.php](/var/www/laravel_boilerplate/app/Actions/AI/AiChatSessionSyncAction.php)

---

## 11. Bottom Line

Per hari ini:

- product-level chat runtime **sudah nyata**
- retrieval-backed artifact path **sudah nyata**
- citation UX **sudah nyata**
- arsitektur **sudah siap diperluas** ke guardrail, approvals, dan governance

Hal terpenting untuk kelanjutan kerja:

> Jangan mundur ke implementasi ad-hoc.
> Runtime layer yang sekarang sudah cukup kuat untuk jadi fondasi fase berikutnya.
