<?php

declare(strict_types=1);

namespace SharedBundle\CommandHandling;

use Shared\CommandHandling\CommandBusInterface;
use Shared\CommandHandling\CommandInterface;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Command bus backed by Symfony Messenger. Dispatches a command and
 * unwraps Messenger's HandlerFailedException so callers see the real
 * domain exception.
 */
final readonly class MessengerCommandBus implements CommandBusInterface
{
    use UnwrapsHandlerFailureTrait;

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
            $this->unwrap($e);
        } catch (\Throwable $e) {
            throw MessengerBusException::throwable($e);
        }
    }
}
