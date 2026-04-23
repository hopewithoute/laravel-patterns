# AI Showcase Integration Roadmap

Peta jalan ini merefleksikan **status implementasi aktual** dan **arah pengembangan berikutnya** per **2026-04-22**.

Roadmap ini tidak lagi mengasumsikan AI masih `not started`. Phase awal sudah dieksekusi, lalu direfaktor menjadi runtime layer yang lebih tepat.

---

## 1. Executive Summary

AI subsystem aplikasi ini sudah bergerak dari:

- proof of concept `ai.runs` + polling,

menjadi:

- chat-first SSE runtime,
- Laravel AI SDK sebagai primitive layer,
- typed artifact UI,
- retrieval abstraction,
- first RAG path dengan citations.

Artinya, fokus roadmap ke depan bukan lagi "memulai AI", tetapi **memperdalam runtime yang sudah ada** agar siap untuk guardrail, approvals, orchestration, dan governance.

---

## 2. Status Implementasi Saat Ini

### Yang Sudah Selesai

- custom provider `cliproxyapi` berbasis OpenAI-compatible gateway
- single-lane chat interface berbasis Inertia/Vue
- SSE streaming
- persisted chat session + conversation memory
- markdown rendering dengan library battle-tested
- typed artifact renderer
- runtime phases:
  - Phase 1: Runtime Contracts
  - Phase 2: Preflight + Prompt Pipeline
  - Phase 3: Artifact Resolver Layer
  - Phase 4: Tool Policy Layer
  - Phase 5: Retrieval Abstraction
- first retrieval-backed artifacts:
  - `source_list`
  - `answer_with_sources`
- inline citation markers yang terhubung ke source cards
- ingestion pipeline untuk knowledge source berbasis observer
- local vector path dengan adapter pattern

### Yang Sudah Ada Tetapi Masih Baseline

- tool execution policy:
  - authz
  - normalization
  - journaling
  - failure capture
- retrieval planner:
  - sudah nyata
  - masih bisa diperdalam kualitas routing-nya
- artifact taxonomy:
  - sudah cukup untuk product slice saat ini
  - belum kaya untuk semua mode output ke depan

### Yang Belum Mulai

- preflight guardrail classifier
- human approval checkpoints
- multi-step orchestration
- observability and governance lengkap

---

## 3. Review Phase Historis

### Phase 0: AI Runtime Boundary & First Tool-Calling Slice

Status:

- **selesai sebagai fase eksplorasi**

Yang berhasil dibuktikan:

- AI bisa mengeksekusi satu tool domain secara aman
- Action Pattern existing tetap bisa dijadikan boundary write
- tenancy dan DTO validation bisa dipertahankan

Catatan:

- flow produk awalnya memakai `ai.runs` + polling
- sekarang flow itu sudah retired
- nilainya tetap penting karena membuktikan vertical slice pertama

### Phase 1-4: Runtime Refactor

Status:

- **selesai sebagai baseline runtime**

Hasil utama:

- orchestration tidak lagi bocor ke controller
- artifact menjadi first-class output
- tool execution menjadi concern terpisah
- Laravel AI SDK dipakai secara lebih tepat sebagai primitive

### Phase 5-6: Retrieval and First RAG Path

Status:

- **sudah berjalan untuk baseline production slice**

Hasil utama:

- retrieval abstraction nyata
- knowledge source DB + vector
- local vector store path
- observer-based ingestion
- citation shaping
- retrieval-backed answer artifact

---

## 4. Current Architecture Direction

Target arsitektur yang sekarang sedang dibangun:

1. Laravel AI SDK tetap menjadi primitive layer
2. application runtime layer mengelola lifecycle orchestration
3. UI consume typed artifacts
4. retrieval, guardrail, approvals, dan governance masuk melalui extension points yang jelas

Modul utamanya:

