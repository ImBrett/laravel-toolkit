Listener Attributes
===================

Tiny helper that lets you register event listeners in Laravel using PHP attributes instead of manual service provider wiring.

Features
--------

- `#[ListensTo(Event::class)]` attribute for listener methods.
- Service provider scans configured subscriber classes and registers them with the event dispatcher.
- Works with container-resolved listeners and repeatable attributes.

Installation
------------

```bash
composer require imbrett/laravel-listener-attributes
```

Usage
-----

1. Configure your subscriber classes (e.g. in `config/listener-attributes.php`):

```php
return [
    'subscribers' => [
        \App\Listeners\AuthSubscriber::class,
    ],
];
```

2. Annotate listener methods:

```php
use ImBrett\LaravelListenerAttributes\Attributes\ListensTo;

class AuthSubscriber
{
    #[ListensTo(UserLoggedIn::class)]
    public function onLogin(UserLoggedIn $event): void
    {
        // ...
    }
}
```

The service provider will discover those methods at boot and call `$dispatcher->listen()` for you. Listeners are registered using the standard `[$class, $method]` signature so they benefit from container resolution on every dispatch.

Configuration
-------------

Publish and edit the default configuration:

```bash
php artisan vendor:publish --tag=listener-attributes-config
```

Testing
-------

`make test TEST_ARGS="--testsuite=ListenerAttributes"`

Documentation
-------------

- [`docs/index.md`](docs/index.md) – documentation table of contents.
- [`docs/getting-started.md`](docs/getting-started.md) – configuration and attribute usage.
- [`docs/advanced.md`](docs/advanced.md) – repeatable attributes, conditional registration, and queueing.
- [`docs/testing.md`](docs/testing.md) – asserting registrations and debugging.
- Register custom attributes at runtime via the `AttributeRegistry` or list them under `listener-attributes.attributes`.

License
-------

MIT © Brett
