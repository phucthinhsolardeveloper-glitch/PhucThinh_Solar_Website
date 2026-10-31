#!/usr/bin/env node
/**
 * Static, read-only progress audit for the PHP -> Nuxt migration.
 *
 * The audit accepts a checkout/export root so it can inspect an integration
 * branch without modifying another agent's worktree. It only proves
 * preview-page coverage; public route cutover and parity are intentionally not
 * inferred from a preview page existing.
 */
const fs = require('node:fs')
const path = require('node:path')

const REPO_ROOT = path.resolve(__dirname, '../../..')
const DEFAULT_MANIFEST = 'tools/visual-tests/fixtures/migration-routes.json'
const PAGE_PREFIX = 'frontend/app/pages/'

function usage() {
  return `Usage: node scripts/audit-nuxt-progress.cjs [options]

Options:
  --root <directory>    Checkout/export root to inspect (default: repo root)
  --manifest <path>     Manifest path relative to root
                         (default: ${DEFAULT_MANIFEST})
  --format <format>     markdown (default) or json
  --help                Show this help

The command is read-only. Export a Git ref to a temporary directory when the
target branch is not checked out, then pass that directory with --root.
`
}

function parseArgs(argv) {
  const args = { root: REPO_ROOT, manifest: DEFAULT_MANIFEST, format: 'markdown' }
  for (let index = 0; index < argv.length; index += 1) {
    const value = argv[index]
    if (value === '--help' || value === '-h') return { help: true }
    if (['--root', '--manifest', '--format'].includes(value)) {
      const next = argv[++index]
      if (!next) throw new Error(`${value} requires a value`)
      args[value.slice(2)] = next
      continue
    }
    throw new Error(`Unknown option: ${value}`)
  }
  if (!['markdown', 'json'].includes(args.format)) throw new Error('format must be markdown or json')
  return args
}

function filesystemFiles(root) {
  const files = new Set()
  const pagesRoot = path.resolve(root, PAGE_PREFIX)
  function visit(directory) {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      const absolute = path.join(directory, entry.name)
      if (entry.isDirectory()) visit(absolute)
      else files.add(path.relative(root, absolute).split(path.sep).join('/'))
    }
  }
  visit(pagesRoot)
  return files
}

function previewPathToPageFile(previewPath) {
  if (typeof previewPath !== 'string' || previewPath.includes('#')) return null
  const [rawPathname] = previewPath.split('?')
  if (!/^\/migration-[a-z0-9-]+$/.test(rawPathname)) return null
  const pathname = new URL(previewPath, 'http://migration.invalid').pathname
  if (pathname !== rawPathname) return null
  return `${PAGE_PREFIX}${pathname.slice(1)}.vue`
}

function validateManifest(manifest) {
  if (!manifest || !Array.isArray(manifest.routes) || manifest.routes.length === 0) {
    throw new Error('Manifest must contain a non-empty routes array')
  }
  const ids = new Set()
  for (const [index, route] of manifest.routes.entries()) {
    if (!route || typeof route.id !== 'string' || typeof route.previewPath !== 'string') {
      throw new Error(`Manifest route ${index + 1} needs id and previewPath`)
    }
    if (ids.has(route.id)) throw new Error(`Duplicate route id: ${route.id}`)
    ids.add(route.id)
    const pageFile = previewPathToPageFile(route.previewPath)
    if (!pageFile) throw new Error(`Route ${route.id} has unsupported preview path: ${route.previewPath}`)
  }
  return manifest
}

function auditManifest(manifest, files) {
  validateManifest(manifest)
  const previewPages = [...files]
    .filter(file => file.startsWith(PAGE_PREFIX) && /^frontend\/app\/pages\/migration-[^/]+\.vue$/.test(file))
    .sort()
  const routes = manifest.routes.map(route => {
    const pageFile = previewPathToPageFile(route.previewPath)
    return {
      id: route.id,
      legacyPath: route.legacyPath || null,
      previewPath: route.previewPath,
      pageFile,
      status: files.has(pageFile) ? 'preview-page-present' : 'preview-page-missing',
    }
  })
  const coveredFiles = new Set(routes.map(route => route.pageFile))
  const unmanifestedPreviewPages = previewPages.filter(file => !coveredFiles.has(file))
  const present = routes.filter(route => route.status === 'preview-page-present').length
  return {
    generatedAt: new Date().toISOString(),
    scope: {
      previewOnly: true,
      publicRouteCutoverProven: false,
      publicRouteOwner: 'PHP',
      source: 'filesystem-root',
    },
    summary: {
      manifestRoutes: routes.length,
      previewPagesFound: previewPages.length,
      manifestRoutesWithPages: present,
      manifestRoutesMissingPages: routes.length - present,
      unmanifestedPreviewPages: unmanifestedPreviewPages.length,
    },
    routes,
    unmanifestedPreviewPages,
  }
}

function loadFromRoot(root, manifestPath) {
  const absoluteManifest = path.resolve(root, manifestPath)
  const rawManifest = fs.readFileSync(absoluteManifest, 'utf8')
  let manifest
  try {
    manifest = JSON.parse(rawManifest)
  } catch (error) {
    throw new Error(`Invalid JSON in ${manifestPath}: ${error.message}`)
  }
  return auditManifest(manifest, filesystemFiles(root))
}

function renderMarkdown(report, root, manifestPath) {
  const { summary } = report
  const lines = [
    '# Nuxt migration progress audit',
    '',
    `- Root: \`${root}\``,
    `- Manifest: \`${manifestPath}\``,
    `- Scope: preview-page coverage only; public route cutover proven: **no**`,
    `- Public route owner: **${report.scope.publicRouteOwner}**`,
    '',
    '## Summary',
    '',
    `- Manifest routes: **${summary.manifestRoutes}**`,
    `- Preview pages found: **${summary.previewPagesFound}**`,
    `- Routes with matching pages: **${summary.manifestRoutesWithPages}**`,
    `- Routes missing pages: **${summary.manifestRoutesMissingPages}**`,
    `- Unmanifested preview pages: **${summary.unmanifestedPreviewPages}**`,
    '',
    '## Route coverage',
    '',
    '| Route | Preview path | Page file | Status |',
    '| --- | --- | --- | --- |',
  ]
  for (const route of report.routes) lines.push(`| ${route.id} | \`${route.previewPath}\` | \`${route.pageFile}\` | ${route.status} |`)
  lines.push('', '## Preview pages outside manifest', '')
  if (report.unmanifestedPreviewPages.length === 0) lines.push('- None')
  else for (const file of report.unmanifestedPreviewPages) lines.push(`- \`${file}\``)
  lines.push('', '> This report does not approve SEO, visual, API, commerce, security, or production cutover parity.')
  return `${lines.join('\n')}\n`
}

function main(argv = process.argv.slice(2)) {
  const args = parseArgs(argv)
  if (args.help) {
    process.stdout.write(usage())
    return 0
  }
  const report = loadFromRoot(args.root, args.manifest)
  if (args.format === 'json') process.stdout.write(`${JSON.stringify(report, null, 2)}\n`)
  else process.stdout.write(renderMarkdown(report, args.root, args.manifest))
  return report.summary.manifestRoutesMissingPages === 0 ? 0 : 1
}

if (require.main === module) {
  try {
    process.exitCode = main()
  } catch (error) {
    process.stderr.write(`audit-nuxt-progress: ${error.message}\n`)
    process.exitCode = 2
  }
}

module.exports = {
  auditManifest,
  parseArgs,
  previewPathToPageFile,
  renderMarkdown,
  validateManifest,
}
