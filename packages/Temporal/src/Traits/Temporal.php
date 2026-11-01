<?php declare(strict_types=1);

namespace ImBrett\LaravelTemporal\Traits;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use ImBrett\LaravelTemporal\Models\TemporalModel;
use ImBrett\LaravelTemporal\Support\TemporalComparatorRegistry;

trait Temporal
{
    /**
     * Upserts a temporal record for the given anchor ID and attributes.
     *
     * This method ensures temporal consistency by closing the current valid record (if any)
     * and creating a new record with the provided attributes. If the current record's values
     * are equal to the new attributes, it returns the current record without making changes.
     * Otherwise, it updates the current record's `valid_to` timestamp to mark its end,
     * and inserts a new record with the specified attributes and validity period.
     *
     * All operations are performed within a database transaction to ensure atomicity.
     *
     * @param int $anchorId The foreign key value identifying the anchor entity.
     * @param array<string, mixed> $attributes The attributes to be upserted for the temporal record.
     * @param string|null $source Optional source identifier for the record.
     * @return self The newly created or existing temporal record.
     */
    public function upsertTemporal(int $anchorId, array $attributes, ?string $source = null): self
    {
        $fk = $this->temporalForeignKey();
        $now = now();
        $inf = config('temporal.infinity');

        return DB::transaction(function () use ($fk, $anchorId, $attributes, $source, $now, $inf) {
            /** @var self|null $current */
            $current = static::query()
                ->where($fk, $anchorId)
                ->where('valid_to', $inf)
                ->lockForUpdate()
                ->first();

            if ($current && $this->valuesEqual($current, $attributes)) {
                return $current;
            }

            $current?->update([
                'valid_to' => $now,
            ]);

            /** @var self $fresh */
            $fresh = static::query()->create(array_merge([
                $fk => $anchorId,
                'valid_from' => $now,
                'valid_to' => $inf,
                'tx_at' => $now,
                'source' => $source,
            ], $attributes));

            return $fresh;
        });
    }

    /**
     * Scope a query to only include records that are currently valid.
     *
     * This scope filters results where the 'valid_to' column matches the configured
     * temporal infinity value, indicating that the record is considered current.
     *
     * @param Builder<TemporalModel> $query
     * @return Builder<TemporalModel>
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('valid_to', config('temporal.infinity'));
    }

    /**
     * Scope a query to only include models whose 'valid_to' date is in the past.
     *
     * @param Builder<TemporalModel> $query The query builder instance.
     * @return Builder<TemporalModel> The modified query builder.
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where('valid_to', '<', CarbonImmutable::now());
    }

    /**
     * Scope a query to only include records where the 'valid_from' date is in the future.
     *
     * @param Builder<TemporalModel> $query
     * @return Builder<TemporalModel>
     */
    public function scopeFuture(Builder $query): Builder
    {
        return $query->where('valid_from', '>', CarbonImmutable::now());
    }

    /**
     * Filters the query to include only records that are valid at the specified moment in time.
     *
     * This scope applies two conditions:
     * - The 'valid_from' column must be less than or equal to the given moment.
     * - The 'valid_to' column must be greater than the given moment.
     *
     * @param Builder<TemporalModel> $query The Eloquent query builder instance.
     * @param string|DateTimeInterface $moment The moment in time to check validity against (parsable by Carbon).
     * @return Builder<TemporalModel> The modified query builder with validity constraints applied.
     */
    public function scopeAsOf(Builder $query, string|DateTimeInterface $moment): Builder
    {
        $ts = $this->parseMoment($moment);

        return $query->where('valid_from', '<=', $ts)
            ->where('valid_to', '>', $ts);
    }

    /**
     * Scope a query to only include records whose validity period overlaps with the given start and end moments.
     *
     * @param Builder<TemporalModel> $query The Eloquent query builder instance.
     * @param string|DateTimeInterface $start The start moment to check for overlap.
     * @param string|DateTimeInterface $end The end moment to check for overlap.
     * @return Builder<TemporalModel> The modified query builder instance.
     */
    public function scopeOverlapping(
        Builder $query,
        string|DateTimeInterface $start,
        string|DateTimeInterface $end
    ): Builder {
        $s = $this->parseMoment($start);
        $e = $this->parseMoment($end);

        return $query->where('valid_from', '<', $e)
            ->where('valid_to', '>', $s);
    }

