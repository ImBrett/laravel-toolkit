<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests\Support;

class ExampleEvent
{
    public function __construct(
        public readonly string $payload
    ) {
    }
}
