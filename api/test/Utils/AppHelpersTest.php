<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Utils;

use ADS\TakeHome\Utils\AppHelpers;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class AppHelpersTest extends TestCase
{
    #[Test]
    public function parseIdAcceptsPositiveInteger(): void
    {
        $this->assertSame(1, AppHelpers::parseId("1"));
        $this->assertSame(42, AppHelpers::parseId("42"));
    }

    /**
     * @return array<string, array{string|list<string>|null}>
     */
    public static function invalidIds(): array
    {
        return [
            "hiányzó" => [null],
            "üres" => [""],
            "nulla" => ["0"],
            "negatív" => ["-1"],
            "vezető nulla" => ["01"],
            "tört" => ["1.5"],
            "szöveg" => ["abc"],
            "szóköz" => [" 1"],
            "túl hosszú" => ["12345678901"],
            "tömb (?cityId[]=1)" => [["1"]],
        ];
    }

    /**
     * @param string|list<string>|null $value
     */
    #[Test]
    #[DataProvider("invalidIds")]
    public function parseIdRejectsInvalidValue(string|array|null $value): void
    {
        $this->assertNull(AppHelpers::parseId($value));
    }

    #[Test]
    public function parseDateAcceptsValidDate(): void
    {
        $date = AppHelpers::parseDate("2023-01-31");

        $this->assertNotNull($date);
        $this->assertSame("2023-01-31 00:00:00", $date->format("Y-m-d H:i:s"));
    }

    #[Test]
    public function parseDateAcceptsLeapDay(): void
    {
        $this->assertNotNull(AppHelpers::parseDate("2020-02-29"));
    }

    /**
     * @return array<string, array{string|list<string>|null}>
     */
    public static function invalidDates(): array
    {
        return [
            "hiányzó" => [null],
            "üres" => [""],
            "nem létező nap" => ["2023-02-31"],
            "nem szökőév" => ["2023-02-29"],
            "nem létező hónap" => ["2023-13-01"],
            "más formátum" => ["2023.01.01"],
            "fordított sorrend" => ["01-01-2023"],
            "kitöltetlen számjegyek" => ["2023-1-1"],
            "időponttal" => ["2023-01-01 12:00"],
            "szöveg" => ["tegnap"],
            "tömb (?from[]=...)" => [["2023-01-01"]],
        ];
    }

    /**
     * @param string|list<string>|null $value
     */
    #[Test]
    #[DataProvider("invalidDates")]
    public function parseDateRejectsInvalidValue(string|array|null $value): void
    {
        $this->assertNull(AppHelpers::parseDate($value));
    }
}
