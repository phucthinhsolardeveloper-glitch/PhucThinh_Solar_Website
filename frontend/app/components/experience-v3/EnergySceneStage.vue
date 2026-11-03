<script setup lang="ts">
import type { EnergyMode } from './EnergyModeSwitch.vue'
import type { DeviceId } from './EnergyInspector.vue'

/**
 * Lab-only spatial stage. Fixes the V2 critique's P0 finding: sky, floor, roof
 * and cabinet surfaces all actually relight per mode via stacked tinted layers,
 * not just the sun disc and windows. Flow paths carry a shape encoding (solid
 * / dashed / dotted) in addition to color + motion, so the active source is
 * legible with reduced motion or without color vision.
 */
const props = defineProps<{
  mode: EnergyMode
  withBattery: boolean
  selected: DeviceId
  activePathIds: string[]
}>()
const emit = defineEmits<{ select: [DeviceId] }>()

const nodeButtons: { id: DeviceId; label: string; x: number; y: number }[] = [
  { id: 'pv', label: 'Mái pin mặt trời', x: 24.5, y: 40 },
  { id: 'inverter', label: 'Inverter', x: 49.5, y: 52 },
  { id: 'battery', label: 'Pin lưu trữ', x: 49.5, y: 76 },
  { id: 'grid', label: 'Điện lưới', x: 12, y: 63 },
  { id: 'load', label: 'Ngôi nhà & phụ tải', x: 79, y: 44 },
]

function isActive(id: string) {
  return props.activePathIds.includes(id)
}
</script>

