import type { PublicHomeSlide } from './public-home'

const heroSlots = [
  { key: 'street-light', needles: ['đèn đường', 'den duong', 'street light'] },
  { key: 'inverter', needles: ['biến tần', 'bien tan', 'inverter'] },
  { key: 'panel', needles: ['tấm pin', 'tam pin', 'solar panel'] },
  { key: 'storage', needles: ['pin lưu trữ', 'pin luu tru', 'battery', 'lưu trữ'] },
] as const

/** Recreates the PHP homepage's stable hero slot mapping from the API's ordered CMS rows. */
export function normalizePublicHomeHero(rows: PublicHomeSlide[]): PublicHomeSlide[] {
  return heroSlots.flatMap((slot, index) => {
    let selected = rows[index]

    for (const candidate of rows) {
      const name = candidate.name?.trim().toLocaleLowerCase('vi') ?? ''
      if (slot.needles.some((needle) => name.includes(needle))) selected = candidate
    }

    return selected ? [{ ...selected, key: slot.key }] : []
  })
}
