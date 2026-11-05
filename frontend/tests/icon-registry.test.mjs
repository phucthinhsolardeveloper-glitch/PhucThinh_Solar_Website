import assert from 'node:assert/strict'
import { readFileSync, readdirSync } from 'node:fs'
import { fileURLToPath } from 'node:url'
import { join } from 'node:path'
import { test } from 'node:test'

const appRoot = fileURLToPath(new URL('../app/', import.meta.url))
const nuxtConfig = readFileSync(fileURLToPath(new URL('../nuxt.config.ts', import.meta.url)), 'utf8')
const registryPath = fileURLToPath(new URL('../node_modules/@iconify-json/lucide/icons.json', import.meta.url))
const registry = JSON.parse(readFileSync(registryPath, 'utf8'))

function sourceFiles(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap((entry) => {
    const path = join(directory, entry.name)
    if (entry.isDirectory()) return sourceFiles(path)
    return /\.(?:vue|ts)$/.test(entry.name) ? [path] : []
  })
}

function resolvesIcon(name) {
  if (registry.icons[name]) return true
  let parent = registry.aliases?.[name]?.parent
  const seen = new Set([name])
  while (parent && !seen.has(parent)) {
    if (registry.icons[parent]) return true
    seen.add(parent)
    parent = registry.aliases?.[parent]?.parent
  }
  return false
}

test('every statically referenced Lucide icon resolves in the bundled registry or its aliases', () => {
  const used = new Set()
  for (const file of sourceFiles(appRoot)) {
    const source = readFileSync(file, 'utf8')
    for (const match of source.matchAll(/i-lucide-([a-z0-9-]+)/g)) used.add(match[1])
  }
  const missing = [...used].filter((name) => !resolvesIcon(name))
  assert.deepEqual(missing, [])
})

test('client icon scanning includes TypeScript helpers that choose icons at runtime', () => {
  assert.match(nuxtConfig, /clientBundle:\s*\{\s*scan:\s*\{\s*globInclude:\s*\[['"]\*\*\/\*\.\{vue,ts,/)
})