<template>
  <div class="scene-stage" :class="[`is-${mode}`, { 'is-no-battery': !withBattery }]">
    <svg viewBox="0 0 1000 560" role="img" aria-labelledby="scene-title scene-desc" preserveAspectRatio="xMidYMid meet">
      <title id="scene-title">Phối cảnh hệ thống điện mặt trời — {{ mode === 'day' ? 'ban ngày' : mode === 'night' ? 'ban đêm' : 'mất điện, tải ưu tiên' }}</title>
      <desc id="scene-desc">Sơ đồ nguyên lý hệ thống điện mặt trời: mái pin, inverter, pin lưu trữ, điện lưới và ngôi nhà.</desc>
      <defs>
        <linearGradient id="sky-day" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#eaf2fb" /><stop offset=".6" stop-color="#f3ecd9" /><stop offset="1" stop-color="#f7e3b8" />
        </linearGradient>
        <linearGradient id="sky-night" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#0c2144" /><stop offset=".6" stop-color="#0a1b38" /><stop offset="1" stop-color="#050f22" />
        </linearGradient>
        <linearGradient id="sky-backup" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0" stop-color="#101a2b" /><stop offset=".65" stop-color="#0a1320" /><stop offset="1" stop-color="#030810" />
        </linearGradient>
        <radialGradient id="sun-glow"><stop stop-color="#f9d27e" stop-opacity=".55" /><stop offset="1" stop-color="#f9d27e" stop-opacity="0" /></radialGradient>
        <radialGradient id="moon-glow"><stop stop-color="#b9c9f2" stop-opacity=".35" /><stop offset="1" stop-color="#b9c9f2" stop-opacity="0" /></radialGradient>
        <radialGradient id="backup-vignette" cx="80%" cy="46%" r="55%"><stop offset="0" stop-color="#1c2f52" stop-opacity=".55" /><stop offset="1" stop-color="#030810" stop-opacity="0" /></radialGradient>
        <linearGradient id="window-warm" x1="0" y1="0" x2="0" y2="1"><stop stop-color="#ffe6a5" /><stop offset="1" stop-color="#d39b42" /></linearGradient>
        <filter id="wire-glow" x="-80%" y="-80%" width="260%" height="260%"><feGaussianBlur stdDeviation="4.5" result="b" /><feMerge><feMergeNode in="b" /><feMergeNode in="SourceGraphic" /></feMerge></filter>
        <marker id="arrow-gold" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse">
          <path d="M0 0 L10 5 L0 10 Z" fill="#ffe0a0" />
        </marker>
      </defs>

      <!-- Sky: the actual day/night/backup environment, not just a sun icon swap -->
      <rect class="sky sky-day" width="1000" height="560" fill="url(#sky-day)" />
      <rect class="sky sky-night" width="1000" height="560" fill="url(#sky-night)" />
      <rect class="sky sky-backup" width="1000" height="560" fill="url(#sky-backup)" />
      <rect class="vignette-backup" width="1000" height="560" fill="url(#backup-vignette)" />

      <circle class="sun-halo" cx="860" cy="100" r="120" fill="url(#sun-glow)" />
      <circle class="sun-disc" cx="860" cy="100" r="26" fill="#e9bd68" />
      <circle class="moon-halo" cx="860" cy="100" r="90" fill="url(#moon-glow)" />
      <circle class="moon-disc" cx="860" cy="100" r="19" fill="#c7d4ef" />

      <!-- Ground plane -->
      <path class="ground-plane" d="M0 345 L1000 230 V560 H0Z" />
      <path class="ground-tint" d="M0 345 L1000 230 V560 H0Z" />

      <!-- Grid pole + overhead line -->
      <g class="grid-object">
        <path d="M143 288 151 286 159 400 150 404Z" fill="#9aa9a8" />
        <path d="M151 286 159 400 164 398 157 287Z" fill="#5e747b" />
        <path d="M113 296 183 293 185 300 113 304Z" fill="#b3b9ab" />
        <path class="grid-line" d="M119 282 C216 262 334 289 437 293" fill="none" stroke="#adbdc5" stroke-opacity=".55" stroke-width="1.5" />
        <g class="grid-disconnect">
          <circle cx="278" cy="276" r="7" fill="#050f22" stroke="#e05b3c" stroke-width="2" />
          <path d="M274 272 282 280M282 272 274 280" stroke="#e05b3c" stroke-width="1.6" stroke-linecap="round" />
        </g>
      </g>

      <!-- PV roof -->
      <g class="pv-object">
        <path d="M168 222 293 165 412 213 285 270Z" class="pv-roof" />
        <g class="pv-cells" stroke-opacity=".6" stroke-width="1.1">
          <path d="M180 220 215 204 325 248 290 264Z" class="pv-panel" />
          <path d="M219 202 254 186 364 230 329 246Z" class="pv-panel" />
          <path d="M258 184 293 168 403 212 368 228Z" class="pv-panel" />
        </g>
      </g>

      <!-- Inverter cabinet -->
      <g class="inverter-object">
        <path d="M465 255 519 234 554 248 500 271Z" class="cab-top" />
        <path d="M465 255 500 271 500 359 465 341Z" class="cab-face" />
        <path d="M500 271 554 248 554 335 500 359Z" class="cab-side" />
        <path d="M478 301 488 305" stroke="#c9962e" stroke-width="3" stroke-linecap="round" class="inverter-indicator" />
      </g>

      <!-- Battery -->
      <g class="battery-object" :class="{ 'is-dimmed': !withBattery }">
        <path d="M454 394 508 374 569 397 514 420Z" class="batt-top" />
        <path d="M454 394 514 420 514 468 454 442Z" class="batt-face" />
        <path d="M514 420 569 397 569 443 514 468Z" class="batt-side" />
        <path d="M479 418 492 423 492 437 479 432Z" fill="#c9962e" class="battery-light" />
      </g>

      <!-- House -->
      <g class="home-object">
        <path d="M739 256 821 212 902 245 902 334 821 365 739 334Z" class="home-mass" />
        <path d="M821 212 902 245 902 334 821 365Z" class="home-mass-side" />
        <path d="M720 256 821 199 921 241 821 286Z" class="roof-main" />
        <path d="M757 256 821 220 883 247 821 276Z" class="window-standard" />
        <path d="M840 293 873 280 873 312 840 325Z" fill="url(#window-warm)" class="window-priority" />
        <path d="M786 290 815 301 815 335 786 324Z" class="window-standard-2" />
      </g>

      <!-- Active flow paths: shape-encoded by type so color/motion aren't the only channel -->
      <g class="wiring" fill="none" stroke="#90a6bd" stroke-opacity=".32" stroke-width="3" stroke-linecap="round">
        <path d="M260 234 C350 262 420 280 486 298" />
        <path d="M520 292 C620 270 720 258 788 253" />
        <path d="M500 330 C504 365 504 395 500 420" />
        <path d="M120 362 C220 398 332 378 480 308" />
      </g>
      <g class="active-wires" fill="none" stroke-linecap="round" filter="url(#wire-glow)">
        <path v-if="isActive('solar')" class="flow-path flow-solid" d="M260 234 C350 262 420 280 486 298" marker-end="url(#arrow-gold)" />
        <path v-if="isActive('solar')" class="flow-current" d="M260 234 C350 262 420 280 486 298" />
        <path v-if="isActive('home')" class="flow-path flow-solid" d="M520 292 C620 270 720 258 788 253" marker-end="url(#arrow-gold)" />
        <path v-if="isActive('home')" class="flow-current" d="M520 292 C620 270 720 258 788 253" />
        <path v-if="isActive('charge')" class="flow-path flow-dotted" d="M500 330 C504 365 504 395 500 420" marker-end="url(#arrow-gold)" />
        <path v-if="isActive('discharge')" class="flow-path flow-dotted" d="M500 420 C504 395 504 365 500 330" marker-end="url(#arrow-gold)" />
        <path v-if="isActive('grid')" class="flow-path flow-dashed" d="M120 362 C220 398 332 378 480 308" marker-end="url(#arrow-gold)" />
      </g>
    </svg>

    <button
      v-for="node in nodeButtons"
      :key="node.id"
      type="button"
      class="scene-node-button"
      :class="{ 'is-selected': selected === node.id }"
      :disabled="node.id === 'battery' && !withBattery"
      :style="{ left: `${node.x}%`, top: `${node.y}%` }"
      :aria-pressed="selected === node.id"
      :aria-label="node.label"
      @click="emit('select', node.id)"
    >
      <span class="scene-node-dot" aria-hidden="true" />
    </button>

    <p class="scene-mood-caption">
      <span class="scene-mood-dot" aria-hidden="true" />
      {{ mode === 'day' ? 'Trời sáng, mái pin nhận nắng' : mode === 'night' ? 'Trời tối, nhà bật đèn' : 'Mất lưới, chỉ tải ưu tiên có điện' }}
    </p>
  </div>
