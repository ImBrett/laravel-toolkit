<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests\Feature;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use ImBrett\LaravelListenerAttributes\Support\SubscriberInspector;
use ImBrett\LaravelListenerAttributes\Tests\Support\AlternateAttribute;
use ImBrett\LaravelListenerAttributes\Tests\Support\AlternateSubscriber;
use ImBrett\LaravelListenerAttributes\Tests\Support\ExampleEvent;
use ImBrett\LaravelListenerAttributes\Tests\Support\ExampleSubscriber;
use ImBrett\LaravelListenerAttributes\Tests\TestCase;

final class ListenerRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('listener-attributes.subscribers', [
            ExampleSubscriber::class,
        ]);

        $inspector = app(SubscriberInspector::class);
        $dispatcher = app(Dispatcher::class);

        foreach ($inspector->listenersFor(ExampleSubscriber::class) as $definition) {
            $dispatcher->listen($definition['event'], $definition['listener']);
        }
    }

    public function testListenersAreRegisteredForAnnotatedMethods(): void
    {
        ExampleSubscriber::$lastPayload = null;

        Event::dispatch(new ExampleEvent('registered'));

        $this->assertSame('registered', ExampleSubscriber::$lastPayload);
    }

    public function testCustomAttributeCanBeRegistered(): void
    {
        config()->set('listener-attributes.subscribers', [
            AlternateSubscriber::class,
        ]);

        AlternateSubscriber::$handled = null;

        app(\ImBrett\LaravelListenerAttributes\Support\AttributeRegistry::class)->extend(AlternateAttribute::class);

        $inspector = app(SubscriberInspector::class);
        $dispatcher = app(Dispatcher::class);

        foreach ($inspector->listenersFor(AlternateSubscriber::class) as $definition) {
            $dispatcher->listen($definition['event'], $definition['listener']);
        }

        Event::dispatch(new ExampleEvent('alt'));

        $this->assertSame('alt', AlternateSubscriber::$handled);
    }
}
