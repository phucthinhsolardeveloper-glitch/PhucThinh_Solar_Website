import assert from 'node:assert/strict'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { join } from 'node:path'
import test from 'node:test'

const root = new URL('..', import.meta.url).pathname
const pattern = /upload\/([a-z]+\/[A-Za-z0-9._-]+\.(?:jpe?g|png|webp|gif|pdf))/g

function files(dir) {
  return readdirSync(dir).flatMap((name) => {
    const path = join(dir, name)
    return statSync(path).isDirectory() ? files(path) : /\.(vue|ts|mjs)$/.test(name) ? [path] : []
  })
}

test('every upload the frontend links to is pinned in the media inventory', () => {
  const pinned = readFileSync(join(root, '../backend/config/media.php'), 'utf8')
  const linked = new Set(['app', 'shared', 'server'].flatMap((dir) => files(join(root, dir)))
    .flatMap((file) => [...readFileSync(file, 'utf8').matchAll(pattern)].map((m) => m[1])))
  const missing = [...linked].filter((path) => !pinned.includes(`'${path}'`))
  assert.deepEqual(missing, [], 'add these paths to backend/config/media.php "pinned"')
})
