#!/usr/bin/env python3
"""Throttled, read-only audit of a site's sitemap URLs. Requires Python 3.10+."""

from __future__ import annotations

import argparse
import json
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from datetime import datetime, timezone
from html.parser import HTMLParser
from pathlib import Path


class PageHead(HTMLParser):
    def __init__(self) -> None:
        super().__init__()
        self.in_title = False
        self.title = ""
        self.canonical = ""
        self.robots = ""

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        values = dict(attrs)
        if tag == "title":
            self.in_title = True
        if tag == "link" and values.get("rel", "").lower() == "canonical":
            self.canonical = values.get("href") or ""
        if tag == "meta" and values.get("name", "").lower() in {"robots", "googlebot"}:
            self.robots = values.get("content") or ""

    def handle_endtag(self, tag: str) -> None:
        if tag == "title":
            self.in_title = False

    def handle_data(self, data: str) -> None:
        if self.in_title:
            self.title += data


class SameHostRedirects(urllib.request.HTTPRedirectHandler):
    def __init__(self, host: str) -> None:
        super().__init__()
        self.host = host

    def redirect_request(self, req, fp, code, msg, headers, newurl):
        target = urllib.parse.urlparse(newurl)
        if target.scheme != "https" or target.hostname != self.host:
            raise urllib.error.URLError("redirect left allowed hostname")
        return super().redirect_request(req, fp, code, msg, headers, newurl)


def get(url: str, opener: urllib.request.OpenerDirector, timeout: float) -> tuple[int, str, str, str, dict[str, str]]:
    request = urllib.request.Request(url, headers={"User-Agent": "PTS-read-only-sitemap-audit/1.0"}, method="GET")
    try:
        with opener.open(request, timeout=timeout) as response:
            body = response.read(5_000_001)
            if len(body) > 5_000_000:
                return response.status, response.geturl(), "", "response over 5 MB", dict(response.headers.items())
            return response.status, response.geturl(), response.headers.get("Content-Type", ""), body.decode("utf-8", "replace"), dict(response.headers.items())
    except urllib.error.HTTPError as error:
        return error.code, error.geturl(), error.headers.get("Content-Type", ""), "", dict(error.headers.items())
    except Exception as error:  # network failures are part of the report
        return 0, url, "", f"{type(error).__name__}: {error}", {}


