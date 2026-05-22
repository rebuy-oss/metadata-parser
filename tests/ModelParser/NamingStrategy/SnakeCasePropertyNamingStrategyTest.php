<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\ModelParser\NamingStrategy;

use Liip\MetadataParser\ModelParser\NamingStrategy\SnakeCasePropertyNamingStrategy;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;

#[Small]
class SnakeCasePropertyNamingStrategyTest extends TestCase
{
    #[DataProvider('provideGetSerializedNameCases')]
    public function testGetSerializedName(string $input, string $expected): void
    {
        $strategy = new SnakeCasePropertyNamingStrategy();
        $result = $strategy->getSerializedName($input);

        $this->assertSame($expected, $result);
    }

    public static function provideGetSerializedNameCases(): iterable
    {
        return [
            ['camelCase', 'camel_case'],
            ['foo', 'foo'],
            ['word', 'word'],
            ['wORD', 'w_o_r_d'],
            ['', ''],
            ['field1Name', 'field1_name'],
            ['longerCamelCaseName', 'longer_camel_case_name'],
        ];
    }

    #[DataProvider('provideJmsCompatibleSerializedNameCases')]
    public function testJmsCompatibleSerializedName(string $input, string $expected): void
    {
        $strategy = SnakeCasePropertyNamingStrategy::jmsSnakeCase();
        $result = $strategy->getSerializedName($input);

        $this->assertSame($expected, $result);
    }

    public static function provideJmsCompatibleSerializedNameCases(): iterable
    {
        return [
            ['camelCase', 'camel_case'],
            ['foo', 'foo'],
            ['word', 'word'],
            ['wORD', 'w_ord'],
            ['totalVAT', 'total_vat'],
            ['', ''],
            ['field1Name', 'field1_name'],
            ['longerCamelCaseName', 'longer_camel_case_name'],
        ];
    }
}
