<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class MessengerCommandBus implements CommandBusInterface
{
    public function __construct(
        private MessageBusInterface $messageBus,
    ) {
    }

    #[\Override]
    public function __invoke(CommandInterface $command): void
    {
        try {
            $this->messageBus->dispatch($command);
        } catch (HandlerFailedException $e) {
            while ($e instanceof HandlerFailedException) {
                $e = $e->getPrevious();
            }

            if (!$e instanceof \Throwable) {
                throw new MessengerBusException();
            }

            throw $e;
        } catch (\Throwable $e) {
            throw MessengerBusException::throwable($e);
        }
    }
}
