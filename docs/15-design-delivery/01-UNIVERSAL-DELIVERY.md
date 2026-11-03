# UNIVERSAL DELIVERY PROTOCOL — task to shipped result

## 0. One rule
Parallelism is permitted. Unfinished work without an owner, deliverable and closure path is not.
Track outcomes instead of generated tasks, branches, lines of code or number of running agents.

## 1. Task classification and minimal process
- QUICK FIX (small isolated change): direct CLI or issue, one branch if needed, targeted test, review and integrate.
- FEATURE (new behavior): issue + acceptance + implementation + functional checks + PR + integration.
- VISUAL CHANGE (UI, motion, brand): issue + three sufficiently different directions ONLY when direction is not already approved; choose one; render before code acceptance; screenshot QA; PR.
- RESEARCH (discovery): evidence and decision artifact; NO code branch/worktree by default. Do not convert every idea into a coding task.
- HIGH RISK (DB, API mutations, deployment, auth, production): safe plan, backup/rollback, specific approval and risk checks. Never use the fast UI path.

## 2. Canonical state machine
IDEA -> TRIAGED -> READY -> IMPLEMENTING -> REVIEW -> CHANGES_REQUESTED (loops to IMPLEMENTING) -> APPROVED -> INTEGRATED -> DONE.
BLOCKED/ABANDONED are explicit with reason, next owner and safe preservation. RESEARCH can close as DECIDED without merging code.
Do not create new issue for each round of correcting the same feature.

## 3. Before work: the Outcome Contract (7 fields)
1. USER / BUSINESS JOB: what improves for whom?
2. DONE LOOKS LIKE: observable behavior and screenshot or API example.
3. CHANGE SCOPE: routes, files and exclusions.
4. SOURCE OF TRUTH: real CMS/API, existing UI, research, approved references.
5. APPROVAL POINT: where design direction/technical contract is selected.
6. EVIDENCE: preview, screenshots, assertions, logs, diff.
7. EXIT: destination branch, reviewer, merge gate, cleanup and rollback.
Tasks without an outcome contract are NOT dispatchable to multiple writers.

## 4. GitHub collaboration
- GitHub Issues = canonical record of goals, decisions, dependencies and acceptance. GitHub Projects = overview.
- Each code branch references one issue or a bounded batch. PR must link issue, screenshots if visual, verification and risk notes.
- One maintainer/integration owner handles shared files, cherry-picks and merge order. Agents can propose, not auto-merge to production.
- GitHub Actions checks code/build/tests; visual approval is separate and explicit.
- For many small internal tasks, a local task file is allowed, but sync the outcome with a linked issue/milestone before calling the whole feature complete.
- The local Task Queue and Orca are OPTIONAL execution adapters, not a second source of truth.

## 5. Worktrees and concurrency
- Create a worktree ONLY for genuinely parallel writes or isolated prototypes; research and single-agent sequential fixes can use existing clean checkout.
- Require issue id, owner, base SHA, target branch, write scope, expected artifact, checkpoint and cleanup state.
- Limit overlapping writes to zero; share component APIs via one integration owner.
- Recommended starting limit: two heavy coding workers + one reviewer. Increase only after measuring machine RAM/swap, conflicts and completion.
- If base becomes stale, re-evaluate merge conflict before retry; do not blindly rebase/overwrite untracked content.
- A finished task's worktree may be removed ONLY after all changes are committed, cherry-picked/merged or deliberately archived, no linked running processes, and a clean status/explicit safety check.

## 6. For visible design: a mandatory visual loop
SOURCE -> CHOSEN VISUAL DIRECTION -> CODE -> RUN -> BROWSER CAPTURE -> CRITIQUE -> FIX -> RECAPTURE -> APPROVE.
- Inspect baseline screenshot before writing UI.
- Check viewport 320, 390, 768, 1440 and another desktop width as relevant.
- Test images after scroll: lazy media can be invisible in naive full-page capture.
- Test actual links, form states, keyboard, focus, loading, empty, error and reduced motion.
- One representative route proves the component; then apply to related routes.
- No aesthetics conclusion from typecheck, HTTP 200, unit tests or score alone.

## 7. Role assignment, tool-neutral
PRODUCT OWNER: chooses scope, makes final design/release decisions.
DESIGN LEAD (Claude or Codex): proposes visually grounded direction, shows mock/preview and critiques actual screenshots.
IMPLEMENTER (Claude or Codex): writes scoped code and tests, provides running preview.
INDEPENDENT REVIEWER: evaluates against outcome and evidence, sends specific changes requests.
INTEGRATOR: controls shared style/API, merge, preview and project consistency.
One person/AI can cover multiple roles serially, but must do an independent review pass after implementation.

## 8. Checkpoints, not endless agents
At start: baseline, branch, scope.
After first usable result: preview and screenshot.
Before PR: QA evidence and self-review.
Before merge: independent review and compatibility.
After merge: integration preview works, smoke check, task closure.
If an agent produces only Markdown planning after one full implementation checkpoint, stop adding research tasks; request a preview or explicitly mark research as its only deliverable.

## 9. Explicit project safety
Preserve original CMS, SEO URLs, data correctness, public assets and production until approved. No auto-deploy production, DB mutation, global CSS rewrite, workspace cleanup or branch deletion. Protect other agents' untracked work.

## 10. Completion report (under 12 lines)
Issue and desired outcome; selected design/technical approach; exact delivered routes; preview URL; screenshot evidence; tests; diff/PR/SHA; integration target and status; remaining defects; rollback; branch/worktree state; next user decision.
