<?php

namespace App\Filament\Support;

use App\Domain\Content\PublishChecks;
use App\Models\Post;
use App\Models\Product;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Runs PublishChecks after a product or post is saved switched on. When it was just switched on (or created on)
 * and an error is found, the record is saved but switched back off, and staff are told exactly why.
 * Records already on the website are never pulled off by this: they only get the warnings.
 */
final class PublishGate
{
    /** @return bool false when the record was switched back off */
    public static function afterSave(Model $record, bool $wasVisible): bool
    {
        if (! $record->getAttribute('is_visible')) {
            return true;
        }
        $result = self::check($record);
        if ($result === null) {
            return true;
        }
        if ($result['errors'] !== [] && ! $wasVisible) {
            DB::table($record->getTable())->where('id', $record->getKey())->update(['is_visible' => false]);
            $record->setAttribute('is_visible', false);
            $record->syncOriginalAttribute('is_visible');
            Notification::make()->danger()->persistent()->title('Đã lưu nhưng CHƯA bật hiển thị')
                ->body(self::lines('Cần sửa trước khi lên website:', $result['errors']).self::lines('Nên sửa thêm:', $result['warnings']))->send();

            return false;
        }
        if ($result['errors'] !== [] || $result['warnings'] !== []) {
            Notification::make()->warning()->persistent()->title('Lưu ý trước khi khách xem')
                ->body(self::lines('Đang có lỗi:', $result['errors']).self::lines('Nên sửa:', $result['warnings']))->send();
        }

        return true;
    }

    /** For the list switch: refuses to switch on and says why. @return bool true when it may go on */
    public static function allowSwitchOn(Model $record): bool
    {
        $result = self::check($record);
        if ($result === null || $result['errors'] === []) {
            return true;
        }
        Notification::make()->danger()->persistent()->title('Chưa bật được hiển thị')
            ->body(self::lines('Cần sửa trước khi lên website:', $result['errors']))->send();

        return false;
    }

    /** @return array{errors: list<string>, warnings: list<string>}|null */
    private static function check(Model $record): ?array
    {
        return match (true) {
            $record instanceof Product => app(PublishChecks::class)->product($record),
            $record instanceof Post => app(PublishChecks::class)->post(PublishChecks::postData($record->fresh() ?? $record), (int) $record->getKey()),
            default => null,
        };
    }

    /** @param list<string> $items */
    private static function lines(string $heading, array $items): string
    {
        return $items === [] ? '' : $heading."\n· ".implode("\n· ", $items)."\n";
    }
}
