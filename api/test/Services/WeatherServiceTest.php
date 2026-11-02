<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Services;

use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Services\WeatherService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Az adatbázist mock helyettesíti, így a lekérdezések eredményének
 * feldolgozását teszteljük, nem magukat az SQL lekérdezéseket.
 */
class WeatherServiceTest extends TestCase
{
    private MySQL&MockObject $db;
    private WeatherService $service;

    protected function setUp(): void
    {
        $this->db = $this->createMock(MySQL::class);
        $this->service = new WeatherService($this->db);
    }

    #[Test]
    public function findCityConvertsTypes(): void
    {
        // A PDO a számokat is szövegként adja vissza.
        $this->db->method("queryAssoc")->willReturn([
            ["id" => "1", "name" => "Budapest", "region" => "Közép-Magyarország"],
        ]);

        $this->assertSame(
            ["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"],
            $this->service->findCity(1),
        );
    }

    #[Test]
    public function findCityReturnsNullForUnknownCity(): void
    {
        $this->db->method("queryAssoc")->willReturn([]);

        $this->assertNull($this->service->findCity(99));
    }

    #[Test]
    public function getCityDailyWeatherPassesParameters(): void
    {
        $this->db->expects($this->once())
            ->method("queryAssoc")
            ->with(
                $this->stringContains("mx.city_id = :cityId"),
                [":cityId" => 3, ":fromDate" => 20230101, ":toDate" => 20230131],
            )
            ->willReturn([]);

        $this->service->getCityDailyWeather(
            3,
            new DateTimeImmutable("2023-01-01"),
            new DateTimeImmutable("2023-01-31"),
        );
    }

    #[Test]
    public function getCityDailyWeatherFormatsRows(): void
    {
        $this->db->method("queryAssoc")->willReturn([
            ["date" => "20230101", "minTemp" => "3.4", "maxTemp" => "8.5", "precipitation" => "0"],
        ]);

        $days = $this->service->getCityDailyWeather(
            1,
            new DateTimeImmutable("2023-01-01"),
            new DateTimeImmutable("2023-01-01"),
        );

        $this->assertSame(
            [["date" => "2023-01-01", "minTemp" => 3.4, "maxTemp" => 8.5, "precipitation" => 0.0]],
            $days,
        );
    }

    #[Test]
    public function getCityDailyWeatherConvertsMissingValuesToNull(): void
    {
        $this->db->method("queryAssoc")->willReturn([
            // -999: hiányzó mérés a forrásadatban, null: hiányzó sor (LEFT JOIN)
            ["date" => "19550101", "minTemp" => "-999", "maxTemp" => null, "precipitation" => "0.1"],
        ]);

        $days = $this->service->getCityDailyWeather(
            3,
            new DateTimeImmutable("1955-01-01"),
            new DateTimeImmutable("1955-01-01"),
        );

        $this->assertNull($days[0]["minTemp"]);
        $this->assertNull($days[0]["maxTemp"]);
        $this->assertSame(0.1, $days[0]["precipitation"]);
    }

    #[Test]
    public function getCityDailyWeatherRoundsToOneDecimal(): void
    {
        // A FLOAT oszlop pontatlansága, illetve a régiós átlag miatt jöhet
        // ilyen érték az adatbázisból.
        $this->db->method("queryAssoc")->willReturn([
            ["date" => "20230101", "minTemp" => "0.10000000149011612", "maxTemp" => "-0.9666666", "precipitation" => "0.025"],
        ]);

        $days = $this->service->getCityDailyWeather(
            1,
            new DateTimeImmutable("2023-01-01"),
            new DateTimeImmutable("2023-01-01"),
        );

        $this->assertSame(0.1, $days[0]["minTemp"]);
        $this->assertSame(-1.0, $days[0]["maxTemp"]);
        $this->assertSame(0.0, $days[0]["precipitation"]);
    }

    #[Test]
    public function getRegionDailyWeatherExcludesMissingValuesFromAverage(): void
    {
        $this->db->expects($this->once())
            ->method("queryAssoc")
            ->with(
                $this->stringContains("NULLIF(mx.max_temp, :missingValue)"),
                $this->callback(fn (array $params): bool => $params[":missingValue"] === -999
                    && $params[":region"] === "Dunántúl"
                    && $params[":fromDate"] === 19550101
                    && $params[":toDate"] === 19550131),
            )
            ->willReturn([
                // Ha egyik városban sincs mérés, az AVG() NULL-t ad.
                ["date" => "19550101", "minTemp" => "-4.33333", "maxTemp" => null, "precipitation" => "0.025"],
            ]);

        $days = $this->service->getRegionDailyWeather(
            "Dunántúl",
            new DateTimeImmutable("1955-01-01"),
            new DateTimeImmutable("1955-01-31"),
        );

        $this->assertSame(
            [["date" => "1955-01-01", "minTemp" => -4.3, "maxTemp" => null, "precipitation" => 0.0]],
            $days,
        );
    }

    #[Test]
    public function findRegionCitiesConvertsTypes(): void
    {
        $this->db->method("queryAssoc")->willReturn([
            ["id" => "3", "name" => "Keszthely", "region" => "Dunántúl"],
            ["id" => "6", "name" => "Pécs", "region" => "Dunántúl"],
        ]);

        $this->assertSame(
            [
                ["id" => 3, "name" => "Keszthely", "region" => "Dunántúl"],
                ["id" => 6, "name" => "Pécs", "region" => "Dunántúl"],
            ],
            $this->service->findRegionCities("dunántúl"),
        );
    }

    #[Test]
    public function getRegionsReturnsNames(): void
    {
        $this->db->method("queryAssoc")->willReturn([
            ["region" => "Dunántúl"],
            ["region" => "Közép-Magyarország"],
        ]);

        $this->assertSame(["Dunántúl", "Közép-Magyarország"], $this->service->getRegions());
    }
}
