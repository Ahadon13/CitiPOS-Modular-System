<?php

declare(strict_types=1);

namespace App\Support;

use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Money\Money;
use NumberFormatter;

final class MoneyHelper
{
    public static function format(?Money $money, string $locale = 'en_PH'): string
    {
        if (! $money) {
            return '₱0.00';
        }

        $currencies = new ISOCurrencies;

        // The NumberFormatter handles the currency symbol and decimal placement automatically
        $numberFormatter = new NumberFormatter($locale, NumberFormatter::CURRENCY);
        $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

        return $moneyFormatter->format($money);
    }

    /**
     * Format a raw integer amount of centavos.
     *
     * Report queries aggregate in SQL and come back as plain integers rather
     * than Money objects, so this saves wrapping every figure at the call site.
     */
    public static function formatCents(int|float|string|null $cents, string $locale = 'en_PH'): string
    {
        return self::format(Money::PHP((string) (int) round((float) ($cents ?? 0))), $locale);
    }
}
