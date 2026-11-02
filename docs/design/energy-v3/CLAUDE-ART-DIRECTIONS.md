# Claude Art Directions — Luminous Engineering V3

Locked identity for all three directions: navy `#0A2251`, deep navy `#061537`, gold `#C9962E`,
light gold `#E0B654`, white `#FFFFFF`, cool-white `#F8FAFC`, Be Vietnam Pro. Header's `#C69A45`
exception is never touched. No purple/neon, no fabricated telemetry, no stock-SaaS card grids.

## A — Precision Paper (editorial light, understated dark inset)

**Question it answers:** "Is PTS a serious engineering brand, not a storefront?" — the page
spends most of its weight on white.

**Palette:** `#F8FAFC` canvas, navy `#0A2251` ink, a single deep-navy inset panel
(`#061537`) for the scene itself; gold only on the active control and the active flow line.

**Type:** large navy display headline (Be Vietnam Pro 700, sentence case, tight tracking), small
dense technical captions (13–14px, weight 600) for the section-axis ("01 — HỆ THỐNG") and grid
annotations — the axis numbering is honest here because the page really is a sequence of three
state readings (Day/Night/Backup), not decoration.

**Layout concept:** a 32px precision grid (rgba(10,34,81,.04), edge-faded via mask) sits behind
an asymmetric 60/40 split: 60% narrative column (headline, one-paragraph context, section axis),
40% a vertically-centered small preview of the dark scene inset, cropped so only the house + one
energy path shows, inviting scroll into the full stage below. The grid fades to nothing before
the stage's own dark panel starts — never grid-under-dark-panel, never grid-under-dense-text.

```
Desktop 1440                                  Mobile 390
┌──────────────────────────────────┐          ┌───────────────┐
│ [grid, faded edges]               │          │ [grid, faded] │
│  01 — HỆ THỐNG                    │          │ 01 — HỆ THỐNG │
│  Điện đi đâu khi      ┌─────────┐ │          │ Điện đi đâu   │
│  trạng thái đổi?      │ dark    │ │          │ khi trạng     │
│  (one-para context)   │ inset   │ │          │ thái đổi?     │
│                        │ crop    │ │          │ ┌───────────┐│
│                        └─────────┘ │          │ │dark inset ││
│  ── grid fades out ──              │          │ │   crop    ││
│  [[ full EnergySceneStage ]]       │          │ └───────────┘│
│  [[ EnergyModeSwitch ]]            │          │ ── fades ──  │
│  [[ EnergyInspector  ]]            │          │ [[ stage ]]  │
└──────────────────────────────────┘          │ [[ switch ]] │
                                                 │ [[ inspec ]]│
                                                 └───────────────┘
```

**Reject:** full-bleed dark everywhere (loses the "quiet sophisticated white public page" goal),
decorative 01/02/03 badges on non-sequential content, grid visible under the inspector's body
copy.

## B — Spatial Atelier (cinematic technical scene, atmosphere-first)

**Question it answers:** "What does the system feel like at night vs noon vs an outage?" — the
scene itself is the hero, full width, and the page is built around proving atmosphere, not just
topology.

**Palette:** the scene owns three actual lighting states as full environments, not accent swaps:
Day sky `#4F7FBD → #BFD9F2` (warm daylight blue, the only place a non-brand blue tint is allowed,
confined to the sky rect as atmosphere, not UI chrome) fading to navy `#0A2251` horizon; Night sky
`#061537 → #0A1B38` near-black with a small cool moonlight rim; Backup sky `#061537` flat with a
tight radial vignette darkening everything except the priority-load window and its gold rim-light.
Gold is reserved for emissive elements (sun disc, active wire, warm windows, priority-load glow).

**Type:** the headline sits as an overlay caption on the scene itself (navy card with soft blur
backing for contrast on bright Day sky, no card needed on Night/Backup) — type treats the scene as
the ground it's printed on, not a separate block above it.

**Layout concept:** full-bleed stage top-to-bottom on desktop, mode switch overlaid bottom-left,
inspector collapses to a slide-in drawer on the right triggered by node selection (not a
permanently-reserved 40% column) so the scene itself gets the full dramatic width. On mobile the
drawer becomes a bottom sheet; the stage keeps full width and height shrinks via `aspect-ratio`
rather than side-by-side columns.

```
Desktop 1440                                   Mobile 390
┌──────────────────────────────────┐           ┌───────────────┐
│ [[ caption overlay, top-left ]]   │           │[[ overlay ]]  │
│                                    │           │               │
│      [[ full-bleed scene ]]       │           │[[ scene,     ]│
│                                    │           │[ aspect-ratio]│
│ [mode switch] ──────[node tap──▶  │           │[  4:5      ]] │
│                       opens drawer]│           │[switch row]   │
└──────────────────────────────────┘           │[bottom sheet  │
                                                 │ on node tap]  │
                                                 └───────────────┘
```

