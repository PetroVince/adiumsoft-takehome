<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Controllers;

use ADS\TakeHome\Controllers\WeatherController;
use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Services\WeatherService;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * A szolgáltatást mock helyettesíti, így a paraméterek ellenőrzését és a
 * válaszok felépítését teszteljük.
 */
class WeatherControllerTest extends TestCase
{
    private WeatherService&MockObject $service;
    private WeatherController $controller;

    protected function setUp(): void
    {
        $this->service = $this->createMock(WeatherService::class);
        $this->controller = new WeatherController($this->service);
    }

    #[Test]
    public function getCityWeatherReturnsData(): void
    {
        $city = ["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"];
        $days = [["date" => "2023-01-01", "minTemp" => 3.4, "maxTemp" => 8.5, "precipitation" => 0.0]];
        $this->service->method("findCity")->with(1)->willReturn($city);
        $this->service->expects($this->once())
            ->method("getCityDailyWeather")
            ->with(1, new DateTimeImmutable("2023-01-01"), new DateTimeImmutable("2023-01-31"))
            ->willReturn($days);

        $response = $this->controller->getCityWeather(
            new HttpRequest(["cityId" => "1", "from" => "2023-01-01", "to" => "2023-01-31"]),
        );

        $this->assertSame(200, $response->getStatus());
        $body = $this->decode($response);
        $this->assertSame($city, $body["city"]);
        $this->assertSame("2023-01-01", $body["from"]);
        $this->assertSame("2023-01-31", $body["to"]);
        $this->assertSame(["temperature" => "°C", "precipitation" => "mm"], $body["units"]);
        $this->assertCount(1, $body["days"]);
    }

    #[Test]
    public function getCityWeatherAcceptsSingleDay(): void
    {
        $this->service->method("findCity")->willReturn(["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"]);
        $this->service->method("getCityDailyWeather")->willReturn([]);

        $response = $this->controller->getCityWeather(
            new HttpRequest(["cityId" => "1", "from" => "2023-01-01", "to" => "2023-01-01"]),
        );

        $this->assertSame(200, $response->getStatus());
    }

    /**
     * @return array<string, array{array<string, string|list<string>>}>
     */
    public static function invalidCityRequests(): array
    {
        return [
            "hiányzó cityId" => [["from" => "2023-01-01", "to" => "2023-01-31"]],
            "hibás cityId" => [["cityId" => "abc", "from" => "2023-01-01", "to" => "2023-01-31"]],
            "hiányzó from" => [["cityId" => "1", "to" => "2023-01-31"]],
            "hiányzó to" => [["cityId" => "1", "from" => "2023-01-01"]],
            "hibás dátum" => [["cityId" => "1", "from" => "2023-02-31", "to" => "2023-03-31"]],
            "from később, mint to" => [["cityId" => "1", "from" => "2023-02-01", "to" => "2023-01-01"]],
        ];
    }

    /**
     * @param array<string, string|list<string>> $query
     */
    #[Test]
    #[DataProvider("invalidCityRequests")]
    public function getCityWeatherRejectsInvalidParameters(array $query): void
    {
        // Hibás kérésnél az adatbázishoz sem fordulunk.
        $this->service->expects($this->never())->method("findCity");
        $this->service->expects($this->never())->method("getCityDailyWeather");

        $response = $this->controller->getCityWeather(new HttpRequest($query));

        $this->assertSame(400, $response->getStatus());
        $this->assertArrayHasKey("error", $this->decode($response));
    }

    #[Test]
    public function getCityWeatherReturns404ForUnknownCity(): void
    {
        $this->service->method("findCity")->willReturn(null);
        $this->service->expects($this->never())->method("getCityDailyWeather");

        $response = $this->controller->getCityWeather(
            new HttpRequest(["cityId" => "99", "from" => "2023-01-01", "to" => "2023-01-31"]),
        );

        $this->assertSame(404, $response->getStatus());
    }

