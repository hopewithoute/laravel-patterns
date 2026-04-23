# AI Runtime Layered Architecture and Coherence Analysis

Dokumen ini menjelaskan bagaimana AI runtime bekerja **layer by layer** berdasarkan implementasi yang aktif di repository per **2026-04-22**. Dokumen ini juga berfungsi sebagai analisis koherensi arsitektur: apa yang sudah rapi, apa yang masih baseline, dan bagian mana yang masih perlu di-hardening.

Dokumen ini harus dibaca sebagai **source of truth implementasi saat ini**, bukan sekadar future vision. Beberapa dokumen lama seperti `docs/ai-architecture.md` dan `docs/ai-roadmap.md` masih relevan sebagai konteks, tetapi status observability, retrieval, dan catalog-driven runtime di dokumen ini lebih baru.

---

## 1. Executive Summary

Runtime AI saat ini sudah melewati fase prototype `ai.runs` lama dan sekarang berbentuk:

- chat-first
- SSE streaming
- Laravel AI SDK sebagai primitive layer
- application runtime sebagai orchestration layer
- typed artifacts sebagai kontrak output UI
- retrieval abstraction dengan hybrid retrieval baseline
- post-run hooks dan telemetry persistence sebagai baseline observability

Secara arsitektural, boundary utamanya sudah benar:

- **Laravel AI SDK** menangani provider, agent, conversation, message, streaming, dan tool primitive.
- **Application runtime layer** menangani preflight, retrieval planning, prompt assembly, tool policy, artifact resolution, dan post-run governance.
- **Frontend** tidak lagi menebak struktur dari text mentah; frontend mengonsumsi text markdown dan typed artifacts.

Kesimpulan utamanya:

> Runtime sudah cukup koheren sebagai fondasi produk. Yang belum lengkap bukan boundary dasarnya, tetapi hardening layer lanjutan seperti model-based guardrail, approval checkpoints, tracing yang lebih kaya, dan control flow multi-step.

---

## 2. Design Goals

Goal desain runtime ini:

1. Menjaga Laravel AI SDK tetap dipakai sesuai perannya sebagai provider and agent primitive, bukan dijadikan tempat business orchestration.
2. Memisahkan lifecycle AI ke layer yang bisa diuji dan diperluas:
   - preflight
   - prompt pipeline
   - retrieval
   - tool policy
   - artifact resolution
   - post-run hooks
3. Menjadikan UI bergantung pada artifact typed contract, bukan inference dari plain text.
4. Menyediakan extension point agar RAG, reranker, telemetry, approvals, dan governance bisa masuk tanpa rewrite besar.
5. Menjaga action/domain write tetap align dengan pattern Laravel yang sudah ada, terutama thin controller dan action boundary.

Non-goal fase sekarang:

- autonomous agent swarm
- dynamic UI generation arbitrary dari model
- orchestration multi-step yang kompleks
- enterprise governance penuh

---

## 3. Runtime At a Glance

Entry point runtime yang aktif berada di:

- `app/Http/Controllers/AiChatMessageStreamController.php`
- `app/Actions/AI/PrepareWorkspaceAssistantRunAction.php`
- `app/Actions/AI/AiChatSessionSyncAction.php`

Layer runtime utama berada di:

- `app/AI/Runtime/Context/*`
- `app/AI/Runtime/Preflight/*`
- `app/AI/Runtime/Middleware/*`
- `app/AI/Runtime/Retrieval/*`
- `app/AI/Runtime/Tools/*`
- `app/AI/Runtime/Artifacts/*`
- `app/AI/Runtime/Hooks/*`
- `app/AI/Runtime/Telemetry/*`
- `app/AI/Runtime/Vectors/*`

Provider aktif menggunakan adaptor OpenAI-compatible lokal:

- provider: `cliproxyapi`
- gateway: `app/AI/Gateway/CliProxyApiGateway.php`
- provider class: `app/AI/Providers/CliProxyApiProvider.php`
- config: `config/ai.php`

