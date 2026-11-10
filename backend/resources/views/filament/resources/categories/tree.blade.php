<x-filament-panels::page>
    <div x-data="ptsCategoryTree" class="pts-tree" :class="{ 'is-editing': editing }" wire:ignore>
        <div class="pts-tree-toolbar">
            <span x-show="!editing">Cây đang khóa, không kéo được. Bấm "Sắp xếp danh mục" khi cần đổi thứ tự hoặc chuyển cấp.</span>
            <span x-show="editing" x-cloak class="pts-tree-editing-hint">Đang sắp xếp: kéo thẻ bằng tay cầm ☰ ở đầu thẻ để đổi thứ tự, hoặc thả vào trong danh mục khác để chuyển cấp (tối đa 4 cấp).</span>
            <span class="pts-tree-toolbar-buttons">
                <button type="button" x-on:click="setAll(true)">Mở hết</button>
                <button type="button" x-on:click="setAll(false)">Thu gọn hết</button>
                <x-filament::button size="sm" icon="heroicon-o-arrows-up-down" x-show="!editing" x-on:click="startEditing()">Sắp xếp danh mục</x-filament::button>
                <x-filament::button size="sm" color="gray" icon="heroicon-o-lock-closed" x-show="editing" x-cloak x-on:click="stopEditing()">Khóa lại</x-filament::button>
            </span>
        </div>

        <ul class="pts-tree-list pts-tree-root" x-ref="root">
            @foreach ($this->getTree() as $node)
                @include('filament.resources.categories.tree-node', ['node' => $node])
            @endforeach
        </ul>

        <div class="pts-tree-savebar" x-show="dirty" x-transition>
            <span>Bạn đã đổi vị trí danh mục. Link cũ của danh mục chuyển chỗ sẽ tự chuyển sang link mới.</span>
            <span class="pts-tree-toolbar-buttons">
                <x-filament::button color="gray" x-on:click="window.location.reload()">Hủy</x-filament::button>
                <x-filament::button x-on:click="save()" x-bind:disabled="saving">Lưu sắp xếp</x-filament::button>
            </span>
        </div>
    </div>

    <style>
        .pts-tree { --line: rgb(0 0 0 / 10%); --card: #fff; --muted: rgb(0 0 0 / 55%); }
        .dark .pts-tree { --line: rgb(255 255 255 / 12%); --card: rgb(255 255 255 / 4%); --muted: rgb(255 255 255 / 55%); }
        .pts-tree-toolbar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-bottom: 1rem; font-size: .875rem; color: var(--muted); }
        .pts-tree-toolbar-buttons { display: inline-flex; gap: .5rem; }
        .pts-tree-toolbar-buttons > button { padding: .35rem .75rem; border: 1px solid var(--line); border-radius: .5rem; font-weight: 600; color: inherit; }
        .pts-tree-list { list-style: none; margin: 0; padding: 0; }
        .pts-tree-children { margin-left: 1.75rem; padding-left: .9rem; border-left: 2px solid var(--line); }
        .pts-tree-item { margin: .4rem 0; }
        .pts-tree-card { display: flex; align-items: center; gap: .6rem; padding: .55rem .75rem; border: 1px solid var(--line); border-radius: .65rem; background: var(--card); }
        .pts-tree-card.is-top { border-width: 1.5px; }
        .pts-tree-card.is-top .pts-tree-name { font-weight: 700; font-size: 1rem; }
        .pts-tree-card.is-hidden { opacity: .6; }
        .pts-tree:not(.is-editing) .pts-tree-handle { display: none; }
        .pts-tree.is-editing .pts-tree-card { border-style: dashed; }
        .pts-tree-editing-hint { color: rgb(180 83 9); font-weight: 600; }
        .dark .pts-tree-editing-hint { color: rgb(252 211 77); }
        .pts-tree-handle { cursor: grab; color: var(--muted); display: inline-flex; }
        .pts-tree-handle:active { cursor: grabbing; }
        .pts-tree-toggle, .pts-tree-toggle-spacer { display: inline-flex; width: 1.5rem; height: 1.5rem; align-items: center; justify-content: center; border-radius: .35rem; flex: none; }
        .pts-tree-toggle:hover { background: var(--line); }
        .pts-tree-chevron { transition: transform .15s ease; transform: rotate(90deg); }
        .pts-tree-item.is-collapsed > .pts-tree-card .pts-tree-chevron { transform: rotate(0deg); }
        .pts-tree-name { flex: 1; min-width: 0; font-weight: 500; }
        .pts-tree-level { font-size: .72rem; font-weight: 700; padding: .1rem .45rem; border-radius: .4rem; background: rgb(217 119 6 / 14%); color: rgb(180 83 9); white-space: nowrap; }
        .dark .pts-tree-level { color: rgb(252 211 77); }
        .pts-tree-flag { font-size: .72rem; font-weight: 700; padding: .1rem .45rem; border-radius: .4rem; background: var(--line); color: var(--muted); }
        .pts-tree-count { font-size: .8rem; color: var(--muted); white-space: nowrap; min-width: 3.5rem; text-align: right; }
        .pts-tree-actions { display: inline-flex; align-items: center; gap: .35rem; }
        .pts-tree-actions a { display: inline-flex; align-items: center; gap: .25rem; padding: .3rem; border-radius: .35rem; color: var(--muted); }
        .pts-tree-actions a:hover { background: var(--line); color: inherit; }
        .pts-tree-actions .pts-tree-edit { color: rgb(217 119 6); font-weight: 600; font-size: .85rem; padding-inline: .5rem; }
        .pts-tree-ghost > .pts-tree-card { border-style: dashed; border-color: rgb(217 119 6); background: rgb(217 119 6 / 8%); }
        .pts-tree.is-dragging .pts-tree-children { min-height: .6rem; }
        .pts-tree.is-dragging .pts-tree-item.is-collapsed > .pts-tree-children { display: block !important; }
        .pts-tree-savebar { position: sticky; bottom: 1rem; z-index: 20; display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; margin-top: 1.25rem; padding: .85rem 1rem; border: 1px solid rgb(217 119 6 / 40%); border-radius: .75rem; background: var(--card); box-shadow: 0 10px 30px -12px rgb(0 0 0 / 35%); backdrop-filter: blur(6px); font-size: .875rem; }
        @media (max-width: 640px) { .pts-tree-children { margin-left: .9rem; padding-left: .5rem; } .pts-tree-count, .pts-tree-level { display: none; } }
    </style>

    @script
    <script>
        Alpine.data('ptsCategoryTree', () => ({
            dirty: false,
            editing: false,
            sorters: [],
            saving: false,
            collapsed: (() => { try { return JSON.parse(localStorage.getItem('pts-category-collapsed') || '[]') } catch { return [] } })(),
            init() {
                this.$nextTick(() => this.$el.querySelectorAll('.pts-tree-list').forEach((list) => this.sortable(list)))
            },
            startEditing() { this.editing = true; this.sorters.forEach((x) => x.option('disabled', false)) },
            stopEditing() {
                // Unsaved moves are thrown away: the page goes back to what is stored.
                if (this.dirty) { if (!confirm('Bỏ các thay đổi chưa lưu và khóa lại?')) return; window.location.reload(); return }
                this.editing = false; this.sorters.forEach((x) => x.option('disabled', true))
            },
            isCollapsed(id) { return this.collapsed.includes(id) },
            hasKids(el) { return el.closest('.pts-tree-item').querySelector(':scope > .pts-tree-children > .pts-tree-item') !== null },
            remember(id, open) {
                this.collapsed = open ? this.collapsed.filter((x) => x !== id) : [...new Set([...this.collapsed, id])]
                try { localStorage.setItem('pts-category-collapsed', JSON.stringify(this.collapsed)) } catch {}
            },
            setAll(open) {
                this.$el.querySelectorAll('.pts-tree-item').forEach((item) => {
                    const id = Number(item.dataset.id)
                    Alpine.$data(item).open = open
                    this.remember(id, open)
                })
            },
            depthOf(list) { let depth = 1, el = list.parentElement; while (el && el !== this.$refs.root.parentElement) { if (el.classList?.contains('pts-tree-item')) depth++; el = el.parentElement } return depth },
            height(item) { const kids = [...item.querySelectorAll(':scope > .pts-tree-children > .pts-tree-item')]; return 1 + (kids.length ? Math.max(...kids.map((k) => this.height(k))) : 0) },
            sortable(list) {
                this.sorters.push(new window.Sortable(list, {
                    disabled: true, group: 'pts-categories', handle: '.pts-tree-handle', animation: 150, fallbackOnBody: true,
                    swapThreshold: 0.6, emptyInsertThreshold: 12, ghostClass: 'pts-tree-ghost',
                    onStart: () => this.$el.classList.add('is-dragging'),
                    onEnd: () => { this.$el.classList.remove('is-dragging'); this.relabel(); this.dirty = true },
                    // Up to four levels: the dragged branch must fit below the list it lands in.
                    onMove: (event) => this.editing && this.depthOf(event.to) + this.height(event.dragged) - 1 <= 4,
                }))
            },
            relabel() {
                this.$el.querySelectorAll('.pts-tree-item').forEach((item) => {
                    const level = this.depthOf(item.parentElement)
                    item.querySelector(':scope > .pts-tree-card [data-level]').textContent = 'Cấp ' + level
                    item.querySelector(':scope > .pts-tree-card').classList.toggle('is-top', level === 1)
                })
            },
            serialize(list) {
                return [...list.querySelectorAll(':scope > .pts-tree-item')].map((item) => ({
                    id: Number(item.dataset.id), children: this.serialize(item.querySelector(':scope > .pts-tree-children')),
                }))
            },
            async save() {
                this.saving = true
                await $wire.saveTree(this.serialize(this.$refs.root))
                this.saving = false
            },
        }))
    </script>
    @endscript
</x-filament-panels::page>
