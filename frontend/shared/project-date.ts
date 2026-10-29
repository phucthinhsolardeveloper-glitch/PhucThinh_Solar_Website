/** Formats PHP's Y-m-d project date value using the legacy public d/m/Y display. */
export function formatProjectDate(value: string): string {
  const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value)
  if (!match) return value

  const [, year, month, day] = match
  const date = new Date(`${value}T00:00:00.000Z`)
  if (Number.isNaN(date.valueOf()) || date.toISOString().slice(0, 10) !== value) return value
  return `${day}/${month}/${year}`
}
