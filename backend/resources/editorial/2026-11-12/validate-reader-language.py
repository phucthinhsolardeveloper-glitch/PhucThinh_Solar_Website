#!/usr/bin/env python3
"""Spot accidental editorial-English jargon in reader-facing solar articles."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parent / "articles"
BAD_TERMS = {
    "Cues": r"\bCues\b",
    "ballast": r"\bballast\b",
    "HVAC": r"\bHVAC\b",
    "module": r"\bmodule\b",
    "wafer": r"\bwafer\b",
    "Checklist": r"\bChecklist\b",
    "Snapshot sơ bộ": r"\bSnapshot\s+sơ\s+bộ\b",
}
files = sorted(ROOT.glob("PTS-KT-*.md"))
if len(files) != 21:
    raise SystemExit(f"Expected 21 reader-facing articles, got {len(files)}")
problems = []
for path in files:
    for line_num, line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        if line.lstrip().startswith(("![", "- [", "* [", "https://")):
            continue
        for term, pattern in BAD_TERMS.items():
            if re.search(pattern, line, re.IGNORECASE):
                problems.append(f"{path.name}:{line_num}: {term}")
if problems:
    raise SystemExit("\n".join(problems))
print(f"PASS language check: {len(files)} articles; no unedited English shorthand")
