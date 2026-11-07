<?php

namespace App\Filament\Resources\Albums;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Models\Album;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class AlbumResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'media';

    protected static ?string $model = Album::class;

    protected static ?string $modelLabel = 'album ảnh';

    protected static ?string $pluralModelLabel = 'Thư viện ảnh';

    protected static string|UnitEnum|null $navigationGroup = 'Hình ảnh & video';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->columns(2)->schema([
                Fields::title('title', 'Tên album'),
                Fields::publicSlug('albums'),
                Textarea::make('summary')->label('Mô tả')->rows(3)->columnSpanFull(),
                Fields::image('image', 'product', 'Ảnh đại diện'),
                Section::make()->schema([Fields::visible(), Fields::sort()]),
            ]),
            Repeater::make('images')->label('Ảnh trong album')->relationship()->orderColumn('sort_order')->reorderable()
                ->schema([Fields::image('path', 'product')->required(), TextInput::make('caption')->label('Chú thích'), Fields::visible('Hiển thị')])
                ->grid(3)->collapsible()->defaultItems(0)->addActionLabel('Thêm ảnh'),
            Fields::seo('product'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_product')->height(48),
                TextColumn::make('title')->label('Album')->searchable(),
                TextColumn::make('images_count')->label('Số ảnh')->counts('images'),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlbums::route('/'),
            'create' => Pages\CreateAlbum::route('/create'),
            'edit' => Pages\EditAlbum::route('/{record}/edit'),
        ];
    }
}
