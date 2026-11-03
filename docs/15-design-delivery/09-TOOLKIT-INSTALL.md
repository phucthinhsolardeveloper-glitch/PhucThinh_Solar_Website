# Design Toolkit — Installed skills, layout selection and motion policy
Status: company WSL verification, 2026-11-03. Designed for Claude Code and Codex independent of Orca.

## 1. Installed once globally on DESKTOP-8IC4AGO
Verified toolchain: Node 24.20.0, npm 11.19.0, skills CLI 1.7.6.

| Skill | Source | Verified location | Use |
|---|---|---|---|
| frontend-design | anthropics/claude-plugins-official | Claude: ~/.claude/skills/frontend-design; Codex: ~/.agents/skills/frontend-design | creative art direction, polished composition |
| ui-ux-pro-max | nextlevelbuilder/ui-ux-pro-max-skill | ~/.agents/skills/ui-ux-pro-max | searchable style/palette/font/layout + UX database |
| design-system | nextlevelbuilder/ui-ux-pro-max-skill | ~/.agents/skills/design-system | tokens, components, reusable rules |
| brand | nextlevelbuilder/ui-ux-pro-max-skill | ~/.agents/skills/brand | identity and brand consistency |
| banner-design | nextlevelbuilder/ui-ux-pro-max-skill | ~/.agents/skills/banner-design | visual composition for hero/banner |
| web-design-guidelines | vercel-labs/agent-skills | ~/.agents/skills/web-design-guidelines | independent design/accessibility critique |
| pts-layout-selector | local docs/15-design-delivery/skills/pts-layout-selector | ~/.agents/skills/pts-layout-selector | choose layout, effects and issue brief |

The shared ~/.agents/skills/ directories are exposed to Claude via ~/.claude/skills/ symlinks (frontend-design keeps Claude's existing copy). Installed version sources are mutable online; use the complete recorded install log at ~/.local/share/pts-design-toolkit/install.log for this run. Restart Claude/Codex sessions created before installation to discover new skills.

## 2. One-time setup on another machine
Run from the PTS repository in WSL:

    bash docs/15-design-delivery/scripts/install-design-skills.sh

Prerequisites: git, Node 20+, npm/npx, skills CLI 1.7.6 (available via npm) and network access. The script uses a curated allowlist of GitHub sources and only installs agent skills globally; it does not execute arbitrary deployment or install Nuxt runtime packages. Inspect SKILL.md before using newly downloaded third-party skills. Keep agents from executing install scripts embedded in skills without review.

## 3. Design selection workflow (no worktree required yet)
1. Open design-picker.html locally in the browser.
2. Choose target page; catalog recommends a primary layout from 12 rendered patterns.
3. Compare up to three options; visually inspect actual demonstrations: layout-lab.html and reference.html.
4. Select up to three purposeful effects; note that GSAP and real WebGL require separate scoped implementation approval.
5. Export design-brief.md using the Download button or copy its content.
6. Attach the brief, layout screenshot and user job to a GitHub Issue. Owner approves direction before starting coding.
7. Claude Code/Codex implement only the approved direction, with real CMS data, browser screenshots, critique, and PR review.
8. Visual reviewer opens latest preview and checks desktop/mobile before accepting.

## 4. Technology tiers for PTS Nuxt
| Level | Libraries | Installation policy |
|---|---|---|
| Native CSS/HTML | Nuxt UI 4, Tailwind CSS 4, Lucide | already in project |
| Standard Vue motion | motion-v | already in frontend/package.json; preferred |
| Complex timeline | GSAP + ScrollTrigger | NOT installed for all routes; add only to approved pilot/worktree |
| Real 3D | @tresjs/nuxt + three | NOT installed for all routes; add only to approved pilot with FPS/bundle/fallback QA |
| Design review | Playwright + screenshots | existing tools/visual-tests |

Official documentation: https://motion.dev/docs/vue ; https://gsap.com/docs/v3/Plugins/ScrollTrigger/ ; https://docs.tresjs.org/getting-started/installation

## 5. What we are not installing
- React/Next.js optimization or shadcn/ui skills as the default frontend framework for PTS Nuxt/Vue.
- Every animation/UI library simply because it appears in a reference gallery.
- A second global component system or a new agent management service.
- Full 3D runtime until one signature scene is genuinely required and reviewed.

## 6. Scenarios to test quality
- Project Cinema: photo-backed story with verified media, structured gallery and clear CTA.
- About: Editorial Split + Field Journal; authentic media and readable typography.
- Solution: Spatial Energy Stage with valid PV/inverter/storage states, 2.5D and fallback.
- Catalog: filters, states and URL/query before decorative animation.
- PDP: product gallery, verified specification and CTA before motion.
- One signature full-bleed hero behind an opt-in header variant only where explicitly approved.

**Caveat:** skills don't execute themselves or choose a good design without a brief, reference and actual screenshot review. The Design Picker is for decision-making, not a production page or a generic theme switch.
