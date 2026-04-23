# Current Ingestion Pipeline Pattern

> **Baseline dokumentasi untuk pipeline ingestion lama sebelum refactor total**

## Overview

Pipeline ingestion yang ada saat ini memakai pola **projection -> upsert -> async indexing**.

Tujuannya adalah memproyeksikan entity bisnis (`Project`, `Task`) menjadi `AiKnowledgeSource`, lalu membangun turunan `AiKnowledgeChunk`, lexical index, dan vector representation untuk retrieval runtime.

## Alur Saat Ini

### 1. Domain action memicu sync setelah commit

Mutasi domain tidak langsung menulis knowledge data di tengah transaksi. Mereka menjadwalkan trigger ingestion lewat `DB::afterCommit(...)`.

Contoh jalur trigger:

1. `ProjectCreateAction`, `ProjectUpdateAction` -> `SyncProjectKnowledgeByIdAction`
2. `TaskCreateAction`, `TaskUpdateAction`, `TaskAssignAction`, `TaskMoveAction`, `TaskStatusUpdateAction`, `TaskCompleteAction` -> `SyncTaskKnowledgeCascadeAction`
3. `CommentCreateAction`, `CommentDeleteAction` -> `SyncTaskKnowledgeByIdAction`
4. `ProjectDeleteAction` -> `DeleteProjectKnowledgeSourcesAction`
5. `TaskDeleteAction` -> `DeleteTaskKnowledgeSourcesAction` lalu `SyncProjectKnowledgeByIdAction`

Pattern penting:

1. Mutasi bisnis tetap jadi sumber kebenaran.
2. Ingestion dianggap side effect pasca-commit.
3. Trigger dibungkus action tipis supaya domain action tidak tahu detail indexing.

### 2. Action projection membentuk payload knowledge

Payload source dibentuk oleh builder terpisah:

1. `BuildProjectKnowledgeSourcePayloadAction`
2. `BuildTaskKnowledgeSourcePayloadAction`

Kedua builder ini menjalankan query projection yang deterministik:

1. Mengambil field domain yang relevan untuk retrieval.
2. Menyusun `content` text snapshot.
3. Menyertakan `meta` untuk filter dan citation.
4. Menetapkan `reference_uri` sebagai identity yang stabil.

Pattern penting:

1. Projection logic dipisah dari orchestration.
2. Snapshot dibangun sebagai plain array, bukan model mutation langsung.
3. `content` adalah representasi tekstual final yang akan di-chunk.

### 3. Sync source memakai upsert yang idempotent

`SyncProjectKnowledgeSourceAction` dan `SyncTaskKnowledgeSourceAction` mendelegasikan ke `SyncAiKnowledgeSourceAction`.

`SyncAiKnowledgeSourceAction` lalu:

1. Menjalankan `UpsertAiKnowledgeSourceAction`
2. Menghitung `checksum` dari `content`
3. Menentukan apakah source berubah
4. Hanya dispatch `SyncAiKnowledgeSourceEmbeddingsJob` jika ada perubahan

Identity source saat ini:

1. `organization_id`
2. `source_type`
3. `reference_uri`

Pattern penting:

1. Idempotency berbasis `checksum` + payload comparison.
2. Queue hanya dikirim saat snapshot berubah.
3. Source status di-reset ke `pending` sebelum indexing ulang.

### 4. Indexing mengganti seluruh turunan source

`SyncAiKnowledgeSourceEmbeddingsJob` memanggil `IndexAiKnowledgeSourceAction`.

Indexer saat ini melakukan:

1. Set source status ke `indexing`
2. Pecah `content` menjadi chunks
3. Hapus lexical index lama untuk source
4. Hapus rows `ai_knowledge_chunks` lama
5. Tulis chunk baru
6. Tulis lexical index baru
7. Minta embedding provider membuat embeddings
8. Upsert hasil ke vector store
9. Set source status ke `indexed`

Pattern penting:

1. Rebuild penuh per source, bukan partial patch per chunk.
2. Chunk adalah artefak turunan yang boleh di-recreate total.
3. Status lifecycle source: `pending` -> `indexing` -> `indexed` / `failed`.

### 5. Delete memakai pencarian source lalu cleanup index

Delete path saat ini:

1. `DeleteTaskKnowledgeSourcesAction` mencari semua `task_snapshot` yang cocok.
2. `DeleteProjectKnowledgeSourcesAction` mencari `project_snapshot` dan `task_snapshot` turunan project.
3. Setelah source ID ditemukan, lexical index dihapus.
4. Lalu source dihapus dan chunk ikut hilang via foreign key cascade.

Pattern penting:

1. Delete tidak berjalan dari event model, tetapi dari action eksplisit.
2. Scope delete dibatasi oleh `organization_id`.
3. Project delete ikut membersihkan descendant task snapshot.

## Best Pattern Yang Layak Dipertahankan

Kalau ingestion nanti dibangun ulang, pattern terbaik dari implementasi sekarang adalah:

1. **Side effect after-commit**: ingestion tidak ikut mencampuri transaksi domain.
2. **Projection builder terpisah**: query + format snapshot dipisah dari orchestration.
3. **Idempotent upsert**: perubahan dideteksi sebelum queue/index dijalankan.
4. **Thin trigger actions**: domain action hanya memanggil trigger tingkat tinggi.
5. **Characterization tests**: behavior create/update/delete knowledge diverifikasi dari action domain, bukan hanya unit kecil.

## Reset Saat Ini

Perubahan setelah dokumen ini tidak membangun pipeline baru. Action trigger ingestion untuk sementara di-reset ke mode **no ingestion** supaya refactor berikutnya bisa dimulai dari baseline yang bersih.
