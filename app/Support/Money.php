<?php

namespace App\Support;

class Money
{
    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    public static function display(int|float|string $cents): string
    {
        return (string) config('store.currency_symbol', '৳').number_format(((int) $cents) / 100, 2);
    }
}
