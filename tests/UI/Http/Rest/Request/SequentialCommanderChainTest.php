<?php

declare(strict_types=1);

namespace SharedBundle\Tests\UI\Http\Rest\Request;

use PHPUnit\Framework\TestCase;
use SharedBundle\UI\Http\Rest\Request\CommanderInterface;
use SharedBundle\UI\Http\Rest\Request\InputBag;
use SharedBundle\UI\Http\Rest\Request\SequentialCommanderChain;

final class SequentialCommanderChainTest extends TestCase
{
    public function test_must_handle_command(): void
    {
        $chain = new SequentialCommanderChain(
            new ACommander(static fn () => self::assertTrue(true))
        );

        $chain->doWithRequest(InputBag::empty());
    }
}

final readonly class ACommander implements CommanderInterface
{
    public function __construct(
        private \Closure $callable,
    ) {
    }

    #[\Override]
    public function support(InputBag $input): bool
    {
        return true;
    }

    #[\Override]
    public function doWithRequest(InputBag $input): void
    {
        call_user_func($this->callable);
    }
}
