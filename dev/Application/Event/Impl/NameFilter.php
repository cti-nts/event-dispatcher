<?php

declare(strict_types=1);

namespace Application\Event\Impl;

use Application\Event\Filter;

class NameFilter implements Filter
{
    protected array $names;

    public function __construct(protected readonly array $args)
    {
        $this->names = $args;
    }

    public function matches(array $eventData): bool
    {
        return in_array($eventData['name'], $this->names, true);
    }

    public function getSqlMatcher(): ?string
    {
        if ($this->names === []) {
            return null;
        }

        // Use PostgreSQL's quote_literal equivalent by escaping single quotes
        // and wrapping in dollar-quoted strings for maximum safety
        $quotedNames = array_map(
            function (string $name): string {
                // Escape single quotes by doubling them
                $escaped = str_replace("'", "''", $name);
                // Also escape backslashes to prevent escape sequence attacks
                $escaped = str_replace('\\', '\\\\', $escaped);
                return "'{$escaped}'";
            },
            $this->names
        );

        return "NEW.name = ANY(ARRAY[" . implode(",", $quotedNames) . "])";
    }
}
