<?php

declare(strict_types=1);

namespace SharedBundle\Tests\CommandHandling;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use Shared\CommandHandling\QueryBusInterface;
use Shared\CommandHandling\QueryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

abstract class AbstractApplicationTestCase extends KernelTestCase
{
    protected ?CommandBusInterface $commandBus;

    protected ?QueryBusInterface $queryBus;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        /** @var CommandBusInterface $commandBus */
        $commandBus = self::getContainer()->get(CommandBusInterface::class);
        $this->commandBus = $commandBus;

        /** @var QueryBusInterface $queryBus */
        $queryBus = self::getContainer()->get(QueryBusInterface::class);
        $this->queryBus = $queryBus;
    }

    final public function handle(CommandInterface $command): void
    {
        assert($this->commandBus instanceof CommandBusInterface);

        $this->commandBus->__invoke($command);
    }

    /**
     * @template TResult
     *
     * @param QueryInterface<TResult> $query
     *
     * @return TResult
     */
    final public function ask(QueryInterface $query): mixed
    {
        assert($this->queryBus instanceof QueryBusInterface);

        return $this->queryBus->__invoke($query);
    }

    final public function fireTerminateEvents(): void
    {
        $kernel = self::$kernel;

        if (
            !$kernel instanceof KernelInterface
            || !self::$booted
        ) {
            $kernel = self::bootKernel();
        }

        $dispatcher = self::getContainer()->get('event_dispatcher');

        assert($dispatcher instanceof EventDispatcherInterface);

        $dispatcher->dispatch(
            new TerminateEvent(
                $kernel,
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
