# Getting Started

Listener Attributes lets you declare event listeners directly on your subscriber classes using PHP attributes.

## Install

```bash
composer require imbrett/laravel-listener-attributes
```

Publish the configuration if you need to customise the subscriber list:

```bash
php artisan vendor:publish --tag=listener-attributes-config
```

The config file exposes a subscriber list and an optional set of extra attributes:

```php
return [
    'subscribers' => [
        \App\Listeners\AuthSubscriber::class,
    ],
    'attributes' => [
        // Additional attribute classes implementing EventAttribute
    ],
];
```

## Annotate listeners

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

At boot, the service provider scans configured subscribers and registers each attributed method with Laravel’s event dispatcher. Listeners are resolved via the container, so you still benefit from dependency injection, middleware, and queueable listeners.

Ready for more control? Dive into [Advanced Usage](advanced.md).
