<?php

namespace Labtime\AiRuntime;

use Labtime\AiRuntime\Foundation\Enums\RuntimeMiddlewareStage;

final class RuntimeBuilder
{
    /** @var array<int, class-string> */
    private array $middlewares = [];

    /** @var array<class-string, string> */
    private array $middlewareStages = [];

    /** @var array<int, class-string> */
    private array $tools = [];

    /** @var array<int, class-string> */
    private array $artifacts = [];

    /** @var array<int, class-string> */
    private array $hooks = [];

    /**
     * @param  array<int, class-string>  $middlewares
     */
    public function middleware(array $middlewares): self
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
        }

        return $this;
    }

    /**
     * @param  array<int, class-string>  $middlewares
     */
    public function decision(array $middlewares): self
    {
        return $this->middlewareForStage($middlewares, RuntimeMiddlewareStage::Decision->value);
    }

    /**
     * @param  array<int, class-string>  $middlewares
     */
    public function retrieval(array $middlewares): self
    {
        return $this->middlewareForStage($middlewares, RuntimeMiddlewareStage::Retrieval->value);
    }

    /**
     * @param  array<int, class-string>  $middlewares
     */
    public function prompt(array $middlewares): self
    {
        return $this->middlewareForStage($middlewares, RuntimeMiddlewareStage::Prompt->value);
    }

    /**
     * @param  array<int, class-string>  $middlewares
     */
    private function middlewareForStage(array $middlewares, string $stage): self
    {
        foreach ($middlewares as $middleware) {
            $this->addMiddleware($middleware);
            $this->middlewareStages[$middleware] = $stage;
        }

        return $this;
    }

    /**
     * @param  class-string  $middleware
     */
    public function addMiddleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * @param  array<int, class-string>  $tools
     */
    public function tools(array $tools): self
    {
        foreach ($tools as $tool) {
            $this->addTool($tool);
        }

        return $this;
    }

    /**
     * @param  class-string  $tool
     */
    public function addTool(string $tool): self
    {
        $this->tools[] = $tool;

        return $this;
    }

    /**
     * @param  array<int, class-string>  $artifacts
     */
    public function artifacts(array $artifacts): self
    {
        foreach ($artifacts as $artifact) {
            $this->addArtifact($artifact);
        }

        return $this;
    }

    /**
     * @param  class-string  $artifact
     */
    public function addArtifact(string $artifact): self
    {
        $this->artifacts[] = $artifact;

        return $this;
    }

    /**
     * @param  array<int, class-string>  $hooks
     */
    public function hooks(array $hooks): self
    {
        foreach ($hooks as $hook) {
            $this->addHook($hook);
        }

        return $this;
    }

    /**
     * @param  class-string  $hook
     */
    public function addHook(string $hook): self
    {
        $this->hooks[] = $hook;

        return $this;
    }

    /**
     * @return array<int, class-string>
     */
    public function middlewares(): array
    {
        return array_values($this->middlewares);
    }

    /**
     * @return array<class-string, string>
     */
    public function middlewareStages(): array
    {
        return $this->middlewareStages;
    }

    /**
     * @return array<int, class-string>
     */
    public function registeredTools(): array
    {
        return array_values($this->tools);
    }

    /**
     * @return array<int, class-string>
     */
    public function registeredArtifacts(): array
    {
        return array_values($this->artifacts);
    }

    /**
     * @return array<int, class-string>
     */
    public function registeredHooks(): array
    {
        return array_values($this->hooks);
    }
}
