<?php declare(strict_types=1);

namespace ImBrett\LaravelListenerAttributes\Support;

use ImBrett\LaravelListenerAttributes\Support\Contracts\EventAttribute;

class AttributeRegistry
{
    /**
     * @var array<int, class-string<EventAttribute>>
     */
    protected array $classes = [];

    /**
     * @param class-string<EventAttribute> $attribute
     * @return $this
     */
    public function extend(string $attribute): self
    {
        $this->classes[] = $attribute;

        return $this;
    }

    /**
     * @return array<int, class-string<EventAttribute>>
     */
    public function all(): array
    {
        return $this->classes;
    }
}
