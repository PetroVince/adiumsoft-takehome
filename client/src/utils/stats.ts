/**
 * Egy tizedesjegyre kerekít (a forrásadatok is ilyen pontosságúak).
 */
export function roundToTenth(value: number): number {
  return Math.round(value * 10) / 10;
}

/**
 * Átlaghőmérséklet a minimum- és maximumhőmérsékletből. Ha valamelyik
 * hiányzik, az eredmény is hiányzó.
 */
export function averageTemp(
  minTemp: number | null,
  maxTemp: number | null,
): number | null {
  if (minTemp === null || maxTemp === null) {
    return null;
  }
  return roundToTenth((minTemp + maxTemp) / 2);
}

/**
 * Medián. Páros elemszámnál a két középső érték átlaga. Üres listára `null`.
 */
export function median(values: number[]): number | null {
  if (values.length === 0) {
    return null;
  }
  const sorted = [...values].sort((a, b) => a - b);
  const middle = Math.floor(sorted.length / 2);
  if (sorted.length % 2 === 1) {
    return sorted[middle];
  }
  return (sorted[middle - 1] + sorted[middle]) / 2;
}

/**
 * Módusz: a leggyakoribb érték(ek), növekvő sorrendben. Több is lehet, ha
 * azonos a gyakoriságuk. Ha minden érték csak egyszer fordul elő, nincs
 * módusz, ilyenkor üres listát ad vissza.
 */
export function modes(values: number[]): number[] {
  const counts = new Map<number, number>();
  for (const value of values) {
    counts.set(value, (counts.get(value) ?? 0) + 1);
  }

  let maxCount = 0;
  for (const count of counts.values()) {
    maxCount = Math.max(maxCount, count);
  }
  if (maxCount < 2) {
    return [];
  }

  return [...counts.entries()]
    .filter(([, count]) => count === maxCount)
    .map(([value]) => value)
    .sort((a, b) => a - b);
}