- `App/AI/Runtime/Preflight/*`
- `App/AI/Runtime/Middleware/*`
- `App/AI/Runtime/Retrieval/*`
- `App/AI/Runtime/Tools/*`
- `App/AI/Runtime/Artifacts/*`
- `App/AI/Runtime/Vectors/*`
- `App/AI/Runtime/Contracts/*`

---

## 5. Updated Phase Map

### Phase 1: Runtime Contracts

Status:

- **done**

Deliverables:

- `AiRuntimeContext`
- lifecycle interfaces
- `ArtifactPayload`
- `ToolExecutionResult`
- `RetrievalResult`

### Phase 2: Preflight + Prompt Pipeline

Status:

- **done**

Deliverables:

- workspace preflight resolver
- prompt middleware chain
- request-to-agent preparation path yang bersih

### Phase 3: Artifact Resolver Layer

Status:

- **done**

Deliverables:

- artifact resolver backend
- deterministic artifact contract
- UI tidak perlu lagi menebak dari plain text

### Phase 4: Tool Policy Layer

Status:

- **done as baseline**

Deliverables:

- authz guard
- tool execution journal
- normalization
- failure capture

### Phase 5: Retrieval Abstraction

Status:

- **done**

Deliverables:

- `KnowledgeSource` nyata
- `RetrievalPlanner` nyata
- retrieval wiring ke runtime preparation

### Phase 6: RAG Integration

Status:

- **partially done**

Deliverables yang sudah ada:

- embedding provider abstraction
- local DB-backed vector store path
- ingestion pipeline
- citation shaping
- `answer_with_sources`

Yang masih tersisa:

- retrieval summarization policy yang lebih matang
- citation UI yang lebih kaya
- retrieval routing / ranking tuning

### Phase 7: Advanced Control Flow

Status:

- **not started**

Target:

- human-in-the-loop checkpoints
- async follow-up jobs
- multi-step plans
- orchestration control flow yang lebih formal

### Phase 8: Observability and Governance

Status:

- **not started**

Target:

- trace per stage
- token/tool/retrieval metrics
- audit taxonomy
- redaction policy
- failure taxonomy

---

## 6. Current Product Capabilities

Yang sudah bisa dijual sebagai showcase teknis:

- chat AI workspace dengan streaming real-time
- tool calling yang align dengan Action Pattern
- retrieval-aware answer dengan citations
- artifact-driven UI
- engine-agnostic vector adapter path

Yang masih berupa roadmap:

- guardrail classifier anti-jailbreak
- approvals / review gates
- deep enterprise governance

---

## 7. Recommended Next Step

Prioritas berikutnya bukan menambah fitur ad-hoc, tetapi memperdalam runtime layer yang sudah ada.

Urutan paling masuk akal:

1. **Guardrail and Generic Middleware Hooks**
   - preflight intent classifier
   - whitelist/blacklist gates
   - optional lightweight classifier sebelum main model call

2. **Retrieval Quality Refinement**
   - ranking/routing
   - retrieval summarization
   - citation strategy

3. **Advanced Control Flow**
   - approvals
   - background follow-up jobs
   - multi-step orchestration

4. **Observability and Governance**
   - tracing
   - metrics
   - audit
   - redaction

---

## 8. Non-Goals for Near Term

Yang belum perlu dilakukan sekarang:

- autonomous agent swarm
- fully dynamic UI DSL dari model
- connector explosion ke banyak sistem eksternal
- rewrite UI chat yang sudah stabil

---

## 9. Bottom Line

Status repo sekarang bukan lagi "AI belum dimulai".

Status yang benar:

- runtime AI **sudah berjalan**
- retrieval baseline **sudah aktif**
- first RAG path **sudah hidup**
- roadmap berikutnya adalah **hardening dan extension**, bukan memulai dari nol

Dokumen resumption operasional terbaru:

- [2026-04-22-ai-runtime-resumption.md](/var/www/laravel_boilerplate/docs/plans/2026-04-22-ai-runtime-resumption.md)
