<?php

declare(strict_types=1);

namespace CuyZ\Valinor\Type\Types;

use CuyZ\Valinor\Type\ObjectType;
use CuyZ\Valinor\Type\CompositeType;
use CuyZ\Valinor\Type\Type;

use function array_map;
use function assert;
use function is_a;

/** @internal */
final class ClassType implements ObjectType, CompositeType
{
    public function __construct(
        /** @var class-string */
        private string $className,
        /** @var array<string, Type> */
        private array $generics = [],
        private ?ClassType $parent = null,
    ) {
        $this->className = ltrim($this->className, '\\');
    }

    public function className(): string
    {
        return $this->className;
    }

    public function generics(): array
    {
        return $this->generics;
    }

    public function hasParent(): bool
    {
        return $this->parent instanceof ClassType;
    }

    public function parent(): ClassType
    {
        assert($this->hasParent());

        /** @var ClassType */
        return $this->parent;
    }

    public function accepts(mixed $value): bool
    {
        return $value instanceof $this->className;
    }

    public function matches(Type $other): bool
    {
        if ($other instanceof MixedType || $other instanceof UndefinedObjectType) {
            return true;
        }

        if ($other instanceof UnionType) {
            return $other->isMatchedBy($this);
        }

        if (! $other instanceof ObjectType) {
            return false;
        }

        return is_a($this->className, $other->className(), true);
    }

    public function traverse(): iterable
    {
        foreach ($this->generics as $type) {
            yield $type;

            if ($type instanceof CompositeType) {
                yield from $type->traverse();
            }
        }
    }

    public function toString(): string
    {
        return empty($this->generics)
            ? $this->className
            : $this->className . '<' . implode(', ', array_map(fn (Type $type) => $type->toString(), $this->generics)) . '>';
    }
}
