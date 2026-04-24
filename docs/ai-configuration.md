# AI Configuration Reference

Dokumen ini merangkum konfigurasi AI yang aktif di aplikasi, terutama yang berasal dari `config/ai.php` dan di-drive lewat environment variables.

Scope dokumen ini sengaja fokus ke konfigurasi **AI kustom aplikasi**. Variabel Laravel standar seperti `APP_*`, `DB_*`, `CACHE_*`, `QUEUE_*`, dan `MAIL_*` tetap mengikuti dokumentasi Laravel bawaan.

## Tujuan

- memberi satu tempat referensi untuk semua env AI yang dipakai aplikasi
- memastikan `.env.example` merefleksikan konfigurasi yang benar-benar dibutuhkan
- menjelaskan default yang aman untuk local development
- menjelaskan pola deploy `Mercure + FrankenPHP`

## Lokasi Konfigurasi

- runtime config utama: `config/ai.php`
- sample environment: `.env.example`
- runtime service wiring: `app/Providers/AiRuntimeServiceProvider.php`

## Default AI Provider

| Variable | Default | Fungsi |
|---|---|---|
| `AI_PROVIDER` | `cliproxyapi` | provider chat/text default |
| `AI_IMAGE_PROVIDER` | `gemini` | provider image default |
| `AI_AUDIO_PROVIDER` | `openai` | provider audio default |
| `AI_TRANSCRIPTION_PROVIDER` | `openai` | provider transcription default |
| `AI_EMBEDDINGS_PROVIDER` | `openai` | provider embeddings default |
| `AI_RERANKING_PROVIDER` | `cohere` | provider reranking default |
| `AI_MODEL` | `gpt-5.4-mini` | model override default untuk text/chat |

## Provider Credentials dan Endpoint

### Anthropic

| Variable | Default |
|---|---|
| `ANTHROPIC_API_KEY` | kosong |
| `ANTHROPIC_URL` | `https://api.anthropic.com/v1` |

### Azure OpenAI

| Variable | Default |
|---|---|
| `AZURE_OPENAI_API_KEY` | kosong |
| `AZURE_OPENAI_URL` | kosong |
| `AZURE_OPENAI_API_VERSION` | `2024-10-21` |
| `AZURE_OPENAI_DEPLOYMENT` | `gpt-4o` |
| `AZURE_OPENAI_EMBEDDING_DEPLOYMENT` | `text-embedding-3-small` |

### Cohere

| Variable | Default |
|---|---|
| `COHERE_API_KEY` | kosong |

### DeepSeek

| Variable | Default |
|---|---|
| `DEEPSEEK_API_KEY` | kosong |

### ElevenLabs

| Variable | Default |
|---|---|
| `ELEVENLABS_API_KEY` | kosong |

### Gemini

| Variable | Default |
|---|---|
| `GEMINI_API_KEY` | kosong |

### Groq

| Variable | Default |
|---|---|
| `GROQ_API_KEY` | kosong |
| `GROQ_URL` | `https://api.groq.com/openai/v1` |

### Jina

| Variable | Default |
|---|---|
| `JINA_API_KEY` | kosong |

### Mistral

| Variable | Default |
|---|---|
| `MISTRAL_API_KEY` | kosong |
| `MISTRAL_URL` | `https://api.mistral.ai/v1` |

### Ollama

| Variable | Default |
|---|---|
| `OLLAMA_API_KEY` | kosong |
| `OLLAMA_BASE_URL` | `http://localhost:11434` |

### CliProxy API

Provider lokal default aplikasi saat ini.

| Variable | Default | Fungsi |
|---|---|---|
| `CLIPROXYAPI_API_KEY` | fallback ke `OPENAI_API_KEY` | auth gateway lokal |
| `CLIPROXYAPI_URL` | `http://127.0.0.1:8317/v1` | base URL gateway |
| `CLIPROXYAPI_MODEL` | fallback ke `AI_MODEL` | model text default |
| `CLIPROXYAPI_EMBEDDINGS_MODEL` | `text-embedding-3-small` | model embeddings |
| `CLIPROXYAPI_EMBEDDINGS_DIMENSIONS` | `1536` | dimensi vector embeddings |

### OpenAI

