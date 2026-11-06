<?php declare(strict_types=1);

return [
    /**
     * A list of subscriber classes that use the #[ListensTo] attribute.
     *
     * Example:
     *  'subscribers' => [
     *      \App\Listeners\AuthSubscriber::class,
     *  ],
     */
    'subscribers' => [],
    /**
     * Additional attribute classes that implement the EventAttribute contract.
     * They will be inspected alongside the built-in #[ListensTo] attribute.
     */
    'attributes' => [],
];
