<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Support\Contracts;

use Closure;

interface EventAttribute
{
    /**
     * @return class-string
     */
    public function event(): string;

    /**
     * Allows attributes to customise how listeners are built.
     *
     * @param class-string $subscriber
     * @return Closure(object): mixed
     */
    public function listener(string $subscriber, string $method): Closure;
}
