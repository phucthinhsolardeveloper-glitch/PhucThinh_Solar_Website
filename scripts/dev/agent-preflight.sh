#!/usr/bin/env bash
# Read-only guard before allowing a coding agent to write in a worktree.
set -euo pipefail
root="$(git rev-parse --show-toplevel 2>/dev/null || true)"
if [[ -z "$root" ]]; then
  echo "BLOCK: Not inside a Git worktree." >&2
  exit 2
fi
cd "$root"
branch="$(git symbolic-ref --quiet --short HEAD || true)"
head="$(git rev-parse --short HEAD)"
echo "PROJECT: $(basename "$root")"
echo "PATH: $root"
echo "BRANCH: ${branch:-DETACHED}"
echo "BASE_COMMIT: $head"
echo "WORKTREE:"
git worktree list
echo "GIT STATUS:"
git status --short --branch
echo "UNCOMMITTED FILE COUNT: $(git status --porcelain | wc -l | tr -d ' ')"
if [[ -z "$branch" ]]; then
  echo "BLOCK: Detached HEAD: choose an explicit feature branch before code changes." >&2
  exit 2
fi
case "$branch" in
  main|master)
    if [[ "${ALLOW_INTEGRATION_WORKTREE:-0}" != 1 ]]; then
      echo "BLOCK: Integration branch '$branch'. Use an isolated task worktree for agent code changes." >&2
      echo "For intentionally read-only inspection, ignore this block; do not set bypass in normal agent dispatch." >&2
      exit 2
    fi
    echo "WARNING: Integration worktree override requested; use for deliberate, reviewed actions only."
    ;;
esac
echo "PREFLIGHT OK: feature branch checkout. Still verify file ownership, runtime and approved task scope."
