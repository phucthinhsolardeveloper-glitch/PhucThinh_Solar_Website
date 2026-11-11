<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\PublicLink;
use App\Models\Banner;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/** Pictures with a fixed place on the site: logo, home slides, popup, certificates, dealer photos. */
class BannerResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'media';

    protected static ?string $model = Banner::class;

    protected static ?string $modelLabel = 'ảnh vị trí';

    protected static ?string $pluralModelLabel = 'Ảnh theo vị trí';

    protected static string|UnitEnum|null $navigationGroup = 'Hình ảnh & video';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    public const PLACEMENTS = [
        'logo' => 'Logo đầu trang', 'logo-footer' => 'Logo chân trang', 'favicon' => 'Biểu tượng trên tab trình duyệt',
        'hero-carousel' => 'Trang chủ · Ảnh đầu trang (chạy vòng)', 'popup' => 'Trang chủ · Popup quảng cáo',
        'home-campaign' => 'Trang chủ · Chiến dịch',
        'chungnhan' => 'Giới thiệu · Chứng nhận', 'adv' => 'Giới thiệu · Ảnh quảng bá', 'hinh-anh-dai-ly' => 'Đại lý · Hình ảnh',
        'bct' => 'Chân trang · Logo đã thông báo Bộ Công Thương',
        'about-cta' => 'Giới thiệu · Lời mời tư vấn', 'about-showroom' => 'Giới thiệu · Showroom',
        'contact-visual' => 'Liên hệ · Ảnh giới thiệu', 'dealer-hero' => 'Đại lý · Ảnh đầu trang',
        'product-category-hero' => 'Danh mục · Ảnh đầu trang', 'catalog-campaign' => 'Danh mục · Chiến dịch',
    ];

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('placement', array_keys(self::PLACEMENTS));
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('placement')->label('Vị trí')->options(self::PLACEMENTS)->required()->searchable()->live()->columnSpanFull(),
            TextInput::make('title')->label('Tiêu đề / chữ thay thế ảnh'),
            TextInput::make('link')->label('Liên kết khi bấm')->placeholder('/san-pham/ hoặc https://…'),
            Textarea::make('summary')->label('Mô tả')->rows(2)->columnSpanFull(),
            Fields::image('image', 'photo')->columnSpanFull(),
            TextInput::make('video_url')->label('Video YouTube')->url(),
            Toggle::make('is_featured')->label('Nổi bật'),
            KeyValue::make('options')->label('Chữ trên ảnh đầu trang')->keyLabel('Mục')->valueLabel('Nội dung')
                ->helperText('titleLine2: dòng tiêu đề thứ hai; ctaLabel / cta2Label / cta2Href: chữ và liên kết hai nút; theme: light hoặc dark; tags: các thẻ, cách nhau bằng dấu phẩy.')
                ->visible(fn (Get $get) => $get('placement') === 'hero-carousel')->columnSpanFull(),
            Fields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->defaultGroup('placement')
            ->groups([Group::make('placement')->label('Vị trí')->getTitleFromRecordUsing(fn (Banner $record) => self::PLACEMENTS[$record->placement] ?? $record->placement)])
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_photo')->height(48),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(50)->placeholder('—'),
                ToggleColumn::make('is_visible')->label('Hiển thị'),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([SelectFilter::make('placement')->label('Vị trí')->options(self::PLACEMENTS)])
            ->recordActions([PublicLink::action(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageBanners::route('/')];
    }
}
