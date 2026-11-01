<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests\Support;

class AlternateSubscriber
{
    public static ?string $handled = null;

    #[AlternateAttribute(ExampleEvent::class)]
    public function onExample(ExampleEvent $event): string
    {
        self::$handled = $event->payload;

        return $event->payload;
    }
}