def locs(xml: str) -> tuple[str, list[str]]:
    root = ET.fromstring(xml)
    kind = root.tag.rsplit("}", 1)[-1]
    urls = [element.text.strip() for element in root.iter() if element.tag.rsplit("}", 1)[-1] == "loc" and element.text]
    return kind, urls


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("sitemap", nargs="?", default="https://phucthinhsolar.com/sitemap.xml")
    parser.add_argument("--delay", type=float, default=0.75, help="seconds between requests (default: 0.75)")
    parser.add_argument("--timeout", type=float, default=20)
    parser.add_argument("--output", type=Path, default=Path("/tmp/pts-sitemap-audit"))
    args = parser.parse_args()
    if args.delay < 0.25:
        parser.error("--delay must be at least 0.25 seconds")
    base = urllib.parse.urlparse(args.sitemap)
    if base.scheme != "https" or not base.hostname:
        parser.error("sitemap URL must use https")
    opener = urllib.request.build_opener(SameHostRedirects(base.hostname))
    checked_at = datetime.now(timezone.utc).isoformat()
    sitemap_queue = [args.sitemap]
    seen_sitemaps: set[str] = set()
    page_urls: list[str] = []
    sitemap_results = []
    delay = args.delay
    while sitemap_queue:
        url = sitemap_queue.pop(0)
        if url in seen_sitemaps:
            continue
        seen_sitemaps.add(url)
        status, final, content_type, body, _ = get(url, opener, args.timeout)
        sitemap_result = {"url": url, "status": status, "finalUrl": final, "contentType": content_type, "error": body if status == 0 else ""}
        if status == 200:
            try:
                kind, found = locs(body)
                sitemap_result["type"] = kind
                sitemap_result["locCount"] = len(found)
                if kind == "sitemapindex":
                    sitemap_queue.extend(found)
                elif kind == "urlset":
                    page_urls.extend(found)
                else:
                    sitemap_result["error"] = f"unexpected XML root: {kind}"
            except Exception as error:
                sitemap_result["error"] = f"XML parse: {error}"
        sitemap_results.append(sitemap_result)
        time.sleep(delay)

    pages = []
    for index, url in enumerate(dict.fromkeys(page_urls), start=1):
        parsed = urllib.parse.urlparse(url)
        if parsed.scheme != "https" or parsed.hostname != base.hostname:
            pages.append({"url": url, "status": 0, "error": "URL outside allowed HTTPS hostname"})
            continue
        status, final, content_type, body, headers = get(url, opener, args.timeout)
        row = {"url": url, "status": status, "finalUrl": final, "contentType": content_type}
        if status == 0:
            row["error"] = body
        elif "html" in content_type.lower():
            head = PageHead()
            head.feed(body)
            x_robots = headers.get("X-Robots-Tag", headers.get("x-robots-tag", ""))
            canonical = urllib.parse.urljoin(final, head.canonical) if head.canonical else ""
            row.update({"title": " ".join(head.title.split()), "canonical": head.canonical, "canonicalUrl": canonical,
                        "canonicalMatches": canonical == final, "robots": head.robots, "xRobotsTag": x_robots,
                        "noindex": "noindex" in (head.robots + " " + x_robots).lower()})
        pages.append(row)
        if index % 50 == 0:
            print(f"Checked {index}/{len(dict.fromkeys(page_urls))} URLs", flush=True)
        time.sleep(delay)

    args.output.mkdir(parents=True, exist_ok=True)
    failures = [row for row in pages if row.get("status") != 200 or row.get("finalUrl") != row.get("url")
                or row.get("canonicalMatches") is False or row.get("noindex") is True]
    failed_sitemaps = [row for row in sitemap_results if row.get("status") != 200 or row.get("finalUrl") != row.get("url") or row.get("error")]
    redirects = [row for row in pages if row.get("status") == 200 and row.get("finalUrl") != row.get("url")]
    html_pages = [row for row in pages if "html" in row.get("contentType", "").lower()]
    audit_summary = {
        "htmlPages": len(html_pages),
        "missingCanonical": sum(not row.get("canonical") for row in html_pages),
        "canonicalMismatch": sum(row.get("canonicalMatches") is False for row in html_pages),
        "noindexInSitemap": sum(row.get("noindex") is True for row in html_pages),
        "pageStatusNot200": sum(row.get("status") != 200 for row in pages),
        "pageFinalUrlMismatch": len(redirects),
    }
    report = {"checkedAt": checked_at, "sitemap": args.sitemap, "delaySeconds": delay,
              "sitemaps": sitemap_results, "declaredUrlCount": len(page_urls), "uniqueUrlCount": len({row['url'] for row in pages}),
              "auditSummary": audit_summary, "pages": pages}
    json_path = args.output / "sitemap-report.json"
    json_path.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    markdown = ["# Read-only sitemap crawl", "", f"- Checked: {checked_at}", f"- Declared URLs: {len(page_urls)}; unique crawled: {len(pages)}",
                f"- Sitemap files: {len(sitemap_results)}; sitemap issues: {len(failed_sitemaps)}; page URLs requiring review: {len(failures)}; page redirects: {len(redirects)}",
                f"- HTML: {audit_summary['htmlPages']}; missing canonical: {audit_summary['missingCanonical']}; canonical mismatch: {audit_summary['canonicalMismatch']}; noindex in sitemap: {audit_summary['noindexInSitemap']}", "",
                "## Sitemap files", "", "| Status | URL | Final URL |", "|---:|---|---|"]
    markdown.extend(f"| {row['status']} | {row['url']} | {row['finalUrl']} |" for row in sitemap_results)
    if failed_sitemaps:
        markdown += ["", "## Sitemap issues", "", "| Status | URL | Final URL | Error |", "|---:|---|---|---|"]
        markdown.extend(f"| {row['status']} | {row['url']} | {row['finalUrl']} | {row.get('error', '')} |" for row in failed_sitemaps)
    markdown += ["", "## URLs requiring review", "", "| Status | URL | Final URL | Robots | Canonical | Error |", "|---:|---|---|---|---|---|"]
    for row in failures:
        markdown.append(f"| {row.get('status')} | {row['url']} | {row.get('finalUrl', '')} | {row.get('robots', '')} | {row.get('canonical', '')} | {row.get('error', '')} |")
    if not failures:
        markdown.append("| — | None | — | — | — | All listed URLs returned 200 at their declared URL. |")
    md_path = args.output / "sitemap-report.md"
    md_path.write_text("\n".join(markdown) + "\n", encoding="utf-8")
    print(json.dumps({"sitemaps": len(sitemap_results), "declaredUrls": len(page_urls), "crawled": len(pages), "failures": len(failures), "json": str(json_path), "markdown": str(md_path)}, ensure_ascii=False))
    return 1 if failures or failed_sitemaps else 0


if __name__ == "__main__":
    raise SystemExit(main())
