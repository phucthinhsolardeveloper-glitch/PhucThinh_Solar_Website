#!/usr/bin/env python3
"""PTS product-family research crawler (READ-ONLY).
Official manufacturer product *index* pages only; no product import or image downloading.
Never interpret unverified series names as individual sellable SKUs.
Outputs staging evidence under docs/10-audits/catalog-expansion-20261117/.
"""
import csv
import html
import json
import re
import unicodedata
from collections import Counter
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlparse
import requests

ROOT = Path(__file__).resolve().parents[2]
BASE = ROOT / "docs/10-audits/catalog-expansion-20261117"
SEEDS = BASE / "manufacturer-seeds.json"
HEADERS = {"User-Agent": "PhucThinhSolar-CatalogueResearch/1.0 (+https://phucthinhsolar.com/; manual review)", "Accept": "text/html,application/xhtml+xml"}
ALLOWED_PREFIX = ("https://",)
TIMEOUT = (6, 14)

def norm(value):
    value = unicodedata.normalize("NFKD", str(value)).lower()
    return "".join(c for c in value if c.isascii() and c.isalnum())

class PageText(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.t = []
        self.skip = 0
    def handle_starttag(self, tag, attrs):
        if tag in ("script", "style", "noscript"): self.skip += 1
    def handle_endtag(self, tag):
        if tag in ("script", "style", "noscript") and self.skip: self.skip -= 1
    def handle_data(self, data):
        if not self.skip: self.t.append(data)
    def text(self): return " ".join(self.t)

def fetch(source):
    url = source["source_url"]
    domain = urlparse(url).hostname or ""
    if not url.startswith(ALLOWED_PREFIX) or domain == "" or urlparse(url).username:
        return (source, None, "INVALID_URL", None)
    try:
        response = requests.get(url, headers=HEADERS, timeout=TIMEOUT, allow_redirects=True)
        # Never follow redirects into private/non-HTTPS hosts.
        if not response.url.startswith("https://"):
            return (source, None, "NON_HTTPS_REDIRECT", response.status_code)
        if response.status_code != 200:
            return (source, None, "HTTP_" + str(response.status_code), response.status_code)
        if "html" not in response.headers.get("Content-Type", "").lower():
            return (source, None, "NON_HTML", response.status_code)
        if len(response.content) > 5_000_000:
            return (source, None, "TOO_LARGE", response.status_code)
        parser = PageText()
        parser.feed(response.text)
        return (source, parser.text(), "HTTP_200", response.status_code)
    except (requests.RequestException, ValueError) as exc:
        return (source, None, type(exc).__name__, None)

def main():
    BASE.mkdir(parents=True, exist_ok=True)
    manifest = json.loads(SEEDS.read_text(encoding="utf-8"))
    stock = json.loads((BASE / "public-products-snapshot.json").read_text(encoding="utf-8"))["products"]
    names = [(p["id"], p["name"], norm(p["name"])) for p in stock]
    source_results = []
    records = []
    # Different official hosts may be fetched concurrently; only ONE index request per domain here.
    with ThreadPoolExecutor(max_workers=4) as executor:
        future_list = [executor.submit(fetch, s) for s in manifest["sources"]]
        for future in as_completed(future_list):
            source, text, state, http_status = future.result()
            source_results.append({"brand": source["brand"], "source_url": source["source_url"], "fetch_state": state, "http_status": http_status, "indexed_families": len(source["models"]), "text_length": len(text or "")})
            doc_norm = norm(html.unescape(text or ""))
            for model in source["models"]:
                family = model["family"]
                # Exact family text on manufacturer page, after punctuation/whitespace normalization.
                found = bool(doc_norm and norm(family) in doc_norm)
                # Short series aliases (GT, HT, etc.) are unsafe for name-based dedup.
                matches = [(pid, name) for pid, name, nn in names if len(norm(family)) >= 6 and norm(family) in nn]
                records.append({
                    "manufacturer": source["brand"], "family_or_series": family,
                    "proposed_category": model["category"], "source_url": source["source_url"],
                    "source_evidence": "INDEX_TEXT_MATCH" if found else "NEEDS_SOURCE_RECHECK",
                    "fetch_state": state, "site_exact_family_name_match": "|".join(str(p[0]) for p in matches),
                    "site_name_match_count": len(matches), "data_quality": "FAMILY_ONLY",
                    "image_qa": "NOT_STARTED", "datasheet_qa": "NOT_STARTED",
                    "regional_availability": "UNVERIFIED", "sales_status": "RESEARCH_ONLY",
                    "cms_publish": "NO", "merchant_feed": "EXCLUDE"
                })
    records.sort(key=lambda r: (r["manufacturer"].lower(), r["family_or_series"].lower()))
    source_results.sort(key=lambda x: x["brand"].lower())
    with (BASE / "official-family-candidates.csv").open("w", newline="", encoding="utf-8-sig") as file:
        writer = csv.DictWriter(file, fieldnames=list(records[0]))
        writer.writeheader(); writer.writerows(records)
    (BASE / "official-page-fetch-evidence.json").write_text(json.dumps({
        "crawled_at_utc": datetime.now(timezone.utc).isoformat(),
        "scope": "manufacturer index snapshots, NOT model-level datasheets",
        "sources": source_results
    }, indent=2, ensure_ascii=False), encoding="utf-8")
    stats = Counter(r["source_evidence"] for r in records)
    print(json.dumps({
        "family_candidates": len(records), "source_pages": len(source_results),
        "fetched_ok": sum(s["fetch_state"] == "HTTP_200" for s in source_results),
        "source_matches": dict(stats), "site_exact_family_name_matches": sum(bool(r["site_name_match_count"]) for r in records),
        "per_brand": dict(Counter(r["manufacturer"] for r in records)),
        "sources_failed": [s for s in source_results if s["fetch_state"] != "HTTP_200"],
        "outputs": ["official-family-candidates.csv", "official-page-fetch-evidence.json"]
    }, indent=2, ensure_ascii=False))

if __name__ == "__main__": main()