---

## 4. End-to-End Flow

Alur lengkap prompt saat ini adalah:

1. User mengirim prompt dari chat interface tunggal.
2. `AiChatMessageStreamController` memvalidasi session dan workspace aktif.
3. `PrepareWorkspaceAssistantRunAction` membangun `AiRuntimeContext`.
4. Preflight resolver mengklasifikasikan prompt, artifact intent, capability, retrieval need, dan risk.
5. Retrieval planner memutuskan apakah retrieval diperlukan dan profile mana yang dipakai.
6. Knowledge source mengambil data dari workspace DB, lexical index, dan/atau vector store.
7. Prompt middleware menyusun instruction final untuk agent.
8. Available tools di-resolve dari config, difilter oleh access policy, lalu dibungkus managed wrapper.
9. `WorkspaceAssistantAgent` menjalankan stream melalui Laravel AI SDK.
10. `CliProxyApiGateway` menerjemahkan stream/tool loop OpenAI-compatible ke event Laravel AI SDK.
11. Saat `tool_result` keluar, backend membangun artifact typed secara incremental untuk dikirim ke UI.
12. Saat stream selesai, backend membangun artifact final tambahan seperti `answer_with_sources`.
13. `AiChatSessionSyncAction` menyimpan conversation state, assistant message meta, artifact final, dan runtime summary.
14. Post-run hooks menulis log governance, log summary, dan telemetry persistence.

Secara produk, ini berarti chat UI melihat satu alur konsisten:

- markdown response yang streaming
- tool traces
- artifact cards yang typed
- history yang persisted dengan metadata runtime

---

## 5. Layer-by-Layer Architecture

### 5.1 Chat and Session Layer

Tanggung jawab layer ini:

- memvalidasi session yang benar untuk user dan workspace aktif
- menerima prompt user
- membuka SSE stream
- menghubungkan request ke runtime preparation dan agent execution

File utama:

- `app/Http/Controllers/AiChatMessageStreamController.php`
- `app/Http/Controllers/AiChatController.php`
- `app/Http/Controllers/AiChatSessionController.php`

Reasoning:

- Controller tetap tipis pada sisi domain write.
- Controller hanya menjadi boundary HTTP dan stream transport.
- Orchestration berat dipindahkan ke action/runtime layer.

Catatan koherensi:

- Ini align dengan thin controller pattern.
- Masih ada sedikit event orchestration di controller untuk SSE emission dan incremental artifact push, tetapi itu masih masuk akal karena concern-nya memang transport-level streaming.

### 5.2 Runtime Context Layer

Tanggung jawab layer ini:

- membuat request-scoped state yang konsisten untuk seluruh lifecycle runtime
- menyimpan actor, workspace, prompt, requested artifact mode, provider, model, dan metadata

File utama:

- `app/AI/Runtime/Context/AiRuntimeContext.php`
- `app/AI/Runtime/Context/AiRuntimeContextFactory.php`

Reasoning:

- Semua layer downstream menerima bentuk context yang sama.
- Ini mengurangi kebocoran parameter acak ke berbagai service.
- Runtime menjadi lebih mudah diperkaya dengan metadata baru seperti retrieval summary, project context, atau governance flags.

Catatan koherensi:

- Layer ini sudah kuat dan menjadi fondasi phase 1 yang benar.

### 5.3 Preflight Layer

Tanggung jawab layer ini:

- mengklasifikasikan prompt
- menentukan apakah prompt boleh diproses
- menentukan capability yang diizinkan
- menentukan artifact intent awal
- menentukan apakah retrieval dibutuhkan
- menetapkan risk baseline dan metadata klasifikasi

File utama:

- `app/AI/Runtime/Preflight/WorkspaceAssistantPreflightResolver.php`
- `app/AI/Runtime/Preflight/WorkspacePromptClassifier.php`

Implementasi sekarang:

