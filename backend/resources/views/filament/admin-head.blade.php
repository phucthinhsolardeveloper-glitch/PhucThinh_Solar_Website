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
    .pts-cal__bar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
    .pts-cal__month { font-size: 1rem; }
    .pts-cal__legend { display: inline-flex; gap: .4rem; margin-left: auto; }
    .pts-cal { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid rgb(128 128 128 / .2); border-radius: .5rem; overflow: hidden; }
    .pts-cal__head { padding: .4rem .5rem; font-size: .75rem; font-weight: 600; color: rgb(107 114 128); background: rgb(128 128 128 / .06); }
    .pts-cal__day { min-height: 6.5rem; padding: .35rem; border-top: 1px solid rgb(128 128 128 / .15); border-left: 1px solid rgb(128 128 128 / .15); display: grid; align-content: start; gap: .25rem; }
    .pts-cal__day:nth-child(7n + 1) { border-left: 0; }
    .pts-cal__day.is-out { opacity: .45; }
    .pts-cal__day.is-today .pts-cal__date { background: #e0a526; color: #0b1f3a; border-radius: 999px; padding: 0 .4rem; }
    .pts-cal__date { justify-self: start; font-size: .75rem; font-weight: 600; }
    .pts-cal__post { display: block; font-size: .75rem; line-height: 1.3; padding: .2rem .35rem; border-radius: .3rem; border-left: 3px solid #94a3b8; background: rgb(128 128 128 / .08); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pts-cal__post.is-live { border-left-color: #16a34a; }
    .pts-cal__post.is-scheduled { border-left-color: #2563eb; }
    .pts-cal__post:hover { text-decoration: underline; }
    .pts-cal__time { font-variant-numeric: tabular-nums; color: rgb(107 114 128); }
    @media (max-width: 768px) { .pts-cal__day { min-height: 4.5rem; } .pts-cal__post { white-space: normal; } .pts-cal__legend { margin-left: 0; } }
    .pts-dq { display: grid; gap: .25rem; }
    .pts-dq__check summary { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; padding: .45rem 0; cursor: pointer; font-size: .875rem; }
    .pts-dq__check summary .pts-attention__dot { margin-top: 0; }
    .pts-dq__label { font-weight: 600; }
    .pts-dq__trend { font-size: .75rem; color: rgb(107 114 128); }
    .pts-dq__trend.is-better { color: #16a34a; }
    .pts-dq__trend.is-worse { color: #dc2626; }
    .pts-dq__items { display: grid; gap: .3rem; padding: 0 0 .6rem 1.1rem; font-size: .85rem; }
    .pts-dq__items a:hover { text-decoration: underline; }
    .pts-top-pages { width: 100%; font-size: .85rem; border-collapse: collapse; }
    .pts-top-pages th { text-align: left; font-weight: 500; color: rgb(107 114 128); padding: 0 0 .4rem; }
    .pts-top-pages th:not(:first-child), .pts-top-pages td:not(:first-child) { text-align: right; white-space: nowrap; padding-left: .75rem; width: 1%; }
    .pts-top-pages td { padding: .35rem 0; border-top: 1px solid rgb(128 128 128 / .15); }
    .pts-top-pages td:first-child { position: relative; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pts-top-pages td:first-child a { position: relative; z-index: 1; }
    .pts-top-pages__bar { position: absolute; left: 0; top: 15%; bottom: 15%; background: rgb(224 165 38 / .16); border-radius: 3px; }
</style>