    /**
     * Scope a query to only include records whose validity period is contained within the specified start and end moments.
     *
     * @param Builder<TemporalModel> $query The Eloquent query builder instance.
     * @param string|DateTimeInterface $start The start moment (as a string or DateTimeInterface).
     * @param string|DateTimeInterface $end The end moment (as a string or DateTimeInterface).
     * @return Builder<TemporalModel> The modified query builder.
     */
    public function scopeContainedWithin(
        Builder $query,
        string|DateTimeInterface $start,
        string|DateTimeInterface $end
    ): Builder {
        $s = $this->parseMoment($start);
        $e = $this->parseMoment($end);

        return $query->where('valid_from', '>=', $s)
            ->where('valid_to', '<=', $e);
    }

    /**
     * Scope a query to only include records whose validity period covers the given start and end moments.
     *
     * @param Builder<TemporalModel> $query The Eloquent query builder instance.
     * @param string|DateTimeInterface $start The start moment to check coverage for.
     * @param string|DateTimeInterface $end The end moment to check coverage for.
     * @return Builder<TemporalModel> The modified query builder.
     */
    public function scopeCovering(
        Builder $query,
        string|DateTimeInterface $start,
        string|DateTimeInterface $end
    ): Builder {
        $s = $this->parseMoment($start);
        $e = $this->parseMoment($end);

        return $query->where('valid_from', '<=', $s)
            ->where('valid_to', '>=', $e);
    }

    /**
     * Scope a query to only include records that have changed since a given moment.
     *
     * @param Builder<TemporalModel> $query The Eloquent query builder instance.
     * @param string|DateTimeInterface $since The moment to compare against, as a string or DateTimeInterface.
     * @return Builder<TemporalModel> The modified query builder.
     */
    public function scopeChangedSince(Builder $query, string|DateTimeInterface $since): Builder
    {
        $ts = $this->parseMoment($since);

        return $query->where('tx_at', '>=', $ts);
    }

    /**
     * Scope a query to only include records with an open-ended validity period.
     *
     * This scope filters results where the 'valid_to' column is set to the configured infinity value,
     * indicating that the record does not have a defined end date.
     *
     * @param Builder<TemporalModel> $query
     * @return Builder<TemporalModel>
     */
    public function scopeOpenEnded(Builder $query): Builder
    {
        return $query->where('valid_to', config('temporal.infinity'));
    }

    /**
     * Scope a query to only include records with a closed validity period.
     *
     * This scope filters results where the 'valid_to' column is not equal to the configured
     * temporal infinity value, indicating that the record has a defined end date.
     *
     * @param Builder<TemporalModel> $query
     * @return Builder<TemporalModel>
     */
    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('valid_to', '<>', config('temporal.infinity'));
    }

    /**
     * Scope a query to only include records matching the given anchor ID.
     *
     * @param  Builder<TemporalModel>  $query
     * @return Builder<TemporalModel>
     */
    public function scopeForAnchor(Builder $query, int|string $anchorId): Builder
    {
        return $query->where($this->temporalForeignKey(), $anchorId);
    }

    /**
     * Determines if the temporal attribute values of the given model match the provided attributes.
     *
     * This method compares the current values of the model's temporal columns with the corresponding
     * values in the provided attributes array. All values are cast to strings before comparison.
     *
     * @param TemporalModel $model The model instance whose temporal attributes are being compared.
     * @param array<mixed> $attrs An associative array of attribute values to compare against the model.
     * @return bool Returns true if all temporal attribute values are equal; otherwise, false.
     */
    protected function valuesEqual(TemporalModel $model, array $attrs): bool
    {
        $customComparator = app(TemporalComparatorRegistry::class)->resolve($model);
        if ($customComparator) {
            return (bool)$customComparator($model, $attrs);
        }

        $cols = $this->temporalColumns();

        $old = array_map(
            static fn (string $col) => (string)($model->{$col}),
            $cols
        );

        $new = array_map(
            static fn (string $col) => (string)($attrs[$col] ?? null),
            $cols
        );

        return $old === $new;
    }

    /**
     * Helper to parse a moment consistently to CarbonImmutable.
     */
    protected function parseMoment(string|DateTimeInterface $moment): CarbonImmutable
    {
        return $moment instanceof DateTimeInterface
            ? CarbonImmutable::instance(CarbonImmutable::createFromInterface($moment))
            : CarbonImmutable::parse($moment);
    }
}
