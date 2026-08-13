<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Ordered categorical palette shared by every chart in the app.
 *
 * Hues are assigned in fixed order and never cycled, so a series keeps its
 * colour when a filter changes the number of series on screen. The order below
 * is validated for colour-vision deficiency separation on adjacent pairs
 * (the pairlist that applies to lines, bars and stacks) against both the light
 * and dark chart surfaces.
 *
 * Past the eighth slot, callers must fold the remainder into a single "Other"
 * series rather than generating a ninth hue.
 */
final class ChartPalette
{
    public const MAX_SERIES = 8;

    /** The neutral used for the folded "Other" series. */
    public const OTHER_LIGHT = '#6b7280';

    public const OTHER_DARK = '#9ca3af';

    /** @var list<string> */
    private const LIGHT = [
        '#2a78d6', // blue
        '#eb6834', // orange
        '#1baf7a', // aqua
        '#eda100', // yellow
        '#e87ba4', // magenta
        '#008300', // green
        '#4a3aa7', // violet
        '#e34948', // red
    ];

    /** @var list<string> */
    private const DARK = [
        '#3987e5',
        '#d95926',
        '#199e70',
        '#c98500',
        '#d55181',
        '#008300',
        '#9085e9',
        '#e66767',
    ];

    /**
     * @return list<string>
     */
    public static function light(?int $count = null): array
    {
        return $count === null ? self::LIGHT : array_slice(self::LIGHT, 0, $count);
    }

    /**
     * @return list<string>
     */
    public static function dark(?int $count = null): array
    {
        return $count === null ? self::DARK : array_slice(self::DARK, 0, $count);
    }

    /**
     * Both modes for a given series count, ready to hand to a chart component.
     *
     * @return array{light: list<string>, dark: list<string>}
     */
    public static function forSeries(int $count, bool $hasOtherSeries = false): array
    {
        $hueCount = min($count, self::MAX_SERIES);

        $light = self::light($hueCount);
        $dark = self::dark($hueCount);

        if ($hasOtherSeries) {
            $light[] = self::OTHER_LIGHT;
            $dark[] = self::OTHER_DARK;
        }

        return ['light' => $light, 'dark' => $dark];
    }
}