    #[Test]
    public function getRegionWeatherReturnsData(): void
    {
        $this->service->method("findRegionCities")->with("Dunántúl")->willReturn([
            ["id" => 3, "name" => "Keszthely", "region" => "Dunántúl"],
            ["id" => 6, "name" => "Pécs", "region" => "Dunántúl"],
        ]);
        $this->service->expects($this->once())
            ->method("getRegionDailyWeather")
            ->with("Dunántúl", new DateTimeImmutable("2023-01-01"), new DateTimeImmutable("2023-01-31"))
            ->willReturn([]);

        $response = $this->controller->getRegionWeather(
            new HttpRequest(["region" => "Dunántúl", "from" => "2023-01-01", "to" => "2023-01-31"]),
        );

        $this->assertSame(200, $response->getStatus());
        $body = $this->decode($response);
        $this->assertSame("Dunántúl", $body["region"]);
        $this->assertSame(
            [["id" => 3, "name" => "Keszthely"], ["id" => 6, "name" => "Pécs"]],
            $body["cities"],
        );
    }

    #[Test]
    public function getRegionWeatherUsesRegionNameFromDatabase(): void
    {
        // A kérésben kisbetűvel, szóközökkel érkezik, az adatbázisban a
        // pontos alak szerepel.
        $this->service->method("findRegionCities")->with("dunántúl")->willReturn([
            ["id" => 3, "name" => "Keszthely", "region" => "Dunántúl"],
        ]);
        $this->service->expects($this->once())
            ->method("getRegionDailyWeather")
            ->with("Dunántúl", $this->anything(), $this->anything())
            ->willReturn([]);

        $response = $this->controller->getRegionWeather(
            new HttpRequest(["region" => " dunántúl ", "from" => "2023-01-01", "to" => "2023-01-31"]),
        );

        $this->assertSame("Dunántúl", $this->decode($response)["region"]);
    }

    /**
     * @return array<string, array{array<string, string|list<string>>}>
     */
    public static function invalidRegionRequests(): array
    {
        return [
            "hiányzó region" => [["from" => "2023-01-01", "to" => "2023-01-31"]],
            "üres region" => [["region" => "  ", "from" => "2023-01-01", "to" => "2023-01-31"]],
            "tömb region" => [["region" => ["Dunántúl"], "from" => "2023-01-01", "to" => "2023-01-31"]],
            "hibás dátum" => [["region" => "Dunántúl", "from" => "2023-13-01", "to" => "2023-01-31"]],
            "from később, mint to" => [["region" => "Dunántúl", "from" => "2023-02-01", "to" => "2023-01-01"]],
        ];
    }

    /**
     * @param array<string, string|list<string>> $query
     */
    #[Test]
    #[DataProvider("invalidRegionRequests")]
    public function getRegionWeatherRejectsInvalidParameters(array $query): void
    {
        $this->service->expects($this->never())->method("findRegionCities");
        $this->service->expects($this->never())->method("getRegionDailyWeather");

        $response = $this->controller->getRegionWeather(new HttpRequest($query));

        $this->assertSame(400, $response->getStatus());
        $this->assertArrayHasKey("error", $this->decode($response));
    }

    #[Test]
    public function getRegionWeatherReturns404ForUnknownRegion(): void
    {
        $this->service->method("findRegionCities")->willReturn([]);
        $this->service->expects($this->never())->method("getRegionDailyWeather");

        $response = $this->controller->getRegionWeather(
            new HttpRequest(["region" => "Alföld", "from" => "2023-01-01", "to" => "2023-01-31"]),
        );

        $this->assertSame(404, $response->getStatus());
    }

    #[Test]
    public function getCitiesAndRegionsWrapLists(): void
    {
        $this->service->method("getCities")->willReturn([["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"]]);
        $this->service->method("getRegions")->willReturn(["Dunántúl"]);

        $this->assertSame(
            ["cities" => [["id" => 1, "name" => "Budapest", "region" => "Közép-Magyarország"]]],
            $this->decode($this->controller->getCities()),
        );
        $this->assertSame(["regions" => ["Dunántúl"]], $this->decode($this->controller->getRegions()));
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(HttpResponseInterface $response): array
    {
        $body = json_decode((string) $response->getBody(), true);
        $this->assertIsArray($body);
        return $body;
    }
}
