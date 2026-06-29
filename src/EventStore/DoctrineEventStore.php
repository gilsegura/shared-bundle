<?php

declare(strict_types=1);

namespace SharedBundle\EventStore;

use Doctrine\ORM\Exception\EntityIdentityCollisionException;
use Shared\Criteria;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Uuid;
use Shared\EventStore\EventStoreException;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use Shared\EventStore\EventVisitorInterface;
use Shared\EventStore\StreamAlreadyExistsException;
use Shared\EventStore\StreamNotFoundException;
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;
use SharedBundle\Persistence\Doctrine\ObjectManagerException;

/**
 * Doctrine-backed event store. Loads and appends domain event streams and
 * visits events for replay. Its constructor arguments come from the
 * #[ObjectManager(DomainMessage::class)] attribute.
 *
 * @template-extends AbstractObjectManager<int, DomainMessage>
 */
#[ObjectManager(DomainMessage::class)]
final readonly class DoctrineEventStore extends AbstractObjectManager implements EventStoreInterface, EventStoreManagerInterface
{
    #[\Override]
    public function load(Uuid $id, ?int $playhead = null): DomainEventStream
    {
        if (null !== $playhead) {
            return $this->loadFromPlayhead($id, $playhead);
        }

        try {
            $messages = $this->search(
                new Criteria\AndX(new Criteria\EqId($id)),
                new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::fromThrowable($e);
        }

        if ([] === $messages) {
            throw StreamNotFoundException::id($id);
        }

        return new DomainEventStream(...$messages);
    }

    /**
     * Loads the events recorded from the given playhead onward (inclusive), in
     * order. Unlike a full load, an empty result is not an error: an aggregate
     * whose snapshot is already at the latest position simply has no later
     * events to replay.
     *
     * @throws EventStoreException
     */
    private function loadFromPlayhead(Uuid $id, int $playhead): DomainEventStream
    {
        try {
            $messages = $this->search(
                new Criteria\AndX(
                    new Criteria\EqId($id),
                    new Criteria\GtePlayhead($playhead)
                ),
                new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::fromThrowable($e);
        }

        return new DomainEventStream(...$messages);
    }

    #[\Override]
    public function append(DomainEventStream $stream): void
    {
        foreach ($stream->messages as $message) {
            try {
                $this->register($message);
            } catch (ObjectManagerException $e) {
                if ($e->getPrevious() instanceof EntityIdentityCollisionException) {
                    throw StreamAlreadyExistsException::playhead($message->id, $message->playhead);
                }

                throw EventStoreException::fromThrowable($e);
            }
        }
    }

    #[\Override]
    public function visitEvents(Criteria\AndX|Criteria\OrX $criteria, EventVisitorInterface $eventVisitor): void
    {
        try {
            $messages = $this->search(
                $criteria,
                new Criteria\OrderX(new Criteria\ByRecordedAt(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::fromThrowable($e);
        }

        foreach ($messages as $message) {
            $eventVisitor($message);
        }
    }
}
