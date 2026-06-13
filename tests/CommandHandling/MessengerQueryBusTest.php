<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

final class MessengerQueryBusTest extends AbstractApplicationTestCase
{
    public function test_must_throw_exception_when_handling_query(): void
    {
        self::expectException(\Exception::class);

        $this->ask(new ThrowableQuery());

        $this->fireTerminateEvents();
    }

    public function test_must_handle_query(): void
    {
        /** @var SerializableObjectsCollection $collection */
        $collection = $this->ask(new AQuery());

        $this->fireTerminateEvents();

        self::assertInstanceOf(SerializableObjectsCollection::class, $collection);
    }
}
