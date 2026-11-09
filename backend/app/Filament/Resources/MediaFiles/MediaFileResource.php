<?php

namespace App\Filament\Resources\MediaFiles;

use App\Filament\Concerns\RequiresPermission;
use App\Models\MediaFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;
use UnitEnum;

/**
 * Every uploaded file with its size, type, dimensions and the pages using it, plus links pointing
 * at files that no longer exist. Read-only: files are never deleted from here.
 */
class MediaFileResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'media';

    protected static ?string $model = MediaFile::class;

    protected static ?string $modelLabel = 'tệp media';

    protected static ?string $pluralModelLabel = 'Kho media';

    protected static string|UnitEnum|null $navigationGroup = 'Hình ảnh & video';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('size', 'desc')
            ->columns([
                ImageColumn::make('preview')->label('Ảnh')->height(44)
                    ->state(fn (MediaFile $record) => ($preview = $record->previewUrl()) ? url($preview) : null),
                TextColumn::make('path')->label('Đường dẫn')->searchable()->limit(60)->tooltip(fn (MediaFile $record) => $record->path)
                    ->url(fn (MediaFile $record) => $record->on_disk ? $record->url() : null, true),
                TextColumn::make('size')->label('Dung lượng')->sortable()->formatStateUsing(fn ($state) => $state ? Number::fileSize((int) $state) : '—'),
                TextColumn::make('dimensions')->label('Kích thước')
                    ->state(fn (MediaFile $record) => $record->width ? $record->width.'×'.$record->height : '—'),
                TextColumn::make('usage_count')->label('Đang dùng')->sortable()->badge()
                    ->color(fn (MediaFile $record) => ! $record->on_disk ? 'danger' : ($record->usage_count === 0 ? 'gray' : 'success'))
                    ->tooltip(fn (MediaFile $record) => implode("\n", $record->usages ?? [])),
                IconColumn::make('on_disk')->label('Có tệp')->boolean(),
                TextColumn::make('mime')->label('Loại')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('modified_at')->label('Sửa lúc')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('folder')->label('Thư mục')->options(fn () => MediaFile::query()->distinct()->orderBy('folder')->pluck('folder', 'folder')->all()),
                TernaryFilter::make('used')->label('Đang dùng')
                    ->queries(true: fn (Builder $q) => $q->where('usage_count', '>', 0), false: fn (Builder $q) => $q->where('usage_count', 0)->where('on_disk', true)),
                TernaryFilter::make('on_disk')->label('Có tệp')->trueLabel('Có tệp')->falseLabel('Bị mất (link hỏng)'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListMediaFiles::route('/')];
    }
}
