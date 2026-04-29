# Laravel AI Runtime

`labtime/ai-runtime` adalah engine-agnostic AI Runtime Kernel untuk Laravel. Package ini menyediakan infrastruktur terstruktur untuk menjalankan orkestrasi AI dengan sistem middleware, tool registry, dan manajemen artifact yang terintegrasi.

## Fitur Utama

- **Pipeline Middleware**: Pisahkan logika keputusan (decision), pengambilan data (retrieval), dan pembentukan prompt (prompt) ke dalam tahapan yang jelas.
- **Tool Registry**: Daftarkan tool secara eksplisit dengan dukungan metadata dan kebijakan eksekusi.
- **Artifact Management**: Kelola output terstruktur (artifact) dari LLM dengan validasi skema.
- **Observability**: Logging dan recording otomatis untuk setiap tahap eksekusi.
- **Engine Agnostic**: Dapat digunakan dengan driver AI apapun melalui integrasi `laravel/ai`.

## Instalasi

Instal package melalui Composer:

```bash
composer require labtime/ai-runtime
```

Publish file konfigurasi:

```bash
php artisan vendor:publish --tag="ai-runtime-config"
```

## Konsep Dasar

### 1. Runtime Definition
Tempat Anda mendefinisikan "blueprint" dari runtime Anda, termasuk middleware, tool, dan artifact apa saja yang tersedia.

```php
use Labtime\AiRuntime\Foundation\Contracts\RuntimeDefinition;
use Labtime\AiRuntime\RuntimeBuilder;

class AssistantDefinition implements RuntimeDefinition
{
    public function define(RuntimeBuilder $runtime): void
    {
        $runtime
            ->decision([ClassifyIntentMiddleware::class])
            ->retrieval([VectorSearchMiddleware::class])
            ->prompt([PersonalizationMiddleware::class])
            ->tools([SearchTool::class, CreateFileTool::class])
            ->artifacts([TaskArtifact::class]);
    }
}
```

### 2. Runtime Context
Berisi data stateful yang diperlukan selama eksekusi, seperti pesan user, metadata workspace, atau informasi autentikasi.

### 3. Runtime Kernel
Jantung dari package ini yang mengeksekusi pipeline berdasarkan definisi dan konteks yang diberikan.

## Cara Penggunaan

Berikut adalah contoh cara menjalankan runtime:

```php
use Labtime\AiRuntime\Execution\RuntimeKernel;
use Labtime\AiRuntime\Foundation\Context\RuntimeContext;

// 1. Inisialisasi Kernel
$kernel = app(RuntimeKernel::class);

// 2. Siapkan Context dan Definition
$context = new RuntimeContext($messages);
$definition = new AssistantDefinition();

// 3. Jalankan Persiapan (Mengeksekusi Pipeline)
$preparedRun = $kernel->prepareRun($definition, $context);

// 4. Akses Hasil Persiapan
$instructions = $preparedRun->instructions; // Prompt akhir
$tools = $preparedRun->tools; // Daftar tool yang tersedia
$decision = $preparedRun->decision; // Keputusan (Allow/Reject)

// 5. Kirim ke LLM (Contoh menggunakan Laravel AI)
$response = Ai::withTools($tools)->prompt($instructions);
```

## Manajemen Tool & Artifact

Package ini mendukung sinkronisasi manifest untuk keperluan frontend atau integrasi eksternal:

```bash
php artisan ai-runtime:sync-manifests
```

## Tahapan Pipeline

1. **Decision**: Menentukan apakah request user valid, aman, dan masuk dalam scope.
2. **Retrieval**: Mengambil data tambahan (RAG) yang diperlukan untuk menjawab request.
3. **Prompt**: Membentuk instruksi akhir yang akan dikirim ke LLM.

## Lisensi

Package ini berlisensi MIT. Silakan lihat file [LICENSE](LICENSE) untuk detail lebih lanjut.
