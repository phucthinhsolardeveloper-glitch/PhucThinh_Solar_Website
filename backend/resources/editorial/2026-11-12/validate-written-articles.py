#!/usr/bin/env python3
"""Validate the six editorial drafts and their metadata for this batch."""

import csv
import json
import re
from pathlib import Path
from urllib.parse import urlparse


ROOT = Path(__file__).parent
ARTICLE_DIR = ROOT / "articles"
TARGET_IDS = [f"PTS-KT-{number:03}" for number in range(10, 16)]
EXPECTED_METADATA_IDS = [f"PTS-KT-{number:03}" for number in range(4, 16)]


def check(condition: bool, message: str) -> None:
    if not condition:
        raise SystemExit(f"FAIL: {message}")


metadata = json.loads((ROOT / "written-articles.json").read_text(encoding="utf-8"))
metadata_ids = [article["id"] for article in metadata["articles"]]
check(metadata_ids[:len(EXPECTED_METADATA_IDS)] == EXPECTED_METADATA_IDS
      and len(metadata_ids) == len(set(metadata_ids)),
      "metadata must retain the 004–015 prefix, with no duplicate IDs")

with (ROOT / "calendar-180.csv").open(encoding="utf-8-sig", newline="") as csv_file:
    calendar = {row["id"]: row for row in csv.DictReader(csv_file)}

for article_id in TARGET_IDS:
    path = ARTICLE_DIR / f"{article_id}.md"
    check(path.is_file(), f"missing article file {path.name}")
    body = path.read_text(encoding="utf-8")
    word_count = len(body.split())
    check(900 <= word_count <= 1600, f"{path.name}: {word_count} words (expected 900–1600)")
    headings = re.findall(r"(?m)^#{1,6} .*", body)
    check(headings and headings[0].startswith("## "), f"{path.name}: first heading must be H2, without a body H1")
    check(not re.search(r"(?m)^#(?!#)\s", body), f"{path.name}: body must not contain H1")
    check("### Nguồn tham khảo" in body, f"{path.name}: missing sources section")
    source_section = body.split("### Nguồn tham khảo", 1)[1]
    urls = re.findall(r"\]\((https?://[^)]+)\)", source_section)
    check(2 <= len(urls) <= 3, f"{path.name}: expected 2–3 source URLs, got {len(urls)}")
    check(
        all(urlparse(url).scheme == "https" and urlparse(url).netloc for url in urls),
        f"{path.name}: source URL must be an absolute HTTPS URL",
    )
    check("Gợi ý infographic" not in body, f"{path.name}: private illustration brief is public")
    check("/upload/news/pts-explainer-" in body, f"{path.name}: missing approved inline infographic")
    check(article_id in calendar, f"{article_id}: missing from calendar-180.csv")
    metadata_entry = next((entry for entry in metadata["articles"] if entry["id"] == article_id), None)
    check(metadata_entry is not None, f"{article_id}: missing metadata")
    check(bool(metadata_entry["summary"].strip()), f"{article_id}: empty summary")
    check(bool(metadata_entry["keywords"].strip()), f"{article_id}: empty keywords")
    print(f"PASS {article_id}: {word_count} words, {len(urls)} sources, metadata present")

print("PASS batch: six article files and metadata are valid")
