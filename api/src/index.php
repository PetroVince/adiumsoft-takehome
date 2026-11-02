<?php

namespace ADS\TakeHome;

include __DIR__ . "/../vendor/autoload.php";

use ADS\TakeHome\Controllers\ExampleController;
use ADS\TakeHome\Controllers\WeatherController;
use ADS\TakeHome\Framework\MySQL;
use ADS\TakeHome\Framework\Router;
use ADS\TakeHome\Services\WeatherService;

$router = new Router();

$mysql = new MySQL("db", "weather", "root", "root");
$helloController = new ExampleController(greeting: "Hello", db: $mysql);
$weatherService = new WeatherService(db: $mysql);
$weatherController = new WeatherController(weatherService: $weatherService);

$router->addRoute("GET", "/hello", $helloController->sayHello(...));
$router->addRoute("GET", "/budapest", $helloController->getBudapestMaxTempCount(...));
$router->addRoute("GET", "/cities", $weatherController->getCities(...));
$router->addRoute("GET", "/regions", $weatherController->getRegions(...));
$router->addRoute("GET", "/weather/city", $weatherController->getCityWeather(...));
$router->addRoute("GET", "/weather/region", $weatherController->getRegionWeather(...));

$router->handleRequest();
