<?php

namespace App\Filament\Resources\PostCategories;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Filament\Support\PublicStatusColumn;
use App\Models\PostCategory;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
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
            Fields::publicSlug('post_categories'),
            Fields::image('image', 'news'),
            Textarea::make('summary')->label('Mô tả')->rows(3)->columnSpanFull(),
            Fields::visible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')->reorderable('sort_order')
            ->columns([
                TextColumn::make('name')->label('Nhóm')->searchable(),
                TextColumn::make('kind')->label('Dùng cho')->formatStateUsing(fn ($state) => self::KINDS[$state] ?? $state)->badge(),
                TextColumn::make('posts_count')->label('Số bài')->counts('posts'),
                ToggleColumn::make('is_visible')->label('Hiển thị'),
                PublicStatusColumn::make(besideSwitch: true),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->filters([SelectFilter::make('kind')->label('Dùng cho')->options(self::KINDS)])
            // Delete stays on every row, greyed out when the group still holds posts: hiding it made the
            // buttons sit at a different place on each row, and the reason it cannot be used was invisible.
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->disabled(fn (PostCategory $record) => $record->posts()->exists())
                    ->tooltip(fn (PostCategory $record) => $record->posts()->exists() ? 'Nhóm còn bài viết nên chưa xóa được' : null),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePostCategories::route('/')];
    }
}
