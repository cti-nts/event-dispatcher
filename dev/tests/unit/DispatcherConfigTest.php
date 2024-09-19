<?php

declare(strict_types=1);

use Application\Event\Filter;
use Application\Messaging\MessageMapper;
use PHPUnit\Framework\TestCase;

final class DispatcherConfigTest extends TestCase
{
    public function testShouldReturnEventChannelOnChannelKey(): void
    {
        putenv('EVENT_CHANNEL=channel1');

        $config = include 'config/dispatcher.php';

        $this->assertEquals('channel1', $config['channel']);
    }

    public function testReturnedArrayShouldContainFilterConfigIfExists(): void
    {
        putenv('EVENT_FILTER=aFilterClassName|arg1|arg2');

        $config = include 'config/dispatcher.php';

        $this->assertEquals('aFilterClassName', $config['filter']['class']);
        $this->assertEquals(['arg1', 'arg2'], $config['filter']['args']);
    }

    public function testReturnedArrayShouldContainMapperConfig(): void
    {
        putenv('MESSAGE_MAPPER=aMapperClassName|arg1|arg2');

        $config = include 'config/dispatcher.php';

        $this->assertEquals('aMapperClassName', $config['mapper']['class']);
        $this->assertEquals(['arg1', 'arg2'], $config['mapper']['args']);
    }

    public function testReturnedFilterConfigShouldBeNullIfEventFilterIsNotSet(): void
    {
        putenv('EVENT_FILTER=');

        $config = include 'config/dispatcher.php';

        $this->assertNull($config['filter']);
    }

    public function testReturnedFilterClassShouldDefaultToNameFilterWhenOnlyArgsAreSet(): void
    {
        putenv('EVENT_FILTER=|arg1|arg2');

        $config = include 'config/dispatcher.php';

        $this->assertEquals(Filter::class, $config['filter']['class']);
        $this->assertEquals(['arg1', 'arg2'], $config['filter']['args']);
    }

    public function testReturnedMapperClassShouldDefaultToMessagingMessageMapperWhenMapperNotSet(): void
    {
        putenv('MESSAGE_MAPPER=');

        $config = include 'config/dispatcher.php';

        $this->assertEquals(MessageMapper::class, $config['mapper']['class']);
        $this->assertEquals([], $config['mapper']['args']);
    }

    public function testReturnedMapperClassShouldDefaultToMessagingMessageMapperWhenOnlyMapperArgsAreSet(): void
    {
        putenv('MESSAGE_MAPPER=|arg1|arg2');

        $config = include 'config/dispatcher.php';

        $this->assertEquals(MessageMapper::class, $config['mapper']['class']);
        $this->assertEquals(['arg1', 'arg2'], $config['mapper']['args']);
    }
}
