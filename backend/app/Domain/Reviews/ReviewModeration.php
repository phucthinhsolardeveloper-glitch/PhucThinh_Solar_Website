<?php

namespace App\Domain\Reviews;

use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReviewModeration
{
    public function moderate(Review $review, User $actor, string $status): void
    {
        $this->moderateMany(collect([$review->id]), $actor, $status);
    }

    /** @param iterable<mixed> $records */
    public function moderateMany(iterable $records, User $actor, string $status): void
    {
        $this->authorize($actor);
        abort_unless(in_array($status, ['approved', 'hidden'], true), 422);
        $ids = collect($records)->map(function (mixed $record): int {
            if ($record instanceof Review) {
                return $record->id;
            }
            if (is_int($record) && $record > 0) {
                return $record;
            }

            throw ValidationException::withMessages(['reviews' => 'Danh sách đánh giá không hợp lệ.']);
        })->unique()->sort()->values()->all();
        if ($ids === []) {
            return;
        }
        DB::transaction(function () use ($ids, $status): void {
            $reviews = Review::query()->whereKey($ids)->lockForUpdate()->get();
            if ($reviews->count() !== count($ids) || $reviews->contains(fn (Review $review) => $review->parent_id !== null)) {
                throw ValidationException::withMessages(['reviews' => 'Chỉ có thể duyệt hoặc ẩn đánh giá gốc hiện có.']);
            }
            foreach ($reviews as $review) {
                if ($review->status !== $status) {
                    $review->update(['status' => $status]);
                }
            }
        });
    }

    public function edit(Review $review, User $actor, array $input): void
    {
        $this->authorize($actor);
        $data = Validator::make($input, [
            'title' => ['nullable', 'string', 'max:180'],
            'content' => ['required', 'string', 'max:5000'],
            'status' => ['required', 'in:pending,approved,hidden'],
        ])->validate();
        $data['title'] = trim(strip_tags((string) ($data['title'] ?? '')));
        $data['content'] = trim(strip_tags($data['content']));
        if ($data['content'] === '') {
            throw ValidationException::withMessages(['content' => 'Nội dung đánh giá không được để trống.']);
        }
        DB::transaction(function () use ($review, $data): void {
            $locked = Review::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->parent_id === null, 404);
            $locked->update($data);
        });
    }

    public function reply(Review $review, User $actor, string $content): Review
    {
        $this->authorize($actor);
        $content = trim(strip_tags($content));
        if ($content === '' || mb_strlen($content) > 5000) {
            throw ValidationException::withMessages(['content' => 'Câu trả lời cần từ 1 đến 5.000 ký tự.']);
        }

        return DB::transaction(function () use ($review, $content): Review {
            $locked = Review::query()->whereKey($review->id)->lockForUpdate()->firstOrFail();
            if ($locked->parent_id !== null || $locked->status !== 'approved') {
                throw ValidationException::withMessages(['content' => 'Chỉ trả lời đánh giá đã duyệt.']);
            }

            /** @var Review $reply */
            $reply = $locked->replies()->create([
                'product_id' => $locked->product_id, 'content' => $content,
                'name' => 'Phúc Thịnh Solar', 'by_staff' => true, 'status' => 'approved',
            ]);

            return $reply;
        });
    }

    private function authorize(User $actor): void
    {
        abort_unless($actor->is_active && $actor->hasPermission('reviews'), 403);
    }
}
