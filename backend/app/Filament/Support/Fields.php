<?php

namespace App\Filament\Support;

use App\Models\Province;
use App\Models\Ward;
use App\Support\Slugs;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** Form pieces shared by the Admin resources, so every screen names and stores things the same way. */
final class Fields
{
    /** Name field. While a record is being created the slug follows it, until someone writes their own slug. */
    public static function title(string $name = 'name', string $label = 'Tên', string $slugField = 'slug'): TextInput
    {
        return TextInput::make($name)->label($label)->required()->maxLength(255)
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $state, ?string $old, string $operation) use ($slugField) {
                $slug = (string) $get($slugField);
                if ($operation === 'create' && ($slug === '' || $slug === Slugs::from($old))) {
                    $set($slugField, Slugs::from($state));
                }
            });
    }

    /** Slug of a page answering on "/<slug>": free across every table sharing that space. */
    public static function publicSlug(string $table, string $from = 'name'): TextInput
    {
        return self::slug($from, fn (?Model $record) => Slugs::publicScope($table, $record?->getKey()));
    }

    /**
     * Address of a page. Left empty it is made from the name; a taken one gets "-2", "-3"... when
     * generated, or an error with a free suggestion when typed. Changing it on a public page keeps the
     * old link working (see RedirectsOldSlug).
     *
     * @param  (Closure(?Model, Get): (Closure(string): bool))|null  $scope  which slugs are taken
     */
    public static function slug(string $from = 'name', ?Closure $scope = null): TextInput
    {
        $taken = fn (?Model $record, Get $get): Closure => $scope ? $scope($record, $get) : fn (string $slug) => false;

        return TextInput::make('slug')->label('Đường dẫn')->maxLength(200)
            ->placeholder('Tự tạo từ tên')
            ->helperText(fn (string $operation) => $operation === 'create'
                ? 'Tự tạo từ tên; sửa nếu muốn đường dẫn ngắn hơn.'
                : 'Đổi đường dẫn thì link cũ tự chuyển sang link mới.')
            ->suffixAction(Action::make('slugFromName')->icon('heroicon-m-arrow-path')->tooltip('Tạo lại từ tên')
                ->action(fn (Get $get, Set $set, ?Model $record) => $set('slug', Slugs::free((string) $get($from), $taken($record, $get)))))
            ->rules([fn (?Model $record, Get $get) => function (string $attribute, mixed $value, Closure $fail) use ($taken, $record, $get) {
                $value = (string) $value;
                if ($value === '') {
                    return;
                }
                if (! preg_match(Slugs::PATTERN, $value)) {
                    $fail('Chỉ dùng chữ thường không dấu, số và dấu gạch ngang. Gợi ý: '.Slugs::from($value));
                } elseif ($taken($record, $get)($value)) {
                    $fail('Đã có trang khác dùng đường dẫn này. Gợi ý: '.Slugs::free($value, $taken($record, $get)));
                }
            }])
            ->dehydrateStateUsing(fn (?string $state, Get $get, ?Model $record) => filled($state)
                ? $state
                : Slugs::free((string) $get($from), $taken($record, $get)));
    }

    /**
     * A picture stored as a bare file name in one upload folder (product, news, photo, seopage).
     * Names get a random suffix so a new upload never replaces a file another page still shows.
     */
    public static function image(string $name, string $folder, string $label = 'Ảnh'): FileUpload
    {
        return FileUpload::make($name)->label($label)
            ->disk('upload_'.$folder)->visibility('public')
            ->image()->maxSize(15 * 1024)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->getUploadedFileNameForStorageUsing(fn ($file) => Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'-'.Str::lower(Str::random(6)).'.'.strtolower($file->getClientOriginalExtension()))
            ->imagePreviewHeight('160')
            ->openable();
    }

    /**
     * A JSON list of sentences edited one per line. The state may arrive as the stored list (nested
     * in a Builder the formatter is not always run first) or as the edited text.
     */
    public static function lines(string $name, string $label): Textarea
    {
        $toText = fn ($state) => is_array($state) ? implode("\n", array_map('strval', $state)) : (string) $state;

        return Textarea::make($name)->label($label)->rows(5)->helperText('Mỗi dòng một ý.')
            ->formatStateUsing($toText)
            ->afterStateHydrated(fn (Textarea $component, $state) => $component->state($toText($state)))
            ->dehydrateStateUsing(fn ($state) => array_values(array_filter(array_map('trim', explode("\n", $toText($state))), fn ($line) => $line !== '')));
    }

    public static function visible(string $label = 'Hiển thị trên website'): Toggle
    {
        return Toggle::make('is_visible')->label($label)->default(true);
    }

    public static function sort(): TextInput
    {
        return TextInput::make('sort_order')->label('Thứ tự')->numeric()->default(0)
            ->helperText('Số nhỏ hiện trước.');
    }

    /**
     * Long formatted text. The editor rewrites HTML it loads (drops heading ids, dir, aria attributes,
     * reflows whitespace), so a field the user did not touch keeps its stored HTML byte for byte.
     * An editor left empty stores nothing rather than an empty paragraph.
     */
    public static function rich(string $name, string $label): RichEditor
    {
        $html = fn (mixed $content): string => filled($content) ? RichContentRenderer::make($content)->getEditor()->getHTML() : '';

        return RichEditor::make($name)->label($label)
            ->fileAttachmentsDisk('uploads')->fileAttachmentsDirectory('filemanager/editor')->fileAttachmentsVisibility('public')
            ->dehydrateStateUsing(function ($state, ?Model $record) use ($name, $html) {
                $original = $record?->getOriginal($name);
                if (is_string($original) && $original !== '' && $html($state) === $html($original)) {
                    return $original;
                }
                $out = is_string($state) ? $state : $html($state);
                if (trim(strip_tags($out, '<img><iframe><table>')) === '') {
                    return $original === '' ? '' : null; // empty stays as it was stored
                }

                return $out;
            });
    }

    /**
     * A one-to-one relation (seo, contentSection) is written only when the record already has one
     * or the form holds something for it, so saving a page never creates empty rows.
     */
    public static function keepRelation(string $relation, Closure $hasContent): Closure
    {
        return function ($livewire, $state) use ($relation, $hasContent): bool {
            $owner = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
            if ($owner instanceof Model && $owner->exists && $owner->{$relation}()->exists()) {
                return true;
            }

            return (bool) $hasContent(is_array($state) ? $state : []);
        };
    }

    /**
     * Province, then ward of that province, from the current administrative list; "address" holds
     * only the street or village. $prefix is "payload." when the fields live inside a draft.
     *
     * @return list<Component>
     */
    public static function location(string $prefix = ''): array
    {
        return [
            Select::make($prefix.'province_id')->label('Tỉnh / thành phố')->searchable()->live()
                ->options(fn () => Province::query()->where('is_visible', true)->orderBy('name')->pluck('name', 'id')->all())
                ->afterStateUpdated(fn (Set $set) => $set($prefix.'ward_id', null)),
            Select::make($prefix.'ward_id')->label('Phường / xã')->searchable()
                ->options(fn (Get $get) => $get($prefix.'province_id')
                    ? Ward::query()->where('province_id', $get($prefix.'province_id'))->orderBy('name')->pluck('name', 'id')->all() : [])
                ->disabled(fn (Get $get) => ! $get($prefix.'province_id'))
                ->placeholder(fn (Get $get) => $get($prefix.'province_id') ? 'Chọn phường / xã' : 'Chọn tỉnh trước'),
            TextInput::make($prefix.'address')->label('Số nhà, đường, thôn / ấp')->maxLength(255)->columnSpanFull(),
        ];
    }

    /** SEO overrides kept in seo_metas through the record's "seo" relationship. */
    public static function seo(string $imageFolder = 'seopage'): Section
    {
        return Section::make('SEO')->collapsed()
            ->relationship('seo', self::keepRelation('seo', fn (array $state) => collect($state)->contains(fn ($value) => filled($value))))
            ->description('Để trống thì website dùng tên và mô tả của trang.')
            ->schema([
                TextInput::make('title')->label('Tiêu đề SEO')->maxLength(255),
                Textarea::make('description')->label('Mô tả SEO')->rows(3)->maxLength(500),
                TextInput::make('keywords')->label('Từ khóa'),
                self::image('image', $imageFolder, 'Ảnh chia sẻ'),
            ]);
    }

    /** Description blocks of a product, brand or category (see App\Casts\ContentBlocks for the shapes). */
    public static function contentBlocks(string $folder = 'product'): Builder
    {
        return Builder::make('blocks')->label('Khối nội dung')
            ->blocks([
                Block::make('heading')->label('Tiêu đề')->schema([TextInput::make('text')->label('Tiêu đề')->required()]),
                Block::make('text')->label('Đoạn văn')->schema([Textarea::make('text')->label('Nội dung')->rows(5)->required()]),
                Block::make('list')->label('Danh sách')->schema([
                    Toggle::make('ordered')->label('Đánh số'),
                    self::lines('items', 'Các ý'),
                ]),
                Block::make('image')->label('Ảnh')->schema([self::image('photo', $folder)->required(), TextInput::make('caption')->label('Chú thích')]),
                Block::make('media')->label('Ảnh kèm chữ')->schema([
                    self::image('photo', $folder)->required(),
                    Select::make('side')->label('Ảnh nằm bên')->options(['left' => 'Trái', 'right' => 'Phải'])->default('left'),
                    TextInput::make('title')->label('Tiêu đề'),
                    Textarea::make('text')->label('Nội dung')->rows(4),
                ]),
                Block::make('cards')->label('Thẻ lựa chọn')->schema([
                    TextInput::make('title')->label('Tiêu đề nhóm'),
                    Repeater::make('items')->label('Thẻ')->schema([
                        self::image('photo', $folder),
                        TextInput::make('title')->label('Tiêu đề')->required(),
                        TextInput::make('desc')->label('Mô tả'),
                        TextInput::make('link')->label('Liên kết')->placeholder('/danh-muc-san-pham/…'),
                    ])->columns(2)->collapsible(),
                ]),
                Block::make('faq')->label('Hỏi đáp')->schema([
                    TextInput::make('question')->label('Câu hỏi')->required(),
                    Textarea::make('answer')->label('Trả lời')->rows(4)->required(),
                ]),
            ])
            ->collapsible()->cloneable()->blockNumbers(false);
    }
}
