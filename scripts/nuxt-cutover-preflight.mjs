#!/usr/bin/env node
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import {
  auditRepository,
  probeOrigin,
  readJson,
  rollbackPlan,
  validateAllowlist,
  validateApproval,
  validateLoopbackOrigin,
  validateProbeResults,
} from './lib/nuxt-cutover.mjs'

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..')
const manifestPath = path.join(root, 'deploy/local-nuxt-hybrid/route-allowlist.json')

function usage() {
  console.log(`Usage:
  node scripts/nuxt-cutover-preflight.mjs
  node scripts/nuxt-cutover-preflight.mjs --probe --php-origin http://127.0.0.1:8080 --nuxt-origin http://127.0.0.1:3100
  node scripts/nuxt-cutover-preflight.mjs --rollback-plan
  node scripts/nuxt-cutover-preflight.mjs --cutover-plan --approval-file FILE --commit FULL_SHA

Default mode is local, read-only preflight. It never applies config, restarts a service,
connects over SSH, changes DNS, or contacts a non-loopback origin.`)
}

function parseArgs(argv) {
  const result = { probe: false, rollbackPlan: false, cutoverPlan: false }
  for (let i = 0; i < argv.length; i += 1) {
    const arg = argv[i]
    if (arg === '--probe') result.probe = true
    else if (arg === '--rollback-plan') result.rollbackPlan = true
    else if (arg === '--cutover-plan') result.cutoverPlan = true
    else if (['--php-origin', '--nuxt-origin', '--approval-file', '--commit'].includes(arg)) {
      const value = argv[++i]
      if (!value) throw new Error(`Missing value for ${arg}`)
      result[arg.slice(2).replace(/-([a-z])/g, (_, letter) => letter.toUpperCase())] = value
    } else if (arg === '--help' || arg === '-h') result.help = true
    else throw new Error(`Unknown argument: ${arg}`)
  }
  const selected = Number(result.probe) + Number(result.rollbackPlan) + Number(result.cutoverPlan)
  if (selected > 1) throw new Error('Choose only one action mode')
  return result
}

export async function main(argv = process.argv.slice(2)) {
  const args = parseArgs(argv)
  if (args.help) return usage()
  const manifest = validateAllowlist(readJson(manifestPath))

  if (args.rollbackPlan) {
    console.log(rollbackPlan())
    return
  }

  if (args.cutoverPlan) {
    if (!args.approvalFile || !args.commit) throw new Error('Cutover plan requires --approval-file and --commit')
    validateApproval(readJson(path.resolve(args.approvalFile)), manifest, args.commit)
    if (manifest.pilot.readiness !== 'ready') throw new Error(`Pilot ${manifest.pilot.id} is blocked: ${manifest.pilot.reason}`)
    console.log('APPROVED PLAN ONLY: implementation hook may be prepared; this command still applies nothing.')
    return
  }

  const checks = auditRepository({ root, manifest })
  for (const check of checks) console.log(`${check.pass ? 'PASS' : 'FAIL'}: ${check.name}`)
  if (checks.some(check => !check.pass)) throw new Error('Static preflight failed')

  if (args.probe) {
    if (!args.phpOrigin || !args.nuxtOrigin) throw new Error('--probe requires --php-origin and --nuxt-origin')
    validateLoopbackOrigin(args.phpOrigin)
    validateLoopbackOrigin(args.nuxtOrigin)
    const phpPaths = [...new Set(['/', manifest.pilot.publicPath])]
    const nuxtPaths = manifest.pilot.readiness === 'blocked'
      ? [...new Set(['/', manifest.pilot.nuxtPath, manifest.pilot.previewHealthPath])]
      : [...new Set([manifest.pilot.nuxtPath, manifest.pilot.previewHealthPath])]
    const php = await probeOrigin(args.phpOrigin, phpPaths)
    const nuxt = await probeOrigin(args.nuxtOrigin, nuxtPaths)
    for (const [owner, results] of [['PHP', php], ['Nuxt', nuxt]]) {
      for (const result of results) console.log(`${owner} ${result.path} status=${result.status} noindex=${result.noindex}`)
    }
    validateProbeResults({ manifest, php, nuxt })
  }

  console.log(`BLOCKED: feature flag remains OFF; pilot ${manifest.pilot.id} is not production-ready.`)
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  main().catch(error => {
    console.error(`FAIL: ${error.message}`)
    process.exitCode = 1
  })
}

export { parseArgs }
