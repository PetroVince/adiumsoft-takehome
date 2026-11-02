import { useEffect, useState, type FormEvent } from "react";
import {
  getRegions,
  getRegionWeather,
  type RegionWeather,
} from "../api/weatherApi.ts";
import {
  DateField,
  ErrorMessage,
  SelectField,
  SubmitButton,
} from "../components/FormControls.tsx";
import { validateDateRange } from "../utils/dateRange.ts";
import { WeatherTable } from "../components/WeatherTable.tsx";

export function RegionWeatherPage() {
  const [regions, setRegions] = useState<string[]>([]);
  const [region, setRegion] = useState("");
  const [from, setFrom] = useState("2023-01-01");
  const [to, setTo] = useState("2023-01-31");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [result, setResult] = useState<RegionWeather | null>(null);

  useEffect(() => {
    getRegions()
      .then((regions) => {
        setRegions(regions);
        if (regions.length > 0) {
          setRegion(regions[0]);
        }
      })
      .catch((e: unknown) => setError(`A régiók betöltése sikertelen: ${e}`));
  }, []);

  const onSubmit = async (e: FormEvent) => {
    e.preventDefault();

    const validationError =
      region === "" ? "Válassz régiót!" : validateDateRange(from, to);
    if (validationError !== null) {
      setError(validationError);
      return;
    }

    setError(null);
    setLoading(true);
    try {
      setResult(await getRegionWeather(region, from, to));
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
          id="region"
          label="Régió"
          value={region}
          onChange={setRegion}
          options={regions.map((region) => ({ value: region, label: region }))}
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
            {result.region}, {result.from} – {result.to}
          </h2>
          <p className="text-sm text-gray-600">
            A városok napi átlaga:{" "}
            {result.cities.map((city) => city.name).join(", ")}
          </p>
          <WeatherTable days={result.days} units={result.units} />
        </>
      )}
    </section>
  );
}
