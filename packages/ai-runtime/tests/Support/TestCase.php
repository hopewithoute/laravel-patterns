<?php

namespace Labtime\AiRuntime\Tests\Support;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new class extends Container
        {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };

        Container::setInstance($this->container);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->container);

        $this->container->instance(ContainerContract::class, $this->container);
        $this->container->instance('config', new Repository([]));
        $this->container->instance('files', new Filesystem);
        $this->container->bind('pipeline', fn (Container $container): Pipeline => new Pipeline($container));
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function setConfig(array $values): void
    {
        /** @var Repository $config */
        $config = $this->container->make('config');

        foreach ($values as $key => $value) {
            $config->set($key, $value);
        }
    }
}
