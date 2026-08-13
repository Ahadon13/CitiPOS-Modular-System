<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonImmutable;

/**
 * Collapses a date range into a bounded number of chart buckets.
 *
 * Plotting one point per day is fine for a month and fatal for a decade: a
 * 2015-2026 range is ~4,000 points per series, which has to be serialised
 * through the Livewire payload and handed to ApexCharts on every filter
 * change. Bucketing keeps any range under ~70 points by widening the
 * granularity instead of adding points.
 *
 * Daily aggregation still happens in SQL (portable across MySQL and the
 * SQLite test database); this class only folds those daily rows into buckets.
 */
final class DateBucket
{
    public const DAY = 'day';

    public const WEEK = 'week';

    public const MONTH = 'month';

    public const QUARTER = 'quarter';

    public const YEAR = 'year';

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly string $granularity,
    ) {}

    public static function resolve(Carbon|CarbonImmutable $start, Carbon|CarbonImmutable $end): self
    {
        $start = CarbonImmutable::parse($start->toDateString())->startOfDay();
        $end = CarbonImmutable::parse($end->toDateString())->endOfDay();

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        $days = $start->diffInDays($end) + 1;

        $granularity = match (true) {
            $days <= 70 => self::DAY,      // up to ~10 weeks of daily points
            $days <= 400 => self::WEEK,     // ~57 weekly points for a year
            $days <= 1_100 => self::MONTH,   // ~36 monthly points for 3 years
            $days <= 5_500 => self::QUARTER, // ~48 quarterly points for 12 years
            default => self::YEAR,          // anything wider than ~15 years
        };

        return new self($start, $end, $granularity);
    }

    /**
     * Every bucket in the range, in order, even the empty ones.
     *
     * @return array<string, string> bucket key => human label
     */
    public function buckets(): array
    {
        $buckets = [];
        $cursor = $this->floor($this->start);

        while ($cursor->lessThanOrEqualTo($this->end)) {
            $buckets[$cursor->toDateString()] = $this->label($cursor);
            $cursor = $this->advance($cursor);
        }

        return $buckets;
    }

    /**
     * The bucket key a given Y-m-d date belongs to.
     */
    public function keyFor(string $date): string
    {
        return $this->floor(CarbonImmutable::parse($date))->toDateString();
    }

    /**
     * Fold SQL daily totals (keyed Y-m-d) onto the bucket series.
     *
     * @param  array<string, int|float>  $dailyTotals
     * @return array<string, int|float> bucket key => summed total
     */
    public function fold(array $dailyTotals): array
    {
        $series = array_fill_keys(array_keys($this->buckets()), 0);

        foreach ($dailyTotals as $date => $total) {
            $key = $this->keyFor((string) $date);

            if (! array_key_exists($key, $series)) {
                continue;
            }

            $series[$key] += $total;
        }

        return $series;
    }

    public function label(CarbonImmutable $date): string
    {
        return match ($this->granularity) {
            self::DAY => $date->format('M j'),
            self::WEEK => $date->format('M j'),
            self::MONTH => $date->format('M Y'),
            self::QUARTER => 'Q'.$date->quarter.' '.$date->year,
            default => (string) $date->year,
        };
    }

    /**
     * Human description of the granularity, for the chart subtitle.
     */
    public function describe(): string
    {
        return match ($this->granularity) {
            self::DAY => 'Daily',
            self::WEEK => 'Weekly',
            self::MONTH => 'Monthly',
            self::QUARTER => 'Quarterly',
            default => 'Yearly',
        };
    }

    private function floor(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this->granularity) {
            self::DAY => $date->startOfDay(),
            self::WEEK => $date->startOfWeek(),
            self::MONTH => $date->startOfMonth(),
            self::QUARTER => $date->startOfQuarter(),
            default => $date->startOfYear(),
        };
    }

    private function advance(CarbonImmutable $date): CarbonImmutable
    {
        return match ($this->granularity) {
            self::DAY => $date->addDay(),
            self::WEEK => $date->addWeek(),
            self::MONTH => $date->addMonth(),
            self::QUARTER => $date->addQuarter(),
            default => $date->addYear(),
        };
    }
}
