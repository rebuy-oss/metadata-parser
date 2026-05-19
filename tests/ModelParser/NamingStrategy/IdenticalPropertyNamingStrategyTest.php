<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\NamingStrategy;

use Liip\MetadataParser\ModelParser\NamingStrategy\IdenticalPropertyNamingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class IdenticalPropertyNamingStrategyTest extends TestCase
{
    /**
     * @dataProvider provideGetSerializedNameCases
     */
    public function testGetSerializedName(string $input): void
    {
        $strategy = new IdenticalPropertyNamingStrategy();
        $result = $strategy->getSerializedName($input);

        $this->assertSame($input, $result);
    }

    public static function provideGetSerializedNameCases(): iterable
    {
        return [
            ['camelCase'],
            ['foo'],
            ['word'],
            ['wORD'],
            [''],
            ['field1Name'],
            ['longerCamelCaseName'],
        ];
    }
}
