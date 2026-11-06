<?php declare(strict_types=1);

namespace ImBrett\LaravelApiQuery\Tests\Unit;

use ImBrett\LaravelApiQuery\Data\SortData;
use ImBrett\LaravelApiQuery\Tests\TestCase;

final class SortDataTest extends TestCase
{
    public function testAcceptsAscendingAndDescendingDirections(): void
    {
        $asc = new SortData('title', 'asc');
        $desc = new SortData('views', 'DESC');

        $this->assertSame('asc', $asc->direction);
        $this->assertSame('DESC', $desc->direction);
    }

    public function testRejectsUnsupportedDirection(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sort direction must be either asc or desc.');

        new SortData('title', 'sideways');
    }
}
