# SHARED BUNDLE
[![tests](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml)
[![codecov](https://codecov.io/github/gilsegura/shared-bundle/graph/badge.svg)](https://codecov.io/github/gilsegura/shared-bundle)
[![static analysis](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml)
[![coding standards](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml)

Symfony integration for the `gilsegura/shared` package. It wires the DDD / CQRS /
event-sourcing building blocks onto Symfony Messenger and Doctrine, so an
application writes its commands, queries, handlers, projectors and repositories
and the bundle takes care of the plumbing: the command, query and event buses,
the Doctrine DBAL types for the shared value objects, the event store, and the
attribute-driven wiring for repositories.

## Installation

```bash
composer require gilsegura/shared-bundle
```

Register the bundle (DoctrineBundle is a required dependency, so it must be
registered too):

```php
// config/bundles.php
return [
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    SharedBundle\SharedBundle::class => ['all' => true],
];
```

## Configuration

The bundle defines three Messenger buses (command, query and async event) and
the middleware around them. Two things are left to the application, because they
are deployment decisions the bundle cannot make for you:

**1. A transport and routing for the async event bus.** Domain events are
published to the `messenger.bus.event.async` bus; route the messages you want to
process out of band to a transport:

```yaml
# config/packages/messenger.yaml
framework:
  messenger:
    transports:
      async: '%env(MESSENGER_TRANSPORT_DSN)%'
    routing:
      'Shared\Domain\DomainMessage': async
```

**2. A configured Doctrine connection.** The command bus runs inside a
`doctrine_transaction` middleware, so a working DBAL connection and entity
manager must be configured by the application.

The bundle maps only `Shared\Domain`. **Mapping the application's own entities
and read models is the application's responsibility.**

The bus ids are exposed as constants so configuration and tests never hardcode
the strings:

```php
SharedBundle\SharedBundle::COMMAND_BUS;  // messenger.bus.command
SharedBundle\SharedBundle::QUERY_BUS;    // messenger.bus.query
SharedBundle\SharedBundle::EVENT_BUS;    // messenger.bus.event.async
```

## How events flow

The bundle bridges two worlds: the synchronous, in-process domain event bus and
the asynchronous Messenger bus.

1. A handler applies events on an aggregate and saves it through its repository.
2. On save, the **`SimpleEventBus`** publishes the domain messages synchronously
   to its **domain listeners** — anything implementing `EventListenerInterface`.
   Application **projectors** live here: they update read models in the same
   request, in order, fail-fast.
3. One of those listeners is the bundle's **`EventPublisher`**. It does not
   process the events; it collects them and, on `kernel.terminate` /
   `console.terminate` / `worker.stopped` (i.e. after the response is sent, or
   on `SIGTERM`), dispatches each `DomainMessage` to the async Messenger bus.
4. On the worker side, **`UnwrapDomainMessageMiddleware`** unwraps the
   `DomainMessage` and passes its `payload` — the actual domain event — to the
   regular Symfony Messenger handlers the application writes with
   `#[AsMessageHandler]`.

So: write a **projector** (`EventListenerInterface`) for work that must happen
synchronously in the same request, and a **Messenger handler** for work that
should happen asynchronously off a transport.

## The pieces

### Command and query buses

`MessengerCommandBus` and `MessengerQueryBus` implement the domain
`CommandBusInterface` / `QueryBusInterface` on top of Messenger. They unwrap
Messenger's `HandlerFailedException` so callers see the real domain exception,
not the framework wrapper.

Handlers are autoconfigured by the interface they implement — no tags, no bus
names:

```php
use Shared\CommandHandling\CommandHandlerInterface;

/** @implements CommandHandlerInterface<RegisterUser> */
final readonly class RegisterUserHandler implements CommandHandlerInterface
{
    public function __invoke(RegisterUser $command): void { ... }
}
```

`CommandHandlerInterface` is routed to the command bus and `QueryHandlerInterface`
to the query bus automatically.

### Event publishing

`EventPublisher` and `UnwrapDomainMessageMiddleware` form the bridge described
above. `EventPublisher` is registered both as a domain `EventListenerInterface`
(it collects domain messages) and as a kernel/console/worker subscriber (it
flushes them to the async bus when the process winds down).

### Domain event listeners (projectors)

Any service implementing `EventListenerInterface` is collected onto the
`SimpleEventBus` by a compiler pass. In practice a read-model projector extends
`Shared\ReadModel\AbstractProjector`, which implements that interface and
resolves an `applyXxx` method from each event's short name (the same convention
aggregates use), so a projector only writes the handlers for the events it cares
about:

```php
use Shared\ReadModel\AbstractProjector;

final readonly class UserProjector extends AbstractProjector {...}
```

Events with no matching `applyXxx` method are simply ignored, so each projector
reacts only to the events it needs. Because `AbstractProjector` implements
`EventListenerInterface`, the projector is registered on the event bus with no
extra configuration.

### Doctrine DBAL types

Custom DBAL types map the shared value objects to columns and back, registered
automatically: `Uuid`, `Email`, `HashedPassword`, `NotEmptyString`,
`Serializable` and `DateTimeImmutable`. Use them as column types in the
application's Doctrine mappings.

### Event store

`DoctrineEventStore` is the Doctrine-backed event store (`EventStoreInterface` +
`EventStoreManagerInterface`). It extends `AbstractObjectManager` and is wired
from its `#[ObjectManager(DomainMessage::class)]` attribute, so it carries no
constructor.

### Persistence: object managers

`AbstractObjectManager` is the base for Doctrine-backed repositories and read
models. It resolves the Doctrine repository from the entity class and provides
protected criteria-based `search` / `count` helpers for concrete managers to
build their own query methods on. Concrete managers declare the entity with
`#[ObjectManager]` and need no constructor:

```php
use SharedBundle\Persistence\Doctrine\AbstractObjectManager;
use SharedBundle\Persistence\Doctrine\Attribute\ObjectManager;

/** @template-extends AbstractObjectManager<int, User> */
#[ObjectManager(User::class)]
final readonly class UserReadModelRepository extends AbstractObjectManager
    implements UserReadModelRepositoryInterface { ... }
```

### Event-sourced repositories

A write-side repository extends `AbstractEventSourcingRepository` and declares
its aggregate with `#[AggregateRoot]`. The bundle injects the event store, the
event bus, the stream decorator and an aggregate factory built for that class,
so the repository has no constructor:

```php
use Shared\EventSourcing\AbstractEventSourcingRepository;
use SharedBundle\EventSourcing\Attribute\AggregateRoot;

/** @template-extends AbstractEventSourcingRepository<User> */
#[AggregateRoot(User::class)]
final readonly class UserRepository extends AbstractEventSourcingRepository
    implements UserRepositoryInterface
{
    public function get(Uuid $id, ?int $playhead = null): User { ... }
    public function store(User $user): void { $this->save($user); }
}
```

### Criteria converter

`DoctrineCriteriaConverter` translates the `Shared\Criteria` DSL into a Doctrine
`Criteria`, so repositories query with the domain's own filter/sort objects
instead of Doctrine-specific expressions.

### Health check

`DBALHealthyConnection` is an invokable that reports whether the DBAL connection
is reachable — useful behind a health-check endpoint.

## License

MIT. See [LICENSE](LICENSE).