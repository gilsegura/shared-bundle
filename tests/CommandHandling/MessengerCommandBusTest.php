<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use SharedBundle\CommandHandling\Testing\Command\ACommand;
use SharedBundle\CommandHandling\Testing\Command\ThrowableCommand;

final class MessengerCommandBusTest extends AbstractApplicationTestCase
{
    public function test_must_throw_exception_when_handling_command(): void
    {
        self::expectException(\Exception::class);

        $this->handle(new ThrowableCommand());

        $this->fireTerminateEvents();
    }

    public function test_must_handle_command(): void
    {
        $this->handle(new ACommand());

        $this->fireTerminateEvents();

        self::assertTrue(true);
    }
}
