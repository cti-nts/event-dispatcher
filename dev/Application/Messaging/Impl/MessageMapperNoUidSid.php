<?php

declare(strict_types=1);

namespace Application\Messaging\Impl;

use Application\Messaging\Message;
use Application\Messaging\MessageMapper;
use DateTimeImmutable;

class MessageMapperNoUidSid implements MessageMapper
{
    protected string $keyAttr = 'aggregate_id';

    public function __construct(protected readonly array $args = [])
    {
        if ($args !== []) {
            $this->keyAttr = $args[0];
        }
    }

    public function map(array $data, Message $message): Message
    {
        $res = $message->withBody(json_encode($data['data']))
            ->withProperty('timestamp', (new DateTimeImmutable((string)$data['timestamp']))->format('Y-m-d H:i:s.u'))
            ->withProperty('id', (string)$data['id'])
            ->withHeader('name', (string)$data['name'])
            ->withHeader('aggregate_id', (string)$data['aggregate_id'])
            ->withHeader('aggregate_version', (string)$data['aggregate_version'])
            ->withKey($data[$this->keyAttr]);

        if (!empty($data['correlation_id'])) {
            return $res->withProperty('correlation_id', (string)$data['correlation_id']);
        }

        return $res;
    }
}
