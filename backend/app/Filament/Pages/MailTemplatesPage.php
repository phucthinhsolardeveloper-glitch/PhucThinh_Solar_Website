<?php

namespace App\Filament\Pages;

use App\Filament\Support\AdminActor;
use App\Mail\MailTemplates;
use App\Mail\MailTemplateStudio;
use App\Models\User;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use UnitEnum;

/**
 * Edits email wording only; transport credentials remain in server configuration.
 *
 * @property-read Schema $form
 */
class MailTemplatesPage extends Page
{
    protected static ?string $title = 'Mẫu email';

    protected static ?string $navigationLabel = 'Mẫu email';

    protected static ?string $slug = 'mau-email';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('settings');
    }

    public function mount(): void
    {
        $saved = json_decode((string) DB::table('settings')->where('key', 'mail_templates')->value('value'), true);
        $templates = [];
        foreach (MailTemplates::LABELS as $key => $label) {
            $templates[$key] = array_merge(MailTemplates::defaults($key), is_array($saved[$key] ?? null) ? $saved[$key] : []);
        }
        $this->form->fill(['templates' => $templates]);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];
        foreach (MailTemplates::LABELS as $key => $label) {
            $sections[] = Section::make($label)->collapsed()->schema([
                TextInput::make("templates.{$key}.subject")->label('Tiêu đề email')->required()->maxLength(180),
                TextInput::make("templates.{$key}.heading")->label('Tiêu đề trong email')->required()->maxLength(180),
                Textarea::make("templates.{$key}.intro")->label('Lời mở đầu')->rows(4)->required()->maxLength(700),
                Textarea::make("templates.{$key}.closing")->label('Lời kết')->rows(3)->maxLength(500),
                Actions::make([
                    Action::make('preview_'.$key)->label('Xem trước')->icon('heroicon-o-eye')
                        ->modalHeading('Xem trước: '.$label)->modalWidth('4xl')
                        ->modalContent(fn () => view('filament.mail-template-preview', ['html' => app(MailTemplateStudio::class)->html($key, $this->draft($key))]))
                        ->modalSubmitAction(false)->modalCancelActionLabel('Đóng'),
                    Action::make('test_'.$key)->label('Gửi thử')->icon('heroicon-o-paper-airplane')
                        ->schema([TextInput::make('recipient')->label('Email nhận bản thử')->email()->required()])
                        ->requiresConfirmation()->action(fn (array $data) => $this->sendTest($key, $data['recipient'])),
                ])->key('mail_template_'.$key),
            ]);
        }

        return $schema->statePath('data')->components($sections);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('Lưu mẫu email')->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canAccess(), 403);
        $input = $this->form->getState()['templates'] ?? [];
        $overrides = [];
        foreach (MailTemplates::LABELS as $key => $label) {
            $defaults = MailTemplates::defaults($key);
            foreach ($defaults as $field => $default) {
                $value = trim((string) ($input[$key][$field] ?? ''));
                if ($value !== $default && $value !== '') {
                    $overrides[$key][$field] = $value;
                }
            }
        }
        DB::table('settings')->updateOrInsert(['key' => 'mail_templates'], [
            'value' => json_encode($overrides, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            'updated_at' => now(), 'created_at' => now(),
        ]);
        app(SiteSettings::class)->clear();
        Notification::make()->title('Đã lưu mẫu email')->success()->send();
    }

    private function draft(string $key): array
    {
        return is_array($this->data['templates'][$key] ?? null) ? $this->data['templates'][$key] : [];
    }

    private function sendTest(string $key, string $recipient): void
    {
        abort_unless(self::canAccess(), 403);
        $actor = AdminActor::current();
        $rateKey = 'mail-template-test:'.$actor->id;
        if (RateLimiter::tooManyAttempts($rateKey, 5)) {
            Notification::make()->title('Đã đạt giới hạn 5 email thử mỗi giờ')->danger()->send();

            return;
        }
        RateLimiter::hit($rateKey, 3600);
        try {
            Mail::to($recipient)->send(app(MailTemplateStudio::class)->mailable($key, $this->draft($key)));
            Notification::make()->title('Đã gửi email thử')->success()->send();
        } catch (\Throwable $exception) {
            Log::warning('Mail template test send failed', ['template' => $key]);
            Notification::make()->title('Không gửi được email thử; kiểm tra cấu hình mail trên server')->danger()->send();
        }
    }
}
