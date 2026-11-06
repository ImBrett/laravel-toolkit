<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Contracts;

interface TemporalContract
{
    /**
     * Get the list of temporal columns for the implementing model.
     *
     * @return array<string> An array of column names that are considered temporal.
     */
    public function temporalColumns(): array;

    /**
     * Get the name of the foreign key column used for temporal anchoring.
     *
     * @return string The foreign key column name.
     */
    public function temporalForeignKey(): string;
}
