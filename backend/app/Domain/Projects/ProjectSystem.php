<?php

namespace App\Domain\Projects;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;

/** The figures of a finished installation shown on its project page. Every one is optional. */
final class ProjectSystem
{
    public const TYPES = ['on-grid' => 'Hòa lưới', 'hybrid' => 'Hybrid có lưu trữ', 'off-grid' => 'Độc lập (off-grid)', 'bess' => 'Lưu trữ BESS', 'lighting' => 'Chiếu sáng năng lượng mặt trời', 'water' => 'Bơm nước năng lượng mặt trời'];

    public const COLUMNS = ['system_type', 'capacity_kwp', 'storage_kwh', 'output_kwh_month', 'saving_vnd_month', 'build_days'];

    /** @return array<string, list<mixed>> validation for a draft */
    public static function rules(): array
    {
        return [
            'system_type' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::TYPES))],
            'capacity_kwp' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'storage_kwh' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'output_kwh_month' => ['nullable', 'integer', 'min:0', 'max:4000000000'],
            'saving_vnd_month' => ['nullable', 'integer', 'min:0'],
            'build_days' => ['nullable', 'integer', 'min:0', 'max:65000'],
        ];
    }

    /** @return list<Component|Field> */
    public static function fields(string $prefix = ''): array
    {
        return [
            Select::make($prefix.'system_type')->label('Loại hệ thống')->options(self::TYPES),
            TextInput::make($prefix.'capacity_kwp')->label('Công suất pin mặt trời')->numeric()->minValue(0)->suffix('kWp'),
            TextInput::make($prefix.'storage_kwh')->label('Dung lượng lưu trữ')->numeric()->minValue(0)->suffix('kWh'),
            TextInput::make($prefix.'output_kwh_month')->label('Sản lượng thực tế mỗi tháng')->integer()->minValue(0)->suffix('kWh'),
            TextInput::make($prefix.'saving_vnd_month')->label('Tiền điện tiết kiệm mỗi tháng')->integer()->minValue(0)->suffix('đ')
                ->helperText('Chỉ nhập số đo được hoặc khách hàng xác nhận.'),
            TextInput::make($prefix.'build_days')->label('Thời gian thi công')->integer()->minValue(0)->suffix('ngày'),
        ];
    }

    /** @return array<string, mixed>|null null when nothing was filled in */
    public static function payload(object $post): ?array
    {
        $number = fn ($value) => is_numeric($value) && (float) $value > 0 ? (float) $value : null;
        $type = (string) ($post->system_type ?? '');
        $system = [
            'type' => isset(self::TYPES[$type]) ? $type : null,
            'typeLabel' => self::TYPES[$type] ?? null,
            'kwp' => $number($post->capacity_kwp ?? null),
            'storageKwh' => $number($post->storage_kwh ?? null),
            'outputKwhMonth' => ($v = $number($post->output_kwh_month ?? null)) !== null ? (int) $v : null,
            'savingMonth' => ($v = $number($post->saving_vnd_month ?? null)) !== null ? (int) $v : null,
            'buildDays' => ($v = $number($post->build_days ?? null)) !== null ? (int) $v : null,
        ];

        return array_filter($system, fn ($value) => $value !== null) === [] ? null : $system;
    }
}
