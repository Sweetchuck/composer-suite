<?php

declare(strict_types = 1);

namespace Sweetchuck\ComposerSuite\Tests\Unit;

use Sweetchuck\ComposerSuite\Utils;

/**
 * @covers \Sweetchuck\ComposerSuite\Utils
 */
class UtilsTest extends TestBase
{

    public function casesIsVector(): array
    {
        return [
            'empty' => [false, []],
            'vector' => [true, ['a', 'b']],
            'first is not zero' => [false, [1 => 'a']],
            'wrong order' => [false, [1 => 'a', 0 => 'b']],
            'associative 1' => [false, ['a' => 'b']],
            'associative 2' => [false, ['a', 'b' => 'c', 'd']],
            'associative 3' => [false, ['a', 'b' => 'c', 2 => 'd']],
        ];
    }

    /**
     * @dataProvider casesIsVector
     */
    public function testIsVector(bool $expected, array $items): void
    {
        $this->tester->assertSame($expected, Utils::isVector($items));
    }
}
