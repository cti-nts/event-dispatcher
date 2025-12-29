<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use Application\Event\DispatcherFactory;
use Application\Execution\Process;
use Application\Execution\Timer;
use Application\Http\Handler as HttpHandler;
use Application\Http\Request as HttpRequest;
use Application\Http\Response as HttpResponse;
use Application\Http\Server as HttpServer;
use DI\ContainerBuilder;

// Timer interval in milliseconds
const DEFAULT_DISPATCH_INTERVAL_MS = 2 * 60 * 1000;

$builder = new ContainerBuilder();
$builder->addDefinitions('config/di.php');
$container = $builder->build();

$httpServer = $container->get(HttpServer::class);
$httpHandler = $container->get(HttpHandler::class);

$dispatcherConfig = require_once 'config/dispatcher.php';
$dispatcherFactory = new DispatcherFactory($container, $dispatcherConfig);

$process = $container->make(
    Process::class,
    [
        'callback' => function (/* $process */) use ($dispatcherFactory) {
            echo "Starting process...\n";

            $eventDispatcher = $dispatcherFactory->create(setupListener: true);

            $eventDispatcher->start();
            sleep(1);
        }
    ]
);

$httpServer->addProcess($process);

$timer = $container->get(Timer::class);

$httpServer->on(
    'start',
    function (/* HttpServer $httpServer */) use ($dispatcherFactory, $timer) {
        $eventDispatcher = $dispatcherFactory->create(setupListener: false);

        echo "Checking for undispatched events...\n";
        $eventDispatcher->dispatchUndispatched();

        $dispatchIntervalMs = (int)(getenv('DISPATCH_INTERVAL_MS') ?: DEFAULT_DISPATCH_INTERVAL_MS);

        $timer->tick($dispatchIntervalMs, function () use ($eventDispatcher) {
            echo "Periodically checking for undispatched events...\n";
            $eventDispatcher->dispatchUndispatched();
            sleep(1);
        });

        echo "HTTP httpServer is started.\n";
    }
);

$httpServer->on(
    'request',
    function (HttpRequest $request, HttpResponse $response) use ($httpHandler) {
        $httpHandler->handle($request, $response);
    }
);

$httpServer->start();
