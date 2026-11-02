<?php

declare(strict_types=1);

namespace ADS\TakeHome\Utils;

use DateTimeImmutable;

/**
 * Segédfüggvények az alkalmazás számára.
 */
class AppHelpers
{
    /**
     * @param string|list<string>|null $value
     */
    public static function parseId(string|array|null $value): ?int
    {
        if (!is_string($value) || preg_match('/^[1-9][0-9]{0,9}$/', $value) !== 1) {
            return null;
        }
        return (int) $value;
    }

    /**
     * @param string|list<string>|null $value
     */
    public static function parseDate(string|array|null $value): ?DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        $date = DateTimeImmutable::createFromFormat("!Y-m-d", $value);
        // A createFromFormat() elfogad pl. 2023-02-31-et is (átfordul
        // márciusra), ezért visszaalakítva is ellenőrizzük.
        if ($date === false || $date->format("Y-m-d") !== $value) {
            return null;
        }
        return $date;
    }
}