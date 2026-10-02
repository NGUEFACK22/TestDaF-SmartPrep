<?php

namespace App\Support;

/**
 * Format — helpers d'affichage (indépendants de l'extension intl).
 */
class Format
{
    public static function percent(float|int|null $value, int $decimals = 1): string
    {
        return number_format((float) $value, $decimals, ',', ' ').' %';
    }

    public static function number(float|int|null $value, int $decimals = 0): string
    {
        return number_format((float) $value, $decimals, ',', ' ');
    }

    /** Convertit des secondes en mm:ss. */
    public static function clock(int $seconds): string
    {
        $seconds = max(0, $seconds);

        return sprintf('%02d:%02d', intdiv($seconds, 60), $seconds % 60);
    }

    public static function words(int $count): string
    {
        return $count.' mot'.($count > 1 ? 's' : '');
    }
}
