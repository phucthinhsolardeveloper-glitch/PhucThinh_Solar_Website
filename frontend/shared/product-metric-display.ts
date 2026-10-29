const numericValueOrRange = /^\d+(?:[.,]\d+)?(?:\s*(?:-|–|—|đến|to)\s*\d+(?:[.,]\d+)?)*$/i

/** Add the configured unit only to bare numeric values; preserve descriptive CMS text verbatim. */
export function formatProductMetricValue(valueInput: string, unitInput: string): string {
  const value = valueInput.trim()
  const unit = unitInput.trim()
  if (!unit || !numericValueOrRange.test(value)) return value
  return value + ' ' + unit
}
