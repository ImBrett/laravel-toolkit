<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests\Support;

use ImBrett\LaravelListenerAttributes\Attributes\ListensTo;

class ExampleSubscriber
{
    public static ?string $lastPayload = null;

    #[ListensTo(ExampleEvent::class)]
    public function handleExample(ExampleEvent $event): void
    {
        self::$lastPayload = $event->payload;
    }
}
