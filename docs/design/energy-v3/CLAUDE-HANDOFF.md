# Claude Handoff — Energy V3 Lab Prototype

## What was produced

| File | Role |
|---|---|
| `frontend/app/pages/experience-energy-v3.vue` | Lab route. Owns domain state (`mode`, `withBattery`, `selected`), scenario copy, active-path computation, per-mode device copy. Renders the Precision-Paper intro (grid + 60/40 split) and the stage section. |
| `frontend/app/components/experience-v3/GridSurface.vue` | Decorative 32px edge-faded technical grid, mask-based fade, no body-text interference. |
| `frontend/app/components/experience-v3/EnergyModeSwitch.vue` | Day/Night/Backup control group. Exports `EnergyMode` type. Disables Backup without battery. |
| `frontend/app/components/experience-v3/EnergySceneStage.vue` | The relit spatial stage: real per-mode sky/ground/material layers (not just sun+window swap — fixes the P0 finding in the critique), shape-encoded flow paths (solid/dashed/dotted, not color-only), always-on arrowheads for reduced-motion legibility, 44px keyboard/touch node buttons overlaid on the SVG. |
| `frontend/app/components/experience-v3/EnergyInspector.vue` | Scenario + per-mode device explanation panel, device picker grid, disclaimer line. Exports `DeviceId`/`Device` types. |
| `docs/design/energy-v3/CLAUDE-ENERGY-V3-CRITIQUE.md` | V2 critique with line-level evidence. |
| `docs/design/energy-v3/CLAUDE-ART-DIRECTIONS.md` | Three directions (Precision Paper / Spatial Atelier / Living System) + recommended synthesis, which is what the prototype implements. |

No files outside `docs/design/energy-v3/**`, the new page, and the new `experience-v3/` component
folder were touched. `main.css`, header/footer, homepage, Nuxt config, PHP/DB, deploy — untouched.
Nothing committed; left as working-tree changes for the integrator to review.

## How the V2 critique maps to this build

- **Sky never changed (P0):** `EnergySceneStage.vue` stacks three full `linearGradient` sky rects
  (`sky-day`/`sky-night`/`sky-backup`) and cross-fades opacity by mode class, plus a ground-tint
  rect and a backup-only vignette — the whole environment relights, not just a sun icon.
- **Materials mode-blind (P0):** roof, PV cells, inverter cabinet, battery, and house mass each
  have `.is-night`/`.is-backup` fill overrides in addition to the emissive elements.
- **Backup indistinct from night (P1):** backup adds a radial vignette, dims the grid pole to
  `opacity: .35`, shows a red disconnect glyph on the grid line, and only the `.window-priority`
  rect glows (not the full house) — night still lights the priority window plus the two standard
  windows, so backup is visibly narrower than night, not a relabeled copy.
- **Motion as the only state channel (P1):** flow paths get a `flow-solid`/`flow-dashed`/
  `flow-dotted` class by *type* (PV/home vs. grid vs. battery) independent of mode, plus a
  permanent `marker-end` arrowhead so direction reads with the animation off — satisfied without
  any JS reduced-motion branching, since the dash animation itself is already killed by the
  existing global `@media (prefers-reduced-motion: reduce)` rule in this component and in
  `main.css`.
- **Mobile parity (P1):** no separate "mobile route" UI; the same `EnergySceneStage` SVG and
  `EnergyInspector` panel stack vertically under 900px (`v3-stage-layout` → 1 column) instead of
  swapping to a second, differently-populated UI.
- **Component organization (P2):** scene, mode switch, and inspector are now three focused SFCs
  instead of one 500-line file, per `01-SYSTEM-DIRECTION.md`'s candidate component list — only the
  three actually used in this prototype were extracted (`GridSurface`, an energy mode switch, an
  energy scene stage, an inspector); the rest of that candidate list was intentionally not built
  ahead of need.

## Preview commands

