# Phúc Thịnh Solar — local Nuxt preview (2026-10-26)

## Open on Windows
- Unified header + real CMS Solutions + footer: http://localhost:3100/migration-site-preview
- Header/menu/footer inspection: http://localhost:3100/migration-shell-preview
- Solutions-only preview: http://localhost:3100/migration-solutions-preview
- Home preview: http://localhost:3100/migration-home-preview
- Product catalog preview: http://localhost:3100/migration-catalog-preview
- Product detail preview (fixture record): http://localhost:3100/migration-product-preview?slug=den-ufo-nang-luong-mat-troi-oriana-ufo-1000-1000w
- Projects preview: http://localhost:3100/migration-projects-preview
- News preview: http://localhost:3100/migration-news-preview
- About preview: http://localhost:3100/migration-about-preview
- Contact preview (read-only, PHP owns form): http://localhost:3100/migration-contact-preview
- Dealer preview (read-only, PHP owns form): http://localhost:3100/migration-dealer-preview
- Central migration preview hub: http://localhost:3100/migration-hub-preview
- News detail preview: http://localhost:3100/migration-news-detail-preview?slug=8-ly-do-ban-nen-su-dung-den-nang-luong-mat-troi-ngay-bay-gio
- Project detail preview: http://localhost:3100/migration-project-detail-preview?slug=du-an-dien-nlmt-ho-anh-hoa-thanh-an
- Solution detail preview: http://localhost:3100/migration-solution-detail-preview?slug=dien-mat-troi-ho-gia-dinh
- Local PHP reference (original master): http://localhost:8080/giai-phap/
- Local PHP migration code/API: http://localhost:8088/giai-phap/

Nuxt is intentionally in **preview mode only**. Public /giai-phap/, /san-pham/, cart, payment and Admin are NOT migrated to Nuxt. Their links may not work within the direct :3100 preview until hybrid routing is implemented. Preview routes have noindex. Do not route production traffic to Nuxt.

## Start, stop and rebuild (WSL Ubuntu)
```bash
cd /home/admin01/src/pts-nuxt4-migration
./scripts/nuxt-local-preview.sh status
./scripts/nuxt-local-preview.sh restart
./scripts/nuxt-local-preview.sh stop
```

- The script uses **existing local** MariaDB and local uploads from the website's Docker Compose network; no production credentials or data are used.
- The local PHP preview container is isolated on 127.0.0.1:8088; current PHP QA remains on 8080. Its code is mounted read-only from this migration worktree. Media is mounted read-only and caches/logs use temporary writable filesystems.
- The ignored local PHP configuration is copied from the existing **local** PHP development repo into this worktree when missing; never add it to Git.
- SSR Nuxt preview runs on 127.0.0.1:3100 with private upstream NUXT_PHP_ORIGIN=http://127.0.0.1:8088.
- Static preview media proxy is disabled by default, enabled by the local script only, and restricted to allowlisted image file paths/types (no PHP/JS/html execution).
- No production server, Git master, or existing Zalo CRM containers are modified by the script.
- Build output, images and log are untracked in frontend/.migration-artifacts/.

## Latest local checks
- PHP site, solutions, catalog GET APIs: 200 with published CMS data (local).
- All 3 Nuxt preview pages: SSR HTTP 200 and noindex; unsafe executable media path 404.
- Browser Chromium at 390x844 and 1440x900: responsive overflow 0, 6 images loaded, no broken images, 0 console errors, 0 pageerrors and no hydration mismatches.
- Images/QA output: frontend/.migration-artifacts/local-preview-qa/{mobile-390.png,desktop-1440.png,manifest.json}
- Visual fidelity and functionality against the current PHP site are **not** fully accepted: pricing, filters, order flow, rich-text sanitizer, image transformation fidelity and all page routing remain Phase A gates.
- Existing optional Vue Router/Volar typechecker warning and npm dependency advisories require a separate pre-production security review.

## Rollback / isolation
To stop **only** this preview, run ./scripts/nuxt-local-preview.sh stop. Do not stop the existing website PHP QA container or Zalo CRM. The old website keeps running from master regardless of the Nuxt preview state.
