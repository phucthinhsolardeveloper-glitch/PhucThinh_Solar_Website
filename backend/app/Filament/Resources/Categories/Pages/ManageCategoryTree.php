<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Domain\Catalog\CategoryTree;
use App\Domain\Catalog\CategoryTreeEditor;
use App\Domain\Routing\PublicUrls;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Product categories as a staircase tree: children indented under their parent, each branch folds open or closed,
 * and cards can be dragged to change order or moved under another category (up to 4 levels), then saved in one go.
 */
class ManageCategoryTree extends Page
{
    protected static string $resource = CategoryResource::class;

    protected string $view = 'filament.resources.categories.tree';

    protected static ?string $title = 'Danh mục sản phẩm';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')->label('Dạng bảng')->icon(Heroicon::OutlinedTableCells)->color('gray')
                ->url(CategoryResource::getUrl('table')),
            Action::make('create')->label('Thêm danh mục cấp 1')->icon(Heroicon::OutlinedPlus)
                ->url(CategoryResource::getUrl('create')),
        ];
    }

    /** @return list<array<string, mixed>> */
    public function getTree(): array
    {
        $tree = new CategoryTree;
        $direct = DB::table('products')->selectRaw('category_id, count(*) n, sum(is_visible) v')->groupBy('category_id')->get()->keyBy('category_id');
        $build = function (int $parent) use (&$build, $tree, $direct): array {
            $nodes = [];
            foreach ($tree->children($parent) as $node) {
                $id = (int) $node['id'];
                $branchVisible = 0;
                foreach ($tree->descendantIds($id) as $descendant) {
                    $branchVisible += (int) ($direct[$descendant]->v ?? 0);
                }
                $record = new Category(['slug' => $node['slug']]);
                $record->id = $id;
                $nodes[] = [
                    'id' => $id, 'name' => (string) $node['name'], 'depth' => (int) $node['depth'], 'visible' => (bool) $node['is_visible'],
                    'direct' => (int) ($direct[$id]->n ?? 0), 'branch' => $branchVisible,
                    'edit' => CategoryResource::getUrl('edit', ['record' => $id]),
                    'addChild' => CategoryResource::getUrl('create', ['parent_id' => $id]),
                    'view' => PublicUrls::url($record),
                    'children' => $build($id),
                ];
            }

            return $nodes;
        };

        return $build(0);
    }

    /** @param list<array{id: int, children?: array}> $roots */
    public function saveTree(array $roots): void
    {
        abort_unless(CategoryResource::canViewAny(), 403);
        try {
            $result = app(CategoryTreeEditor::class)->apply($roots);
        } catch (InvalidArgumentException $e) {
            Notification::make()->danger()->title('Chưa lưu được')->body($e->getMessage())->persistent()->send();

            return;
        }
        $body = [];
        if ($result['moved'] !== []) {
            $body[] = 'Đổi chỗ: '.implode('; ', $result['moved']).'. Link cũ tự chuyển sang link mới.';
        }
        if ($result['reordered'] > 0) {
            $body[] = 'Đổi thứ tự '.$result['reordered'].' danh mục.';
        }
        Notification::make()->success()->title($body === [] ? 'Không có thay đổi' : 'Đã lưu cây danh mục')->body(implode(' ', $body))->send();
        $this->redirect(CategoryResource::getUrl('index'));
    }
}
