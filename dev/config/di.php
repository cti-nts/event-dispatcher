<?php

declare(strict_types=1);

use Application\Event\Dispatcher;
use Application\Event\Filter;
use Application\Event\Impl\DefaultDispatcher;
use Application\Event\Impl\NameFilter;
use Application\Event\Store;
use Application\Execution\Process;
use Application\Execution\Timer;
use Application\Http\Handler;
use Application\Http\Impl\PingHandler;
use Application\Http\Server;
use Application\Messaging\Impl\DefaultMessageMapper;
use Application\Messaging\MessageBuilder;
use Application\Messaging\MessageMapper;
use Application\Messaging\Producer;
use Infrastructure\Event\Adapter\Postgres\Store as PostgresStore;
use Infrastructure\Execution\Adapter\Swoole\Process as SwooleProcess;
use Infrastructure\Execution\Adapter\Swoole\Timer as SwooleTimer;
use Infrastructure\Http\Adapter\Swoole\Server as SwooleServer;
use Infrastructure\Messaging\Adapter\EnqueueRdkafka\MessageBuilder as KafkaMessageBuilder;
use Infrastructure\Messaging\Adapter\EnqueueRdkafka\Producer as KafkaProducer;

return [
    Server::class => DI\get(SwooleServer::class), SwooleServer::class => DI\autowire()->constructorParameter('port', (int)getenv('HTTP_PORT')),
    Handler::class => DI\get(PingHandler::class),
    Producer::class => DI\autowire(KafkaProducer::class),
    Process::class => DI\autowire(SwooleProcess::class),
    Store::class => DI\autowire(PostgresStore::class),
    MessageMapper::class => DI\autowire(DefaultMessageMapper::class),
    MessageBuilder::class => DI\autowire(KafkaMessageBuilder::class),
    Dispatcher::class => DI\autowire(DefaultDispatcher::class),
    Timer::class => DI\autowire(SwooleTimer::class),
    Filter::class => DI\autowire(NameFilter::class),
];
