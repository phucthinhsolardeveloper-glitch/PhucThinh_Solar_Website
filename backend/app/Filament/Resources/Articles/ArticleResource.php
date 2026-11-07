<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Support\PostResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class ArticleResource extends PostResource
{
    protected static string $kind = 'article';

    protected static ?string $modelLabel = 'bài viết';

    protected static ?string $pluralModelLabel = 'Bài viết';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
