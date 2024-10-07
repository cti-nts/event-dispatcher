<?php

declare(strict_types=1);

namespace Application\Event\Impl;

use Application\Event\Filter;

class NameFilter implements Filter
{
    protected array $names;

    public function __construct(protected array $args)
    {
        $this->names = $args;
    }

    #[\Override]
    public function matches(array $eventData): bool
    {
        return in_array($eventData['name'], $this->names, true);
    }

    #[\Override]
    public function getSqlMatcher(): ?string
    {
        return "NEW.name IN ('" . implode("','", $this->names) . "')";
    }
}
