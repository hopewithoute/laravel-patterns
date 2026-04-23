<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKnowledgeChunk extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'knowledge_source_id',
        'organization_id',
        'project_id',
        'chunk_index',
        'content',
        'token_count',
        'content_hash',
        'embedding',
        'embedding_provider',
        'embedding_model',
        'vector_store',
        'vector_namespace',
        'vector_document_id',
        'vector_chunk_id',
        'embedded_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'chunk_index' => 'integer',
            'token_count' => 'integer',
            'embedding' => 'array',
            'embedded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AiKnowledgeSource::class, 'knowledge_source_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
