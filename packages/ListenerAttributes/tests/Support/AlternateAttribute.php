<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Tests\Support;

use Attribute;
use Closure;
use ImBrett\LaravelListenerAttributes\Support\Contracts\EventAttribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class AlternateAttribute implements EventAttribute
{
    /**
     * @param class-string $event
     */
    public function __construct(
        private string $event
    ) {
    }

    public function event(): string
    {
        return $this->event;
    }

    public function listener(string $subscriber, string $method): Closure
    {
        return static function (object $event) use ($subscriber, $method): string {
            return (string)app()->call($subscriber . '@' . $method, [
                'event' => $event,
            ]);
        };
    }
}