</template>

<style scoped>
.scene-stage {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  background: transparent;
}
.scene-stage svg {
  display: block;
  width: 100%;
  height: auto;
  -webkit-mask-image: linear-gradient(90deg, transparent 0%, #000 3%, #000 97%, transparent 100%);
  mask-image: linear-gradient(90deg, transparent 0%, #000 3%, #000 97%, transparent 100%);
}

/* --- Sky: three real environments cross-fading, fixing V2's static-sky gap --- */
.sky { opacity: 0; transition: opacity 0.5s ease; }
.is-day .sky-day { opacity: 1; }
.is-night .sky-night { opacity: .04; }
.is-backup .sky-backup { opacity: .04; }
.vignette-backup { opacity: 0; transition: opacity 0.5s ease; }
.is-backup .vignette-backup { opacity: 1; }

.ground-plane { fill: #102a4b; transition: fill 0.5s ease; }
.is-night .ground-plane { fill: #07182f; }
.is-backup .ground-plane { fill: #060d18; }
.ground-tint { transition: fill 0.5s ease, opacity 0.5s ease; }
.is-day .ground-tint { fill: #2f5490; opacity: .22; }
.is-night .ground-tint { fill: #050f22; opacity: .5; }
.is-backup .ground-tint { fill: #020608; opacity: .72; }

.sun-halo, .sun-disc, .moon-halo, .moon-disc { transition: opacity 0.4s ease; }
.sun-halo, .sun-disc { opacity: 0; }
.moon-halo, .moon-disc { opacity: 0; }
.is-day .sun-halo { opacity: 1; }
.is-day .sun-disc { opacity: 1; }
.is-night .moon-halo { opacity: .8; }
.is-night .moon-disc { opacity: .85; }

.grid-line { transition: stroke-opacity 0.4s ease; }
.grid-object { transition: opacity 0.4s ease; }
.grid-disconnect { opacity: 0; transition: opacity 0.4s ease; }
.is-backup .grid-object { opacity: .35; }
.is-backup .grid-disconnect { opacity: 1; }
.is-no-battery .grid-disconnect { opacity: 0; }

.pv-roof, .pv-panel, .cab-top, .cab-face, .cab-side, .home-mass, .home-mass-side, .roof-main,
.batt-top, .batt-face, .batt-side, .window-standard, .window-standard-2 {
  transition: fill 0.4s ease, opacity 0.4s ease;
}
.pv-roof { fill: #526d8e; }
.pv-panel { fill: #3971a0; stroke: #7ca6c5; }
.cab-top { fill: #e6e8df; }
.cab-face { fill: #cbd5d2; }
.cab-side { fill: #879aa0; }
.home-mass { fill: #d9d7ca; }
.home-mass-side { fill: #909da0; }
.roof-main { fill: #b98d48; }
.batt-top { fill: #627b91; }
.batt-face { fill: #263e62; }
.batt-side { fill: #10243d; }
.window-standard, .window-standard-2 { fill: #334e6d; }

.is-night .pv-panel, .is-backup .pv-panel { opacity: .45; }
.is-night .pv-roof, .is-backup .pv-roof { fill: #2a3a52; }
.is-night .cab-top, .is-backup .cab-top { fill: #8a9196; }
.is-night .cab-face, .is-backup .cab-face { fill: #5c6668; }
.is-night .cab-side, .is-backup .cab-side { fill: #3c4547; }
.is-night .home-mass, .is-backup .home-mass { fill: #6c7584; }
.is-night .home-mass-side, .is-backup .home-mass-side { fill: #454d57; }
.is-night .roof-main, .is-backup .roof-main { fill: #5a4428; }

.window-priority { transition: fill-opacity 0.4s ease, filter 0.4s ease; fill-opacity: 0; }
.is-night .window-priority { fill-opacity: 1; filter: drop-shadow(0 0 7px rgba(233, 185, 96, 0.55)); }
.is-backup .window-priority { fill-opacity: 1; filter: drop-shadow(0 0 11px rgba(233, 185, 96, 0.75)); }
.is-night .window-standard, .is-night .window-standard-2 { fill: #c99a4d; }
.is-backup .window-standard, .is-backup .window-standard-2 { fill: #1c2a3c; }

.inverter-indicator { transition: stroke 0.4s ease; }
.is-night .inverter-indicator, .is-backup .inverter-indicator { stroke: #e5c16d; }

.battery-light { transition: opacity 0.4s ease; }
.is-dimmed .battery-light { opacity: .25; }
.is-dimmed .batt-face, .is-dimmed .batt-top, .is-dimmed .batt-side { opacity: .4; }

.flow-path { stroke: #ffe0a0; stroke-width: 4; opacity: .95; }
.flow-solid { stroke-dasharray: none; }
.flow-dashed { stroke-dasharray: 10 7; animation: flow-dash 0.9s linear infinite; }
.flow-dotted { stroke-dasharray: 2 7; animation: flow-dash 0.7s linear infinite; }

/* Current pulses: a second layer riding the same path, visible even on a solid "pipe" line */
.flow-current { stroke: #fff3d6; stroke-width: 2.4; stroke-dasharray: 3 15; opacity: .9; animation: flow-dash 0.8s linear infinite; mix-blend-mode: screen; }

@keyframes flow-dash { to { stroke-dashoffset: -36; } }

.scene-node-button {
  position: absolute;
  width: 44px;
  height: 44px;
  margin: -22px 0 0 -22px;
  border: 0;
  border-radius: 50%;
  background: transparent;
  cursor: pointer;
  display: grid;
  place-items: center;
}
.scene-node-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(244, 217, 157, 0.75);
  box-shadow: 0 0 0 0 rgba(244, 217, 157, 0.35);
  transition: box-shadow 0.2s ease, background 0.2s ease;
}
.scene-node-button.is-selected .scene-node-dot {
  background: #ffe0a0;
  box-shadow: 0 0 0 7px rgba(255, 224, 160, 0.22);
}
.scene-node-button:disabled { cursor: not-allowed; opacity: .3; }
.scene-node-button:focus-visible { outline: 3px solid #e4bd67; outline-offset: 2px; border-radius: 50%; }

.scene-mood-caption {
  position: absolute;
  left: 14px;
  bottom: 12px;
  display: flex;
  align-items: center;
  gap: 7px;
  margin: 0;
  padding: 5px 10px;
  border-radius: 3px;
  background: rgba(5, 15, 34, 0.55);
  color: #e3e9ed;
  font-size: 11px;
  font-weight: 600;
}
.scene-mood-dot { width: 6px; height: 6px; border-radius: 50%; background: #ffe0a0; }

@media (prefers-reduced-motion: reduce) {
  .flow-path { animation: none !important; }
  .sky, .vignette-backup, .ground-plane, .ground-tint, .sun-halo, .sun-disc, .moon-halo, .moon-disc,
  .grid-object, .grid-disconnect, .pv-roof, .pv-panel, .window-priority {
    transition-duration: 0.01ms !important;
  }
}
</style>
