// Partner brands with a logo in public/assets/images/brand-logos/. The About wall shows all of them, and the
// product landing page shows them beside the brands that currently have products.
export const PARTNER_BRANDS = [
  { name: 'AESOLAR', logo: 'aesolar.webp' },
  { name: 'Anker SOLIX', logo: 'ankersolix.webp' },
  { name: 'Astronergy', logo: 'astronergy.webp' },
  { name: 'Blue Carbon', logo: 'bluecarbon.webp' },
  { name: 'Canadian Solar', logo: 'canadiansolar.webp' },
  { name: 'Deye', logo: 'deye.webp' },
  { name: 'EPSIVO', logo: 'epsivo.webp' },
  { name: 'GoodWe', logo: 'goodwe.webp' },
  { name: 'Growatt', logo: 'growatt.webp' },
  { name: 'Hober', logo: 'hober.webp' },
  { name: 'Huawei', logo: 'huawei.webp' },
  { name: 'Jindian', logo: 'jdjindian.webp' },
  { name: 'LONGi', logo: 'longi.webp' },
  { name: 'Oriana', logo: 'oriana.webp' },
  { name: 'Sharp', logo: 'sharp.webp' },
  { name: 'Sigenergy', logo: 'sigenergy.webp' },
  { name: 'SOFAR', logo: 'sofar.webp' },
  { name: 'SolaX', logo: 'solax.webp' },
  { name: 'Sungrow', logo: 'sungrow.webp' },
  { name: 'TCL Solar', logo: 'tclsolar.webp' },
] as const

/** Compares brand names the way they are typed in Admin: accents, case, spaces and the "JD" prefix ignored. */
export function brandKey(value: string): string {
  const key = value.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]/g, '')
  return key === 'jdjindian' ? 'jindian' : key
}

export function partnerBrandLogo(name: string): string {
  const brand = PARTNER_BRANDS.find((item) => brandKey(item.name) === brandKey(name))
  return brand ? `/assets/images/brand-logos/${brand.logo}` : ''
}
