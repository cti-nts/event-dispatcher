<?php

declare(strict_types=1);

namespace Infrastructure\Messaging\Adapter\EnqueueRdkafka;

use Application\Messaging\Impl\MessageMapperNoUidSid;
use Application\Messaging\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MessageMapperNoUidSidTest extends TestCase
{
    public function testThrowsExceptionWhenJsonEncodeFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to encode event data');

        $mapper = new MessageMapperNoUidSid();
        $message = $this->createStub(Message::class);

        // Create unserializable data
        $resource = fopen('php://memory', 'r');

        $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => ['resource' => $resource],
            'timestamp' => '2024-01-01 00:00:00.000000',
        ], $message);

        fclose($resource);
    }

    public function testHandlesValidDataWithCorrelationId(): void
    {
        $mapper = new MessageMapperNoUidSid();
        $message = $this->createMock(Message::class);

        $message->expects($this->once())
            ->method('withBody')
            ->with('{"key":"value"}')
            ->willReturnSelf();

        $message->expects($this->atLeastOnce())
            ->method('withProperty')
            ->willReturnSelf();

        $message->expects($this->exactly(3))
            ->method('withHeader')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('withKey')
            ->willReturnSelf();

        $result = $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => ['key' => 'value'],
            'timestamp' => '2024-01-01 12:30:45.123456',
            'correlation_id' => 'corr-123',
        ], $message);

        $this->assertSame($message, $result);
    }

    public function testHandlesValidDataWithoutCorrelationId(): void
    {
        $mapper = new MessageMapperNoUidSid();
        $message = $this->createMock(Message::class);

        $message->expects($this->once())
            ->method('withBody')
            ->with('{"key":"value"}')
            ->willReturnSelf();

        $message->expects($this->atLeastOnce())
            ->method('withProperty')
            ->willReturnSelf();

        $message->expects($this->exactly(3))
            ->method('withHeader')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('withKey')
            ->willReturnSelf();

        $result = $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => ['key' => 'value'],
            'timestamp' => '2024-01-01 12:30:45.123456',
        ], $message);

        $this->assertSame($message, $result);
    }
}