- classifier berbasis keyword/rule
- blocked phrases dan prompt injection phrases berasal dari `config/ai.php`
- retrieval profile awal dipilih lewat policy selector

Reasoning:

- Guardrail awal ditempatkan sebelum prompt dibangun dan sebelum model dipanggil.
- Ini penting agar penolakan out-of-scope, jailbreak, dan invalid prompt tidak dibebankan ke model utama.
- Capability gating dilakukan sedini mungkin agar tool resolution punya input yang bersih.

Catatan koherensi:

- Boundary layer ini tepat.
- Limitasi utamanya bukan desain, tetapi kualitas classifier yang masih rule-based.
- Arsitekturnya sudah siap untuk ditambah classifier model-based, intent classifier ringan, atau preflight RAG route tanpa mengubah layer lain.

### 5.4 Retrieval Planning Layer

Tanggung jawab layer ini:

- memutuskan apakah retrieval akan dijalankan
- memilih retrieval profile
- memilih sumber retrieval dan filter

File utama:

- `app/AI/Runtime/Retrieval/WorkspaceRetrievalPlanner.php`
- `app/AI/Runtime/Retrieval/WorkspaceRetrievalPolicyProfileResolver.php`

Implementasi sekarang:

- keputusan retrieval berasal dari preflight decision
- profile diselesaikan dari config policy
- result plan berisi strategy, sources, filters, dan metadata intent/classifier

Reasoning:

- Retrieval seharusnya menjadi hasil planning, bukan efek samping prompt middleware.
- Dengan memisahkan planner, keputusan retrieval bisa diobservasi, diuji, dan diubah independen dari provider/model.

Catatan koherensi:

- Ini salah satu boundary yang paling sehat di runtime sekarang.
- Jalur ini sudah siap untuk future planner yang lebih cerdas, misalnya classifier + budget policy + freshness policy.

### 5.5 Retrieval Execution Layer

Tanggung jawab layer ini:

- menjalankan retrieval ke source yang relevan
- menormalisasi documents dan citations
- melakukan fusion dan reranking

File utama:

- `app/AI/Runtime/Retrieval/WorkspaceCompositeKnowledgeSource.php`
- `app/AI/Runtime/Retrieval/WorkspaceDatabaseKnowledgeSource.php`
- `app/AI/Runtime/Retrieval/WorkspaceLexicalKnowledgeSource.php`
- `app/AI/Runtime/Retrieval/WorkspaceVectorKnowledgeSource.php`

Sub-layer yang aktif:

- `workspace_db`
- `lexical_docs`
- `vector_docs`

Implementasi sekarang:

- DB knowledge source mengambil project/task dari workspace secara langsung
- lexical knowledge source memakai adapter `LexicalSearchIndex`
- vector knowledge source memakai `EmbeddingProvider` dan `VectorStore`
- multi-source result digabung melalui `ResultFusion`
- hasil gabungan bisa di-rerank melalui `Reranker`

Reasoning:

- Retrieval sengaja tidak langsung bergantung ke satu backend.
- DB lookup, lexical search, dan vector retrieval punya karakter berbeda dan ditempatkan sebagai source terpisah.
- Fusion dan reranking menjadi explicit step agar hybrid retrieval bisa ditingkatkan tanpa mengganti knowledge source contract.

Catatan koherensi:

- Ini arsitektur retrieval yang cukup matang untuk baseline.
- Adapter pattern pada embeddings, vector store, lexical index, reranker, dan fusion sudah tepat.
- Retrieval masih baseline pada kualitas ranking, tetapi boundary-nya sudah benar.

### 5.6 Prompt Middleware Layer

Tanggung jawab layer ini:

- menyusun instruction final untuk model
- menyisipkan context workspace
- menyisipkan operating constraints
- menyisipkan retrieval summary
- menyisipkan artifact behavior guidance

File utama:

