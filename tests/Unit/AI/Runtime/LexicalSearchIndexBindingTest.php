<?php

namespace Tests\Unit\AI\Runtime;

use App\AI\Runtime\Contracts\LexicalSearchIndex;
use App\AI\Runtime\Retrieval\PgsqlTsVectorLexicalSearchIndex;
use App\AI\Runtime\Retrieval\SqliteFtsLexicalSearchIndex;
use Tests\TestCase;

class LexicalSearchIndexBindingTest extends TestCase
{
    public function test_it_resolves_the_pgsql_lexical_index_from_runtime_config(): void
    {
        config()->set('ai.runtime.lexical.driver', 'pgsql_tsvector');
        config()->set('ai.runtime.lexical.language', 'simple');

        $index = $this->app->make(LexicalSearchIndex::class);

        $this->assertInstanceOf(PgsqlTsVectorLexicalSearchIndex::class, $index);
        $this->assertSame('pgsql_tsvector', $index->driverName());
    }

    public function test_it_resolves_the_sqlite_lexical_index_from_runtime_config(): void
    {
        config()->set('ai.runtime.lexical.driver', 'sqlite_fts5');

        $index = $this->app->make(LexicalSearchIndex::class);

        $this->assertInstanceOf(SqliteFtsLexicalSearchIndex::class, $index);
        $this->assertSame('sqlite_fts5', $index->driverName());
    }
}
