<?php

declare(strict_types=1);

namespace Infrastructure\Execution\Adapter\Swoole;

use Application\Execution\Timer as ApplicationTimer;
use Swoole\Timer as SwooleTimer;

class Timer implements ApplicationTimer
{
    #[\Override]
    public function tick(int $intervalMs, callable $callback, array $params = []): int|bool
    {
        return SwooleTimer::tick($intervalMs, $callback, $params);
    }
}
