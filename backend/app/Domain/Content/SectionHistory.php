<?php

namespace App\Domain\Content;

use App\Models\ContentSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** Keeps the state a description had before each change or deletion, and puts any of them back. */
final class SectionHistory
{
    public const KEEP = 30;

    /** Called before a change is written: stores the state being replaced, as it is in the database. */
    public static function remember(ContentSection $section, string $reason): void
    {
        $row = $section->exists ? DB::table('content_sections')->where('id', $section->getKey())->first() : null;
        if ($row === null) {
            return;
        }
        $user = auth('admin')->user();
        DB::table('content_section_versions')->insert([
            'owner_type' => $row->owner_type, 'owner_id' => (int) $row->owner_id, 'mode' => (string) $row->mode, 'inherit_source' => $row->inherit_source,
            'blocks' => (string) $row->blocks, 'is_visible' => (bool) $row->is_visible,
            'reason' => $reason, 'created_by' => $user instanceof User ? $user->id : null, 'created_at' => now(),
        ]);
        // Only the most recent states of one owner are worth keeping.
        $old = DB::table('content_section_versions')->where('owner_type', $row->owner_type)->where('owner_id', $row->owner_id)
            ->orderByDesc('id')->skip(self::KEEP)->take(PHP_INT_MAX)->pluck('id');
        if ($old->isNotEmpty()) {
            DB::table('content_section_versions')->whereIn('id', $old)->delete();
        }
    }

    /** Puts a stored state back on its owner, keeping the current one in the history first. */
    public static function restore(int $versionId): ContentSection
    {
        return DB::transaction(function () use ($versionId): ContentSection {
            $version = DB::table('content_section_versions')->where('id', $versionId)->lockForUpdate()->firstOrFail();
            $section = ContentSection::query()->firstOrNew(['owner_type' => $version->owner_type, 'owner_id' => $version->owner_id]);
            $section->setRawAttributes(array_merge($section->getAttributes(), [
                'owner_type' => $version->owner_type, 'owner_id' => $version->owner_id, 'mode' => $version->mode,
                'inherit_source' => $version->inherit_source, 'blocks' => $version->blocks, 'is_visible' => $version->is_visible,
            ]));
            $section->historyReason = 'restore';
            $section->save();

            return $section;
        });
    }
}
