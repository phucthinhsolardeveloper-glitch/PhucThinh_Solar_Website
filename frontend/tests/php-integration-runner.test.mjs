import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const runner = readFileSync(new URL('../../scripts/test-pts-public-api-integration.php', import.meta.url), 'utf8')

test('dynamic PHP API integration checks map database slugvi to public slug queries', () => {
  assert.match(runner, /'product'=>array\("select id,slugvi,namevi from #_product/)
  assert.match(runner, /\$fixtureField = \$param === 'slug' \? 'slugvi' : \$param/)
  assert.match(runner, /\$fixtures\[\$fixtureKey\]\[\$fixtureField\]/)
  assert.match(runner, /ptsIntegrationRequest\(\$name, \$path, 'GET', array\(\$param=>\$value\)\)/)
  assert.match(runner, /array\('solution_detail',[\s\S]*array\('schemaVersion','solution','navigation','editorial'\)\)/)
})
