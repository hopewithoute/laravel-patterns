# AI Architecture and Tool Calling

Dokumen ini menjelaskan arsitektur AI yang **aktif saat ini** per **2026-04-22**. Fokusnya bukan lagi vertical slice `ai.runs` lama, tetapi runtime chat-first berbasis Laravel AI SDK, SSE streaming, typed artifacts, dan retrieval-ready orchestration.

Dokumen ini adalah snapshot implementasi yang berjalan di repo sekarang.

---

## 1. Tujuan Arsitektur

Target arsitektur AI di aplikasi ini adalah membangun **AI Runtime Layer di atas Laravel AI SDK**, bukan sekadar menempelkan provider LLM ke controller.

Prinsip utamanya:

- Laravel AI SDK dipakai sebagai primitive:
  - provider
  - agent
  - tool
  - conversation memory
  - streaming
- orchestration lifecycle tinggal di application layer:
  - preflight
  - prompt middleware
  - retrieval planning
  - tool execution policy
  - artifact resolution
  - post-run persistence
- UI mengonsumsi **typed artifacts**, bukan menebak struktur dari text mentah
- retrieval, guardrail, approvals, dan governance harus bisa ditambahkan tanpa merombak jalur chat utama

---

## 2. Current Product Shape

Shape produk yang aktif sekarang:

1. user masuk ke halaman chat AI workspace,
2. user mengirim prompt ke satu session chat,
3. backend menyiapkan runtime context,
4. agent Laravel AI SDK melakukan streaming via SSE,
5. tool call dieksekusi melalui policy layer,
6. assistant text dan artifact dipersist ke conversation store,
7. frontend merender markdown, tool traces, dan typed artifacts.

Implikasi penting:

- **legacy flow `ai.runs` sudah retired**
- HTTP controller bukan tempat orchestration berat
- chat session adalah source of truth produk AI saat ini

---

## 3. High-Level Components

Komponen utama yang aktif:

- **Chat HTTP layer**
  - [AiChatController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatController.php)
  - [AiChatSessionController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatSessionController.php)
  - [AiChatMessageStreamController.php](/var/www/laravel_boilerplate/app/Http/Controllers/AiChatMessageStreamController.php)
- **Runtime preparation**
  - [PrepareWorkspaceAssistantRunAction.php](/var/www/laravel_boilerplate/app/Actions/AI/PrepareWorkspaceAssistantRunAction.php)
- **Runtime layer**
  - [app/AI/Runtime](/var/www/laravel_boilerplate/app/AI/Runtime)
- **Agent shell**
  - [WorkspaceAssistantAgent.php](/var/www/laravel_boilerplate/app/Ai/Agents/WorkspaceAssistantAgent.php)
- **Provider gateway**
  - [CliProxyApiGateway.php](/var/www/laravel_boilerplate/app/AI/Gateway/CliProxyApiGateway.php)
- **Persistence sync**
  - [AiChatSessionSyncAction.php](/var/www/laravel_boilerplate/app/Actions/AI/AiChatSessionSyncAction.php)
- **Frontend chat UI**
  - [Index.vue](/var/www/laravel_boilerplate/resources/js/pages/Ai/Index.vue)
- **Artifact renderer**
  - [AiArtifactRenderer.vue](/var/www/laravel_boilerplate/resources/js/components/ai/AiArtifactRenderer.vue)

---

## 4. Runtime Lifecycle

### 4.1 Request Entry

Request chat masuk ke `AiChatMessageStreamController`.

Controller melakukan:

1. validasi session dan workspace aktif,
2. membangun `PreparedWorkspaceAssistantRun`,
3. membuat agent dengan instruction dan tools hasil runtime preparation,
4. melanjutkan conversation existing atau membuat conversation baru,
5. meng-stream response ke frontend via `text/event-stream`.

Controller **tidak**:

- menyusun prompt secara ad-hoc,
- menentukan artifact di frontend,
- menjalankan tool langsung tanpa policy/runtime abstraction.

### 4.2 Runtime Preparation

`PrepareWorkspaceAssistantRunAction` adalah boundary utama untuk request-to-agent preparation.

Yang disiapkan:

- `AiRuntimeContext`
- `PreflightDecision`
- hasil prompt middleware
- daftar tools yang diizinkan
- retrieval result bila planner memutuskan retrieval dibutuhkan
- journal policy untuk eksekusi tool

