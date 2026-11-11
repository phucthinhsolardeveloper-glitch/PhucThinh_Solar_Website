<?php

use App\Domain\Site\SocialNetworks;
use App\Filament\Pages\SiteSettingsPage;
use App\Models\User;
use App\Support\SiteSettings;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

function socialOwner(): User
{
    return User::query()->create(['name' => 'Chủ', 'username' => 'chu-'.bin2hex(random_bytes(3)),
        'password' => 'test-password', 'role' => User::ROLE_OWNER, 'is_active' => true]);
}

it('shows only the networks that have a link, in the order they are listed', function () {
    DB::table('settings')->whereIn('key', SocialNetworks::keys())->delete();
    foreach (['tiktok' => 'https://www.tiktok.com/@pts', 'fanpage' => 'https://www.facebook.com/pts'] as $key => $href) {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($href), 'updated_at' => now(), 'created_at' => now()]);
    }
    app(SiteSettings::class)->clear();

    $links = $this->getJson('/api/v1/site')->assertOk()->json('footer.socialLinks');

    expect($links)->toBe([
        ['label' => 'Facebook', 'href' => 'https://www.facebook.com/pts', 'kind' => 'facebook'],
        ['label' => 'TikTok', 'href' => 'https://www.tiktok.com/@pts', 'kind' => 'tiktok'],
    ]);
});

it('keeps every network editable in Admin and saves what the owner typed', function () {
    $this->actingAs(socialOwner(), 'admin');

    $page = Livewire::test(SiteSettingsPage::class);
    foreach (SocialNetworks::keys() as $key) {
        $page->assertFormFieldExists($key);
    }

    $page->set('data.zalo_oa', 'https://zalo.me/0944555957')
        ->set('data.shopee', 'https://shopee.vn/phucthinhsolar')
        ->call('save')->assertHasNoErrors();
    app(SiteSettings::class)->clear();

    expect(json_decode(DB::table('settings')->where('key', 'zalo_oa')->value('value'), true))->toBe('https://zalo.me/0944555957');

    $kinds = collect($this->getJson('/api/v1/site')->assertOk()->json('footer.socialLinks'))->pluck('kind')->all();
    expect($kinds)->toContain('zalo')->toContain('shopee');
});

it('refuses an address that is not a link, so the footer cannot show a broken icon', function () {
    $this->actingAs(socialOwner(), 'admin');

    Livewire::test(SiteSettingsPage::class)
        ->set('data.tiktok', 'tiktok phuc thinh')
        ->call('save')->assertHasErrors(['data.tiktok']);
});

it('hides a network without losing its link, and shows it again', function () {
    DB::table('settings')->whereIn('key', [...SocialNetworks::keys(), SocialNetworks::HIDDEN_SETTING])->delete();
    $this->actingAs(socialOwner(), 'admin');

    $page = Livewire::test(SiteSettingsPage::class)
        ->set('data.fanpage', 'https://www.facebook.com/pts')->set('data.youtube', 'https://youtube.com/@pts')
        ->callAction(TestAction::make('toggle_youtube')->schemaComponent('youtube'))
        ->call('save')->assertHasNoErrors();
    app(SiteSettings::class)->clear();

    expect(collect($this->getJson('/api/v1/site')->json('footer.socialLinks'))->pluck('kind')->all())->toBe(['facebook'])
        ->and(json_decode((string) DB::table('settings')->where('key', 'youtube')->value('value')))->toBe('https://youtube.com/@pts');

    Livewire::test(SiteSettingsPage::class)->assertSet('data.social_hidden', ['youtube'])
        ->callAction(TestAction::make('toggle_youtube')->schemaComponent('youtube'))
        ->call('save');
    app(SiteSettings::class)->clear();
    expect(collect($this->getJson('/api/v1/site')->json('footer.socialLinks'))->pluck('kind')->all())->toBe(['facebook', 'youtube']);
});
