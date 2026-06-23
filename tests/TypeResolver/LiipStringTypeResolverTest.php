<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\TypeResolver;

use Liip\MetadataParser\TypeResolver\LiipStringTypeResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\TypeInfo\TypeResolver\StringTypeResolver;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolverInterface;

class LiipStringTypeResolverTest extends TestCase
{
    private TypeResolverInterface $liipStringTypeResolver;

    protected function setUp(): void
    {
        $this->liipStringTypeResolver = new LiipStringTypeResolver(new StringTypeResolver());
    }

    #[DataProvider('provideResolveDoesNotAddArrayKeyCases')]
    public function testResolveDoesNotAddArrayKey(string $inputType, ?string $expectedType = null): void
    {
        $type = $this->liipStringTypeResolver->resolve($inputType);

        $this->assertSame($expectedType ?? $inputType, $type->__toString());
    }

    public static function provideResolveDoesNotAddArrayKeyCases(): iterable
    {
        yield [
            'Doctrine\Common\Collections\ArrayCollection<string>',
        ];

        yield [
            'Doctrine\Common\Collections\Collection<int>',
        ];

        yield [
            'Doctrine\Common\Collections\ArrayCollection<string, int>',
        ];

        yield [
            'Doctrine\Common\Collections\ArrayCollection<int|string, \DateTime>',
        ];

        yield [
            'Doctrine\Common\Collections\ArrayCollection<array<string, Doctrine\Common\Collections\Collection<int[]>>>',
            'Doctrine\Common\Collections\ArrayCollection<array<string, Doctrine\Common\Collections\Collection<array<int>>>>',
        ];

        yield [
            'iterable<\DateTime>',
        ];

        yield [
            'iterable<int|string, \DateTime>',
        ];

        yield [
            'iterable<int|string, iterable<int[]>>',
            'iterable<int|string, iterable<array<int>>>',
        ];

        yield [
            'array<string>',
        ];

        yield [
            'array<int, string>',
        ];

        yield [
            'string[]',
            'array<string>',
        ];

        yield [
            '\DateTime[]',
            'array<\DateTime>',
        ];

        yield [
            'array<array<string>>',
        ];

        yield [
            'array<array<string>>|null',
        ];

        yield [
            'array<array<int, array<\DateTime[]>>>',
            'array<array<int, array<array<\DateTime>>>>',
        ];

        yield [
            'array<iterable<int, \ArrayIterator<Doctrine\Common\Collections\Collection<\DateTime[]>>>>',
            'array<iterable<int, \ArrayIterator<Doctrine\Common\Collections\Collection<array<\DateTime>>>>>',
        ];

        yield [
            'array<int[]>|\ArrayIterator<\DateTime>|Doctrine\Common\Collections\ArrayCollection<string, \DateTime[]>|null',
            'Doctrine\Common\Collections\ArrayCollection<string, array<\DateTime>>|\ArrayIterator<\DateTime>|array<array<int>>|null',
        ];
    }
}
