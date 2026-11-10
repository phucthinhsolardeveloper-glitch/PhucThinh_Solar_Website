<?php

namespace App\Filament\Resources\Videos;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\PublicLink;
use App\Models\Video;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VideoResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'media';

    protected static ?string $model = Video::class;

    protected static ?string $modelLabel = 'video';

    protected static ?string $pluralModelLabel = 'Video';

    protected static string|UnitEnum|null $navigationGroup = 'Hình ảnh & video';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPlayCircle;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('title')->label('Tên video')->required(),
            TextInput::make('url')->label('Link YouTube')->url()->required()
                ->rule('regex:#^https://(www\.)?(youtube\.com|youtu\.be)/#')->validationMessages(['regex' => 'Dán link video YouTube.']),
            Fields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->columns([
                TextColumn::make('title')->label('Video')->searchable(),
                TextColumn::make('url')->label('Link')->limit(40)->url(fn (Video $record) => $record->url, true),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->recordActions([PublicLink::action(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageVideos::route('/')];
    }
}
