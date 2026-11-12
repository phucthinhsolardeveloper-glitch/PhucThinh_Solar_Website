<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditorialPlanItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime'];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public const STAGES = [
        'idea' => 'Lên ý tưởng',
        'research' => 'Đang nghiên cứu',
        'writing' => 'Đang soạn',
        'review' => 'Chờ duyệt',
        'ready' => 'Sẵn sàng hẹn giờ',
        'scheduled' => 'Đã hẹn giờ',
        'published' => 'Đã xuất bản',
    ];
}
