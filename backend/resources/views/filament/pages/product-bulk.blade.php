<x-filament-panels::page>
    <style>
        .pb-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 12px; }
        .pb-stat b { display: block; font-size: 1.5rem; line-height: 1.2; }
        .pb-stat span { font-size: .8125rem; opacity: .7; }
        .pb-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .pb-table th, .pb-table td { padding: 8px; border-bottom: 1px solid rgb(128 128 128 / .2); text-align: left; vertical-align: top; overflow-wrap: anywhere; }
        .pb-bad { color: rgb(220 38 38); } .pb-warn { color: rgb(217 119 6); } .pb-muted { opacity: .65; }
        .pb-row { padding: 12px 0; border-bottom: 1px solid rgb(128 128 128 / .2); }
        .pb-tools { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
        .pb-diff { display: grid; grid-template-columns: minmax(120px, 200px) 1fr 1fr; gap: 4px 12px; font-size: .8125rem; margin-top: 6px; white-space: pre-line; overflow-wrap: anywhere; }
        @media (max-width: 640px) { .pb-diff { grid-template-columns: 1fr; } }
    </style>

    @php($plan = $this->plan())
    @if ($plan === null && $images === null)
        <x-filament::section>
            Xuất danh sách ra Excel, sửa rồi chọn <b>Nhập tệp</b> để xem trước. Chưa có gì được ghi cho tới khi bạn bấm Áp dụng.
        </x-filament::section>
    @endif

    @if ($images !== null)
        <x-filament::section heading="Thêm ảnh từ ZIP">
            <div class="pb-stats">
                <div class="pb-stat"><b>{{ $images['counts']['ready_main'] }}</b><span>Ảnh chính sẽ thêm</span></div>
                <div class="pb-stat"><b>{{ $images['counts']['ready_gallery'] }}</b><span>Ảnh thư viện sẽ thêm</span></div>
                <div class="pb-stat"><b>{{ $images['counts']['existing'] }}</b><span>Đã có, giữ nguyên</span></div>
                <div class="pb-stat"><b>{{ $images['counts']['missing_product'] }}</b><span>Không có sản phẩm</span></div>
                <div class="pb-stat"><b class="{{ $images['counts']['error'] ? 'pb-bad' : '' }}">{{ $images['counts']['error'] }}</b><span>Lỗi</span></div>
            </div>
            <table class="pb-table" style="margin-top: 12px">
                <tr><th>Tệp</th><th>Mã</th><th>Kết quả</th></tr>
                @foreach ($images['rows'] as $row)
                    <tr><td>{{ $row['entry'] }}</td><td>{{ $row['code'] }}</td><td class="{{ $row['status'] === 'error' ? 'pb-bad' : '' }}">{{ $row['message'] }}</td></tr>
                @endforeach
            </table>
        </x-filament::section>
    @endif

    @if ($plan !== null)
        @php($counts = $plan['counts'])
        <x-filament::section heading="Xem trước">
            <div class="pb-stats">
                <div class="pb-stat"><b>{{ $counts['create'] }}</b><span>Tạo mới</span></div>
                <div class="pb-stat"><b>{{ $counts['update'] }}</b><span>Cập nhật</span></div>
                <div class="pb-stat"><b>{{ $counts['unchanged'] }}</b><span>Không đổi</span></div>
                <div class="pb-stat"><b>{{ $counts['archive'] + $counts['hide_update'] }}</b><span>Sẽ bị ẩn</span></div>
                <div class="pb-stat"><b>{{ $counts['show_update'] }}</b><span>Sẽ hiện lại</span></div>
                <div class="pb-stat"><b>{{ $counts['skip'] }}</b><span>Bỏ qua</span></div>
                <div class="pb-stat"><b class="{{ $plan['errors'] ? 'pb-bad' : '' }}">{{ $plan['errors'] }}</b><span>Lỗi</span></div>
                <div class="pb-stat"><b class="{{ $plan['conflicts'] ? 'pb-warn' : '' }}">{{ $plan['conflicts'] }}</b><span>Xung đột</span></div>
            </div>
            <div class="pb-tools" style="margin-top: 12px">
                <x-filament::button color="gray" size="sm" wire:click="replan">Cập nhật theo lựa chọn</x-filament::button>
                <x-filament::button color="gray" size="sm" wire:click="cancel">Hủy tệp này</x-filament::button>
                @if ($plan['errors'] > 0)
                    <span class="pb-bad">Sửa hết lỗi trong tệp rồi tải lại. Chưa có gì được ghi.</span>
                @endif
            </div>
        </x-filament::section>

        @if ($plan['new_brands'] !== [])
            <x-filament::section heading="Thương hiệu chưa có">
                <table class="pb-table">
                    @foreach ($plan['new_brands'] as $brand)
                        <tr>
                            <td>{{ $brand['name'] }} <span class="pb-muted">({{ count($brand['rows']) }} dòng)</span></td>
                            <td>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="brandChoices.{{ $brand['key'] }}">
                                        <option value="">Chọn cách xử lý</option>
                                        <option value="create">Tạo thương hiệu mới</option>
                                        <option value="blank">Để trống</option>
                                        @foreach (\App\Models\Brand::query()->orderBy('name')->pluck('name', 'id') as $id => $name)
                                            <option value="existing:{{ $id }}">Ghép vào {{ $name }}</option>
                                        @endforeach
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif

        @if ($plan['slug_matches'] !== [])
            <x-filament::section heading="Dòng mới trùng đường dẫn sản phẩm đang có">
                <table class="pb-table">
                    @foreach ($plan['slug_matches'] as $match)
                        <tr>
                            <td>Dòng {{ $match['row'] }}: {{ $match['name'] }} <span class="pb-muted">{{ $match['code'] }}</span><br>Đang có: {{ $match['existing_name'] }} <span class="pb-muted">{{ $match['existing_code'] }}</span></td>
                            <td>
                                <x-filament::input.wrapper>
                                    <x-filament::input.select wire:model="productChoices.{{ $match['key'] }}">
                                        <option value="">Chọn cách xử lý</option>
                                        <option value="update:{{ $match['existing_id'] }}">Cập nhật sản phẩm đang có</option>
                                        <option value="create">Tạo sản phẩm mới, đường dẫn khác</option>
                                        <option value="skip">Bỏ qua dòng này</option>
                                    </x-filament::input.select>
                                </x-filament::input.wrapper>
                            </td>
                        </tr>
                    @endforeach
                </table>
            </x-filament::section>
        @endif

        <x-filament::section>
            @php($view = $this->visibleRows())
            <div class="pb-tools">
                @foreach (['issues' => 'Cần xem', 'changes' => 'Thay đổi', 'all' => 'Tất cả'] as $key => $label)
                    <x-filament::button size="sm" :color="$show === $key ? 'primary' : 'gray'" wire:click="showRows('{{ $key }}')">{{ $label }}</x-filament::button>
                @endforeach
                <span class="pb-muted">{{ $view['total'] }} dòng</span>
                @if ($plan['conflicts'] > 0)
                    <x-filament::button size="sm" color="gray" wire:click="overwriteAll(true)">Ghi đè tất cả xung đột</x-filament::button>
                    <x-filament::button size="sm" color="gray" wire:click="overwriteAll(false)">Giữ dữ liệu website</x-filament::button>
                @endif
            </div>
            @foreach ($view['rows'] as $row)
                <div class="pb-row" wire:key="row-{{ $row['row'] }}">
                    <b>Dòng {{ $row['row'] }}</b> · {{ ['create' => 'Tạo mới', 'update' => 'Cập nhật', 'archive' => 'Ẩn', 'skip' => 'Bỏ qua'][$row['action']] ?? $row['action'] }}
                    · {{ $row['name'] ?: $row['code'] }}
                    @foreach ($row['errors'] as $message)<div class="pb-bad">{{ $message }}</div>@endforeach
                    @foreach ($row['warnings'] as $message)<div class="pb-warn">{{ $message }}</div>@endforeach
                    @foreach ($row['conflicts'] as $conflict)
                        <label class="pb-diff" wire:key="c-{{ $conflict['key'] }}">
                            <span><x-filament::input.checkbox wire:model="overwrite.{{ $conflict['key'] }}" /> Ghi đè {{ $conflict['label'] }}</span>
                            <span>Website: {{ $conflict['current'] }}</span>
                            <span>Excel: {{ $conflict['incoming'] }}</span>
                        </label>
                    @endforeach
                    @if ($show !== 'issues')
                        @foreach ($row['changes'] as $change)
                            <div class="pb-diff"><span class="pb-muted">{{ $change['label'] }}</span><span class="pb-muted">{{ $change['current'] ?: '—' }}</span><span>{{ $change['incoming'] ?: '(xóa)' }}</span></div>
                        @endforeach
                    @endif
                </div>
            @endforeach
            @if ($view['pages'] > 1)
                <div class="pb-tools" style="margin-top: 12px">
                    <x-filament::button size="sm" color="gray" wire:click="$set('rowsPage', {{ max(1, $rowsPage - 1) }})">Trước</x-filament::button>
                    <span>Trang {{ $rowsPage }}/{{ $view['pages'] }}</span>
                    <x-filament::button size="sm" color="gray" wire:click="$set('rowsPage', {{ min($view['pages'], $rowsPage + 1) }})">Sau</x-filament::button>
                </div>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
