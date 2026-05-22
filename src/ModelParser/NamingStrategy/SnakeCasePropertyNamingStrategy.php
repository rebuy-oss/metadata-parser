<?php

declare(strict_types=1);

namespace Liip\MetadataParser\ModelParser\NamingStrategy;

final class SnakeCasePropertyNamingStrategy implements PropertyNamingStrategyInterface
{
    private string $regex;

    public function __construct(bool $groupUpperCases = false)
    {
        $this->regex = $groupUpperCases ? '/[A-Z]+/' : '/[A-Z]/';
    }

    public static function jmsSnakeCase(): self
    {
        return new self(groupUpperCases: true);
    }

    public function getSerializedName(string $name): string
    {
        return strtolower((string) preg_replace($this->regex, '_\0', $name));
    }
}
