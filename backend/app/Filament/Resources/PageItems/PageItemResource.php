<?php

namespace App\Filament\Resources\PageItems;

use App\Filament\Concerns\RequiresPermission;
use App\Filament\Support\Fields;
use App\Models\PageItem;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use UnitEnum;

/** Small editable blocks of the fixed pages: home steps, about history, dealer benefits, page intros and CTAs. */
class PageItemResource extends Resource
{
    use RequiresPermission;

    protected static string $permission = 'content';

    protected static ?string $model = PageItem::class;

    protected static ?string $modelLabel = 'mục nội dung';

    protected static ?string $pluralModelLabel = 'Nội dung các trang';

    protected static string|UnitEnum|null $navigationGroup = 'Nội dung';

    protected static ?int $navigationSort = 6;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    /** section => [label, fields shown]; sections not listed show every field. */
    public const SECTIONS = [
        'home.process' => ['Trang chủ · Quy trình làm việc', ['title', 'summary', 'figure', 'image']],
        'home.about' => ['Trang chủ · Giới thiệu ngắn', ['title', 'summary', 'body', 'image', 'link']],
        'home.about_points' => ['Trang chủ · Điểm nổi bật giới thiệu', ['title', 'summary', 'image']],
        'home.energy_chain' => ['Trang chủ · Chuỗi năng lượng', ['title', 'subtitle', 'summary', 'figure', 'image']],
        'about.history' => ['Giới thiệu · Lịch sử', ['title', 'body', 'image']],
        'about.compass' => ['Giới thiệu · Sứ mệnh, tầm nhìn', ['title', 'body', 'image']],
        'about.reasons' => ['Giới thiệu · Lý do chọn chúng tôi', ['title', 'summary', 'image']],
        'about.principles' => ['Giới thiệu · Nguyên tắc', ['title', 'summary', 'image']],
        'about.team' => ['Giới thiệu · Đội ngũ', ['title', 'summary', 'body', 'image']],
        'contact.intro' => ['Liên hệ · Lời giới thiệu', ['title', 'body']],
        'dealer.intro' => ['Đại lý · Phần mở đầu', ['title', 'summary', 'image']],
        'dealer.overview' => ['Đại lý · Tổng quan', ['title', 'summary']],
        'dealer.benefits' => ['Đại lý · Quyền lợi', ['title', 'summary', 'image']],
        'dealer.stats' => ['Đại lý · Số liệu', ['title', 'figure']],
        'dealer.support' => ['Đại lý · Hỗ trợ', ['title', 'summary']],
        'dealer.testimonials' => ['Đại lý · Phản hồi đại lý', ['title', 'summary', 'body', 'image']],
        'product.commitments' => ['Trang sản phẩm · Cam kết', ['title', 'image']],
        'solutions.intro' => ['Giải pháp · Phần mở đầu', ['title', 'subtitle', 'summary', 'body', 'image']],
        'solutions.cta' => ['Giải pháp · Lời mời tư vấn', ['title', 'subtitle', 'summary', 'extra', 'image']],
        'news.intro' => ['Tin tức · Phần mở đầu', ['title', 'summary']],
        'news.cta' => ['Tin tức · Lời mời tư vấn', ['title', 'subtitle', 'summary', 'extra', 'image']],
        'projects.intro' => ['Dự án · Phần mở đầu', ['title', 'summary']],
        'projects.benefits' => ['Dự án · Lợi ích', ['title', 'summary', 'image']],
        'projects.trust' => ['Dự án · Cam kết', ['title', 'image']],
        'projects.cta' => ['Dự án · Lời mời tư vấn', ['title', 'subtitle', 'summary', 'extra', 'image']],
        'site.footer' => ['Chân trang · Giới thiệu công ty', ['title', 'body']],
    ];

    private const FIELD_LABELS = ['title' => 'Tiêu đề', 'subtitle' => 'Tiêu đề phụ', 'summary' => 'Mô tả ngắn', 'body' => 'Nội dung',
        'extra' => 'Chữ trên nút / nội dung thêm', 'figure' => 'Con số', 'image' => 'Ảnh', 'link' => 'Liên kết'];

    /** @return array<string, string> */
    public static function sectionOptions(): array
    {
        $options = array_map(fn ($section) => $section[0], self::SECTIONS);
        foreach (PageItem::query()->distinct()->pluck('section') as $section) {
            $options[$section] ??= str_starts_with($section, 'archive.') ? 'Lưu trữ (không hiển thị) · '.$section : $section;
        }

        return $options;
    }

    private static function shows(string $field): \Closure
    {
        return fn (Get $get) => in_array($field, self::SECTIONS[$get('section')][1] ?? array_keys(self::FIELD_LABELS), true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('section')->label('Thuộc phần')->options(fn () => self::sectionOptions())->required()->searchable()->live()->columnSpanFull(),
            TextInput::make('title')->label(self::FIELD_LABELS['title'])->visible(self::shows('title')),
            TextInput::make('subtitle')->label(self::FIELD_LABELS['subtitle'])->visible(self::shows('subtitle')),
            TextInput::make('figure')->label(self::FIELD_LABELS['figure'])->visible(self::shows('figure')),
            TextInput::make('link')->label(self::FIELD_LABELS['link'])->visible(self::shows('link')),
            TextInput::make('extra')->label(self::FIELD_LABELS['extra'])->visible(self::shows('extra')),
            Textarea::make('summary')->label(self::FIELD_LABELS['summary'])->rows(3)->columnSpanFull()->visible(self::shows('summary')),
            Fields::rich('body', self::FIELD_LABELS['body'])->columnSpanFull()->visible(self::shows('body')),
            Fields::image('image', 'news')->visible(self::shows('image')),
            Fields::visible(),
            Fields::sort(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('section')
            ->defaultGroup('section')
            ->columns([
                ImageColumn::make('image')->label('Ảnh')->disk('upload_news')->height(36),
                TextColumn::make('title')->label('Tiêu đề')->searchable()->limit(60)->placeholder('—'),
                TextColumn::make('section')->label('Phần')->formatStateUsing(fn ($state) => self::sectionOptions()[$state] ?? $state)->toggleable(),
                IconColumn::make('is_visible')->label('Hiển thị')->boolean(),
                TextColumn::make('sort_order')->label('Thứ tự')->sortable(),
            ])
            ->groups([Group::make('section')->label('Phần')->getTitleFromRecordUsing(fn (PageItem $record) => self::sectionOptions()[$record->section] ?? $record->section)])
            ->filters([SelectFilter::make('section')->label('Phần')->options(fn () => self::sectionOptions())->searchable()])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ManagePageItems::route('/')];
    }
}
