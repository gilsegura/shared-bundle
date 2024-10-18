<?php

declare(strict_types=1);

namespace SharedBundle\EventStore;

use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Shared\Criteria;
use Shared\Domain\DomainEventStream;
use Shared\Domain\DomainMessage;
use Shared\Domain\Uuid;
use Shared\EventStore\DomainEventStreamNotFoundException;
use Shared\EventStore\EventStoreException;
use Shared\EventStore\EventStoreInterface;
use Shared\EventStore\EventStoreManagerInterface;
use Shared\EventStore\EventVisitorInterface;
use Shared\EventStore\PlayheadAlreadyExistsException;
use SharedBundle\Criteria\CriteriaConverterException;
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;

final readonly class DoctrineEventStore extends AbstractObjectManager implements EventStoreInterface, EventStoreManagerInterface
{
    public function __construct(
        EntityManagerInterface $manager,
    ) {
        parent::__construct($manager, $manager->getRepository(DomainMessage::class));
    }

    /**
     * @throws DomainEventStreamNotFoundException
     * @throws CriteriaConverterException
     */
    #[\Override]
    public function load(Uuid $id, ?int $playhead = null): DomainEventStream
    {
        if (null !== $playhead) {
            return $this->loadFromPlayhead($id, $playhead);
        }

        /** @var DomainMessage[] $messages */
        $messages = $this->search(
            new Criteria\AndX(new Criteria\EqId($id)),
            new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
        );

        if ([] === $messages) {
            throw DomainEventStreamNotFoundException::new($id);
        }

        return new DomainEventStream(...$messages);
    }

    /**
     * @throws DomainEventStreamNotFoundException
     * @throws CriteriaConverterException
     */
    private function loadFromPlayhead(Uuid $id, int $playhead): DomainEventStream
    {
        /** @var DomainMessage[] $messages */
        $messages = $this->search(
            new Criteria\AndX(
                new Criteria\EqId($id),
                new Criteria\EqPlayhead($playhead)
            ),
            new Criteria\OrderX(new Criteria\ByPlayhead(Criteria\Expr\Order::ASC))
        );

        if ([] === $messages) {
            throw DomainEventStreamNotFoundException::new($id, $playhead);
        }

        return new DomainEventStream(...$messages);
    }

    #[\Override]
    public function append(DomainEventStream $stream): void
    {
        foreach ($stream->messages as $message) {
            try {
                $this->register($message);
            } catch (ConstraintViolationException) {
                throw PlayheadAlreadyExistsException::new($message->id, $message->playhead);
            } catch (ORMException $e) {
                throw EventStoreException::new($e);
            }
        }
    }

    /**
     * @throws CriteriaConverterException
     */
    #[\Override]
    public function visitEvents(Criteria\AndX|Criteria\OrX $criteria, EventVisitorInterface $eventVisitor): void
    {
        /** @var DomainMessage[] $messages */
        $messages = $this->search(
            $criteria,
            new Criteria\OrderX(new Criteria\ByRecordedAt(Criteria\Expr\Order::ASC))
        );

        foreach ($messages as $message) {
            $eventVisitor->doWithEvent($message);
        }
    }
}
