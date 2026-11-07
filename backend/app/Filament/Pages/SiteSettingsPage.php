<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/**
 * Company details and switches read by the whole site (settings table, one JSON value per key).
 * Mail server passwords stay in the server's .env and never appear here.
 *
 * @property-read Schema $form
 */
class SiteSettingsPage extends Page
{
    protected static ?string $title = 'Thông tin website';

    protected static ?string $navigationLabel = 'Thông tin website';

    protected static ?string $slug = 'thong-tin-website';

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    /** Keys this page edits; anything else in the table is left alone. */
    public const TEXT_KEYS = ['site_name', 'hotline', 'hotline_technical', 'phone', 'zalo', 'oaidzalo', 'email', 'address', 'thoigianlamviec',
        'website', 'fanpage', 'youtube', 'linkedin', 'twitter', 'coords', 'coords_iframe', 'toado', 'toado_iframe', 'header_promise', 'home_hero_mode'];

    public const SWITCH_KEYS = ['public_product_prices', 'review_require_order_code'];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('settings');
    }

    public function mount(): void
    {
        $values = DB::table('settings')->pluck('value', 'key')->map(fn ($value) => json_decode((string) $value, true));
        $data = [];
        foreach (self::TEXT_KEYS as $key) {
            $data[$key] = is_scalar($values[$key] ?? null) ? (string) $values[$key] : '';
        }
        foreach (self::SWITCH_KEYS as $key) {
            $data[$key] = (string) ($values[$key] ?? '') === '1';
        }
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Công ty')->columns(2)->schema([
                TextInput::make('site_name')->label('Tên công ty')->required()->columnSpanFull(),
                TextInput::make('address')->label('Địa chỉ')->columnSpanFull(),
                TextInput::make('email')->label('Email')->email(),
                TextInput::make('thoigianlamviec')->label('Giờ làm việc'),
                TextInput::make('website')->label('Website')->url(),
                TextInput::make('header_promise')->label('Câu cam kết trên đầu trang'),
            ]),
            Section::make('Điện thoại')->columns(2)->schema([
                TextInput::make('hotline')->label('Hotline bán hàng'),
                TextInput::make('hotline_technical')->label('Hotline kỹ thuật'),
                TextInput::make('phone')->label('Điện thoại'),
                TextInput::make('zalo')->label('Số Zalo'),
                TextInput::make('oaidzalo')->label('Zalo OA'),
            ]),
            Section::make('Mạng xã hội')->columns(2)->schema([
                TextInput::make('fanpage')->label('Facebook')->url(),
                TextInput::make('youtube')->label('YouTube')->url(),
                TextInput::make('linkedin')->label('LinkedIn')->url(),
                TextInput::make('twitter')->label('X / Twitter')->url(),
            ]),
            Section::make('Bản đồ')->columns(2)->collapsed()->schema([
                TextInput::make('coords')->label('Tọa độ trụ sở'),
                TextInput::make('toado')->label('Tọa độ chi nhánh'),
                Textarea::make('coords_iframe')->label('Mã nhúng bản đồ trụ sở')->rows(3)->columnSpanFull(),
                Textarea::make('toado_iframe')->label('Mã nhúng bản đồ chi nhánh')->rows(3)->columnSpanFull(),
            ]),
            Section::make('Hiển thị')->columns(2)->schema([
                Toggle::make('public_product_prices')->label('Hiện giá sản phẩm trên website'),
                Toggle::make('review_require_order_code')->label('Đánh giá phải kèm mã đơn hàng'),
                Select::make('home_hero_mode')->label('Phần đầu trang chủ')->options(['text' => 'Chữ trên nền', 'image' => 'Ảnh lớn'])->default('text'),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')
                ->footer([Actions::make([Action::make('save')->label('Lưu thay đổi')->submit('save')->keyBindings(['mod+s'])])]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $now = now();
        foreach (self::TEXT_KEYS as $key) {
            $this->store($key, (string) ($data[$key] ?? ''), $now);
        }
        foreach (self::SWITCH_KEYS as $key) {
            $this->store($key, ! empty($data[$key]) ? '1' : '0', $now);
        }
        Notification::make()->title('Đã lưu thông tin website')->success()->send();
    }

    private function store(string $key, string $value, mixed $now): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => $now, 'created_at' => $now]);
    }
}
