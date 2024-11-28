<?php

declare(strict_types=1);

namespace SharedBundle\Persistence\Doctrine;

final class ObjectManagerException extends \Exception
{
    public static function throwable(\Throwable $e): self
    {
        return new self($e->getMessage(), $e->getCode(), $e);
    }
}
