<?php

namespace App\Filament\Resources\SeoPages;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\PublicLink;
use App\Models\SeoMeta;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Title, description and share picture of the fixed pages (home, catalog, news hub…). */
class SeoPageResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'settings';

    protected static ?string $model = SeoMeta::class;

    protected static ?string $modelLabel = 'SEO trang';

    protected static ?string $pluralModelLabel = 'SEO các trang chính';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public const PAGES = ['home' => 'Trang chủ', 'gioi-thieu' => 'Giới thiệu', 'san-pham' => 'Sản phẩm', 'giai-phap' => 'Giải pháp',
        'du-an' => 'Dự án', 'tin-tuc' => 'Tin tức', 'thu-vien-anh' => 'Thư viện ảnh', 'video' => 'Video', 'dai-ly' => 'Đại lý', 'lien-he' => 'Liên hệ'];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('page');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Tiêu đề SEO')->maxLength(255),
            Textarea::make('description')->label('Mô tả SEO')->rows(3)->maxLength(500),
            TextInput::make('keywords')->label('Từ khóa'),
            Fields::image('image', 'seopage', 'Ảnh chia sẻ'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page')->label('Trang')->formatStateUsing(fn ($state) => self::PAGES[$state] ?? $state),
                TextColumn::make('title')->label('Tiêu đề SEO')->limit(60),
                TextColumn::make('description')->label('Mô tả')->limit(60)->toggleable(),
            ])
            ->recordActions([PublicLink::row(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageSeoPages::route('/')];
    }
}
