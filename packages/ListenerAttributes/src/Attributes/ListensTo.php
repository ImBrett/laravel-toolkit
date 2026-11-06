<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Attributes;

use Attribute;
use Closure;
use ImBrett\LaravelListenerAttributes\Support\Contracts\EventAttribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ListensTo implements EventAttribute
{
    /**
     * @param class-string $event
     */
    public function __construct(
        public string $event,
    ) {
    }

    public function event(): string
    {
        return $this->event;
    }

    public function listener(string $subscriber, string $method): Closure
    {
        return static function (object $event) use ($subscriber, $method): mixed {
            $callback = $subscriber . '@' . $method;

            return app()->call($callback, [
                'event' => $event,
            ]);
        };
    }
}
