<?php

use App\Domain\Content\PostRevisions;
use App\Filament\Resources\PostRevisions\Pages\EditPostRevision;
use App\Filament\Resources\PostRevisions\PostRevisionResource;
use App\Models\Post;
use App\Models\User;
use Livewire\Livewire;

function editorialUser(array $permissions): User
{
    return User::query()->create(['name' => 'Người '.bin2hex(random_bytes(2)), 'username' => 'ed-'.bin2hex(random_bytes(4)), 'password' => 'test-password',
        'role' => User::ROLE_EDITOR, 'permissions' => $permissions, 'is_active' => true]);
}

it('sends a draft for review, returns it with a note, then publishes it', function () {
    $writer = editorialUser(['content']);
    $publisher = editorialUser(['content', 'content_publish']);
    $post = Post::query()->where('kind', 'article')->where('is_visible', true)->firstOrFail();
    $draft = app(PostRevisions::class)->draft($post, $writer);

    $this->actingAs($writer, 'admin');
    Livewire::test(EditPostRevision::class, ['record' => $draft->getKey()])
        ->assertActionHidden('returnDraft')->assertActionHidden('publish')
        ->callAction('requestReview', ['note' => 'Nhờ xem giúp đoạn mở bài'])->assertHasNoActionErrors();
    expect($draft->fresh()->review_state)->toBe('waiting')
        ->and($publisher->notifications()->count())->toBe(1)
        ->and(PostRevisionResource::getNavigationBadge())->toBe('1');

    $this->actingAs($publisher, 'admin');
    Livewire::test(EditPostRevision::class, ['record' => $draft->getKey()])
        ->callAction('returnDraft', ['note' => 'Thiếu ảnh minh họa'])->assertHasNoActionErrors();
    expect($draft->fresh()->review_state)->toBe('returned')->and($draft->fresh()->review_note)->toBe('Thiếu ảnh minh họa')
        ->and($writer->notifications()->first()->data['title'])->toContain('Bài bị trả lại');

    $this->actingAs($writer, 'admin');
    Livewire::test(EditPostRevision::class, ['record' => $draft->getKey()])->callAction('requestReview')->assertHasNoActionErrors();
    $this->actingAs($publisher, 'admin');
    Livewire::test(EditPostRevision::class, ['record' => $draft->getKey()])->callAction('publish');
    expect($draft->fresh()->state)->toBe('published')->and($draft->fresh()->review_state)->toBeNull();
});
