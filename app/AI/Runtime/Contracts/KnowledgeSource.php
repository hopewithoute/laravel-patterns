<?php

namespace App\AI\Runtime\Contracts;

use App\AI\Runtime\Context\AiRuntimeContext;
use App\AI\Runtime\Retrieval\RetrievalPlan;
use App\AI\Runtime\Retrieval\RetrievalResult;

interface KnowledgeSource
{
    public function supports(RetrievalPlan $plan): bool;

    public function retrieve(AiRuntimeContext $context, RetrievalPlan $plan): RetrievalResult;
}
