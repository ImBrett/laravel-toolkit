<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use ImBrett\LaravelListenerAttributes\Attributes\ListensTo;
use ImBrett\LaravelListenerAttributes\Support\AttributeRegistry;
use ImBrett\LaravelListenerAttributes\Support\Contracts\EventAttribute;
use ImBrett\LaravelListenerAttributes\Support\SubscriberInspector;

class ListenerAttributesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/listener-attributes.php', 'listener-attributes');

        $this->app->singleton(AttributeRegistry::class, function () {
            $registry = new AttributeRegistry();
            $registry->extend(ListensTo::class);

            return $registry;
        });

        $this->app->singleton(SubscriberInspector::class);
    }

    public function boot(
        Dispatcher $dispatcher,
        Config $config,
        SubscriberInspector $inspector,
        AttributeRegistry $registry
    ): void {
        $this->publishes([
            __DIR__ . '/../../config/listener-attributes.php' => config_path('listener-attributes.php'),
        ], 'listener-attributes-config');

        /** @var array<int, class-string> $subscribers */
        $subscribers = $config->get('listener-attributes.subscribers', []);
        /** @var array<int, class-string> $attributes */
        $attributes = $config->get('listener-attributes.attributes', []);

        foreach ($attributes as $attribute) {
            if (!is_subclass_of($attribute, EventAttribute::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Attribute class [%s] must implement %s.',
                    $attribute,
                    EventAttribute::class
                ));
            }

            $registry->extend($attribute);
        }

        foreach ($subscribers as $subscriber) {
            foreach ($inspector->listenersFor($subscriber) as $definition) {
                $dispatcher->listen($definition['event'], $definition['listener']);
            }
        }
    }
}
