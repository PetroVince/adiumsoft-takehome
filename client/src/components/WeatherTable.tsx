import type { DailyWeather, Units } from "../api/weatherApi.ts";
import { averageTemp, median, modes } from "../utils/stats.ts";

type Column = {
  label: string;
  unit: string;
  values: (number | null)[];
};

// Ennél több módusz értéket nem írunk ki, csak a darabszámukat.
const MAX_MODES_SHOWN = 3;

const numberFormat = new Intl.NumberFormat("hu-HU", {
  maximumFractionDigits: 2,
});

export function WeatherTable({
  days,
  units,
}: {
  days: DailyWeather[];
  units: Units;
}) {
  if (days.length === 0) {
    return (
      <p className="mt-6 text-center text-gray-500">
        Nincs adat a megadott időszakra.
      </p>
    );
  }

  const columns: Column[] = [
    {
      label: "Minimum",
      unit: units.temperature,
      values: days.map((day) => day.minTemp),
    },
    {
      label: "Maximum",
      unit: units.temperature,
      values: days.map((day) => day.maxTemp),
    },
    {
      label: "Átlag",
      unit: units.temperature,
      values: days.map((day) => averageTemp(day.minTemp, day.maxTemp)),
    },
    {
      label: "Csapadék",
      unit: units.precipitation,
      values: days.map((day) => day.precipitation),
    },
  ];

  // A hiányzó mérések nem számítanak bele a statisztikákba.
  const presentValues = columns.map((column) =>
    column.values.filter((value): value is number => value !== null),
  );

  return (
    <div className="mt-6 max-h-[70vh] overflow-auto rounded-lg border border-gray-200 shadow-sm">
      <table className="w-full border-collapse text-sm tabular-nums">
        <thead className="sticky top-0 bg-amber-100 text-left">
          <tr>
            <th className="px-4 py-2 font-semibold">Dátum</th>
            {columns.map((column) => (
              <th
                key={column.label}
                className="px-4 py-2 text-right font-semibold"
              >
                {column.label}
              </th>
            ))}
          </tr>
        </thead>
        <tbody>
          {days.map((day, rowIndex) => (
            <tr key={day.date} className="odd:bg-white even:bg-gray-50">
              <td className="px-4 py-1.5 whitespace-nowrap">{day.date}</td>
              {columns.map((column) => (
                <td
                  key={column.label}
                  className="px-4 py-1.5 text-right whitespace-nowrap"
                >
                  {formatValue(column.values[rowIndex], column.unit)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
        <tfoot className="sticky bottom-0 bg-amber-50 font-semibold">
          <tr className="border-t-2 border-amber-300">
            <th className="px-4 py-2 text-left">Medián</th>
            {columns.map((column, index) => (
              <td
                key={column.label}
                className="px-4 py-2 text-right whitespace-nowrap"
              >
                {formatValue(median(presentValues[index]), column.unit)}
              </td>
            ))}
          </tr>
          <tr>
            <th className="px-4 py-2 text-left">Módusz</th>
            {columns.map((column, index) => (
              <td
                key={column.label}
                className="px-4 py-2 text-right whitespace-nowrap"
              >
                {presentValues[index].length === 0
                  ? "–"
                  : formatModes(modes(presentValues[index]), column.unit)}
              </td>
            ))}
          </tr>
        </tfoot>
      </table>
    </div>
  );
}

function formatValue(value: number | null, unit: string): string {
  if (value === null) {
    return "–";
  }
  return `${numberFormat.format(value)} ${unit}`;
}

function formatModes(values: number[], unit: string): string {
  if (values.length === 0) {
    return "nincs";
  }
  const shown = values
    .slice(0, MAX_MODES_SHOWN)
    .map((value) => numberFormat.format(value))
    .join("; ");
  const hidden = values.length - MAX_MODES_SHOWN;
  return hidden > 0 ? `${shown} ${unit} (+${hidden})` : `${shown} ${unit}`;
}
