import { useEffect, useState, type FormEvent } from "react";
import {
  getCities,
  getCityWeather,
  type City,
  type CityWeather,
} from "../api/weatherApi.ts";
import {
  DateField,
  ErrorMessage,
  SelectField,
  SubmitButton,
} from "../components/FormControls.tsx";
import { validateDateRange } from "../utils/dateRange.ts";
import { WeatherTable } from "../components/WeatherTable.tsx";

export function CityWeatherPage() {
  const [cities, setCities] = useState<City[]>([]);
  const [cityId, setCityId] = useState("");
  const [from, setFrom] = useState("2023-01-01");
  const [to, setTo] = useState("2023-01-31");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<CityWeather | null>(null);

  useEffect(() => {
    getCities()
      .then((cities) => {
        setCities(cities);
        if (cities.length > 0) {
          setCityId(String(cities[0].id));
        }
      })
      .catch((e: unknown) => setError(`A városok betöltése sikertelen: ${e}`));
  }, []);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();

    const validationError =
      cityId === "" ? "Válassz várost!" : validateDateRange(from, to);
    if (validationError !== null) {
      setError(validationError);
      return;
    }

    setError(null);
    setLoading(true);
    try {
      setResult(await getCityWeather(Number(cityId), from, to));
    } catch (e) {
      setError(e instanceof Error ? e.message : String(e));
      setResult(null);
    } finally {
      setLoading(false);
    }
  };

  return (
    <section>
      <form
        onSubmit={onSubmit}
        className="grid gap-4 sm:grid-cols-[2fr_1fr_1fr_auto] items-end"
      >
        <SelectField
          id="city"
          label="Város"
          value={cityId}
          onChange={setCityId}
          // Két "Szombathely" is van az adatbázisban, a régió segít megkülönböztetni.
          options={cities.map((city) => ({
            value: String(city.id),
            label: `${city.name} (${city.region})`,
          }))}
        />
        <DateField
          id="from"
          label="Kezdődátum"
          value={from}
          onChange={setFrom}
        />
        <DateField id="to" label="Végdátum" value={to} onChange={setTo} />
        <SubmitButton loading={loading}>Lekérdezés</SubmitButton>
      </form>

      <ErrorMessage message={error} />

      {result && (
        <>
          <h2 className="mt-6 text-lg font-semibold">
            {result.city.name}, {result.from} – {result.to}
          </h2>
          <WeatherTable days={result.days} units={result.units} />
        </>
      )}
    </section>
  );
}
