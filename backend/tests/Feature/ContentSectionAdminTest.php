<?php

use App\Domain\Content\SectionHistory;
use App\Filament\Resources\ContentSections\ContentSectionResource;
use App\Filament\Resources\ContentSections\Pages\ManageContentSections;
use App\Models\ContentSection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function sectionAdmin(): User
{
    return User::query()->create(['name' => 'Thử', 'username' => 'cs-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_OWNER, 'permissions' => [], 'is_active' => true]);
}

function blocksOf(ContentSection $section): array
{
    return json_decode((string) $section->fresh()->getRawOriginal('blocks'), true);
}

it('keeps every earlier state of a description and restores any of them, even after deletion', function () {
    $section = ContentSection::query()->where('owner_type', Product::class)->firstOrFail();
    $original = blocksOf($section);
    $section->update(['blocks' => [['type' => 'text', 'data' => ['text' => 'Bản sửa 1']]]]);
    $section->update(['blocks' => [['type' => 'text', 'data' => ['text' => 'Bản sửa 2']]]]);
    $versions = ContentSectionResource::versions(Product::class, (int) $section->owner_id);
    expect($versions)->toHaveCount(2);

    SectionHistory::restore(array_key_last($versions));
    expect(blocksOf($section))->toBe($original)->and(ContentSectionResource::versions(Product::class, (int) $section->owner_id))->toHaveCount(3);

    $owner = [$section->owner_type, $section->owner_id];
    $section->delete();
    $deleted = DB::table('content_section_versions')->where('owner_type', $owner[0])->where('owner_id', $owner[1])->where('reason', 'delete')->value('id');
    SectionHistory::restore((int) $deleted);
    expect(json_decode(DB::table('content_sections')->where('owner_type', $owner[0])->where('owner_id', $owner[1])->value('blocks'), true))->toBe($original);
});

it('lists descriptions with coverage for staff with the catalog permission', function () {
    $this->actingAs(sectionAdmin(), 'admin');
    Livewire\Livewire::test(ManageContentSections::class)->assertOk()->assertSee('sản phẩm đang hiện');
});
