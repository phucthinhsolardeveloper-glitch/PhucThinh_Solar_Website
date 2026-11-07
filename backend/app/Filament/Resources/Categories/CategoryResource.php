<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\Options;
use App\Models\Category;
use App\Rules\FreePublicSlug;
use BackedEnum;
use Closure;
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
                Fields::slug()->rules([fn (Get $get, ?Model $record) => self::slugRule($get('parent_id'), $record)]),
                Select::make('parent_id')->label('Thuộc danh mục')->placeholder('Danh mục cấp 1')
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
        return $table
            ->defaultSort('depth')
            ->columns([
                TextColumn::make('name')->label('Danh mục')->searchable()
                    ->formatStateUsing(fn (Category $record) => Options::categoryLabel($record)),
                TextColumn::make('slug')->label('Đường dẫn')->toggleable(),
                TextColumn::make('products_count')->label('Sản phẩm')->counts('products')->sortable(),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([
                SelectFilter::make('depth')->label('Cấp')->options([1 => 'Cấp 1', 2 => 'Cấp 2', 3 => 'Cấp 3', 4 => 'Cấp 4']),
                TernaryFilter::make('is_visible')->label('Hiển thị'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
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

    private static function slugRule(mixed $parentId, ?Model $record): Closure|FreePublicSlug
    {
        if (! $parentId) {
            return new FreePublicSlug('categories', $record?->getKey());
        }

        return function (string $attribute, mixed $value, Closure $fail) use ($parentId, $record) {
            $taken = DB::table('categories')->where('parent_id', $parentId)->where('slug', $value)
                ->when($record, fn ($q) => $q->where('id', '!=', $record->getKey()))->exists();
            if ($taken) {
                $fail('Danh mục cùng cấp đã có đường dẫn này.');
            }
        };
    }
}
