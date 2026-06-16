# SHARED BUNDLE

[![tests](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/tests.yaml)
[![codecov](https://codecov.io/github/gilsegura/shared-bundle/graph/badge.svg)](https://codecov.io/github/gilsegura/shared-bundle)
[![static analysis](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/static-analysis.yaml)
[![coding standards](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml/badge.svg)](https://github.com/gilsegura/shared-bundle/actions/workflows/coding-standards.yaml)

The Symfony integration for [`gilsegura/shared`](https://github.com/gilsegura/shared).
It wires the framework-agnostic DDD/ES/CQRS building blocks to Symfony Messenger
and Doctrine: command/query buses, a Doctrine-backed event store, DBAL types for
the domain value objects, a criteria-to-Doctrine converter, and the event-bus
plumbing.

## Installation

```bash
composer require gilsegura/shared-bundle
```

Requires PHP 8.4+, and runs on **Symfony 7.4 and 8.1**. Register the bundle (it
declares its dependency on DoctrineBundle, so make sure DoctrineBundle is
installed):

```php
// config/bundles.php
return [
    // ...
    SharedBundle\SharedBundle::class => ['all' => true],
];
```

## What it provides

### Command & query buses (Messenger)

`MessengerCommandBus` and `MessengerQueryBus` implement the `shared`
`CommandBusInterface` / `QueryBusInterface` on top of Symfony Messenger. They
unwrap `HandlerFailedException` so your handlers' real exceptions surface
instead of Messenger's wrapper. The bundle preconfigures three buses:

- `messenger.bus.command` — synchronous, transactional (`doctrine_transaction`).
- `messenger.bus.query` — synchronous, no transaction.
- `messenger.bus.event.async` — for asynchronous domain-event handling.

### Doctrine event store

`DoctrineEventStore` persists and loads `DomainEventStream`s through Doctrine,
serializing payloads with `gilsegura/serializer`.

### DBAL types for the domain value objects

Doctrine DBAL types so the `shared` value objects map transparently to columns:
`uuid`, `email`, `hashed_password`, `not_empty_string`, `serializable`, and an
immutable `datetime`. They are registered automatically via the bundle's
`prependExtension`, so you can use them directly in your mappings.

### Criteria → Doctrine

`DoctrineCriteriaConverter` translates the `shared` criteria/expression tree
(`AndX`, `OrX`, `Comparison`, `OrderX`) into a Doctrine `Criteria`, so a query
built with the `shared` DSL runs against a Doctrine repository.
`AbstractObjectManager` gives you a typed base for Doctrine-backed repositories.

### Event-bus wiring

Services implementing `Shared\EventHandling\EventListenerInterface` are
autoconfigured with a tag, and `EventBusSubscriberPass` injects them into the
`SimpleEventBus` at compile time. `EventPublisher` flushes recorded domain
messages to the async event bus on kernel/console/worker termination, so events
are published after the response is sent.

## Symfony 7.4 and 8.1

The bundle targets both the current LTS (7.4) and the latest stable (8.1). The
dependency on DoctrineBundle is declared through `getBundleDependencies()` rather
than the `#[RequiredBundle]` attribute, since that attribute only exists in
Symfony 8.1+; every other API used here is stable across both versions.

## Design notes

- **Thin integration layer.** All domain logic lives in `gilsegura/shared`; this
  package only adapts it to Symfony and Doctrine.
- **Exceptions are typed and unwrapped.** Infrastructure failures become
  `MessengerBusException` / `ObjectManagerException`; handler exceptions are
  unwrapped from Messenger's `HandlerFailedException`.
- **PHP 8.4, strictly analysed.** Built and checked under PHPStan `max` with
  strict rules and the Symfony extension.

## License

MIT. See [LICENSE](LICENSE).
