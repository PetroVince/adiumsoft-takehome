import type { ReactNode } from "react";
import { FIRST_DATE, LAST_DATE } from "../utils/dateRange.ts";

const fieldClassName =
  "p-2 border-b-1 bg-gray-100 rounded-t-md focus:outline-none focus:border-b-amber-500";

export function Field({
  id,
  label,
  children,
}: {
  id: string;
  label: string;
  children: ReactNode;
}) {
  return (
    <div className="flex flex-col">
      <label htmlFor={id} className="pb-1 text-sm font-bold">
        {label}
      </label>
      {children}
    </div>
  );
}

export function SelectField({
  id,
  label,
  value,
  options,
  onChange,
}: {
  id: string;
  label: string;
  value: string;
  options: { value: string; label: string }[];
  onChange: (value: string) => void;
}) {
  return (
    <Field id={id} label={label}>
      <select
        id={id}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        className={fieldClassName}
      >
        {options.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </Field>
  );
}

export function DateField({
  id,
  label,
  value,
  onChange,
}: {
  id: string;
  label: string;
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <Field id={id} label={label}>
      <input
        id={id}
        type="date"
        value={value}
        min={FIRST_DATE}
        max={LAST_DATE}
        onChange={(e) => onChange(e.target.value)}
        className={fieldClassName}
      />
    </Field>
  );
}

export function SubmitButton({
  loading,
  children,
}: {
  loading: boolean;
  children: ReactNode;
}) {
  return (
    <button
      type="submit"
      disabled={loading}
      className="self-end py-2 px-4 font-bold bg-amber-300 hover:bg-amber-400 active:bg-amber-500 rounded-lg cursor-pointer disabled:cursor-wait disabled:opacity-60"
    >
      {loading ? "Betöltés…" : children}
    </button>
  );
}

export function ErrorMessage({ message }: { message: string | null }) {
  if (message === null) {
    return null;
  }
  return (
    <p className="mt-4 p-3 rounded-md bg-red-50 text-red-700 text-sm">
      {message}
    </p>
  );
}