This worktree's `frontend/` has no `node_modules` installed (confirmed — `node_modules/.bin` does
not exist here), and the machine currently has four other Nuxt dev servers already running across
other worktrees with only ~199 MiB RAM free (`free -h`, checked live). Installing dependencies and
booting a fifth dev server here risked crashing Docker/other agents' sessions per prior incident
(`dont-parallel-docker-build` operating rule), so **I did not start a dev server or run
`vue-tsc`/`nuxt build` in this session.** This is a real limitation, not a silent skip — see
Known Issues.

To preview once resources are free:

```bash
cd frontend
npm install        # only if node_modules is genuinely absent here
npm run dev -- --port <free-port>
# open http://127.0.0.1:<free-port>/experience-energy-v3
```

Recommended QA matrix once running: widths 320 / 390 / 768 / 1440, all three modes, battery
toggle off (confirms Backup disables and selection falls back to `inverter`), keyboard-only pass
(Tab through mode buttons → scene node dots → device grid), and `prefers-reduced-motion: reduce`
emulation in DevTools to confirm the flow dash animation stops while arrowheads remain.

## Known issues / unknowns (explicitly not verified)

- **NOT TESTED:** no dev server was run. Component code has not been rendered in a browser this
  session; only read back for structural/logical correctness. Do not treat this as a passing QA
  run — it is SOURCE-ONLY review.
- **NOT TESTED:** no `vue-tsc`/type-check pass, since it requires the missing `node_modules`.
  Type errors in prop/emit signatures are possible even though the files were written against the
  same patterns already used by `EnergySystemExplorerV2.vue`.
- **NOT TESTED:** no Playwright screenshots at 320/390/768/1440 per `04-QUALITY-AND-RELEASE.md`'s
  QA matrix — that is the first thing the next session (Codex/QA) should run once a dev server is
  available without contention.
- **UNVERIFIED a11y claim:** scene nodes are plain `<button>` elements absolutely positioned over
  the SVG (not SVG-native `tabindex`/`role` nodes), chosen specifically for more predictable
  keyboard/screen-reader/touch behavior than focusable SVG children — but this has not been
  checked with a real screen reader.
- **Design simplification vs. V2:** the V3 stage's SVG geometry is deliberately simpler than
  V2's (fewer decorative panel-cell lines, shadows, grain texture) to keep the lighting-layer work
  legible and reviewable in one pass; it is not a pixel-for-pixel evolution of V2's artwork. If
  the integrator wants V2's exact geometric detail with V3's lighting model layered on top, that
  is a follow-up pass, not part of this deliverable.
- **Scenario/device copy** is adapted from V2's existing Vietnamese strings (same hedged,
  non-telemetry tone) plus new per-mode device copy; it has not been reviewed by a native-Vietnamese
  SME pass beyond matching V2's existing register.

## Suggested follow-up for Codex / QA (not done here)

1. Run the Playwright QA matrix from `04-QUALITY-AND-RELEASE.md` once a dev server slot is free.
2. `vue-tsc --noEmit` pass after `npm install`.
3. Confirm `aria-pressed` and focus-visible states against a real screen reader (NVDA/VoiceOver),
   not just visual inspection.
4. If this prototype scores ≥80 on the `04-QUALITY-AND-RELEASE.md` rubric, extract
   `EnergySceneStage`'s relighting technique (stacked opacity-switched gradient/tint layers) as the
   documented pattern for `EnergySceneStage` in `01-SYSTEM-DIRECTION.md`'s reusable-component list
   — the other candidates (`SectionAxis`, `EnergyFlowPath`, etc.) were intentionally not
   pre-extracted from this single prototype.
5. Decide whether V2's richer SVG geometry (grain texture, soft shadows, PV cell line detail)
   should be merged into this lighting model, or whether V3's simplified geometry is adopted going
   forward — this was left as an open call for the integrator, not decided unilaterally here.
