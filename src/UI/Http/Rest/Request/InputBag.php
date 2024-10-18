<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Request;

use Shared\Serializer\SerializableInterface;

final class InputBag implements SerializableInterface
{
    private function __construct(
        public array $values,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function kv(string $key, mixed $value): self
    {
        return new self([$key => $value]);
    }

    public function merge(InputBag $input): self
    {
        return new self(array_merge($this->values, $input->values));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->values[$key];
    }

    #[\Override]
    public static function deserialize(array $data): self
    {
        return new self($data);
    }

    #[\Override]
    public function serialize(): array
    {
        return $this->values;
    }
}
