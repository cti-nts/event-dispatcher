<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Message as ApplicationMessage;
use Enqueue\RdKafka\RdKafkaMessage;

class Message implements ApplicationMessage
{
    public function __construct(protected RdKafkaMessage $delegate)
    {
    }

    #[\Override]
    public function getBody(): string
    {
        return $this->delegate->getBody();
    }

    #[\Override]
    public function getHeaders(): array
    {
        return $this->delegate->getHeaders();
    }

    #[\Override]
    public function getHeader(string $name, mixed $default = null): mixed
    {
        return $this->delegate->getHeader(name: $name, default: $default);
    }

    #[\Override]
    public function getProperties(): array
    {
        return $this->delegate->getProperties();
    }

    #[\Override]
    public function getProperty(string $name, mixed $default = null): mixed
    {
        return $this->delegate->getProperty(name: $name, default: $default);
    }

    #[\Override]
    public function getKey(): ?string
    {
        return $this->delegate->getKey();
    }

    #[\Override]
    public function withBody(string $body): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $delegate->setBody(body: $body);
        return new Message($delegate);
    }

    #[\Override]
    public function withHeader(string $name, mixed $value): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $delegate->setHeader(name: $name, value: $value);
        return new Message($delegate);
    }

    #[\Override]
    public function withProperty(string $name, mixed $value): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $delegate->setProperty(name: $name, value: $value);
        return new Message($delegate);
    }

    #[\Override]
    public function withoutHeader(string $name): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $headers = $delegate->getHeaders();
        unset($headers[$name]);
        $delegate->setHeaders($headers);
        return new Message($delegate);
    }

    #[\Override]
    public function withoutProperty(string $name): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $properties = $delegate->getProperties();
        unset($properties[$name]);
        $delegate->setProperties($properties);
        return new Message($delegate);
    }

    #[\Override]
    public function withKey(string $key): ApplicationMessage
    {
        $delegate = clone $this->delegate;
        $delegate->setKey($key);
        return new Message($delegate);
    }
}
