<?php

declare(strict_types=1);

namespace Liip\MetadataParser\Metadata;

final readonly class ParameterMetadata implements \JsonSerializable, \Stringable
{
    public function __construct(
        private string $name,
        private bool $required,
        /**
         * @var mixed The default value can be of any type
         */
        private mixed $defaultValue = null,
    ) {
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public static function fromReflection(\ReflectionParameter $reflParameter): self
    {
        if ($reflParameter->isOptional()) {
            return new self($reflParameter->getName(), false, $reflParameter->getDefaultValue());
        }

        return new self($reflParameter->getName(), true);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * @throws \BadMethodCallException if the parameter is required and therefore has no default value
     */
    public function getDefaultValue(): mixed
    {
        if ($this->required) {
            throw new \BadMethodCallException(\sprintf('Parameter %s is required and therefore has no default value', (string) $this));
        }

        return $this->defaultValue;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'required' => $this->required,
            'default_value' => $this->defaultValue,
        ];
    }
}
