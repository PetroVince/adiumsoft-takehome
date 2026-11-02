import { useEffect, useState } from "react";
import { CityWeatherPage } from "./pages/CityWeatherPage.tsx";
import { RegionWeatherPage } from "./pages/RegionWeatherPage.tsx";

type Page = "city" | "region";

const pages: { id: Page; label: string }[] = [
  { id: "city", label: "Város" },
  { id: "region", label: "Régió" },
];

// Routing könyvtár nélkül, az URL hash részéből (#city, #region) olvassuk ki
// az aktuális oldalt, így frissítés után is ugyanott maradunk.
function pageFromHash(): Page {
  return window.location.hash === "#region" ? "region" : "city";
}

export function App() {
  const [page, setPage] = useState<Page>(pageFromHash);

  useEffect(() => {
    const onHashChange = () => setPage(pageFromHash());
    window.addEventListener("hashchange", onHashChange);
    return () => window.removeEventListener("hashchange", onHashChange);
  }, []);

  return (
    <div className="min-h-screen bg-gray-50 text-gray-900">
      <header className="bg-white border-b-2 border-amber-400">
        <div className="max-w-5xl mx-auto px-4 sm:px-8 py-4 flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-xl font-bold">Időjárási adatok</h1>
          <nav className="flex gap-2">
            {pages.map(({ id, label }) => (
              <a
                key={id}
                href={`#${id}`}
                className={`py-1.5 px-4 rounded-lg font-semibold ${
                  page === id ? "bg-amber-300" : "hover:bg-amber-100"
                }`}
              >
                {label}
              </a>
            ))}
          </nav>
        </div>
      </header>

      <main className="max-w-5xl mx-auto px-4 sm:px-8 py-6">
        <div className="p-4 sm:p-6 bg-white rounded-lg shadow-sm">
          {/* A key miatt oldalváltáskor az űrlap állapota is alaphelyzetbe áll. */}
          {page === "city" ? (
            <CityWeatherPage key="city" />
          ) : (
            <RegionWeatherPage key="region" />
          )}
        </div>
      </main>
    </div>
  );
}
