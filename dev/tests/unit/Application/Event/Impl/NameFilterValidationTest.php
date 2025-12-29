<?php

declare(strict_types=1);

namespace Application\Event\Impl;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class NameFilterValidationTest extends TestCase
{
    public function testThrowsExceptionWhenNonStringNameProvided(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All filter names must be strings');

        new NameFilter(['valid', 123, 'another']);
    }

    public function testThrowsExceptionWhenArrayContainsNonStrings(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All filter names must be strings');

        new NameFilter([null, 'test']);
    }

    public function testAcceptsValidStringArray(): void
    {
        $filter = new NameFilter(['name1', 'name2', 'name3']);

        $this->assertTrue($filter->matches(['name' => 'name1']));
        $this->assertFalse($filter->matches(['name' => 'other']));
    }

    public function testMatchesReturnsFalseWhenEventNameIsMissing(): void
    {
        $filter = new NameFilter(['test']);

        $this->assertFalse($filter->matches([]));
        $this->assertFalse($filter->matches(['other_key' => 'value']));
    }

    public function testMatchesHandlesEmptyEventData(): void
    {
        $filter = new NameFilter(['test']);

        $this->assertFalse($filter->matches([]));
    }

    public function testGetSqlMatcherReturnsNullForEmptyNames(): void
    {
        $filter = new NameFilter([]);

        $this->assertNull($filter->getSqlMatcher());
    }
}