- `app/AI/Runtime/Middleware/WorkspaceContextPromptMiddleware.php`
- `app/AI/Runtime/Middleware/WorkspaceOperatingConstraintsPromptMiddleware.php`
- `app/AI/Runtime/Middleware/WorkspaceRetrievalPromptMiddleware.php`
- `app/AI/Runtime/Middleware/WorkspaceArtifactPromptMiddleware.php`

Reasoning:

- Prompt assembly adalah pipeline, bukan satu string builder yang bercampur dengan controller logic.
- Masing-masing concern prompt bisa berkembang sendiri tanpa saling menimpa.
- Ini membuka jalan untuk future middleware seperti observability tags, model selection hints, RAG policy notes, approval mode, atau compliance banners.

Catatan koherensi:

- Layer ini sudah berada di tempat yang tepat.
- Saat ini middleware masih mostly additive string-based, jadi ke depan mungkin perlu template/prompt object yang lebih terstruktur bila kompleksitas meningkat.

### 5.7 Tool Resolution and Policy Layer

Tanggung jawab layer ini:

- menentukan tool mana yang tersedia
- memfilter tool berdasarkan capability
- membungkus tool dengan managed runtime wrapper
- menerapkan policy execution, normalization, retry, dan failure classification

File utama:

- `app/AI/Runtime/Tools/ConfigAvailableToolResolver.php`
- `app/AI/Runtime/Tools/WorkspaceToolAccessResolver.php`
- `app/AI/Runtime/Tools/ConfigManagedToolFactory.php`
- `app/AI/Runtime/Tools/ConfigToolMetadataResolver.php`
- `app/AI/Runtime/Tools/WorkspaceToolExecutionPolicy.php`
- `app/AI/Runtime/Tools/RuntimeToolCatalog.php`
- `app/AI/Runtime/ManagedTools/CreateTaskTool.php`

Implementasi sekarang:

- tool catalog dan metadata berasal dari `config('ai.runtime.tools.definitions')`
- access resolver memeriksa allowed capabilities dari preflight
- managed tool wrapper mencatat `ToolExecutionResult` ke journal
- execution policy menangani authz, retry transient error, failure behavior, dan logging

Reasoning:

- Model seharusnya hanya melihat tool yang memang tersedia dan diizinkan.
- Tool domain existing tetap dipakai, tetapi dibungkus agar runtime punya hook untuk policy, telemetry, dan artifact generation.
- Ini menjaga Action Pattern existing tetap menjadi boundary domain, bukan digantikan oleh tool runtime.

Catatan koherensi:

- Layer ini align dengan existing action pattern dan cukup bersih.
- Kelemahan saat ini adalah managed wrapper masih per-tool dan belum masuk ke generic wrapper stack yang lebih luas, tetapi kontraknya sudah mendukung evolusi ke arah itu.

### 5.8 Provider and Gateway Layer

Tanggung jawab layer ini:

- menyediakan provider OpenAI-compatible lokal
- menangani text generation, structured output, tool loop, dan stream translation

File utama:

- `app/AI/Gateway/CliProxyApiGateway.php`
- `app/AI/Providers/CliProxyApiProvider.php`
- `app/Providers/AppServiceProvider.php`

Implementasi sekarang:

- runtime memakai provider `cliproxyapi`
- gateway memanggil `chat/completions`
- gateway menangani recursive tool loop sendiri
- gateway menerjemahkan chunk streaming menjadi event Laravel AI SDK seperti `stream_start`, `text_delta`, `tool_call`, `tool_result`, `stream_end`

Reasoning:

- Compatibility gap harus ditutup di gateway, bukan di controller.
- Dengan begitu controller dan runtime layer tetap bicara dalam abstraksi Laravel AI SDK.
- Structured output, streaming, dan tool chaining tetap dipusatkan di provider boundary.

Catatan koherensi:

- Ini keputusan yang sangat tepat.
- Provider customization ditempatkan pada boundary yang benar, sehingga aplikasi tidak bocor ke wire protocol OpenAI-compatible.

### 5.9 Artifact Resolution Layer

Tanggung jawab layer ini:

