<?php

namespace App\Filament\Resources\PostCategories;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Models\PostCategory;
use App\Rules\FreePublicSlug;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

/** Groups of articles, solutions and projects; each answers on "/<slug>" as a listing page. */
class PostCategoryResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = PostCategory::class;

    protected static ?string $modelLabel = 'nhóm bài';

    protected static ?string $pluralModelLabel = 'Nhóm bài viết, giải pháp, dự án';

    protected static ?string $navigationLabel = 'Nhóm bài';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 4;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public const KINDS = ['article' => 'Bài viết', 'solution' => 'Giải pháp', 'project' => 'Dự án'];

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('kind')->label('Dùng cho')->options(self::KINDS)->required()->default('article'),
            Fields::title(),
            Fields::slug()->rules([fn (?Model $record) => new FreePublicSlug('post_categories', $record?->getKey())]),
            Fields::image('image', 'news'),
            Textarea::make('summary')->label('Mô tả')->rows(3)->columnSpanFull(),
            Fields::visible(),
            Fields::sort(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nhóm')->searchable(),
                TextColumn::make('kind')->label('Dùng cho')->formatStateUsing(fn ($state) => self::KINDS[$state] ?? $state)->badge(),
                TextColumn::make('posts_count')->label('Số bài')->counts('posts'),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([SelectFilter::make('kind')->label('Dùng cho')->options(self::KINDS)])
            ->recordActions([EditAction::make(), DeleteAction::make()->hidden(fn (PostCategory $record) => $record->posts()->exists())]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePostCategories::route('/')];
    }
}
