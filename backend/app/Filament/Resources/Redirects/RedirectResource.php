<?php

namespace App\Filament\Resources\Redirects;

use App\Domain\Routing\Redirects;
use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\PublicLink;
use App\Models\Redirect;
use BackedEnum;
use Closure;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/** Old links that now open another page. Renamed pages add themselves; others can be added by hand. */
class RedirectResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'settings';

    protected static ?string $model = Redirect::class;

    protected static ?string $modelLabel = 'chuyển hướng';

    protected static ?string $pluralModelLabel = 'Chuyển hướng link';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUturnRight;

    public static function form(Schema $schema): Schema
    {
        $path = fn (string $attribute, mixed $value, Closure $fail) => Redirects::normalise((string) $value) === null
            ? $fail('Nhập đường dẫn trong website, bắt đầu bằng "/", ví dụ /san-pham/ten-cu') : null;

        return $schema->columns(1)->components([
            TextInput::make('from_path')->label('Link cũ')->required()->maxLength(500)->rules([fn () => $path])->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn ($state) => Redirects::normalise((string) $state)),
            TextInput::make('to_path')->label('Chuyển tới')->required()->maxLength(500)->rules([fn () => $path])->different('from_path')
                ->dehydrateStateUsing(fn ($state) => Redirects::normalise((string) $state)),
            Toggle::make('is_prefix')->label('Chuyển cả các trang nằm dưới link cũ'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('from_path')->label('Link cũ')->searchable(),
                TextColumn::make('to_path')->label('Chuyển tới')->searchable(),
                TextColumn::make('source')->label('Tạo bởi')->badge()->color('gray')
                    ->formatStateUsing(fn ($state) => $state === 'auto' ? 'Tự động khi đổi đường dẫn' : 'Thêm tay'),
                TextColumn::make('hits')->label('Lượt chuyển')->sortable()
                    ->description(fn (Redirect $record) => $record->last_hit_at ? 'gần nhất '.$record->last_hit_at->format('d/m/Y') : null),
            ])
            ->filters([SelectFilter::make('source')->label('Tạo bởi')->options(['auto' => 'Tự động', 'manual' => 'Thêm tay'])])
            ->recordActions([PublicLink::action(), EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageRedirects::route('/')];
    }
}
