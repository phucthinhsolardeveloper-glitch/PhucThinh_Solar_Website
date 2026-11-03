# Claude Energy V3 Critique — EnergySystemExplorerV2.vue

Scope: read-only review of `.claude-inputs/EnergySystemExplorerV2.vue` (copy of wave1's
`frontend/app/components/experience/EnergySystemExplorerV2.vue`) and its route wrapper
`experience-energy-v2.vue`. Not modified. Severity: **P0** blocks the "visibly change the
atmosphere" brief goal, **P1** meaningfully hurts UX/credibility, **P2** polish.

## P0 — The sky does not actually change

`#stage-sky` (line 125) is one `linearGradient` with three navy stops (`#17345e → #10284d →
#0a1b38`) and it is painted once as a flat `<rect>` (line 140). None of `.is-night` /
`.is-backup` touch that rect or that gradient — the CSS block at lines 378–386 only recolors the
sun disc/halo opacity, the window fill, PV cell opacity and the two indicator lights. So "day"
and "night" are the *same navy dusk sky* with a brighter or dimmer sun circle on top. This is
exactly the gap the brief calls out: day never gets brighter/warmer, night never gets darker, and
nothing in the scene other than four small shapes is mode-aware. A user flipping Day → Night →
Backup sees a sun icon dim and a couple of windows change color against an unchanged backdrop —
not three different moods. The acceptance gate in `04-QUALITY-AND-RELEASE.md` ("screenshot
difference between modes must be visually apparent, not just a tiny text change") would fail
today.

## P0 — Ground plane, roof, and cabinets are mode-blind too

`stage-floor` (line 126), `roof-front`/`roof-side` (127–128), `panel` (129), `cabinet` (130–131)
are each single static gradients reused by all three modes. A real day→night transition changes
ambient fill light on every surface, not just emissive elements (sun + windows). Here the house
siding, inverter cabinet, and roof trusses are lit identically at 2pm and at midnight. Visually
this reads as "day" always being is a lit night scene with a sun sticker, not a different time of
day.

## P1 — Backup mode has no distinct geometry, only a CSS class name

`is-backup` (lines 381, 383, 385, 386) reuses the night values almost verbatim (`sun-disc`
opacity .28 vs night's .8 is the only numeric difference; window fill `#f1d99b` vs night's
`#eac778` is a few percent apart) plus one `drop-shadow` glow on the whole house. The brief
explicitly wants backup to **isolate the grid and highlight only the priority load** — V2 never
dims/disconnects the grid-pole artwork or narrows which window glows; the whole house silhouette
still glows under backup exactly like night. A viewer cannot tell backup from night by looking at
the stage; they can only tell by reading the mode button label.

## P1 — Energy flow motion carries meaning no other channel repeats

The only indicator of "what source is active" the moving dash on `.active-wires path`
(`stroke-dasharray: 1 13`, `animation: packet-flow`, lines 387–392). Turn off the flow toggle or
load with `prefers-reduced-motion`, and the active vs. muted wiring collapses to a thin gold
stroke that is the same gold for every mode — there is no second encoding (color temperature,
dash density, icon state) that survives motion being off. `04-QUALITY-AND-RELEASE.md` requires
reduced-motion to "still convey graph direction by static arrows/captions"; V2's static fallback
is just a slightly duller version of the same wire, not an arrow or directional cue.

## P1 — Scene and inspector are desktop-proportioned, mobile loses the diagram

At `max-width: 767px` (line 450 on) the SVG stays mounted but `.scene-labels` are hidden (line
472) and a parallel `.mobile-route` text chain (396, 474) takes over as the actual information
carrier. That means two different UIs exist for the same state — the SVG stops being legible
(no labels) while still occupying vertical space, and the real explanation moves to a flat
icon-chain with a single animated dot. This is workable but doubles the code paths to keep in
sync (`flowNodes`, `activePaths`, and now `mobile-route`'s dot animation all must agree), and the
desktop-only `scene-callouts` dots (252–254) are inert on mobile with no equivalent.

## P2 — Inspector describes state, but never demonstrates "why", only re-states the mode name

`scenario.title/body/route` (lines 24–48) are solid, honest, appropriately hedged copy. But the
device detail pane (`currentDevice.description`/`limit`, lines 11–16) is static per device and
does not change with mode — e.g. selecting "Pin lưu trữ" during Day mode still shows the same
battery copy as during Night, even though the battery's actual role (idle vs. charging vs.
discharging vs. backup-feeding) is exactly what changed. The inspector explains the *device* but
not the *device-in-this-mode*, which is the one piece of context a visitor actually needs when
comparing states.

## P2 — Code organization: one 507-line SFC mixing scene geometry, state logic, and all three
breakpoints' CSS inline

Everything — domain copy, SVG path data, animation keyframes, and three responsive breakpoints —
lives in one `<script setup>` + one `<style scoped>` block. That's a reasonable size for a single
prototype but not a seed for `EnergySceneStage`/`EnergyModeSwitch`/`EnergyInspector` reuse named
in `01-SYSTEM-DIRECTION.md`; extracting now (rather than after a second page copies this file)
avoids a second parity-drift problem like the one `main.css`'s own comments warn about for
header colors.

## What V2 gets right (keep)

- Mode/device disabled-state logic (`backup` disabled without battery, `battery` device disabled
  without battery) is already correct and should carry forward unchanged.
- The honesty language ("Minh họa nguyên lý — không phải dữ liệu vận hành thực tế", footer
  disclaimer) is calibrated correctly and must be preserved verbatim-in-spirit in V3.
  `prefers-reduced-motion` is already handled globally (line 504) and per-element durations are
  respected.
- Visual perspective/isometric composition of PV roof, inverter cabinet, battery and grid pole is
  genuinely accomplished pure-SVG work; V3 should evolve its lighting model, not redraw the
  geometry from scratch.