- mengubah hasil tool dan retrieval menjadi typed artifacts
- menentukan artifact intent dan type
- membuat kontrak data yang stabil untuk UI

File utama:

- `app/AI/Runtime/Artifacts/WorkspaceArtifactResolver.php`
- `app/Actions/AI/BuildAiArtifactsAction.php`
- `app/AI/Runtime/Artifacts/RuntimeArtifactModeCatalog.php`

Implementasi sekarang:

- tool result bisa menghasilkan artifact eksplisit atau fallback typed artifact
- retrieval bisa menghasilkan artifact seperti `source_list` dan `answer_with_sources`
- artifact mode tersedia secara catalog-driven dari config

Reasoning:

- Artifact seharusnya dibangun di backend, bukan dideteksi di frontend.
- UI cukup menjadi renderer typed payload.
- Ini mengurangi heuristik rapuh di frontend dan membuat output contract lebih deterministik.

Catatan koherensi:

- Boundary ini sudah benar dan sangat penting untuk masa depan.
- Heuristik artifact intent masih ada untuk beberapa kasus auto mode, tetapi itu ada di resolver, bukan tersebar di UI.

### 5.10 Persistence and Post-Run Layer

Tanggung jawab layer ini:

- sinkronisasi conversation id dan session state
- menyimpan artifact final ke message meta
- membangun runtime run report
- memicu post-run hooks

File utama:

- `app/Actions/AI/AiChatSessionSyncAction.php`
- `app/AI/Runtime/Execution/RuntimeRunReport.php`
- `app/AI/Runtime/Hooks/CompositePostRunHook.php`
- `app/AI/Runtime/Hooks/LogRuntimeSummaryHook.php`
- `app/AI/Runtime/Hooks/LogRuntimeGovernanceHook.php`
- `app/AI/Runtime/Hooks/PersistRuntimeTelemetryHook.php`

Reasoning:

- Persistence bukan hanya menyimpan text assistant, tetapi juga runtime summary yang dibutuhkan untuk audit, history, dan analytics.
- Post-run hooks membuat persistence, logging, telemetry, analytics, dan follow-up side effects tidak menempel ke controller.

Catatan koherensi:

- Layer ini sudah menunjukkan desain runtime yang dewasa.
- Ini juga membuktikan observability tidak lagi “belum dimulai”; baseline observability sudah aktif.

### 5.11 Telemetry and Analytics Layer

Tanggung jawab layer ini:

- menyimpan telemetry run per response
- menyimpan breakdown retrieval per source
- menyediakan analytics read-side untuk dashboard/query service

File utama:

- `app/AI/Runtime/Telemetry/DatabaseTelemetryStore.php`
- `app/AI/Runtime/Telemetry/DatabaseTelemetryAnalytics.php`
- contracts:
  - `app/AI/Runtime/Contracts/TelemetryStore.php`
  - `app/AI/Runtime/Contracts/TelemetryAnalytics.php`

Implementasi sekarang:

- storage dan analytics memakai adapter berbeda tetapi contract-nya eksplisit
- data disimpan per run dan per source
- analytics bisa diquery per retrieval profile, intent, dan source key

Reasoning:

- Write-side dan read-side telemetry memang punya concern berbeda.
- Store bertugas menulis fakta operasional.
- Analytics bertugas menyajikan agregasi untuk review kualitas retrieval, cost, dan failure trend.

Catatan koherensi:

- Ini desain yang sehat.
- Memisahkan `TelemetryStore` dan `TelemetryAnalytics` justru lebih tepat daripada memaksa satu adapter melakukan dua tanggung jawab yang berbeda.

---

## 6. Current Feature Set

Feature yang sudah berjalan:

