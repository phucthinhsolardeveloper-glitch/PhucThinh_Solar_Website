import assert from 'node:assert/strict'
import { readFileSync } from 'node:fs'
import { test } from 'node:test'

const source = readFileSync(new URL('../../domains/phucthinhsolar.com/public_html/libraries/pts_public_solution_detail_api.php', import.meta.url), 'utf8')

test('solution media adapter preserves the upload directory passed by the API', () => {
  assert.match(source, /return '\/'\.trim\(\$uploadDirectory, '\/'\)\.'\/'\.\$value;/)
  assert.doesNotMatch(source, /function ptsSolutionDetailMediaUrl[\s\S]*?return '\/upload\/news\/'\.\$value;/)
})
