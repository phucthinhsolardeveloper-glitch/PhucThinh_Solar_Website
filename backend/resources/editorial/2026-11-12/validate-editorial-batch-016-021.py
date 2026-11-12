#!/usr/bin/env python3
"""Verify the 18–19 November editorial drafts; drafts are not publish-ready without cover rights."""
import csv
import json
import re
from pathlib import Path
from urllib.parse import urlparse

root = Path(__file__).parent
calendar = {x["id"]: x for x in csv.DictReader((root / "calendar-180.csv").open(encoding="utf-8-sig"))}
articles = json.loads((root / "written-articles.json").read_text(encoding="utf-8"))["articles"]
ids = [x["id"] for x in articles]
assert ids == [f"PTS-KT-{i:03d}" for i in range(4, 22)], "Unexpected or duplicate metadata IDs"
manifest = json.loads((root / "editorial-photo-manifest-016-021.json").read_text(encoding="utf-8"))
entries = {x["plan_key"]: x for x in manifest["entries"]}
assert len(entries) == 6 and manifest["status"] == "needs_photo"
for n in range(16, 22):
    key = f"PTS-KT-{n:03d}"
    item = calendar[key]
    assert item["date"] in ("2026-11-18", "2026-11-19")
    assert item["time"] in ("08:00", "12:00", "19:30")
    body = (root / "articles" / (key + ".md")).read_text(encoding="utf-8")
    words = len(body.split())
    assert 900 <= words <= 1600, f"{key}: {words} words"
    assert re.match(r"^## ", body), f"{key}: article must start H2"
    assert not re.search(r"(?m)^# [^#]", body), f"{key}: duplicate H1"
    assert "### Nguồn tham khảo" in body, f"{key}: sources section missing"
    sources = body.split("### Nguồn tham khảo", 1)[1]
    urls = re.findall(r"\]\((https?://[^)]+)\)", sources)
    assert 2 <= len(urls) <= 4, f"{key}: only {len(urls)} sources"
    assert all(urlparse(u).scheme == "https" and urlparse(u).netloc for u in urls), f"{key}: invalid source"
    assert "Gợi ý infographic" not in body, f"{key}: internal illustration brief leaked into public copy"
    row = next(a for a in articles if a["id"] == key)
    assert len(row["summary"].strip()) > 40 and len(row["keywords"].strip()) > 15
    assert entries[key]["status"] == "needs_photo", f"{key}: source image not yet reviewed"
    print(f"PASS {key}: {words} words, {len(urls)} source links, editor-only artwork brief in manifest")
print("PASS editorial batch 016–021 (6 drafts; image review still required)")