- chat interface tunggal
- persisted chat sessions
- SSE streaming
- markdown response rendering
- OpenAI-compatible custom provider
- tool calling untuk action domain
- managed tool wrapping
- config-driven tool catalog
- config-driven artifact mode catalog
- preflight keyword guardrail baseline
- capability-based tool access resolution
- prompt middleware pipeline
- workspace DB retrieval
- lexical retrieval
- vector retrieval
- fusion dan optional reranking path
- citation shaping
- typed artifact rendering
- `source_list`
- `answer_with_sources`
- post-run summary logging
- governance logging
- telemetry persistence
- telemetry analytics read-side

Feature yang sudah ada tetapi masih baseline:

- keyword classifier
- retrieval profile routing
- fusion weighting
- reranker integration
- auto artifact intent heuristics
- tool failure behavior policy

Feature yang belum ada:

- model-based guardrail / intent classifier
- human approval checkpoints
- async follow-up jobs
- orchestration multi-step
- stage-level tracing UI atau observability dashboard penuh

---

## 7. Architectural Reasoning

Bagian ini menjelaskan kenapa runtime ini secara umum koheren.

### 7.1 Primitive vs Orchestration Separation

Keputusan paling penting adalah mempertahankan Laravel AI SDK sebagai primitive layer dan tidak memindahkan business orchestration ke SDK-facing classes.

Ini benar karena:

- provider concern berbeda dari business lifecycle concern
- prompt, retrieval, artifact, dan governance adalah concern aplikasi
- SDK tetap bisa diganti/ditingkatkan tanpa membongkar domain runtime

### 7.2 Typed Artifacts as Product Contract

Typed artifact adalah keputusan arsitektural yang tepat karena:

- UI menjadi stabil
- history bisa menyimpan payload yang sama dengan yang di-render saat stream
- retrieval-backed output seperti citations bisa dihadirkan tanpa regex dan string parsing di frontend

### 7.3 Retrieval as Pipeline, Not Prompt Hack

Retrieval dipisah ke planner + source + fusion + reranking.

Ini koheren karena:

- retrieval bisa diukur
- retrieval bisa diubah per profile
- retrieval tidak terkunci ke vector saja
- DB facts, lexical evidence, dan semantic similarity bisa hidup bersama

### 7.4 Policy Layer Around Tools

Tool tidak dieksekusi langsung.

Ini benar karena:

- authz harus terjadi di runtime, bukan di prompt
- retry dan failure semantics harus deterministik
- runtime butuh normalized tool results untuk artifact, telemetry, dan audit

### 7.5 Post-Run Hooks for Side Effects

Post-run hook adalah pola yang benar untuk pertumbuhan runtime karena:

- persistence, analytics, audit, notification, dan async jobs tidak seharusnya digabung ke sync controller path
- side effects bisa ditambah tanpa mengubah lifecycle utama

---

## 8. Coherence Review

### 8.1 Yang Sudah Koheren

- Boundary runtime terhadap Laravel AI SDK sudah jelas.
- Controller relatif tipis dan tidak memegang domain write logic.
- Prompt enrichment tidak bocor ke controller.
- Retrieval punya abstraction yang nyata dan extensible.
- Tool policy sudah berdiri sebagai layer sendiri.
- Artifact resolution sudah menjadi concern backend.
- Telemetry write-side dan analytics read-side sudah terpisah.
- Catalog-driven tool dan artifact mode membuat runtime lebih declarative.

### 8.2 Yang Masih Sedikit Coupled

- SSE controller masih memegang alur emission event dan incremental artifact push.
- Artifact intent auto mode masih memakai heuristik prompt untuk beberapa kasus.
- Managed tool wrapping masih dominan per-tool wrapper, belum generic middleware stack antar tool.
- Retrieval context masih disuntikkan sebagai summary prompt, belum menjadi structured evidence object di seluruh jalur runtime.

### 8.3 Yang Masih Baseline

- Guardrail masih keyword-based.
- Reranker default masih bisa null dan kualitas ranking bergantung konfigurasi lokal.
- Governance baru berupa logs dan telemetry persistence, belum ada active enforcement layer seperti approval gates.
- Tidak ada planner untuk multi-step execution.

