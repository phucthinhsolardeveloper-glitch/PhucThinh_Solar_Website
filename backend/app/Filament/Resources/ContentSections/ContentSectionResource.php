<?php

namespace App\Filament\Resources\ContentSections;

use App\Domain\Content\SectionHistory;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Resources\Brands\BrandResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Support\Options;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ContentSection;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/** Every description block set (products, brands, categories) in one list, with history and restore. */
class ContentSectionResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'catalog';

    protected static ?string $model = ContentSection::class;

    protected static ?string $modelLabel = 'khối mô tả';

    protected static ?string $pluralModelLabel = 'Khối mô tả';

    protected static string|UnitEnum|null $navigationGroup = 'Sản phẩm';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    public const OWNERS = [Product::class => 'Sản phẩm', Brand::class => 'Thương hiệu', Category::class => 'Danh mục'];

    public const MODES = ['own' => 'Khối riêng', 'inherit' => 'Kế thừa', 'both' => 'Riêng + kế thừa', 'hide' => 'Ẩn khối mô tả'];

    public const REASONS = ['update' => 'Trước khi sửa', 'delete' => 'Trước khi xóa', 'restore' => 'Trước khi khôi phục', 'import' => 'Trước khi nhập'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('owner_type')->label('Thuộc')->badge()->color('gray')->formatStateUsing(fn ($state) => self::OWNERS[$state] ?? $state),
                TextColumn::make('owner_name')->label('Tên')->state(fn (ContentSection $record) => self::ownerName($record))
                    ->url(fn (ContentSection $record) => self::ownerUrl($record)),
                TextColumn::make('mode')->label('Cách hiển thị')->formatStateUsing(fn ($state) => self::MODES[$state] ?? $state),
                TextColumn::make('block_count')->label('Số khối')->state(fn (ContentSection $record) => count(json_decode((string) $record->getRawOriginal('blocks'), true) ?: [])),
                ToggleColumn::make('is_visible')->label('Hiện'),
                TextColumn::make('updated_at')->label('Cập nhật')->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('owner_type')->label('Thuộc')->options(self::OWNERS),
                SelectFilter::make('mode')->label('Cách hiển thị')->options(self::MODES),
            ])
            ->recordActions([
                Action::make('history')->label('Lịch sử')->icon(Heroicon::OutlinedClock)
                    ->modalHeading(fn (ContentSection $record) => 'Khôi phục khối mô tả: '.self::ownerName($record))
                    ->modalDescription('Bản hiện tại được giữ lại trong lịch sử trước khi khôi phục.')
                    ->modalSubmitActionLabel('Khôi phục')
                    ->schema(fn (ContentSection $record) => [
                        Select::make('version')->label('Phiên bản')->required()->native(false)
                            ->options(self::versions((string) $record->owner_type, (int) $record->owner_id))
                            ->helperText(fn () => self::versions((string) $record->owner_type, (int) $record->owner_id) === [] ? 'Chưa có phiên bản cũ.' : null),
                    ])
                    ->action(function (array $data): void {
                        SectionHistory::restore((int) $data['version']);
                        Notification::make()->success()->title('Đã khôi phục khối mô tả')->send();
                    }),
                DeleteAction::make()->modalDescription('Khối mô tả được giữ trong lịch sử và khôi phục lại được.'),
            ]);
    }

    /** @return array<int, string> stored versions of one owner, newest first */
    public static function versions(string $ownerType, int $ownerId): array
    {
        return DB::table('content_section_versions as v')->leftJoin('users as u', 'u.id', '=', 'v.created_by')
            ->where('v.owner_type', $ownerType)->where('v.owner_id', $ownerId)->orderByDesc('v.id')
            ->get(['v.id', 'v.created_at', 'v.reason', 'v.blocks', 'v.mode', 'u.name'])
            ->mapWithKeys(fn ($v) => [(int) $v->id => date('H:i d/m/Y', strtotime((string) $v->created_at)).' · '.(self::REASONS[$v->reason] ?? $v->reason)
                .' · '.count(json_decode((string) $v->blocks, true) ?: []).' khối · '.(self::MODES[$v->mode] ?? $v->mode).($v->name ? ' · '.$v->name : '')])->all();
    }

    public static function ownerName(ContentSection $record): string
    {
        return match ($record->owner_type) {
            Category::class => Options::categoryPaths()[$record->owner_id] ?? '#'.$record->owner_id,
            Brand::class => (string) (Brand::query()->whereKey($record->owner_id)->value('name') ?? '#'.$record->owner_id),
            default => (string) (Product::query()->whereKey($record->owner_id)->value('name') ?? '#'.$record->owner_id),
        };
    }

    public static function ownerUrl(ContentSection $record): ?string
    {
        $resource = [Product::class => ProductResource::class, Brand::class => BrandResource::class, Category::class => CategoryResource::class][$record->owner_type] ?? null;

        return $resource ? $resource::getUrl('edit', ['record' => $record->owner_id]) : null;
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageContentSections::route('/')];
    }
}
