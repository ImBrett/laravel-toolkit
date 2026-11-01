<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Support;

use Closure;
use ImBrett\LaravelListenerAttributes\Attributes\ListensTo;
use ImBrett\LaravelListenerAttributes\Support\Contracts\EventAttribute;
use InvalidArgumentException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 */
class SubscriberInspector
{
    /**
     * @return iterable<int, array{event: class-string, listener: Closure}>
     */
    public function __construct(
        private AttributeRegistry $registry
    ) {
    }

    /**
     * @param class-string $subscriber
     * @return iterable<int, array{event: class-string, listener: Closure(object): mixed}>
     */
    public function listenersFor(string $subscriber): iterable
    {
        if (!class_exists($subscriber)) {
            throw new InvalidArgumentException(sprintf('Subscriber class [%s] does not exist.', $subscriber));
        }

        $reflection = new ReflectionClass($subscriber);
        $attributes = $this->registry->all() ?: [ListensTo::class];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            foreach ($attributes as $attributeClass) {
                foreach ($method->getAttributes($attributeClass, ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
                    /** @var EventAttribute $instance */
                    $instance = $attribute->newInstance();

                    yield [
                        'event' => $instance->event(),
                        'listener' => $instance->listener($subscriber, $method->getName()),
                    ];
                }
            }
        }
    }
}