### 8.4 Apakah Perlu Rombak Besar?

Tidak.

Secara keseluruhan arsitektur runtime tidak butuh rewrite. Yang dibutuhkan adalah:

- memperdalam kualitas setiap layer
- menambah control points baru
- memperkaya observability

Foundation-nya sudah cukup tepat.

---

## 9. Review Against Early Phases

### Phase 0

Phase 0 berhasil membuktikan tool calling slice pertama dan alignment dengan action pattern domain. Nilai utamanya tetap penting, walaupun flow produk lama `ai.runs` + polling sudah tidak dipakai.

### Phase 1

Contracts dan `AiRuntimeContext` menjadi keputusan awal yang benar. Hampir semua refactor berikutnya bertumpu pada fondasi ini.

### Phase 2

Preflight dan prompt pipeline berhasil memindahkan preparation logic keluar dari controller. Ini mengurangi kebocoran orchestration.

### Phase 3

Artifact resolver adalah titik balik penting. Runtime mulai berbicara dalam typed output, bukan plain text semata.

### Phase 4

Tool policy layer berhasil menjaga tool calling tetap align dengan action pattern existing dan menambah ruang untuk retry, governance, dan telemetry.

### Phase 5

Retrieval abstraction berhasil dibangun dengan cara yang extensible dan provider-agnostic.

### Phase 6

First RAG path sudah nyata:

- ingestion pipeline ada
- vector path ada
- lexical path ada
- citations ada
- retrieval-backed artifact ada

Jadi phase ini belum sepenuhnya selesai, tetapi sudah live sebagai baseline production slice.

---

## 10. Main Gaps

Gap utama saat ini:

1. **Preflight intelligence masih sederhana**
   - belum ada lite classifier model atau retrieval-aware guardrail
2. **No explicit approval control flow**
   - approval masih artifact/UI concept, belum lifecycle checkpoint
3. **No asynchronous continuation path**
   - post-run hook belum dipakai untuk background continuation atau follow-up jobs
4. **Observability belum end-to-end**
   - sudah ada telemetry, tetapi belum ada trace visualization, stage timing, dan dashboard operasional
5. **Artifact taxonomy masih sempit**
   - cukup untuk current product slice, belum kaya untuk workflows mendatang
6. **Prompt middleware masih string-first**
   - masih aman sekarang, tetapi akan makin rapuh bila jumlah constraints bertambah besar

---

## 11. Recommended Hardening Order

Urutan hardening yang paling masuk akal:

1. Tambah **preflight intelligence layer** yang lebih kuat
   - intent classifier ringan
   - whitelist/blacklist guardrail
   - retrieval route policy
2. Tambah **runtime tracing enrichment**
   - stage timings
   - retrieval decision trace
   - tool policy trace
3. Tambah **approval-capable control flow**
   - explicit pause/resume checkpoint
   - approval artifact linked to runtime state
4. Rapikan **artifact taxonomy**
   - lebih banyak typed contract untuk domain workflows
5. Evolusi **tool wrapper pipeline**
   - generic pre/post hooks per tool execution

---

## 12. Bottom Line

AI runtime saat ini sudah memiliki bentuk arsitektur yang masuk akal dan koheren:

- provider concern berada di gateway/provider layer
- orchestration concern berada di runtime layer
- domain write tetap mengikuti action pattern existing
- UI mengonsumsi kontrak typed artifact
- retrieval dan telemetry sudah menjadi subsystem nyata, bukan placeholder

Yang tersisa sekarang bukan membangun ulang runtime, melainkan memperdalam kualitasnya agar siap untuk:

- guardrail yang lebih kuat
- approval workflow
- retrieval quality tuning
- governance dan observability yang lebih matang

Jika besok pekerjaan dilanjutkan, titik paling aman untuk resumption adalah:

1. mempertahankan boundary yang sudah ada
2. menambah hardening per layer
3. menghindari shortcut yang memindahkan logic kembali ke controller atau frontend
