<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Message as ApplicationMessage;
use Application\Messaging\MessageBuilder as ApplicationMessageBuilder;
use Application\Messaging\MessageMapper;
use Enqueue\RdKafka\RdKafkaMessage;

class MessageBuilder implements ApplicationMessageBuilder
{
    public function __construct(protected readonly MessageMapper $mapper)
    {
    }

    public function build(array $data): ApplicationMessage
    {
        $message = new Message(new RdKafkaMessage());
        return $this->mapper->map(data: $data, message: $message);
    }
}
