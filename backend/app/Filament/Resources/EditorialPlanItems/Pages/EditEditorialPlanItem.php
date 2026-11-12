<?php

namespace App\Filament\Resources\EditorialPlanItems\Pages;

use App\Filament\Concerns\SavesAndStays;
use App\Filament\Resources\EditorialPlanItems\EditorialPlanItemResource;
use App\Models\EditorialPlanItem;
use App\Models\Post;
use Filament\Resources\Pages\EditRecord;

class EditEditorialPlanItem extends EditRecord
{
    use SavesAndStays;

    protected static string $resource = EditorialPlanItemResource::class;

    protected function afterSave(): void
    {
        $item = $this->getRecord();
        if ($item instanceof EditorialPlanItem && $item->wasChanged('scheduled_for') && $item->post_id) {
            // A changed editorial slot also updates the matching *hidden* article's date.
            // Public content is never re-scheduled by a planning-only editor.
            Post::query()->whereKey($item->post_id)->where('kind', 'article')->where('is_visible', false)
                ->update(['published_at' => $item->scheduled_for]);
        }
    }

    public function getSubheading(): ?string
    {
        return 'Điều chỉnh lịch biên tập không tự động đăng bài. Chỉ bài đã duyệt và bật hiển thị mới được hẹn giờ thực tế.';
    }
}
