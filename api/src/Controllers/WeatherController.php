<?php

declare(strict_types=1);

namespace ADS\TakeHome\Controllers;

use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Services\WeatherService;
use ADS\TakeHome\Utils\AppHelpers;
use DateTimeImmutable;

class WeatherController
{
    public function __construct(
        private readonly WeatherService $weatherService,
    ) {
    }

    /**
     * Az összes város listája (a kliensoldali választóhoz).
     */
    public function getCities(): HttpResponseInterface
    {
        return new JsonHttpResponse(["cities" => $this->weatherService->getCities()]);
    }

    /**
     * Az összes régió listája (a kliensoldali választóhoz).
     */
    public function getRegions(): HttpResponseInterface
    {
        return new JsonHttpResponse(["regions" => $this->weatherService->getRegions()]);
    }

    /**
     * Egy város napi időjárási adatai a megadott időszakban.
     *
     * Query paraméterek: `cityId`, `from` és `to` (YYYY-MM-DD, mindkettő zárt intervallum határ).
     */
    public function getCityWeather(HttpRequest $request): HttpResponseInterface
    {
        $cityId = AppHelpers::parseId($request->query["cityId"] ?? null);
        if ($cityId === null) {
            return new JsonHttpResponse(["error" => "'cityId' query paraméter kötelező, pozitív egész szám"], 400);
        }

        $dateRange = $this->parseDateRange($request);
        if ($dateRange instanceof HttpResponseInterface) {
            return $dateRange;
        }
        [$from, $to] = $dateRange;

        $city = $this->weatherService->findCity($cityId);
        if ($city === null) {
            return new JsonHttpResponse(["error" => "nem található város ezzel az azonosítóval: {$cityId}"], 404);
        }

        return new JsonHttpResponse([
            "city" => $city,
            "from" => $from->format("Y-m-d"),
            "to" => $to->format("Y-m-d"),
            "units" => [
                "temperature" => "°C",
                "precipitation" => "mm",
            ],
            "days" => $this->weatherService->getCityDailyWeather($cityId, $from, $to),
        ]);
    }

    /**
     * Egy régió városainak napi szinten átlagolt időjárási adatai a megadott
     * időszakban.
     *
     * Query paraméterek: `region` (pl. Dunántúl), `from` és `to` (YYYY-MM-DD, mindkettő zárt intervallum határ).
     */
    public function getRegionWeather(HttpRequest $request): HttpResponseInterface
    {
        $region = $request->query["region"] ?? null;
        if (!is_string($region) || trim($region) === "") {
            return new JsonHttpResponse(["error" => "'region' query paraméter kötelező"], 400);
        }

        $dateRange = $this->parseDateRange($request);
        if ($dateRange instanceof HttpResponseInterface) {
            return $dateRange;
        }
        [$from, $to] = $dateRange;

        $cities = $this->weatherService->findRegionCities(trim($region));
        if (count($cities) === 0) {
            return new JsonHttpResponse(["error" => "nem található ilyen régió: {$region}"], 404);
        }
        // Az adatbázisban tárolt alakot adjuk vissza, nem a kérésben kapottat.
        $regionName = $cities[0]["region"];

        return new JsonHttpResponse([
            "region" => $regionName,
            "cities" => array_map(fn (array $city): array => [
                "id" => $city["id"],
                "name" => $city["name"],
            ], $cities),
            "from" => $from->format("Y-m-d"),
            "to" => $to->format("Y-m-d"),
            "units" => [
                "temperature" => "°C",
                "precipitation" => "mm",
            ],
            "days" => $this->weatherService->getRegionDailyWeather($regionName, $from, $to),
        ]);
    }

    /**
     * A `from` és `to` query paraméterek feldolgozása. Hibás paraméterek
     * esetén a visszaküldendő hibaválasszal tér vissza.
     *
     * @return array{DateTimeImmutable, DateTimeImmutable}|HttpResponseInterface
     */
    private function parseDateRange(HttpRequest $request): array|HttpResponseInterface
    {
        $from = AppHelpers::parseDate($request->query["from"] ?? null);
        $to = AppHelpers::parseDate($request->query["to"] ?? null);
        if ($from === null || $to === null) {
            return new JsonHttpResponse(["error" => "'from' és 'to' query paraméter kötelező, YYYY-MM-DD formátumban"], 400);
        }
        if ($from > $to) {
            return new JsonHttpResponse(["error" => "'from' nem lehet későbbi, mint 'to'"], 400);
        }
        return [$from, $to];
    }
}
