<?php

namespace App\Filament\Resources\AuditEvents;

use App\Models\AuditEvent;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Who changed orders, payments, prices, specs, SEO, settings and permissions, and who deleted what. Owners only. */
class AuditEventResource extends Resource
{
    protected static ?string $model = AuditEvent::class;

    protected static ?string $modelLabel = 'nhật ký thay đổi';

    protected static ?string $pluralModelLabel = 'Nhật ký thay đổi';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 10;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->isOwner();
    }

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

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextEntry::make('created_at')->label('Lúc')->dateTime('H:i:s d/m/Y'),
            TextEntry::make('actor_label')->label('Người thực hiện'),
            TextEntry::make('action')->label('Thao tác'),
            TextEntry::make('entity_type')->label('Đối tượng')->formatStateUsing(fn (AuditEvent $record) => $record->entity_type.($record->entity_id ? ' #'.$record->entity_id : '')),
            TextEntry::make('before_lines')->label('Trước')->listWithLineBreaks()->placeholder('—')
                ->state(fn (AuditEvent $record) => self::lines($record->before)),
            TextEntry::make('after_lines')->label('Sau')->listWithLineBreaks()->placeholder('—')
                ->state(fn (AuditEvent $record) => self::lines($record->after)),
        ]);
    }

    /** @return list<string> "field: value" lines, nested values shown as JSON */
    private static function lines(?array $values): array
    {
        return array_map(fn ($key) => $key.': '.(is_scalar($values[$key]) || $values[$key] === null ? (string) $values[$key] : json_encode($values[$key], JSON_UNESCAPED_UNICODE)),
            array_keys($values ?? []));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('created_at')->label('Lúc')->dateTime('H:i d/m/Y')->sortable(),
                TextColumn::make('actor_label')->label('Người thực hiện')->searchable(),
                TextColumn::make('action')->label('Thao tác')->badge()
                    ->color(fn (string $state) => str_contains($state, 'delete') ? 'danger' : (str_starts_with($state, 'payment') ? 'warning' : 'gray')),
                TextColumn::make('entity_type')->label('Đối tượng')->formatStateUsing(fn (AuditEvent $record) => $record->entity_type.($record->entity_id ? ' #'.$record->entity_id : '')),
                TextColumn::make('ip')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('entity_type')->label('Đối tượng')->options(fn () => AuditEvent::query()->distinct()->pluck('entity_type', 'entity_type')->all()),
                SelectFilter::make('action')->label('Thao tác')->options(fn () => AuditEvent::query()->distinct()->pluck('action', 'action')->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListAuditEvents::route('/')];
    }
}
