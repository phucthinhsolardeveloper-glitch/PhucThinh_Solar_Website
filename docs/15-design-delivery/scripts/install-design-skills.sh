#!/usr/bin/env bash
# One-time curated Design Skill Pack installer for Claude Code and Codex.
# Installs global agent skills only. Does not modify project dependencies or source.
set -Eeuo pipefail
export DISABLE_TELEMETRY=1
export CI=1
export FORCE_COLOR=0
ROOT="$HOME/.local/share/pts-design-toolkit"
mkdir -p "$ROOT"
LOG="$ROOT/install.log"
echo "PTS Design Toolkit installer | $(date -Iseconds)" | tee -a "$LOG"
echo "node $(node -v); npx skills $(npx --no-install skills --version)" | tee -a "$LOG"
if ! command -v git >/dev/null || ! command -v npx >/dev/null; then echo "Missing git/npx" >&2; exit 2; fi
install_group() {
  local label="$1"; shift
  echo "== Installing $label ==" | tee -a "$LOG"
  if ! npx --no-install skills add "$@" -g -a claude-code -a codex -y >>"$LOG" 2>&1; then
    echo "FAILED: $label. See $LOG" >&2
    return 1
  fi
  echo "OK: $label" | tee -a "$LOG"
}
install_group "nextlevelbuilder curated pack" nextlevelbuilder/ui-ux-pro-max-skill \
  --skill ui-ux-pro-max --skill design-system --skill brand --skill banner-design
install_group "vercel web-design-guidelines" vercel-labs/agent-skills \
  --skill web-design-guidelines
# The official Anthropic frontend-design already exists for Claude at ~/.claude/skills.
# Install only for Codex; never overwrite the existing Claude skill.
echo "== Installing frontend-design for Codex only ==" | tee -a "$LOG"
if ! npx --no-install skills add anthropics/claude-plugins-official \
  --skill frontend-design -g -a codex -y >>"$LOG" 2>&1; then
  echo "FAILED: official frontend-design Codex; see $LOG" >&2
  exit 1
fi
echo "OK: official frontend-design Codex" | tee -a "$LOG"
for item in ui-ux-pro-max design-system brand banner-design web-design-guidelines; do
  test -s "$HOME/.agents/skills/$item/SKILL.md" || test -s "$HOME/.claude/skills/$item/SKILL.md" || {
    echo "Missing installed skill $item" >&2; exit 1;
  }
done
test -s "$HOME/.agents/skills/frontend-design/SKILL.md"
test -s "$HOME/.claude/skills/frontend-design/SKILL.md"
echo "VERIFY PASS: all six named design skills are available; pre-existing Claude frontend-design retained" | tee -a "$LOG"
npx --no-install skills ls -g >>"$LOG" 2>&1 || true
printf 'LOG=%s\n' "$LOG"


# Register project-specific design selector (repo source remains authoritative).
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
SELECTOR="$PROJECT_ROOT/docs/15-design-delivery/skills/pts-layout-selector"
test -s "$SELECTOR/SKILL.md" || { echo "Missing project selector: $SELECTOR" >&2; exit 1; }
mkdir -p "$HOME/.agents/skills" "$HOME/.claude/skills"
if [ ! -e "$HOME/.agents/skills/pts-layout-selector" ]; then
  ln -s "$SELECTOR" "$HOME/.agents/skills/pts-layout-selector"
fi
if [ ! -e "$HOME/.claude/skills/pts-layout-selector" ]; then
  ln -s "$HOME/.agents/skills/pts-layout-selector" "$HOME/.claude/skills/pts-layout-selector"
fi
test -s "$HOME/.agents/skills/pts-layout-selector/SKILL.md"
test -s "$HOME/.claude/skills/pts-layout-selector/SKILL.md"
echo "READY: PTS selector for Claude and Codex; restart existing CLI sessions"
