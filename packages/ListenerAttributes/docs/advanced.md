# Advanced Usage

Push listener attributes further with repeatable decorators, conditional registration, and queue support.

## Multiple events per method

Attributes are repeatable—annotate the same method several times:

```php
class AuditSubscriber
{
    #[ListensTo(UserRegistered::class)]
    #[ListensTo(UserDeleted::class)]
    public function syncDirectory(UserEvent $event): void
    {
        // respond to both registration and deletion
    }
}
```

## Conditional registration

Need to register listeners only in certain environments? Wrap configuration in a service provider:

```php
public function boot(): void
{
    if (app()->environment('production')) {
        config()->push('listener-attributes.subscribers', \App\Listeners\AuditSubscriber::class);
    }
}
```

## Queueable listeners

Listeners registered via attributes are indistinguishable from manually registered ones—mark them as queueable the usual way:

```php
class AuthSubscriber
{
    #[ListensTo(UserLoggedIn::class)]
    public function onLogin(UserLoggedIn $event): void
    {
        dispatch(new UpdateLastSeen($event->user));
    }
}
```

## Custom attributes

You can extend the package by creating your own attribute that wraps `ListensTo`:

```php
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ListensToUserEvents extends ListensTo
{
    public function __construct(string $event)
    {
        parent::__construct($event);
    }
}
```

Use your attribute in place of the base class to express domain-specific intent.

Register additional attribute classes via the container or config:

```php
use ImBrett\LaravelListenerAttributes\Support\AttributeRegistry;

app(AttributeRegistry::class)->extend(CustomAttribute::class);
```

Alternatively, list them under `listener-attributes.attributes` or call `$registry->extend()` inside a service provider. Every attribute must implement the `EventAttribute` contract so it can describe the target event and listener factory.

Navigate to [Testing & Debugging](testing.md) for confidence tips.
