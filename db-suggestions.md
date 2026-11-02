# Adatbázis struktúra módosítás javaslatok

<!-- ez a fájl egy nem kötelező feladat része -->

1. **Elsődleges kulcs és index hiányzik az időjárási táblákon.** Mindhárom
   táblára `PRIMARY KEY (city_id, date)` kellene: kizárja a duplikált sorokat,
   és a lekérdezéseknek nem kellene a teljes táblát végigolvasniuk.

2. **Idegen kulcsok hiányoznak.** A `city_id` mezőknek a `cities.id`-ra kellene
   hivatkozniuk, most nem létező városhoz is be lehet szúrni adatot.

3. **A dátum `BIGINT` (pl. `20230101`), `DATE` helyett.** Érvénytelen dátum is
   tárolható (pl. `20231345`), a dátumfüggvények nem használhatók, minden
   lekérdezésnél konvertálni kell. Napi adatokról van szó, ezért `DATE` és nem
   `DATETIME`.

4. **A régió `enum` helyett külön `regions` táblában legyen.** Új régió
   felvétele vagy átnevezése így nem igényel `ALTER TABLE`-t.

5. **A három időjárási tábla összevonható egybe**
   (`daily_weather (city_id, date, min_temp, max_temp, precipitation)`), mert
   ugyanannak a városnak ugyanarra a napra vonatkozó adatai. Így nem kell minden
   lekérdezésben két `JOIN`.

6. **`FLOAT` helyett `DECIMAL(4,1)`.** Az adatok egy tizedesjegy pontosságúak, a
   `FLOAT` ezeket csak közelítőleg tárolja.
