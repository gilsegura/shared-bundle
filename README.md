# SHARED BUNDLE

[![tests](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml)
[![codecov](https://codecov.io/github/gilsegura/shared-bundle/graph/badge.svg)](https://codecov.io/github/gilsegura/shared-bundle)
[![static analysis](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml)
[![coding standards](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml)

Symfony bridge for the [gilsegura/shared](https://github.com/gilsegura/shared)
CQRS and Event Sourcing component.

The bundle wires the framework-agnostic building blocks of `gilsegura/shared`
into a Symfony application: a Doctrine-backed event store and object managers,
custom DBAL types for the domain value objects, Messenger-based command and
query buses, a lifecycle-aware event publisher, and a database health check.

## Features

* PHP 8.5+
* Symfony 8.1+
* Doctrine ORM 3 and DBAL 4
* Doctrine-backed event store and read-model persistence
* Custom DBAL types for the shared value objects
* Messenger-based command and query buses
* Lifecycle-aware event publisher with graceful worker shutdown
* Automatic registration of event listeners on the event bus
* Database health check
* Transport-agnostic: route events to any Messenger transport you configure
* Semantic exceptions aligned with `gilsegura/shared`

## Installation

```bash
composer require gilsegura/shared-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    SharedBundle\SharedBundle::class => ['all' => true],
];
```

The bundle requires DoctrineBundle and prepends its own configuration (DBAL
types and Messenger wiring) automatically when it boots.

## DBAL types

The bundle registers custom DBAL types that map the shared value objects to
database columns, prepending them to the Doctrine configuration on boot:

| Type | Maps | Column |
| --- | --- | --- |
| `uuid` | `Uuid` | UUID/string |
| `email` | `Email` | string |
| `hashed_password` | `HashedPassword` | string |
| `not_empty_string` | `NotEmptyString` | string |
| `datetime_immutable` | `DateTimeImmutable` | datetime |
| `serializable` | any `SerializableInterface` | JSON |

The `serializable` type is the bridge to `gilsegura/serializer`: on write it
stores the object as a `{class, attributes}` JSON document; on read it restores
the exact concrete type through the serializer facade. This is how a
`DomainMessage`'s `metadata` and event `payload` are persisted.

Mapping a value object in an entity is then a matter of naming the type:

```xml
<field name="id" column="id" type="uuid"/>
<field name="payload" column="payload" type="serializable"/>
```

## Event store

`DoctrineEventStore` implements the shared `EventStoreInterface` and
`EventStoreManagerInterface` on top of a Doctrine `EntityManager`. It persists
`DomainMessage` streams, enforces playhead uniqueness per aggregate, and exposes
`load()` (optionally up to a playhead), `append()` and `visitEvents()` for
walking stored events through a visitor. Loading a missing or duplicate stream
surfaces the shared `StreamNotFoundException` / `StreamAlreadyExistsException`.

It extends `AbstractObjectManager`, the shared base for Doctrine-backed
repositories described below.

## Object managers

`AbstractObjectManager` is a generic, immutable base for Doctrine repositories.
It wraps an `EntityManager` and a `Selectable` repository, and provides
protected helpers — `search()`, `count()`, `register()` and `unregister()` —
that translate the shared `Criteria` into Doctrine queries and persist or remove
managed objects. Read-model repositories and the event store build on it, so
filtering, ordering and pagination are expressed once in terms of `Criteria`.

## Command and query buses

`MessengerCommandBus` and `MessengerQueryBus` implement the shared
`CommandBusInterface` and `QueryBusInterface` over Symfony Messenger.

The command bus dispatches a command and returns nothing. The query bus
dispatches a query and returns the handler's result, preserving the query's
declared result type (a read model, a list of read models, or `null` when
nothing is found). If no handler processed the query, a `MessengerBusException`
is raised.

Handler failures are unwrapped to their root cause by
`UnwrapsHandlerFailureTrait` and re-thrown, so callers see the original domain
exception rather than Messenger's `HandlerFailedException`. Any other failure is
wrapped in a `MessengerBusException`.

Handlers are plain invokable classes implementing the shared marker interfaces;
Messenger discovers which handler serves which message from the `__invoke`
signature.

## Event publishing

`EventPublisher` decouples recording events from dispatching them. It implements
the shared `EventListenerInterface` to collect `DomainMessage` instances during
the request or command lifecycle, and subscribes to kernel and console
`TERMINATE` events and to Messenger's `WorkerStoppedEvent` to flush the
collected messages to the event bus when the unit of work finishes.

Combined with Messenger's `stop_worker_on_signals`, pending events are flushed on
a graceful `SIGTERM` / `SIGINT` shutdown, so a worker stopped mid-run does not
lose buffered events.

`UnwrapDomainMessageMiddleware` is a Messenger middleware that unwraps a received
`DomainMessage` envelope so listeners receive the domain message directly.

### Routing events to a transport

The publisher dispatches each `DomainMessage` to the event bus without binding
it to any specific transport, so the choice of transport is yours. Route the
domain message to a transport in your Messenger configuration:

```yaml
framework:
    messenger:
        transports:
            async: '%env(MESSENGER_TRANSPORT_DSN)%'

        routing:
            'Shared\Domain\DomainMessage': async
```

The transport DSN can be AMQP, Doctrine, Redis or any other Messenger
transport — the bundle does not require or assume any of them.

## Event listener registration

`EventBusSubscriberPass` is a compiler pass that discovers every service tagged
as an event listener and registers it on the `SimpleEventBus` automatically, so
listeners are wired without manual configuration. Tag a service with the shared
`EventListenerInterface` autoconfiguration and it is subscribed to the bus.

## Criteria conversion

`DoctrineCriteriaConverter` translates the shared `Criteria` expressions into a
Doctrine `Collections\Criteria`, used by the object managers for filtering,
ordering and pagination. Unsupported expressions raise a
`CriteriaConverterException`.

## Health check

`DBALHealthyConnection` is an invokable health check that verifies the Doctrine
DBAL connection is reachable. It returns a boolean and can be wired into a
health endpoint or a console command for readiness probes.

## Requirements

* PHP 8.5+ and Symfony 8.1+
* Doctrine ORM 3 / DBAL 4, DoctrineBundle
* The `ext-pcntl` extension is recommended so Messenger workers can handle
  `SIGTERM` / `SIGINT` for a graceful shutdown that flushes pending events.

## How it fits together

A typical write flow: a command is dispatched through `MessengerCommandBus` to
its handler, which loads an aggregate from `DoctrineEventStore`, applies domain
events, and saves it back. The recorded `DomainMessage` stream is persisted with
the `serializable` DBAL type. `EventPublisher` buffers the resulting events and,
when the lifecycle ends, publishes them to listeners (such as projectors) that
update read models. A read flow dispatches a query through `MessengerQueryBus`
to a handler that reads from a read-model repository built on
`AbstractObjectManager`.

## License

MIT. See [LICENSE](LICENSE).
