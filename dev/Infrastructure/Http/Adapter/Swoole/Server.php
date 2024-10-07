<?php

declare(strict_types=1);

namespace Infrastructure\Http\Adapter\Swoole;

use Application\Execution\Process;
use Application\Http\Server as HttpServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleServer;

class Server implements HttpServer
{
    protected SwooleServer $delegate;

    public function __construct(protected int $port)
    {
        $this->delegate = new SwooleServer(host: '0.0.0.0', port: $port);
    }

    #[\Override]
    public function start(): void
    {
        $this->delegate->start();
    }

    #[\Override]
    public function on(string $eventName, callable $callback): void
    {
        $swooleCallbak = match ($eventName) {
            'start' => function (SwooleServer $server) use ($callback) {
                $callback($this);
            },
            'request' => function (SwooleRequest $request, SwooleResponse $response) use ($callback) {
                $callback(new Request($request), new Response($response));
            },
            default => $callback
        };
        $this->delegate->on(event_name: $eventName, callback: $swooleCallbak);
    }

    #[\Override]
    public function addProcess(Process $process): bool
    {
        return (bool)$this->delegate->addProcess($process->getDelegate());
    }
}
