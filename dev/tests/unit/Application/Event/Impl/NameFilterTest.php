<?php

declare(strict_types=1);

namespace Application\Event\Impl;

use PHPUnit\Framework\TestCase;

class NameFilterTest extends TestCase
{
    public function testShouldMatchEventsWithMatchingName(): void
    {
        $sut = new NameFilter(['matching1', 'matching2']);

        $this->assertTrue($sut->matches([
            'name' => 'matching1'
        ]));
    }

    public function testShouldNotMatchEventsWithNotMatchingName(): void
    {
        $sut = new NameFilter(['matching1', 'matching2']);

        $this->assertFalse($sut->matches([
            'name' => 'notmatching'
        ]));
    }

    public function testShouldProvideAnSqlMatcher(): void
    {
        $sut = new NameFilter(['matching1', 'matching2']);

        $this->assertEquals("NEW.name = ANY(ARRAY['matching1','matching2'])", $sut->getSqlMatcher());
    }

    public function testShouldReturnNullForEmptyNames(): void
    {
        $sut = new NameFilter([]);

        $this->assertNull($sut->getSqlMatcher());
    }

    public function testShouldEscapeSingleQuotesInNames(): void
    {
        $sut = new NameFilter(["name'with'quotes"]);

        $this->assertEquals("NEW.name = ANY(ARRAY['name''with''quotes'])", $sut->getSqlMatcher());
    }

    public function testShouldEscapeBackslashesInNames(): void
    {
        $sut = new NameFilter(["name\\with\\backslash"]);

        $this->assertEquals("NEW.name = ANY(ARRAY['name\\\\with\\\\backslash'])", $sut->getSqlMatcher());
    }
}
