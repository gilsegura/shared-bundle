<?php

declare(strict_types=1);

namespace SharedBundle\UI\Http\Rest\Request;

interface CommanderChainInterface
{
    public function doWithRequest(InputBag $input): void;
}
