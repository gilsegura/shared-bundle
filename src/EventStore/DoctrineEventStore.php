<?php

declare(strict_types=1);

namespace SharedBundle\EventStore;

use Doctrine\ORM\EntityManagerInterface;
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
use SharedBundle\Persistence\Doctrine\ObjectManagerException;

/**
 * @template-extends AbstractObjectManager<int, DomainMessage>
 */
final readonly class DoctrineEventStore extends AbstractObjectManager implements EventStoreInterface, EventStoreManagerInterface
{
    public function __construct(
        EntityManagerInterface $manager,
    ) {
        parent::__construct(
            $manager,
            $manager->getRepository(DomainMessage::class)
        );
    }

    #[\Override]
    public function load(Uuid $id, ?int $playhead = null): DomainEventStream
    {
        if (null !== $playhead) {
            return $this->loadFromPlayhead($id, $playhead);
        }

        try {
            /** @var DomainMessage[] $messages */
            $messages = $this->search(
                new Criteria\AndX(new Criteria\EqId($id)),
                new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::throwable($e);
        }

        if ([] === $messages) {
            throw StreamNotFoundException::id($id);
        }

        return new DomainEventStream(...$messages);
    }

    /**
     * @throws EventStoreException
     * @throws StreamNotFoundException
     */
    private function loadFromPlayhead(Uuid $id, int $playhead): DomainEventStream
    {
        try {
            /** @var DomainMessage[] $messages */
            $messages = $this->search(
                new Criteria\AndX(
                    new Criteria\EqId($id),
                    new Criteria\EqPlayhead($playhead)
                ),
                new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::throwable($e);
        }

        if ([] === $messages) {
            throw StreamNotFoundException::playhead($id, $playhead);
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

                throw EventStoreException::throwable($e);
            }
        }
    }

    #[\Override]
    public function visitEvents(Criteria\AndX|Criteria\OrX $criteria, EventVisitorInterface $eventVisitor): void
    {
        try {
            /** @var DomainMessage[] $messages */
            $messages = $this->search(
                $criteria,
                new Criteria\OrderX(new Criteria\ByRecordedAt(Criteria\Expr\Order::ASC))
            );
        } catch (ObjectManagerException $e) {
            throw EventStoreException::throwable($e);
        }

        foreach ($messages as $message) {
            $eventVisitor->__invoke($message);
        }
    }
}
