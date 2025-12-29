<?php

declare(strict_types=1);

namespace Application\Event;

use Application\Messaging\MessageBuilder;
use Application\Messaging\Producer as MessagingProducer;
use DI\Container;

class DispatcherFactory
{
    public function __construct(
        private readonly Container $container,
        private readonly array $config
    ) {
    }

    public function create(bool $setupListener): Dispatcher
    {
        $filter = $this->createFilter();

        return $this->container->make(Dispatcher::class, [
            'store' => $this->container->make(Store::class, [
                'filter' => $filter,
                'setupListener' => $setupListener
            ]),
            'producer' => $this->container->make(MessagingProducer::class, [
                'config' => $this->config['connectionConfig'],
                'channel' => $this->config['channel']
            ]),
            'filter' => $filter,
            'builder' => $this->createMessageBuilder()
        ]);
    }

    private function createFilter(): ?Filter
    {
        if (!$this->config['filter']) {
            return null;
        }

        return $this->container->make(
            $this->config['filter']['class'],
            ['args' => $this->config['filter']['args']]
        );
    }

    private function createMessageBuilder(): MessageBuilder
    {
        return $this->container->make(MessageBuilder::class, [
            'mapper' => $this->container->make(
                $this->config['mapper']['class'],
                ['args' => $this->config['mapper']['args']]
            )
        ]);
    }
}
