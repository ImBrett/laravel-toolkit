# Testing & Debugging

Verify that your attributes register correctly and diagnose issues quickly.

## Assert listener registration

```php
Event::fake();

event(new UserLoggedIn($user));

Event::assertListening(UserLoggedIn::class, [AuthSubscriber::class, 'onLogin']);
```

Because the package uses Laravel’s native dispatcher API, all existing `Event::fake` helpers continue to work.

## Discoverability checks

If listeners do not fire:

1. Confirm the class is listed in `config('listener-attributes.subscribers')`.
2. Ensure the class is autoloadable (Composer `dump-autoload` if needed).
3. Clear cached configuration (`php artisan config:clear`) after adding new subscribers.

## Unit testing listener logic

Treat attribute-driven methods like regular listeners:

```php
public function test_it_updates_last_seen(): void
{
    $listener = new AuthSubscriber();

    $listener->onLogin(new UserLoggedIn($user));

    $this->assertEquals(now(), $user->fresh()->last_seen_at);
}
```

## Running the package suite

```bash
make test TEST_ARGS="--testsuite=ListenerAttributes"
```

The bundled suite covers scanning, registration, and event dispatch integration—use it as a reference when writing your own tests.
