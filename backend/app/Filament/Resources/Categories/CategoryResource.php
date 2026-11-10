<?php

namespace App\Filament\Resources\Categories;

use App\Domain\Catalog\CategoryTree;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\Options;
use App\Filament\Support\PublicLink;
use App\Models\Category;
use App\Support\Slugs;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use UnitEnum;

class CategoryResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'catalog';

    protected static ?string $model = Category::class;

    protected static ?string $modelLabel = 'danh mục sản phẩm';

    protected static ?string $pluralModelLabel = 'Danh mục sản phẩm';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Fields::title(),
                Fields::slug('name', fn (?Model $record, Get $get) => $get('parent_id')
                    ? Slugs::scope('categories', ['parent_id' => $get('parent_id')], $record?->getKey())
                    : Slugs::publicScope('categories', $record?->getKey())),
                Select::make('parent_id')->label('Thuộc danh mục')->placeholder('Danh mục cấp 1')
                    ->default(fn () => request()->integer('parent_id') ?: null)
                    ->options(fn (?Model $record) => Options::categoryPaths(3, $record?->getKey()))->searchable()->live()
                    ->disabled(fn (?Category $record) => (bool) $record?->children()->exists())
                    ->helperText(fn (?Category $record) => $record?->children()->exists() ? 'Danh mục đang có danh mục con nên không đổi được cấp.' : null),
                Select::make('pdp_profile')->label('Kiểu trang sản phẩm')->options(Options::pdpProfiles())
                    ->helperText('Quyết định nhóm thông số và gợi ý hiển thị trên trang sản phẩm.'),
                Textarea::make('summary')->label('Mô tả ngắn')->rows(3)->columnSpanFull(),
                Fields::rich('body', 'Nội dung')->columnSpanFull(),
                Fields::image('image', 'product'),
                Grid::make(1)->schema([
                    Fields::visible(),
                    Toggle::make('is_featured')->label('Nổi bật'),
                    Toggle::make('is_major')->label('Nhóm chính trên trang chủ'),
                    Toggle::make('in_solution_hub')->label('Hiện trong trang Giải pháp'),
                    Fields::sort(),
                ]),
            ]),
            Section::make('Khối mô tả dùng chung')->relationship('contentSection', Fields::keepRelation('contentSection', fn (array $state) => filled($state['blocks'] ?? null)))->collapsed()
                ->description('Sản phẩm trong danh mục kế thừa các khối này khi sản phẩm không có mô tả riêng.')
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => $data + ['mode' => 'own'])
                ->schema([Fields::visible('Dùng các khối này'), Fields::contentBlocks()]),
            Fields::seo('product'),
        ]);
    }

    public static function table(Table $table): Table
    {
        // Shown as the tree it is: each category right under its parent, in the same order as the site menu.
        return $table
            ->defaultSort(fn (Builder $query) => $query->orderByRaw('FIELD(categories.id, '.implode(',', self::treeOrder() ?: [0]).')'))
            ->paginated([50, 100, 'all'])->defaultPaginationPageOption(100)
            ->columns([
                TextColumn::make('name')->label('Danh mục')->searchable()->weight(fn (Category $record) => (int) $record->depth === 1 ? 'bold' : null)
                    ->formatStateUsing(fn (Category $record) => str_repeat('↳ ', max(0, (int) $record->depth - 1)).$record->name)
                    ->description(fn (Category $record) => (int) $record->depth > 1 ? 'trong '.self::parentPath($record) : null),
                TextColumn::make('depth')->label('Cấp')->badge()->formatStateUsing(fn ($state) => 'Cấp '.$state)
                    ->color(fn ($state) => ['1' => 'primary', '2' => 'info', '3' => 'success', '4' => 'gray'][(string) $state] ?? 'gray'),
                TextColumn::make('children_count')->label('Danh mục con')->counts('children'),
                TextColumn::make('products_count')->label('SP trực tiếp')->counts('products')->sortable()->toggleable(),
                TextColumn::make('branch_products')->label('SP cả nhánh')
                    ->state(fn (Category $record) => DB::table('products')->whereIn('category_id', app(CategoryTree::class)->descendantIds((int) $record->getKey()))->where('is_visible', true)->count())
                    ->tooltip('Sản phẩm đang hiện trong danh mục này và mọi danh mục con. 0 nghĩa là trang danh mục chưa hiện trên website.'),
                TextColumn::make('slug')->label('Đường dẫn')->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->toggleable(),
            ])
            ->filters([
                SelectFilter::make('branch')->label('Nhánh cấp 1')
                    ->options(fn () => DB::table('categories')->whereNull('parent_id')->orderBy('sort_order')->orderByDesc('id')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data) => filled($data['value'] ?? null)
                        ? $query->whereIn('categories.id', app(CategoryTree::class)->descendantIds((int) $data['value'])) : $query),
                SelectFilter::make('depth')->label('Cấp')->options([1 => 'Cấp 1', 2 => 'Cấp 2', 3 => 'Cấp 3', 4 => 'Cấp 4']),
                TernaryFilter::make('is_visible')->label('Hiển thị'),
            ])
            ->recordActions([
                PublicLink::action()->iconButton(),
                Action::make('addChild')->label('Thêm danh mục con')->tooltip('Thêm danh mục con')->iconButton()->icon(Heroicon::OutlinedPlus)->color('gray')
                    ->visible(fn (Category $record) => (int) $record->depth < 4)
                    ->url(fn (Category $record) => self::getUrl('create', ['parent_id' => $record->getKey()])),
                EditAction::make(),
            ]);
    }

    /** Category ids depth-first, children in the menu order (sort_order, newest first on ties). @return list<int> */
    public static function treeOrder(): array
    {
        $tree = app(CategoryTree::class);
        $order = [];
        $walk = function (int $parent) use (&$walk, &$order, $tree) {
            foreach ($tree->children($parent) as $node) {
                $order[] = (int) $node['id'];
                $walk((int) $node['id']);
            }
        };
        $walk(0);

        return $order;
    }

    private static function parentPath(Category $category): string
    {
        $chain = app(CategoryTree::class)->chain((int) $category->getKey());
        array_pop($chain);

        return implode(' › ', array_map(fn ($node) => $node['name'], $chain));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCategoryTree::route('/'),
            'table' => Pages\ListCategories::route('/bang'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    /** Depth follows the parent; a top category's slug is a public address. */
    public static function withDepth(array $data): array
    {
        $parentDepth = $data['parent_id'] ? (int) DB::table('categories')->where('id', $data['parent_id'])->value('depth') : 0;
        $data['depth'] = $parentDepth + 1;

        return $data;
    }
}
