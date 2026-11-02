<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

/**
 * HTTP kérés.
 *
 * A keretrendszer egy ilyen objektumot ad át a végpont kezelő függvényeknek.
 */
class HttpRequest
{
    /**
     * @param array<string, string|list<string>> $query A kérés query paraméterei.
     */
    public function __construct(
        public readonly array $query,
        // a többit most hagyjuk
    ) {
    }

    /**
     * Létrehoz egy új kérést globális PHP változókból.
     */
    public static function fromGlobals(): HttpRequest
    {
        return new HttpRequest(
            query: $_GET,
        );
    }
}
