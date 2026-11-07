#!/usr/bin/env bash
# Brings master into the Laravel 13 migration branch, so the migration always builds on what production runs.
# Run after every push to master. Stops on a merge conflict and leaves it for a person to resolve.
# Usage: scripts/sync-laravel-branch.sh [worktree path]
set -Eeuo pipefail

worktree="${1:-/home/admin01/src/pts-laravel13}"
branch="migration/laravel13-integration"

cd "$worktree"
[ "$(git rev-parse --abbrev-ref HEAD)" = "$branch" ] || { echo "FAIL: $worktree không ở nhánh $branch." >&2; exit 1; }
[ -z "$(git status --porcelain)" ] || { echo "FAIL: worktree Laravel còn thay đổi chưa commit; commit hoặc cất trước khi đồng bộ." >&2; exit 1; }

git fetch --quiet origin
behind="$(git rev-list --count HEAD..origin/master)"
if [ "$behind" = 0 ]; then
    echo "OK: $branch đã chứa origin/master ($(git rev-parse --short origin/master))."
    exit 0
fi
echo "Đồng bộ $behind commit từ origin/master vào $branch..."
if ! git merge --no-edit origin/master; then
    echo "FAIL: xung đột khi gộp master. Giải quyết trong $worktree rồi commit; không ép push." >&2
    exit 1
fi
git push --quiet origin "$branch"
echo "OK: $branch = $(git rev-parse --short HEAD), đã chứa origin/master $(git rev-parse --short origin/master)."
