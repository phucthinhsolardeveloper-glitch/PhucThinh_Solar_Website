<style>
    /* Sidebar menu scrolls without a permanent scrollbar: thin on hover, none when collapsed. */
    .fi-sidebar-nav { scrollbar-width: thin; scrollbar-color: transparent transparent; }
    .fi-sidebar-nav:hover { scrollbar-color: rgb(128 128 128 / 0.35) transparent; }
    .fi-sidebar:not(.fi-sidebar-open) .fi-sidebar-nav { scrollbar-width: none; }

    /* Dashboard: "Cần xử lý" list and the most viewed pages table. */
    .pts-attention { display: grid; gap: .65rem; }
    .pts-attention li { display: flex; gap: .65rem; align-items: flex-start; font-size: .875rem; line-height: 1.4; }
    .pts-attention__dot { flex: none; width: .55rem; height: .55rem; margin-top: .4rem; border-radius: 999px; background: #94a3b8; }
    .pts-attention__dot.is-danger { background: #dc2626; }
    .pts-attention__dot.is-warning { background: #e0a526; }
    .pts-attention__dot.is-info { background: #16365f; }
    .pts-attention__link { font-weight: 600; }
    .pts-attention__link:hover, .pts-attention__sub a:hover { text-decoration: underline; }
    .pts-attention__sub { margin-top: .15rem; font-size: .8rem; color: rgb(107 114 128); }
    .pts-queue { display: grid; gap: 1rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 26rem), 1fr)); align-items: start; }
    .pts-queue__heading { display: inline-flex; align-items: center; gap: .5rem; }
    .pts-queue__heading .pts-attention__dot { margin-top: 0; }
    .pts-queue__list { display: grid; gap: .55rem; font-size: .875rem; }
    .pts-queue__list li { display: grid; gap: .05rem; }
    .pts-queue__more { display: inline-block; margin-top: .75rem; font-size: .8rem; font-weight: 600; }
    .pts-queue__more:hover { text-decoration: underline; }
    .pts-dq__check > .pts-queue__more { margin-block: .6rem .5rem; }
    .pts-section-source { font-size: .8rem; line-height: 1.5; }
    .pts-section-source__links { display: flex; flex-wrap: wrap; gap: .35rem 1.25rem; margin-top: .35rem; }
    .pts-page-directory__page + .pts-page-directory__page { margin-top: 1.5rem; }
    .pts-page-directory__page > h3 { font-size: 1rem; font-weight: 700; border-bottom: 1px solid rgb(148 163 184 / 35%); padding-bottom: .35rem; }
    .pts-page-directory__block { margin-top: .75rem; }
    .pts-cal__stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(9.5rem, 1fr)); gap: .6rem; }
    .pts-cal__stat { text-align: left; padding: .6rem .8rem; border-radius: .6rem; border: 1px solid rgb(128 128 128 / .22); border-top: 3px solid var(--cal); }
    .pts-cal__stat b { display: block; font-size: 1.5rem; line-height: 1.2; font-variant-numeric: tabular-nums; }
    .pts-cal__stat span { font-size: .8rem; font-weight: 600; }
    .pts-cal__stat small { display: block; font-size: .72rem; color: rgb(107 114 128); }
    .pts-cal__stat[aria-pressed="true"] { background: color-mix(in srgb, var(--cal) 16%, transparent); border-color: var(--cal); }
    .is-live { --cal: #16a34a; } .is-scheduled { --cal: #2563eb; } .is-waiting { --cal: #d97706; } .is-todo { --cal: #94a3b8; } .is-late { --cal: #dc2626; }
    .pts-cal__bar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .pts-cal__month { font-size: 1.05rem; min-width: 8rem; text-align: center; }
    .pts-cal__hint { font-size: .78rem; color: rgb(107 114 128); margin-left: auto; }
    .pts-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid rgb(128 128 128 / .2); border-radius: .5rem; overflow: hidden; }
    .pts-cal__head { padding: .4rem .5rem; font-size: .75rem; font-weight: 600; color: rgb(107 114 128); background: rgb(128 128 128 / .06); }
    .pts-cal__day { min-height: 6.5rem; padding: .35rem; border-top: 1px solid rgb(128 128 128 / .15); border-left: 1px solid rgb(128 128 128 / .15); display: grid; align-content: start; gap: .25rem; }
    .pts-cal__day:nth-child(7n + 1) { border-left: 0; }
    .pts-cal__day.is-out { opacity: .45; }
    .pts-cal__day.is-drop { background: rgb(37 99 235 / .12); }
    .pts-cal__day.is-today .pts-cal__date { background: #e0a526; color: #0b1f3a; border-radius: 999px; padding: 0 .4rem; }
    .pts-cal__date { justify-self: start; font-size: .75rem; font-weight: 600; }
    .pts-cal__post { display: block; width: 100%; text-align: left; font-size: .75rem; line-height: 1.3; padding: .25rem .35rem; border-radius: .3rem; border-left: 3px solid var(--cal); background: color-mix(in srgb, var(--cal) 11%, transparent); overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
    .pts-cal__post.is-todo, .pts-cal__post.is-late { background: transparent; border: 1px dashed var(--cal); }
    .pts-cal__post[draggable="true"] { cursor: grab; }
    .pts-cal__post:hover { text-decoration: underline; }
    .pts-cal__time { font-variant-numeric: tabular-nums; color: rgb(107 114 128); }
    .pts-cal__shade { position: fixed; inset: 0; z-index: 40; background: rgb(0 0 0 / .45); }
    .pts-cal__panel { position: fixed; z-index: 41; top: 0; right: 0; bottom: 0; width: min(42rem, 100%); display: grid; grid-template-rows: auto 1fr auto; background: #fff; color: #111827; box-shadow: -8px 0 24px rgb(0 0 0 / .25); }
    .dark .pts-cal__panel { background: #18181b; color: #f4f4f5; }
    .pts-cal__panel > header, .pts-cal__panel > footer { padding: 1rem 1.25rem; display: grid; gap: .5rem; }
    .pts-cal__panel > header { border-bottom: 1px solid rgb(128 128 128 / .2); }
    .pts-cal__panel > footer { border-top: 1px solid rgb(128 128 128 / .2); }
    .pts-cal__panel h2 { font-size: 1.1rem; font-weight: 700; line-height: 1.35; }
    .pts-cal__tag { justify-self: start; font-size: .75rem; font-weight: 600; padding: .1rem .55rem; border-radius: 999px; border: 1px solid var(--cal); }
    .pts-cal__read { overflow-y: auto; padding: 1rem 1.25rem; font-size: .92rem; line-height: 1.65; }
    .pts-cal__read h2 { margin: 1.1rem 0 .4rem; } .pts-cal__read h3 { font-weight: 700; margin: .9rem 0 .3rem; }
    .pts-cal__read p, .pts-cal__read ul, .pts-cal__read ol { margin: .55rem 0; } .pts-cal__read ul { list-style: disc; padding-left: 1.2rem; } .pts-cal__read ol { list-style: decimal; padding-left: 1.2rem; }
    .pts-cal__read table { width: 100%; border-collapse: collapse; display: block; overflow-x: auto; } .pts-cal__read th, .pts-cal__read td { border: 1px solid rgb(128 128 128 / .3); padding: .35rem .5rem; text-align: left; vertical-align: top; }
    .pts-cal__read a { text-decoration: underline; } .pts-cal__read img { max-width: 100%; height: auto; border-radius: .4rem; }
    .pts-cal__acts { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .pts-cal__when { border: 1px solid rgb(128 128 128 / .4); border-radius: .5rem; padding: .35rem .5rem; font-size: .85rem; background: transparent; }
    .dark .pts-cal__when { color-scheme: dark; }
    .pts-cal__error { color: #dc2626; font-size: .82rem; }
    @media (max-width: 768px) { .pts-cal { grid-template-columns: 1fr; } .pts-cal__head, .pts-cal__day.is-out, .pts-cal__day.is-empty:not(.is-today) { display: none; } .pts-cal__day { min-height: 0; border-left: 0; } .pts-cal__hint { display: none; } }
    .pts-dq { display: grid; gap: .25rem; }
    .pts-dq__check summary { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .45rem 0; cursor: pointer; font-size: .875rem; }
    .pts-dq__check summary .pts-attention__dot { margin-top: 0; }
    .pts-dq__label { font-weight: 600; }
    .pts-dq__trend { font-size: .75rem; color: rgb(107 114 128); }
    .pts-dq__trend.is-better { color: #16a34a; }
    .pts-dq__trend.is-worse { color: #dc2626; }
    .pts-dq__items { display: grid; gap: .3rem; padding: 0 0 .6rem 1.1rem; font-size: .85rem; }
    .pts-dq__items a:hover { text-decoration: underline; }
    .pts-order-items table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .pts-order-items th { text-align: left; font-weight: 600; color: rgb(107 114 128); padding: 0 .5rem .5rem 0; border-bottom: 1px solid rgb(128 128 128 / .2); }
    .pts-order-items td { padding: .6rem .5rem .6rem 0; border-bottom: 1px solid rgb(128 128 128 / .12); vertical-align: top; }
    .pts-order-items td a { font-weight: 600; }
    .pts-order-items td a:hover { text-decoration: underline; }
    .pts-order-items td .pts-attention__sub { display: block; }
    .pts-order-items .n { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
    .pts-order-items .c { text-align: center; width: 3rem; }
    .pts-order-items__totals { display: grid; grid-template-columns: 1fr auto; gap: .35rem 1.5rem; width: min(22rem, 100%); margin: .9rem 0 0 auto; font-size: .875rem; }
    .pts-order-items__totals dd { text-align: right; font-variant-numeric: tabular-nums; }
    .pts-order-items__totals .is-sum { padding-top: .45rem; border-top: 2px solid currentColor; font-weight: 700; font-size: 1rem; }
    .pts-top-pages { width: 100%; font-size: .85rem; border-collapse: collapse; }
    .pts-top-pages th { text-align: left; font-weight: 500; color: rgb(107 114 128); padding: 0 0 .4rem; }
    .pts-top-pages th:not(:first-child), .pts-top-pages td:not(:first-child) { text-align: right; white-space: nowrap; padding-left: .75rem; width: 1%; }
    .pts-top-pages td { padding: .35rem 0; border-top: 1px solid rgb(128 128 128 / .15); }
    .pts-top-pages td:first-child { position: relative; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pts-top-pages td:first-child a { position: relative; z-index: 1; }
    .pts-top-pages__bar { position: absolute; left: 0; top: 15%; bottom: 15%; background: rgb(224 165 38 / .16); border-radius: 3px; }

    /* Product list: a hidden product reads as switched off at a glance; its status and buttons stay clear. */
    .fi-ta-row.pts-row-hidden .fi-ta-image img { filter: grayscale(1); opacity: .4; }
    .fi-ta-row.pts-row-hidden .fi-ta-cell:is(.fi-ta-cell-name, .fi-ta-cell-category\.name, .fi-ta-cell-price, .fi-ta-cell-sales-readiness) { opacity: .5; }
</style>
