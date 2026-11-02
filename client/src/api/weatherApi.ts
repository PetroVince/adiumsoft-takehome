export type City = {
  id: number;
  name: string;
  region: string;
};

export type DailyWeather = {
  date: string;
  minTemp: number | null;
  maxTemp: number | null;
  precipitation: number | null;
};

export type Units = {
  temperature: string;
  precipitation: string;
};

export type CityWeather = {
  city: City;
  from: string;
  to: string;
  units: Units;
  days: DailyWeather[];
};

export type RegionWeather = {
  region: string;
  cities: Pick<City, "id" | "name">[];
  from: string;
  to: string;
  units: Units;
  days: DailyWeather[];
};

export async function getCities(): Promise<City[]> {
  const body = await get<{ cities: City[] }>("/cities");
  return body.cities;
}

export async function getRegions(): Promise<string[]> {
  const body = await get<{ regions: string[] }>("/regions");
  return body.regions;
}

export function getCityWeather(
  cityId: number,
  from: string,
  to: string,
): Promise<CityWeather> {
  return get<CityWeather>("/weather/city", {
    cityId: String(cityId),
    from,
    to,
  });
}

export function getRegionWeather(
  region: string,
  from: string,
  to: string,
): Promise<RegionWeather> {
  return get<RegionWeather>("/weather/region", { region, from, to });
}

async function get<T>(
  path: string,
  query?: Record<string, string>,
): Promise<T> {
  const baseUrl = import.meta.env.VITE_API_URL;
  const queryString = query ? `?${new URLSearchParams(query)}` : "";

  const res = await fetch(`${baseUrl}${path}${queryString}`);
  if (!res.ok) {
    // A szerver hibaüzenete {"error": "..."} formában érkezik.
    const body = (await res.json().catch(() => null)) as {
      error?: string;
    } | null;
    throw new Error(body?.error ?? `HTTP ${res.status}`);
  }
  return (await res.json()) as T;
}
