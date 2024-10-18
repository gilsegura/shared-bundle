<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\Collection;
use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use Shared\CommandHandling\Item;
use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

abstract class AbstractApplicationTestCase extends KernelTestCase
{
    protected ?CommandBusInterface $commandBus;

    protected ?QueryBusInterface $queryBus;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var CommandBusInterface|null $commandBus */
        $commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->commandBus = $commandBus;

        /** @var QueryBusInterface|null $queryBus */
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $this->queryBus = $queryBus;
    }

    final public function handle(CommandInterface $command): void
    {
        $this->commandBus->handle($command);
    }

    final public function ask(QueryInterface $query): Item|Collection
    {
        return $this->queryBus->ask($query);
    }

    final public function fireTerminateEvents(): void
    {
        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');

        $dispatcher->dispatch(
            new TerminateEvent(
                self::$kernel,
                Request::create('/'),
                new Response()
            ),
            KernelEvents::TERMINATE
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $this->commandBus = null;
        $this->queryBus = null;
    }
}
