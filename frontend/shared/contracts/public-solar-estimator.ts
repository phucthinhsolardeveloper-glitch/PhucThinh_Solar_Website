import { isRecord, isText } from './core.ts'

type Choice = { key: string; label: string }
type BillRange = { min: number; max: number; default: number }

/** GET /api/v1/solar-estimator: the choices of the estimate form. Off until the owner switches it on in Admin. */
export interface PublicSolarEstimatorForm {
  schemaVersion: 1
  enabled: boolean
  types: Choice[]
  habits: Choice[]
  regions: Choice[]
  bill: { household: BillRange; business: BillRange }
  businessPrice: number
}

/** POST /api/v1/solar-estimate: one estimate, computed only by Laravel. */
export interface PublicSolarEstimate {
  type: 'household' | 'business'
  typeLabel: string
  region: string
  habit: string
  battery: boolean
  bill: number
  usageKwh: number
  kwp: number
  panels: number
  panelWatt: number
  roofM2: number
  limitedByRoof: boolean
  batteryKwh: number
  outputKwh: number
  saving: number
  newBill: number
  savingPercent: number
  cost: { low: number; high: number }
  payback: { low: number; high: number } | null
  co2Tonnes: number
  basis: string
  summary: string
}

const amount = (value: unknown): value is number => typeof value === 'number' && Number.isFinite(value) && value >= 0
const choices = (value: unknown): value is Choice[] => Array.isArray(value) && value.length > 0
  && value.every((item) => isRecord(item) && isText(item.key) && /^[a-z-]{2,20}$/.test(item.key) && isText(item.label))
const range = (value: unknown): value is BillRange => isRecord(value) && amount(value.min) && amount(value.max) && amount(value.default) && value.min < value.max

export function isPublicSolarEstimatorForm(value: unknown): value is PublicSolarEstimatorForm {
  return isRecord(value) && value.schemaVersion === 1 && typeof value.enabled === 'boolean' && choices(value.types) && choices(value.habits)
    && choices(value.regions) && isRecord(value.bill) && range(value.bill.household) && range(value.bill.business) && amount(value.businessPrice)
}

export function isPublicSolarEstimate(value: unknown): value is PublicSolarEstimate {
  if (!isRecord(value)) return false
  const numbers = ['bill', 'usageKwh', 'kwp', 'panels', 'panelWatt', 'roofM2', 'batteryKwh', 'outputKwh', 'saving', 'newBill', 'savingPercent', 'co2Tonnes']
  return (value.type === 'household' || value.type === 'business') && isText(value.typeLabel) && isText(value.region) && isText(value.habit)
    && typeof value.battery === 'boolean' && typeof value.limitedByRoof === 'boolean' && numbers.every((key) => amount(value[key]))
    && isRecord(value.cost) && amount(value.cost.low) && amount(value.cost.high)
    && (value.payback === null || (isRecord(value.payback) && amount(value.payback.low) && amount(value.payback.high)))
    && isText(value.basis) && typeof value.summary === 'string' && isText(value.summary.replace(/\n/g, ' '))
}
