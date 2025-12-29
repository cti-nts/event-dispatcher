<?php

declare(strict_types=1);

namespace Application\Messaging\Impl;

use Application\Messaging\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class DefaultMessageMapperValidationEnhancedTest extends TestCase
{
    public function testThrowsExceptionWhenIdIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: id');

        $mapper = new DefaultMessageMapper();
        $message = $this->createStub(Message::class);

        $mapper->map([
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => [],
            'timestamp' => '2024-01-01 00:00:00',
        ], $message);
    }

    public function testThrowsExceptionWhenNameIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: name');

        $mapper = new DefaultMessageMapper();
        $message = $this->createStub(Message::class);

        $mapper->map([
            'id' => 1,
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => [],
            'timestamp' => '2024-01-01 00:00:00',
        ], $message);
    }

    public function testThrowsExceptionWhenDataIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: data');

        $mapper = new DefaultMessageMapper();
        $message = $this->createStub(Message::class);

        $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'timestamp' => '2024-01-01 00:00:00',
        ], $message);
    }

    public function testThrowsExceptionWhenKeyAttributeIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required field: aggregate_id');

        $mapper = new DefaultMessageMapper();
        $message = $this->createStub(Message::class);

        $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_version' => 1,
            'data' => [],
            'timestamp' => '2024-01-01 00:00:00',
        ], $message);
    }

    public function testThrowsExceptionWhenJsonEncodeFails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Failed to encode event data');

        $mapper = new DefaultMessageMapper();
        $message = $this->createStub(Message::class);

        // Create unserializable data (resource)
        $resource = fopen('php://memory', 'r');

        $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => ['resource' => $resource],
            'timestamp' => '2024-01-01 00:00:00',
        ], $message);

        fclose($resource);
    }

    public function testHandlesValidDataSuccessfully(): void
    {
        $mapper = new DefaultMessageMapper();
        $message = $this->createMock(Message::class);

        $message->expects($this->once())
            ->method('withBody')
            ->with('{"key":"value"}')
            ->willReturnSelf();

        $message->expects($this->exactly(4))
            ->method('withProperty')
            ->willReturnSelf();

        $message->expects($this->exactly(3))
            ->method('withHeader')
            ->willReturnSelf();

        $message->expects($this->once())
            ->method('withKey')
            ->with('1')
            ->willReturnSelf();

        $result = $mapper->map([
            'id' => 1,
            'name' => 'test',
            'aggregate_id' => 1,
            'aggregate_version' => 1,
            'data' => ['key' => 'value'],
            'timestamp' => '2024-01-01 00:00:00',
            'correlation_id' => 'corr-123',
            'user_id' => 'user-456',
        ], $message);

        $this->assertSame($message, $result);
    }
}
