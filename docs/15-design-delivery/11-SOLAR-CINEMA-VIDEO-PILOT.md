# Solar Cinema — video preview for PTS (2026-11-03)

Lưu trữ lịch sử: route preview đã được gỡ trong đợt migration. Video, poster và
script tạo thử đã được xóa khỏi release Nuxt ngày 2026-11-08 vì không có
consumer FE/API/Admin; chỉ giữ tài liệu quyết định thiết kế này.

## Intent and placement
- Target: a concept film for a selected solutions/about storytelling section, **not** a production homepage replacement.
- Visual language: Precision Paper + Solar Cinema, original PTS navy/gold tokens, modest camera motion, website-owned HTML title/buttons.
- Route: `/migration-solar-cinema-preview` (preview only, server `X-Robots-Tag: noindex` and client robots noindex).
- Do not represent conceptual illustrative media as a commissioned/installed PTS project.

## Current local asset
- Original illustration already in the repository: `frontend/public/assets/images/site-visuals/pdp-usecase-v2/panel-commercial-roof.webp`.
- Historical deliverables: `pts-solar-cinema-preview.mp4` and `pts-solar-cinema-poster.webp`; both were removed after the preview route was retired.
- The local motion-photo generator was removed with its output; a future approved video needs a new scoped implementation.
- Current implementation: source deferred by browser `preload=none`, intersection-based pause, visibility pause, reduced-motion and mobile manual start, manual play/pause control, poster on non-playback. Existing header/footer reuse `usePublicSite()`.

## Higgsfield attempt and target generation
- The connected Higgsfield account rejected video submission with: `Requires plus plan or higher`. No generation job was submitted. Quote: Cinema Studio 4.0, 8 s, 16:9, 720p, mute, 56 Higgsfield credits.
- Future video should be created with existing brand media approved for marketing or using a conceptual solar-roof video prompt; **do not** overlay a hallucinated corporate logo in the generated video. Render the real site logo separately from the actual branding asset in Nuxt.
- Desired shot: one continuous slow aerial push over a realistic Vietnamese industrial rooftop PV system in natural light, warm-gold restrained reflections and physically plausible materials. Left-third negative space for HTML text. No captions, no generated data, no fake customer projects, no garish neon/energy rays. 8 s 16:9 mute; first and last frames should be visually compatible for looping.
- Replace the local motion-photo MP4 with an approved generated/rendered or licensed video only after visual and source-rights review.

## Checks
- Nuxt typecheck: PASS.
- Preview page: HTTP 200 and `X-Robots-Tag: noindex, nofollow, noarchive` confirmed on localhost:3195.
- MP4 and WebP poster: HTTP 200 with expected MIME confirmed.
- Desktop 1440 px and mobile 390 px screenshots inspected; no horizontal overflow observed.
- Current PHP API unavailable in pilot environment (`/_migration/site` 502); header/footer display the existing site composable's fallback brand. This is a local preview limitation, not a claim of production failure.

## Integration boundary
- No production or staging deploy was performed. Main repo `master` was not edited by this branch.
- Use this as one owner-reviewable visual pilot. If selected, integrate only the video component/asset into the chosen CMS-managed placement, preserve original images and homepage/header/footer constraints, retest on low-end mobile, and confirm source media rights.
