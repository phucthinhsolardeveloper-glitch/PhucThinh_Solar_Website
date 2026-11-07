<?php

namespace App\Filament\Resources\Users;

use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Admin accounts. Only owners manage them, and an owner cannot lock themselves out. */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }

    protected static ?string $modelLabel = 'tài khoản quản trị';

    protected static ?string $pluralModelLabel = 'Tài khoản quản trị';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 9;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    public static function canViewAny(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->isOwner();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    private static function isSelf(?Model $record): bool
    {
        return $record !== null && $record->getKey() === auth('admin')->id();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('name')->label('Họ tên')->required(),
            TextInput::make('username')->label('Tên đăng nhập')->required()->unique(ignoreRecord: true)->alphaDash(),
            TextInput::make('email')->label('Email')->email(),
            TextInput::make('phone')->label('Điện thoại'),
            TextInput::make('password')->label('Mật khẩu')->password()->revealable()
                ->required(fn (string $operation) => $operation === 'create')->minLength(8)
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Để trống nếu không đổi mật khẩu.' : null),
            Select::make('role')->label('Vai trò')->options([User::ROLE_OWNER => 'Chủ website (toàn quyền)', User::ROLE_EDITOR => 'Biên tập viên (theo quyền)'])
                ->default(User::ROLE_EDITOR)->required()->live()->disabled(fn (?Model $record) => self::isSelf($record)),
            CheckboxList::make('permissions')->label('Được quản lý')->options(User::PERMISSIONS)->columns(2)->columnSpanFull()
                ->visible(fn (Get $get) => $get('role') === User::ROLE_EDITOR),
            Toggle::make('is_active')->label('Đang hoạt động')->default(true)->disabled(fn (?Model $record) => self::isSelf($record)),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Họ tên')->searchable(),
                TextColumn::make('username')->label('Tên đăng nhập')->searchable(),
                TextColumn::make('role')->label('Vai trò')->badge()->formatStateUsing(fn ($state) => $state === User::ROLE_OWNER ? 'Chủ website' : 'Biên tập viên'),
                TextColumn::make('permissions')->label('Được quản lý')->badge()->separator(',')
                    ->formatStateUsing(fn ($state) => User::PERMISSIONS[$state] ?? $state)->toggleable(),
                IconColumn::make('is_active')->label('Hoạt động')->boolean(),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManageUsers::route('/')];
    }
}
