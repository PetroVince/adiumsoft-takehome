<?php

declare(strict_types=1);

namespace ADS\TakeHome\Services;

use ADS\TakeHome\Framework\MySQL;
use DateTimeImmutable;

/**
 * Időjárási adatok lekérdezése az adatbázisból.
 */
class WeatherService
{
    /**
     * Az adatforrásban ez az érték jelöli a hiányzó mérést.
     */
    private const MISSING_VALUE = -999;

    public function __construct(
        private readonly MySQL $db,
    ) {
    }

    /**
     * @return array{id: int, name: string, region: string}|null
     */
    public function findCity(int $cityId): ?array
    {
        $cities = $this->db->queryAssoc(
            "SELECT id, name, region FROM weather.cities WHERE id = :cityId",
            [":cityId" => $cityId],
        );
        if (count($cities) === 0) {
            return null;
        }
        $city = $cities[0];
        return [
            "id" => (int) $city["id"],
            "name" => strval($city["name"]),
            "region" => strval($city["region"]),
        ];
    }

    /**
     * Egy város napi időjárási adatai a megadott időszakban (zárt
     * intervallum).
     *
     * @return list<array{date: string, minTemp: float|null, maxTemp: float|null, precipitation: float|null}>
     */
    public function getCityDailyWeather(int $cityId, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->db->queryAssoc(
            "SELECT
                mx.date AS `date`,
                mn.min_temp AS minTemp,
                mx.max_temp AS maxTemp,
                p.precipitation AS precipitation
            FROM
                weather.maximum_temperatures mx
                LEFT JOIN weather.minimum_temperatures mn
                    ON mn.city_id = mx.city_id AND mn.date = mx.date
                LEFT JOIN weather.precipitation p
                    ON p.city_id = mx.city_id AND p.date = mx.date
            WHERE
                mx.city_id = :cityId
                AND mx.date BETWEEN :fromDate AND :toDate
            ORDER BY
                mx.date
            ",
            [
                ":cityId" => $cityId,
                ":fromDate" => (int) $from->format("Ymd"),
                ":toDate" => (int) $to->format("Ymd"),
            ],
        );

        return array_map(fn (array $row): array => [
            "date" => $this->formatDate($row["date"]),
            "minTemp" => $this->normalizeValue($row["minTemp"]),
            "maxTemp" => $this->normalizeValue($row["maxTemp"]),
            "precipitation" => $this->normalizeValue($row["precipitation"]),
        ], $rows);
    }

    /**
     * Az összes város, azonosító szerint rendezve.
     *
     * @return list<array{id: int, name: string, region: string}>
     */
    public function getCities(): array
    {
        $cities = $this->db->queryAssoc("SELECT id, name, region FROM weather.cities ORDER BY id");
        return array_map(fn (array $city): array => [
            "id" => (int) $city["id"],
            "name" => strval($city["name"]),
            "region" => strval($city["region"]),
        ], $cities);
    }

    /**
     * Azok a régiók, amelyekhez tartozik legalább egy város.
     *
     * @return list<string>
     */
    public function getRegions(): array
    {
        $rows = $this->db->queryAssoc("SELECT DISTINCT region FROM weather.cities ORDER BY region");
        return array_map(fn (array $row): string => strval($row["region"]), $rows);
    }

    /**
     * A régió városai. A régió nevének egyezése kis- és nagybetű független.
     *
     * @return list<array{id: int, name: string, region: string}>
     */
    public function findRegionCities(string $region): array
    {
        $cities = $this->db->queryAssoc(
            "SELECT id, name, region FROM weather.cities WHERE region = :region ORDER BY id",
            [":region" => $region],
        );
        return array_map(fn (array $city): array => [
            "id" => (int) $city["id"],
            "name" => strval($city["name"]),
            "region" => strval($city["region"]),
        ], $cities);
    }

    /**
     * Egy régió városainak napi szinten átlagolt időjárási adatai a megadott
     * időszakban (zárt intervallum).
     *
     * A hiányzó mérések nem számítanak bele az átlagba, ha egy napon egyik
     * városban sincs mérés, az érték `null`.
     *
     * @return list<array{date: string, minTemp: float|null, maxTemp: float|null, precipitation: float|null}>
     */
    public function getRegionDailyWeather(string $region, DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $rows = $this->db->queryAssoc(
            "SELECT
                mx.date AS `date`,
                AVG(NULLIF(mn.min_temp, :missingValue)) AS minTemp,
                AVG(NULLIF(mx.max_temp, :missingValue)) AS maxTemp,
                AVG(NULLIF(p.precipitation, :missingValue)) AS precipitation
            FROM
                weather.cities c
                JOIN weather.maximum_temperatures mx
                    ON mx.city_id = c.id
                LEFT JOIN weather.minimum_temperatures mn
                    ON mn.city_id = mx.city_id AND mn.date = mx.date
                LEFT JOIN weather.precipitation p
                    ON p.city_id = mx.city_id AND p.date = mx.date
            WHERE
                c.region = :region
                AND mx.date BETWEEN :fromDate AND :toDate
            GROUP BY
                mx.date
            ORDER BY
                mx.date
            ",
            [
                ":missingValue" => self::MISSING_VALUE,
                ":region" => $region,
                ":fromDate" => (int) $from->format("Ymd"),
                ":toDate" => (int) $to->format("Ymd"),
            ],
        );

        return array_map(fn (array $row): array => [
            "date" => $this->formatDate($row["date"]),
            "minTemp" => $this->normalizeValue($row["minTemp"]),
            "maxTemp" => $this->normalizeValue($row["maxTemp"]),
            "precipitation" => $this->normalizeValue($row["precipitation"]),
        ], $rows);
    }

    /**
     * Az adatbázisban YYYYMMDD egész számként tárolt dátumot YYYY-MM-DD
     * formátumra alakítja.
     */
    private function formatDate(string|int|float|bool|null $value): string
    {
        $date = str_pad(strval($value), 8, "0", STR_PAD_LEFT);
        return substr($date, 0, 4) . "-" . substr($date, 4, 2) . "-" . substr($date, 6, 2);
    }

    /**
     * A hiányzó méréseket (`-999`, illetve hiányzó sor) `null`-ra alakítja.
     */
    private function normalizeValue(string|int|float|bool|null $value): ?float
    {
        if ($value === null) {
            return null;
        }
        $number = (float) $value;
        if ($number <= self::MISSING_VALUE) {
            return null;
        }
        return round($number, 1);
    }
}
