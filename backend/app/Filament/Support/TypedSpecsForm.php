<?php

namespace App\Filament\Support;

use App\Domain\Catalog\ProductDetail\ProductSpecs;
use App\Domain\Catalog\ProductSpecTemplates;
use App\Domain\Catalog\SpecFormat;
use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;

/**
 * "Thông số" of the product form, shaped by the product type (from the category, or "Kiểu trang sản phẩm").
 * Column specs bind to their column; other specs live under typed_specs.<key> and are written to additional_specs
 * with their key, which the product page reads first. Free rows stay in "Thông số thêm".
 */
final class TypedSpecsForm
{
    public static function type(Get $get): string
    {
        return app(ProductSpecs::class)->profileForCategory((string) $get('pdp_profile'), (int) $get('category_id'));
    }

    /** @return array<int, mixed> */
    public static function components(): array
    {
        $components = [
            Text::make(fn (Get $get) => 'Loại sản phẩm: '.(ProductSpecTemplates::types()[self::type($get)] ?? '')
                .'. Ô nhập đổi theo danh mục (hoặc "Kiểu trang sản phẩm" ở tab Thông tin chung). Ô có dấu * cần có trước khi bật hiển thị.'),
        ];
        foreach (array_keys(ProductSpecTemplates::types()) as $type) {
            $components[] = Grid::make(3)->visible(fn (Get $get) => self::type($get) === $type)
                ->schema(array_map(fn (array $field) => self::input($field), ProductSpecTemplates::fields($type)));
        }
        $components[] = Section::make('Thông số cũ không thuộc loại này')->collapsed()->columns(3)
            ->description('Có dữ liệu nhưng không dùng cho loại sản phẩm hiện tại. Xóa trắng nếu không còn đúng.')
            ->visible(fn (Get $get) => self::stray($get) !== [])
            ->schema(collect(ProductSpecTemplates::columns())->except('cong_suat')->map(fn ($column, $key) => TextInput::make($column)
                ->label(ProductResource::SPECS[$column] ?? $column)
                ->visible(fn (Get $get) => in_array($column, self::stray($get), true)))->values()->all());

        return $components;
    }

    /** @param array{key: string, label: string, column: ?string, unit: string, placeholder: string, suggest: list<string>, required: bool} $field */
    private static function input(array $field): TextInput
    {
        $path = $field['column'] ?? 'typed_specs.'.$field['key'];
        $input = TextInput::make($path)->label($field['label'].($field['required'] ? ' *' : ''));
        if ($field['column'] === 'power_watts') {
            return $input->numeric()->minValue(0)->suffix('W');
        }
        if ($field['unit'] !== '') {
            $unit = $field['unit'];
            $input->suffix($unit)->formatStateUsing(fn ($state) => ProductSpecTemplates::forBox($unit, $state))
                ->dehydrateStateUsing(fn ($state) => ProductSpecTemplates::fromBox($unit, $state));
        }
        if ($field['placeholder'] !== '') {
            $input->placeholder($field['placeholder']);
        }
        if ($field['suggest'] !== []) {
            $input->datalist($field['suggest']);
        }
        if ($field['column'] !== null && isset(SpecFormat::COLUMNS[$field['column']])) {
            // Measured specs are rewritten one way as soon as the field is left ("4-6H" -> "4–6 giờ").
            $kind = SpecFormat::COLUMNS[$field['column']];
            $input->live(onBlur: true)->placeholder($field['placeholder'] ?: SpecFormat::EXAMPLES[$kind])
                ->afterStateUpdated(fn ($state, $set) => is_string($state) && $state !== '' ? $set($field['column'], SpecFormat::apply($state, $kind)) : null);
        }

        return $input;
    }

    /** @return list<string> spec columns holding a value but not part of the current type */
    private static function stray(Get $get): array
    {
        $used = collect(ProductSpecTemplates::fields(self::type($get)))->pluck('column')->filter()->all();
        $out = [];
        foreach (ProductSpecTemplates::columns() as $key => $column) {
            if ($key !== 'cong_suat' && ! in_array($column, $used, true) && filled($get($column))) {
                $out[] = $column;
            }
        }

        return $out;
    }

    /**
     * Before the form is filled: keyed rows of the product's type go to their boxes, the rest stay free rows.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function fill(array $data): array
    {
        $type = app(ProductSpecs::class)->profileForCategory((string) ($data['pdp_profile'] ?? ''), (int) ($data['category_id'] ?? 0));
        $keys = ProductSpecTemplates::extraKeys($type);
        $typed = [];
        $free = [];
        foreach ((array) ($data['additional_specs'] ?? []) as $row) {
            $key = (string) ($row['key'] ?? '');
            if ($key !== '' && isset($keys[$key]) && ! isset($typed[$key])) {
                $typed[$key] = (string) ($row['value'] ?? '');
            } else {
                $free[] = $row;
            }
        }
        $data['typed_specs'] = $typed;
        $data['additional_specs'] = $free;

        return $data;
    }

    /**
     * Before save: typed boxes become keyed rows (type order) followed by the free rows. Keyed rows of another
     * type that are no longer shown (category changed) are kept as free rows so nothing typed is lost.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function save(array $data, ?Product $record): array
    {
        $type = app(ProductSpecs::class)->profileForCategory((string) ($data['pdp_profile'] ?? ''), (int) ($data['category_id'] ?? 0));
        $keys = ProductSpecTemplates::extraKeys($type);
        $typed = (array) ($data['typed_specs'] ?? []);
        $rows = [];
        foreach ($keys as $key => $label) {
            $value = trim((string) ($typed[$key] ?? ''));
            if ($value !== '') {
                $rows[] = ['key' => $key, 'label' => $label, 'value' => $value];
            }
        }
        $free = array_values(array_filter((array) ($data['additional_specs'] ?? []), fn ($row) => trim((string) ($row['label'] ?? '')) !== ''));
        $freeLabels = array_map(fn ($row) => mb_strtolower(trim((string) $row['label'])), $free);
        /** @var array<int, array<string, mixed>> $previous */
        $previous = $record ? (array) $record->getAttribute('additional_specs') : [];
        foreach ($previous as $old) {
            $old = (array) $old;
            $key = (string) ($old['key'] ?? '');
            if ($key !== '' && ! isset($keys[$key]) && ! in_array(mb_strtolower(trim((string) ($old['label'] ?? ''))), $freeLabels, true)) {
                $free[] = ['key' => $key, 'label' => (string) $old['label'], 'value' => (string) ($old['value'] ?? '')];
            }
        }
        $data['additional_specs'] = [...$rows, ...$free];
        unset($data['typed_specs']);

        return $data;
    }
}
