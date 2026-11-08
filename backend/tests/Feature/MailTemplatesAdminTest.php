<?php

use App\Filament\Pages\MailTemplatesPage;
use App\Mail\AdminNotification;
use App\Mail\MailTemplates;
use App\Mail\MailTemplateStudio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

function mailAdmin(array $permissions = []): User
{
    return User::query()->create(['name' => 'Quản trị mail thử', 'username' => 'mail-test-'.bin2hex(random_bytes(4)),
        'password' => 'test-password', 'role' => $permissions === [] ? User::ROLE_OWNER : User::ROLE_EDITOR,
        'permissions' => $permissions, 'is_active' => true]);
}

it('saves template wording without touching mail credentials or changing untouched copy', function () {
    $this->actingAs(mailAdmin(), 'admin');
    $before = app(MailTemplates::class)->copy('contact_admin', ['contact_subject' => 'Thử nghiệm']);
    $this->get('/admin/mau-email')->assertOk();
    Livewire\Livewire::test(MailTemplatesPage::class)->call('save')->assertHasNoFormErrors();
    expect(app(MailTemplates::class)->copy('contact_admin', ['contact_subject' => 'Thử nghiệm']))->toBe($before);

    Livewire\Livewire::test(MailTemplatesPage::class)
        ->fillForm(['templates.contact_admin.heading' => 'Nội dung quản trị mới'])
        ->call('save')->assertHasNoFormErrors();
    $saved = json_decode((string) DB::table('settings')->where('key', 'mail_templates')->value('value'), true);
    expect($saved['contact_admin'])->toBe(['heading' => 'Nội dung quản trị mới'])
        ->and(app(MailTemplates::class)->copy('contact_admin', [])['heading'])->toBe('Nội dung quản trị mới');
});

it('renders safe sample HTML and protects the mail settings area', function () {
    $html = app(MailTemplateStudio::class)->html('order_customer', ['heading' => '<script>alert(1)</script>Xác nhận thử']);
    expect($html)->toContain('Xác nhận thử')->not->toContain('<script>');

    $this->actingAs(mailAdmin(['content']), 'admin');
    $this->get('/admin/mau-email')->assertForbidden();
    $this->flushSession();
    $this->actingAs(mailAdmin(['settings']), 'admin');
    $this->get('/admin/mau-email')->assertOk();
    Livewire\Livewire::test(MailTemplatesPage::class)
        ->assertFormComponentActionDoesNotExist('mail_template_contact_admin', 'test_contact_admin');
});

it('sends a synthetic test email only to the address entered by a settings editor', function () {
    Mail::fake();
    $this->actingAs(mailAdmin(['settings', 'settings_mail_test']), 'admin');

    Livewire\Livewire::test(MailTemplatesPage::class)
        ->callFormComponentAction('mail_template_contact_admin', 'test_contact_admin', ['recipient' => 'preview@example.com'])
        ->assertHasNoActionErrors();

    Mail::assertSent(AdminNotification::class, fn ($mail) => $mail->hasTo('preview@example.com'));
});
