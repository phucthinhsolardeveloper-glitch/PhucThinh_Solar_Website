@php($hasChildren = count($node['children']) > 0)
<li class="pts-tree-item" data-id="{{ $node['id'] }}" x-data="{ open: !isCollapsed({{ $node['id'] }}) }" :class="{ 'is-collapsed': !open }">
    <div class="pts-tree-card" @class(['is-top' => $node['depth'] === 1, 'is-hidden' => ! $node['visible']])>
        <span class="pts-tree-handle" title="Kéo để đổi thứ tự hoặc chuyển sang danh mục khác" aria-hidden="true">
            <x-filament::icon icon="heroicon-m-bars-2" class="h-5 w-5" />
        </span>
        <button type="button" class="pts-tree-toggle" x-show="hasKids($el)" x-on:click="open = !open; remember({{ $node['id'] }}, open)"
                :aria-expanded="open.toString()" :title="open ? 'Thu gọn' : 'Mở rộng'">
            <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4 pts-tree-chevron" />
        </button>
        <span class="pts-tree-toggle-spacer" x-show="!hasKids($el)"></span>
        <span class="pts-tree-name">{{ $node['name'] }}</span>
        <span class="pts-tree-level" data-level>Cấp {{ $node['depth'] }}</span>
        @unless ($node['visible'])<span class="pts-tree-flag">Đang ẩn</span>@endunless
        <span class="pts-tree-count" title="Sản phẩm đang hiện trong cả nhánh (trực tiếp: {{ $node['direct'] }})">{{ $node['branch'] }} SP</span>
        <span class="pts-tree-actions">
            @if ($node['view'])
                <a href="{{ $node['view'] }}" target="_blank" rel="noopener" title="Xem trên website"><x-filament::icon icon="heroicon-o-arrow-top-right-on-square" class="h-4 w-4" /></a>
            @endif
            @if ($node['depth'] < 4)
                <a href="{{ $node['addChild'] }}" title="Thêm danh mục con"><x-filament::icon icon="heroicon-o-plus" class="h-4 w-4" /></a>
            @endif
            <a href="{{ $node['edit'] }}" class="pts-tree-edit" title="Chỉnh sửa"><x-filament::icon icon="heroicon-o-pencil-square" class="h-4 w-4" /> Sửa</a>
        </span>
    </div>
    <ul class="pts-tree-list pts-tree-children" x-show="open" x-collapse>
        @foreach ($node['children'] as $child)
            @include('filament.resources.categories.tree-node', ['node' => $child])
        @endforeach
    </ul>
</li>
