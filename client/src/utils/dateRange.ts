// Az adatbázisban szereplő időszak.
export const FIRST_DATE = "1901-01-01";
export const LAST_DATE = "2023-12-31";

/**
 * Ellenőrzi a dátumtartományt, hiba esetén a hibaüzenettel tér vissza.
 */
export function validateDateRange(from: string, to: string): string | null {
  if (from === "" || to === "") {
    return "A kezdő- és végdátum megadása kötelező.";
  }
  if (from > to) {
    return "A kezdődátum nem lehet későbbi a végdátumnál.";
  }
  return null;
}