**Reject:** permanently-visible three-column desktop inspector (crowds the atmosphere, the thing
this direction is supposed to sell); literal photographic sky (no new image assets allowed, and
would read as a different, non-technical brand); saturating the whole UI chrome gold (gold must
stay confined to emissive/active elements or it stops meaning "energized").

## C — Living System (interaction-first diagram + scenario navigator)

**Question it answers:** "Which part of my situation does this affect?" — closer to a product
decision tool than a hero visual; diagram is compact, controls and consequences are the content.

**Palette:** cool-white canvas, navy diagram linework, gold strictly for the active path and
selected control — matches direction A's restraint but drops the cinematic sky entirely in favor
of a smaller schematic-style stage (closer to an engineering drawing than a rendered scene) so
more room goes to a live "what changes" readout.

**Type:** small dense UI labels (14px, weight 600, normal case — no all-caps) throughout; the
"headline" is replaced by a one-line current-state statement that re-renders per mode
("Ban ngày: PV cấp điện trực tiếp cho tải") so the words themselves are the primary feedback
channel, atmosphere is secondary.

**Layout concept:** three-up control row across the top (Day/Night/Backup, always visible, no
scroll needed to reach them), a horizontally-oriented compact diagram directly below spanning the
full width, and a two-column "what changed" readout beneath it (left: route + inspector copy,
right: a device grid) rather than a side-by-side split — this stacks better on narrow screens
without an atmosphere-vs-reading tradeoff since there's no cinematic atmosphere to lose.

```
Desktop 1440                                   Mobile 390
┌──────────────────────────────────┐           ┌───────────────┐
│ [Day] [Night] [Backup]            │           │[Day][Night]   │
│                                    │           │[Backup]       │
│ [[ compact horizontal diagram ]]  │           │[[ compact     │
│                                    │           │   diagram ]]  │
│ current-state line                │           │ state line    │
│ ┌─────────────┐ ┌───────────────┐ │           │[route+copy]   │
│ │ route+copy  │ │ device grid   │ │           │[device grid]  │
│ └─────────────┘ └───────────────┘ │           │ (stacked)     │
└──────────────────────────────────┘           └───────────────┘
```

**Reject:** a literal recreation of V2's isometric house scene at small scale (loses the thing
isometric scale is good at — depth — once shrunk; better to go flatter and more diagrammatic
instead of a shrunk cinematic scene that reads cluttered); eyebrow-style ALL-CAPS section labels
on every block (only the mode buttons need to stand out, not every container).

## Recommended synthesis (what V3 actually builds)

**B's lighting model + A's canvas discipline + C's always-visible controls.** Specifically:

- Keep a **real three-state lighting environment** (B's sky/ground/material recoloring per mode,
  not V2's sun-icon-only swap) — this is the literal brief ask and B is the only direction that
  treats it as architecture rather than an accent.
- Keep the **scene inside a bounded dark panel on a white/precision-grid page**, not full-bleed
  (A's canvas discipline) — `02-PAGE-ROLLOUT.md` explicitly scopes the dark scene to "selected
  inserts," not a fully dark site, and the lab route should demonstrate that constraint, not
  abandon it the moment it's visually tempting.
- Keep mode controls **always visible above the fold**, not hidden behind a node-tap drawer
  (C's always-on control row) — `04-QUALITY-AND-RELEASE.md`'s interaction gate requires mode
  select to update scene+path+inspector together and be reachable without hunting, which rules
  out B's collapse-to-drawer inspector as the *only* path to information on first load.
- The inspector stays a **permanently visible panel beside the stage on desktop** (closer to A),
  **below the stage on mobile** (closer to C's stacking, not B's bottom sheet) — a bottom sheet
  that must be invoked adds a tap most users on a 390px screen won't make, and the quality gate
  requires the explanation to be reachable without extra interaction.
- Reject B's literal daylight-blue sky tint for the Day sky's *base* register and instead keep the
  sky inside the brand's navy family even at its brightest, lightening via `--color-navy-400/300`
  blue-navy tints and a warm gold sun-glow wash — true sky-blue would be the first non-brand hue on
  the page and the brief's locked palette doesn't carve out an exception for it. Day must still
  read as unmistakably brighter than Night through luminance and warmth, just without leaving the
  navy family.

This synthesis is what `experience-energy-v3.vue` implements: a Precision-Paper page shell
(white canvas, 32px edge-faded grid, 60/40 editorial intro) wrapping a Spatial-Atelier-grade
`EnergySceneStage` (full day/night/backup material relighting) with a Living-System-style
always-visible `EnergyModeSwitch` and persistent `EnergyInspector`.