### 4.3 Streaming

Streaming berjalan melalui SSE dari controller ke frontend.

Event penting:

- `stream_start`
- `text_delta`
- `tool_call`
- `tool_result`
- `stream_end`
- artifact event hasil resolver

Assistant text diakumulasi selama stream berlangsung, lalu dipakai lagi di akhir untuk membangun retrieval-backed artifact seperti `answer_with_sources`.

### 4.4 Persistence

Persistence diselesaikan oleh `AiChatSessionSyncAction`.

Yang disinkronkan:

- `conversation_id`
- judul session
- `last_message_at`
- artifact final ke `agent_conversation_messages.meta`

Dengan begitu, artifact yang dilihat saat streaming dan artifact yang tersimpan di history tetap berasal dari resolver yang sama.

---

## 5. Runtime Modules

Struktur runtime saat ini:

```text
app/AI/Runtime/
  Artifacts/
  Context/
  Contracts/
  Execution/
  Middleware/
  Preflight/
  Retrieval/
  Tools/
  Vectors/
```

Makna modul:

- `Context`: request-scoped runtime state
- `Contracts`: lifecycle interfaces
- `Preflight`: intent/capability gating
- `Middleware`: prompt enrichment pipeline
- `Retrieval`: planner, knowledge source, citation normalization
- `Tools`: execution policy, journals, wrappers
- `Artifacts`: typed output resolution
- `Vectors`: embeddings dan vector store adapter

---

## 6. Phase Status

Status implementasi runtime saat ini:

- **Phase 0**: first vertical slice historis, sudah retired sebagai product flow
- **Phase 1: Runtime Contracts**: done
- **Phase 2: Preflight + Prompt Pipeline**: done
- **Phase 3: Artifact Resolver Layer**: done
- **Phase 4: Tool Policy Layer**: done as baseline
- **Phase 5: Retrieval Abstraction**: done
- **Phase 6: First RAG Path**: partially done, sudah ada local vector path + ingestion + citation shaping
- **Phase 7: Advanced Control Flow**: belum dimulai
- **Phase 8: Observability and Governance**: belum dimulai

---

## 7. Provider Layer

Provider aktif menggunakan adaptor OpenAI-compatible lokal:

- provider name: `cliproxyapi`
- base URL lokal dikonfigurasi di `config/ai.php`
- gateway: [CliProxyApiGateway.php](/var/www/laravel_boilerplate/app/AI/Gateway/CliProxyApiGateway.php)

Keputusan penting:

- provider tetap memakai `chat/completions`
- gateway menangani:
  - non-streaming text
  - structured output
  - tool loop recursion
  - streaming event translation

Arti arsitekturalnya:

- Laravel AI SDK tetap jadi host runtime
- compatibility gap ditutup di gateway, bukan di controller atau UI

---

## 8. Tool Calling Contract

Tool calling sekarang mengikuti boundary berikut:

1. model memilih tool,
2. runtime memeriksa akses dan policy,
3. managed tool wrapper mengeksekusi business action,
4. hasil dinormalisasi menjadi `ToolExecutionResult`,
5. resolver membangun artifact dari hasil itu bila perlu.

Tool pertama yang aktif:

- `CreateTaskTool`

Boundary penting:

- tool tetap tipis
- domain write tetap lewat Action Pattern existing
- workspace authz tetap diperiksa di policy/runtime layer

Ini menjaga alignment dengan pola repo:

- controller tipis
- DTO/action tetap jadi write boundary
- tool tidak menjadi service layer baru yang liar

---

## 9. Retrieval Architecture

Retrieval sekarang bukan placeholder lagi.

Komponen yang sudah ada:

- `WorkspaceRetrievalPlanner`
- `WorkspaceCompositeKnowledgeSource`
- `WorkspaceDatabaseKnowledgeSource`
- `WorkspaceVectorKnowledgeSource`
- `DatabaseVectorStore`
- `SdkEmbeddingProvider`
- `CitationPayload`

Sumber knowledge saat ini:

- workspace DB entities
- local vectorized knowledge source

Hasil retrieval dinormalisasi menjadi:

- `documents`
- `citations`
- `metadata`

