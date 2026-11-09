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
    .pts-top-pages { width: 100%; font-size: .85rem; border-collapse: collapse; }
    .pts-top-pages th { text-align: left; font-weight: 500; color: rgb(107 114 128); padding: 0 0 .4rem; }
    .pts-top-pages th:not(:first-child), .pts-top-pages td:not(:first-child) { text-align: right; white-space: nowrap; padding-left: .75rem; width: 1%; }
    .pts-top-pages td { padding: .35rem 0; border-top: 1px solid rgb(128 128 128 / .15); }
    .pts-top-pages td:first-child { position: relative; max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pts-top-pages td:first-child a { position: relative; z-index: 1; }
    .pts-top-pages__bar { position: absolute; left: 0; top: 15%; bottom: 15%; background: rgb(224 165 38 / .16); border-radius: 3px; }
</style>
