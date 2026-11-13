Scripts used for the 2026-11-13 SEO/mobile audit (see docs/12-ai/handoff-2026-11-13-seo-sale.md). Each takes a work dir S.
- crawl.py S: fetch every <loc> of S/sitemap.xml (as Googlebot), write S/crawl.json (title, description, canonical, robots, JSON-LD types, Offer/availability, h1, img alt)
- check.py S: follow redirects for each path in S/old-urls.txt against production, write S/old-check.tsv
- links.py S: every internal <a href> of the sitemap pages, report non-200 targets and where they are linked from
- swap.cjs base start target scrollY cpu: per-frame scrollY / URL / h1 / header top during a client navigation
- jump.cjs base: masthead, brand row, chip row and grid positions across category pages at 390 px
