# 01 — System Direction / Claude Design Brief

## Goal
Make PTS feel like a substantial engineering/energy brand: quiet sophisticated white public pages with subtle technical grid and bold editorial rhythm; dramatic deep-navy spatial scenes where useful; luminous gold energy flow that conveys real interaction states rather than decoration. Preserve PTS recognizable brand.

## Identity: locked to staging
- Navy primary #0A2251; deep navy #061537; gold primary #C9962E; gold light #E0B654; white #FFFFFF; cool-white #F8FAFC; Be Vietnam Pro.
- Header's approved gold #C69A45 is a component exception; do not change header.
- No purple, acid-neon, ungrounded KPI, fake case-study pictures, generic SaaS card stacks or automatic background animation under long text.

## Two canvases, same identity
**Precision Paper:** white/off-white content canvas. Optional 32px technical grid rendered with thin rgba(10,34,81,.035-.055) lines and edge fade, only on selected storytelling/technical sections. On dense tables/forms/articles the grid should fade out completely. An editorial section axis (01—HỆ THỐNG), thin ruler ticks and alignment guides may hint at energy engineering without looking like CAD software. Use generous whitespace, high-contrast navy typography, distinct asymmetric compositions, very restrained gold highlights. Test grid at 1440 and 390; no moiré, shimmer or accessibility interference.
**Spatial Energy Canvas:** deep-navy, coherent 2.5D PV/inverter/grid/battery/load scene, actual lighting mood, animated paths with verified direction; inspector describing devices. The scene is reserved for selected Home/Solutions/PDP inserts, NOT full dark site.

## Energy V2 observed problem
`EnergySystemExplorerV2.vue` presently sets `stage-sky` gradient to static navy values and `.is-night/.is-backup` primarily recolor the sun, windows, PV opacity and indicators. The ambient sky and architectural materials don't actually undergo a full day/night light transformation. Preserve its spatial composition and energy-path concept; upgrade background layers, luminosity, house windows and active electrical topology by mode.

## State-driven scene v3
| Aspect | DAY | NIGHT | BACKUP |
|---|---|---|---|
| Sky | distinctly brighter blue daylight, warm sunlight | dark navy night, subtle indirect moonlight | lowered ambient navy, isolated/backup emphasis |
| PV | receives visible sunlight / active if system config allows | subdued/inactive as no sun | conditional, no unconditional PV claims |
| House | daylight facade, windows mostly neutral | warm illuminated occupied rooms | only priority-load area illuminated if configured |
| Battery | optional charge path from PV | discharge path if equipped | discharge to supported backup load only |
| Grid | conditional supply | conditional support if chosen | graphically isolated; no grid feed claimed |
| Energy flow | PV→inverter→load, optional charge | battery/grid→inverter→load | backup-enabled battery→inverter→priority load |
| Inspector | active source and components | source change explained | explicit backup support requirements |
No watts, charging %, savings, equipment compatibility or actual telemetry without verified source. Mark all scenes "Minh họa nguyên lý — không phải dữ liệu vận hành thực tế." Battery disabled => backup mode disabled. Reduced-motion still conveys graph direction by static arrows and text.

## Layout language
1. Cinematic light/dark full-bleed scene, reserved for high-impact.
2. Editorial asymmetrical split: 60/40 visual/narrative, avoiding card repetition.
3. Precision-grid foundation, selective fade and section-axis.
4. Technical system breakdown: diagram + inspector + grounded related product categories.
5. Scrolling narrative/project story with real photo source, never hijack wheel.
6. Lightweight technical quote/caption with source provenance.
7. Dense specification and commerce layouts remain light and functional.

## Candidate reusable patterns
`GridSurface`, `SectionAxis`, `EnergySceneStage`, `EnergyModeSwitch`, `EnergyFlowPath`, `EnergyInspector`, `TechnicalMarker`, `TechnicalCallout`, `SystemContextPanel`, `StoryChapterRail`. Do not prematurely create 10 shared components: prototype first, refactor only approved ones.

## Claude must explore 3 genuinely different directions
A / Precision Paper: editorial light-page grid, understated dark-energy inset.
B / Spatial Atelier: large cinematic technical scene, atmospheric sunlight/moonlight.
C / Living System: interaction-first diagram and scenario navigator connected to products.
Each should have desktop/mobile composition, a rationale and screenshotable working study; do not make 3 variants that only change colors.

## Design sources
Internal: `/home/admin01/orca/workspaces/Web_PTS_PHP/pts-uiux-wave1-review/frontend/app/components/experience/EnergySystemExplorerV2.vue`, existing `pts-20261102-claude-design-study/docs/design/claude-design-study/CONCEPTS.md`; staging `https://staging.phucthinhsolar.com/`.
External: https://tympanus.net/codrops/ ; https://gitee.com/ice-gl/icegl-three-vue-tres ; https://motion.dev/docs/vue-scroll-animations ; https://www.w3.org/WAI/WCAG22/quickref/