Retrieval tidak diekspos mentah ke UI. Yang masuk ke UI adalah artifact hasil resolusi, misalnya:

- `source_list`
- `answer_with_sources`

---

## 10. Ingestion Path

Knowledge ingestion untuk RAG baseline sudah tersedia.

Komponen penting:

- [UpsertAiKnowledgeSourceAction.php](/var/www/laravel_boilerplate/app/Actions/AI/UpsertAiKnowledgeSourceAction.php)
- [SyncProjectKnowledgeSourceAction.php](/var/www/laravel_boilerplate/app/Actions/AI/SyncProjectKnowledgeSourceAction.php)
- [SyncTaskKnowledgeSourceAction.php](/var/www/laravel_boilerplate/app/Actions/AI/SyncTaskKnowledgeSourceAction.php)
- observers:
  - [ProjectObserver.php](/var/www/laravel_boilerplate/app/Observers/ProjectObserver.php)
  - [TaskObserver.php](/var/www/laravel_boilerplate/app/Observers/TaskObserver.php)
  - [CommentObserver.php](/var/www/laravel_boilerplate/app/Observers/CommentObserver.php)

Prinsipnya:

- domain entity berubah
- observer setelah commit memicu sinkronisasi knowledge source
- chunk/embedding/vector store diperbarui lewat adapter pattern

Ini membuat jalur RAG pertama sudah hidup tanpa mengikat sistem ke satu engine vector tertentu.

---

## 11. Artifact Model

Artifact dipilih di backend melalui resolver, bukan ditebak di frontend.

Artifact yang sudah didukung:

- `task_summary`
- `approval_card`
- `stats_card`
- `json_fallback`
- `source_list`
- `answer_with_sources`

Untuk retrieval-backed answer:

- answer text final dipakai sebagai basis artifact
- citations dinormalisasi lebih dulu
- source diberi `marker`, `anchor`, dan `marker_number`
- marker inline seperti `[1]` dihubungkan ke source card di frontend

Komponen kunci:

- [WorkspaceArtifactResolver.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/WorkspaceArtifactResolver.php)
- [AnswerWithSourcesFormatter.php](/var/www/laravel_boilerplate/app/AI/Runtime/Artifacts/AnswerWithSourcesFormatter.php)
- [AiArtifactAnswerWithSources.vue](/var/www/laravel_boilerplate/resources/js/components/ai/AiArtifactAnswerWithSources.vue)

---

## 12. Frontend Rendering Model

Frontend tidak lagi melakukan inferensi struktur dari text response.

Frontend sekarang:

- merender text stream character-by-character
- merender markdown via `markdown-it` + `dompurify`
- merender artifact via explicit registry
- menampilkan tool trace untuk debug
- menghubungkan citation marker ke source card anchor

Keputusan UI penting:

- chat tetap single-lane
- layout dibuat fullscreen chat lane
- kontrol debug seperti artifact mode hanya muncul saat AI debug aktif

---

## 13. Historical Note

`ai.runs` + polling tetap penting secara sejarah karena fase itu membuktikan boundary AI pertama.

Tetapi statusnya sekarang:

- retired
- tidak lagi menjadi arsitektur produk aktif
- digantikan oleh chat-first SSE runtime

Dokumen lama yang membahas polling flow sebaiknya dianggap sebagai referensi fase awal, bukan current architecture.

---

## 14. Current Gaps

Yang belum selesai:

- guardrail classifier tahap awal
- human approval checkpoints
- multi-step action planning yang lebih formal
- post-run hooks nyata untuk analytics/notification/follow-up jobs
- observability penuh:
  - stage trace
  - token metrics
  - retrieval metrics
  - audit taxonomy
  - redaction policy

---

## 15. Bottom Line

Per hari ini, sistem AI sudah berada di titik berikut:

- chat-first runtime **sudah nyata**
- provider custom **sudah stabil**
- tool calling **sudah align** dengan Action Pattern existing
- retrieval baseline **sudah hidup**
- typed artifacts dan citation UX **sudah berjalan**

Prioritas arsitektural berikutnya bukan kembali ke polling atau controller-heavy flow, tetapi memperdalam:

1. retrieval quality,
2. generic middleware / guardrail hooks,
3. advanced control flow,
4. observability and governance.
