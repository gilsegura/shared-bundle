<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Request;

interface CommanderInterface
{
    public function support(InputBag $input): bool;

    public function doWithRequest(InputBag $input): void;
}