| Variable | Default |
|---|---|
| `OPENAI_API_KEY` | kosong |
| `OPENAI_URL` | `https://api.openai.com/v1` |

### OpenRouter

| Variable | Default |
|---|---|
| `OPENROUTER_API_KEY` | kosong |

### Voyage AI

| Variable | Default |
|---|---|
| `VOYAGEAI_API_KEY` | kosong |

### xAI

| Variable | Default |
|---|---|
| `XAI_API_KEY` | kosong |
| `XAI_URL` | `https://api.x.ai/v1` |

## Runtime AI

### Lexical Retrieval

| Variable | Default | Fungsi |
|---|---|---|
| `AI_RUNTIME_LEXICAL_DRIVER` | `sqlite_fts5` untuk SQLite, `pgsql_tsvector` untuk PostgreSQL, selain itu `null` | driver lexical search |
| `AI_RUNTIME_LEXICAL_LANGUAGE` | `simple` | language/tokenization profile |

### Telemetry

| Variable | Default | Fungsi |
|---|---|---|
| `AI_RUNTIME_TELEMETRY_DRIVER` | `database` | penyimpanan telemetry runtime |

### Stream Transport

| Variable | Default | Fungsi |
|---|---|---|
| `AI_RUNTIME_STREAM_DRIVER` | `sse` | transport output runtime (`sse`, `mercure`, `redis`) |
| `AI_RUNTIME_STREAM_REDIS_CONNECTION` | kosong | nama koneksi Redis untuk publish |
| `AI_RUNTIME_STREAM_REDIS_PREFIX` | `ai-runtime` | prefix channel Redis |
| `AI_RUNTIME_STREAM_MERCURE_TOPIC_PREFIX` | `ai-runtime` | prefix topic Mercure |

## Mercure + FrankenPHP

Implementasi saat ini membedakan URL publish backend dan URL subscribe frontend.

| Variable | Default | Fungsi |
|---|---|---|
| `MERCURE_PUBLISH_URL` | kosong | URL internal yang dipakai backend untuk publish |
| `MERCURE_SUBSCRIBE_URL` | `/.well-known/mercure` | URL yang dipakai browser untuk subscribe |
| `MERCURE_JWT` | kosong | token publish ke hub Mercure |
| `MERCURE_HUB_URL` | legacy fallback | kompatibilitas lama bila publish/subscribe belum dipisah |

### Rekomendasi Local / Container

Jika Mercure di-serve dari FrankenPHP pada host yang sama:

```env
AI_RUNTIME_STREAM_DRIVER=mercure
MERCURE_PUBLISH_URL=http://php/.well-known/mercure
MERCURE_SUBSCRIBE_URL=/.well-known/mercure
MERCURE_JWT=your-publisher-token
AI_RUNTIME_STREAM_MERCURE_TOPIC_PREFIX=ai-runtime
```

### Kenapa Dipisah

- backend sering publish ke hostname internal container
- browser tidak bisa mengakses hostname internal itu
- browser harus subscribe ke URL yang valid dari perspective client, biasanya same-origin path seperti `/.well-known/mercure`

## Minimal Setup yang Direkomendasikan

### Local default tanpa Mercure

```env
AI_PROVIDER=cliproxyapi
AI_MODEL=gpt-5.4-mini
CLIPROXYAPI_URL=http://127.0.0.1:8317/v1
AI_RUNTIME_STREAM_DRIVER=sse
AI_RUNTIME_TELEMETRY_DRIVER=database
```

### Local dengan Mercure

```env
AI_PROVIDER=cliproxyapi
AI_MODEL=gpt-5.4-mini
CLIPROXYAPI_URL=http://127.0.0.1:8317/v1
AI_RUNTIME_STREAM_DRIVER=mercure
MERCURE_PUBLISH_URL=http://php/.well-known/mercure
MERCURE_SUBSCRIBE_URL=/.well-known/mercure
MERCURE_JWT=your-publisher-token
```

## Catatan Operasional

- simpan secret seperti API key dan `MERCURE_JWT` hanya di `.env`, bukan di source control
- gunakan `.env.example` sebagai kontrak setup minimum untuk developer baru
- jika ada penambahan env baru di `config/ai.php`, update `.env.example` dan dokumen ini dalam perubahan yang sama

