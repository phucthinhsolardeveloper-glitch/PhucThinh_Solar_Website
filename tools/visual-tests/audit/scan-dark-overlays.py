#!/usr/bin/env python3
"""List overlays and viewers in the Nuxt app that still use a dark backdrop instead of the light glass."""
import pathlib
import re
import sys

root = pathlib.Path(__file__).resolve().parents[3] / "frontend" / "app"
# Dark full-screen backdrops, Tailwind or CSS. Small badges and photo shades do not match both parts.
dark = re.compile(r"bg-(?:navy-9\d0|black|slate-950)/[3-9]\d|bg-\[rgb\((?:8_18_34|10_11_14)|rgba?\((?:6[ ,]+21[ ,]+55|0[ ,]+0[ ,]+0)[^)]*[/,]\s*\.?[3-9]\d?")
overlay = re.compile(r"::backdrop|fixed inset-0|overlay:|position: fixed; (?:z-index: \d+; )?inset: 0|top-full z-\[100\]|sheet-scrim")
found = 0
for path in sorted(root.rglob("*")):
    if path.suffix not in {".vue", ".css", ".ts"}:
        continue
    for number, line in enumerate(path.read_text(encoding="utf8").splitlines(), 1):
        if dark.search(line) and overlay.search(line):
            found += 1
            print(f"{path.relative_to(root)}:{number}: {line.strip()[:130]}")
print(f"{found} dark overlays")
sys.exit(0)
