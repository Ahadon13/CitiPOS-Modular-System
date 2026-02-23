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
}
