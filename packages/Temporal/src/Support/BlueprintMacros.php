<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Support;

use Illuminate\Database\Schema\Blueprint;

class BlueprintMacros
{
    /**
     * Registers custom macros for Laravel's Blueprint class to support temporal attributes.
     *
     * This method adds two macros:
     * - `temporalColumns`: Adds three datetime columns (`valid_from`, `valid_to`, and `tx_at`) to a migration blueprint.
     *   - `valid_from`: The start of the validity period.
     *   - `valid_to`: The end of the validity period.
     *   - `tx_at`: The transaction timestamp, set to the current time by default.
     * - `temporalIndexes`: Adds indexes for temporal queries, using a specified foreign key column.
     *   - Index on `[fkColumn, valid_to]`.
     *   - Index on `[fkColumn, valid_from, valid_to]`.
     *
     * These macros facilitate the creation of tables that support temporal data management.
     *
     * Laravel binds $this to the Blueprint instance inside macro closures.
     */
    public static function register(): void
    {
        if (!Blueprint::hasMacro('temporalColumns')) {
            Blueprint::macro(
                'temporalColumns',
                function () {
                    $this->dateTime('valid_from');
                    $this->dateTime('valid_to');
                    $this->dateTime('tx_at')->useCurrent();
                }
            );
        }

        if (!Blueprint::hasMacro('temporalIndexes')) {
            Blueprint::macro(
                'temporalIndexes',
                function (string $fkColumn) {
                    $this->index([$fkColumn, 'valid_to']);
                    $this->index([$fkColumn, 'valid_from', 'valid_to']);
                }
            );
        }
    }
}
