<?php

namespace App\Filament\Pages;

use App\Domain\Audit\Audit;
use App\Domain\Catalog\Listing\SearchSynonyms;
use App\Domain\Commerce\VietQr;
use App\Domain\Leads\LeadRouting;
use App\Domain\Site\SocialNetworks;
use App\Models\Province;
use App\Models\User;
use App\Models\Ward;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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

    /** Keys this page edits besides the social ones; anything else in the table is left alone. */
    private const OWN_KEYS = ['site_name', 'hotline', 'hotline_technical', 'phone', 'zalo', 'email', 'address', 'working_hours',
        'gtm_id', 'ga4_id', 'bank_bin', 'bank_account_no', 'bank_account_name', 'shipping_sender_name', 'shipping_sender_phone', 'shipping_sender_address', 'shipping_sender_ward_id'];

    /** @return list<string> */
    public static function textKeys(): array
    {
        return [...self::OWN_KEYS, ...SocialNetworks::keys()];
    }

    /** Only the owner may change where customers send money. */
    public const OWNER_KEYS = ['bank_bin', 'bank_account_no', 'bank_account_name'];

    public const SWITCH_KEYS = ['public_product_prices', 'review_require_order_code', LeadRouting::SETTING];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('settings');
    }

    private static function isOwner(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->isOwner();
    }

    public function mount(): void
    {
        $values = DB::table('settings')->pluck('value', 'key')->map(fn ($value) => json_decode((string) $value, true));
        $data = [];
        foreach (self::textKeys() as $key) {
            $data[$key] = is_scalar($values[$key] ?? null) ? (string) $values[$key] : '';
        }
        foreach (self::SWITCH_KEYS as $key) {
            $data[$key] = (string) ($values[$key] ?? '') === '1';
        }
        $synonyms = $values[SearchSynonyms::SETTING] ?? null;
        $data['social_hidden'] = SocialNetworks::hidden($values->all());
        $data['search_synonyms'] = implode("\n", is_array($synonyms) ? array_map('strval', $synonyms) : SearchSynonyms::DEFAULTS);
        $data['shipping_sender_province'] = Ward::query()->whereKey((int) ($data['shipping_sender_ward_id'] ?? 0))->value('province_id');
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Công ty')->columns(2)->schema([
                TextInput::make('site_name')->label('Tên công ty')->required()->columnSpanFull(),
                TextInput::make('address')->label('Địa chỉ')->columnSpanFull(),
                TextInput::make('email')->label('Email')->email(),
                TextInput::make('working_hours')->label('Giờ làm việc'),
            ]),
            Section::make('Điện thoại')->columns(2)->schema([
                TextInput::make('hotline')->label('Hotline bán hàng')->tel(),
                TextInput::make('hotline_technical')->label('Hotline kỹ thuật')->tel(),
                TextInput::make('phone')->label('Điện thoại')->tel(),
                TextInput::make('zalo')->label('Số Zalo')->tel(),
            ]),
            Section::make('Mạng xã hội')->columns(2)
                ->description('Dán link trang nào thì chân trang hiện biểu tượng của trang đó. Bấm "Đang hiện" để tạm ẩn mà vẫn giữ link, rồi Lưu thay đổi. Các link đang hiện cũng được khai báo cho Google là tài khoản chính thức của công ty.')
                ->schema(array_map(
                    fn (array $network, string $key) => TextInput::make($key)->label($network[0])->url()
                        ->placeholder('Dán link '.$network[0].', ví dụ '.$network[2])->maxLength(300)
                        ->hintAction(Action::make('toggle_'.$key)
                            ->label(fn (Get $get) => in_array($key, (array) $get('social_hidden'), true) ? 'Đang ẩn · bấm để hiện' : 'Đang hiện · bấm để ẩn')
                            ->icon(fn (Get $get) => in_array($key, (array) $get('social_hidden'), true) ? 'heroicon-m-eye-slash' : 'heroicon-m-eye')
                            ->color(fn (Get $get) => in_array($key, (array) $get('social_hidden'), true) ? 'gray' : 'success')
                            ->visible(fn (Get $get) => filled($get($key)))
                            ->action(function (Get $get, Set $set) use ($key) {
                                $hidden = array_values(array_filter((array) $get('social_hidden'), fn ($k) => $k !== $key));
                                if (! in_array($key, (array) $get('social_hidden'), true)) {
                                    $hidden[] = $key;
                                }
                                $set('social_hidden', $hidden);
                            })),
                    SocialNetworks::NETWORKS,
                    SocialNetworks::keys(),
                )),
            Hidden::make('social_hidden'),
            Section::make('Tài khoản nhận chuyển khoản')->columns(3)->visible(fn () => self::isOwner())
                ->description('Khách chọn chuyển khoản sẽ thấy mã VietQR theo tài khoản này, kèm số tiền và nội dung riêng cho từng đơn. Để trống thì không nhận chuyển khoản.')
                ->schema([
                    // allowHtml: the option draws the bank's badge, so a long list is scannable by colour.
                    Select::make('bank_bin')->label('Ngân hàng')->searchable()->native(false)->allowHtml()
                        ->options(VietQr::options())
                        ->getSearchResultsUsing(fn (string $search) => VietQr::options($search))
                        ->getOptionLabelUsing(fn (?string $value) => VietQr::options()[$value] ?? null),
                    TextInput::make('bank_account_no')->label('Số tài khoản')->regex('/^[0-9A-Za-z ]{4,23}$/'),
                    TextInput::make('bank_account_name')->label('Tên chủ tài khoản')->maxLength(60)->helperText('Viết như trên thẻ ngân hàng, không dấu cũng được.'),
                ]),
            Section::make('Kho gửi hàng (Nhất Tín)')->columns(2)->collapsed()
                ->description('Thông tin người gửi trên vận đơn. Để trống tên, điện thoại, địa chỉ thì dùng thông tin công ty.')
                ->schema([
                    TextInput::make('shipping_sender_name')->label('Tên người gửi'),
                    TextInput::make('shipping_sender_phone')->label('Điện thoại người gửi')->tel(),
                    TextInput::make('shipping_sender_address')->label('Số nhà, đường')->columnSpanFull(),
                    Select::make('shipping_sender_province')->label('Tỉnh / thành')->searchable()->live()->dehydrated(false)
                        ->options(fn () => Province::query()->where('is_visible', true)->orderBy('name')->pluck('name', 'id')->all()),
                    Select::make('shipping_sender_ward_id')->label('Phường / xã')->searchable()
                        ->options(fn ($get) => $get('shipping_sender_province') ? Ward::query()->where('province_id', $get('shipping_sender_province'))->orderBy('name')->pluck('name', 'id')->all() : []),
                ]),
            Section::make('Bán hàng')->columns(2)->schema([
                Toggle::make('public_product_prices')->label('Hiện giá sản phẩm trên website'),
                Toggle::make('review_require_order_code')->label('Đánh giá phải kèm mã đơn hàng'),
                Toggle::make(LeadRouting::SETTING)->label('Tự chia liên hệ mới cho nhân viên')
                    ->helperText('Giao cho người có quyền Liên hệ đang ít việc nhất, hẹn gọi lại sau 2 giờ làm việc (tính trong giờ T2–T7 8:00–17:00; ví dụ gửi 16:30 thì hẹn 9:30 hôm sau). Tắt thì liên hệ mới để chưa phân công.'),
            ]),
            Section::make('Đo lường (Google Analytics, Tag Manager)')->collapsed()->schema([
                // Either the id alone or the whole snippet Google gives: only the GTM-XXXX id is kept.
                TextInput::make('gtm_id')->label('Mã vùng chứa GTM')->placeholder('GTM-57TJSRVM')->maxLength(4000)
                    ->regex('/GTM-[A-Z0-9]{4,12}/i')->validationMessages(['regex' => 'Không thấy mã dạng GTM-XXXXXXX (lấy trong Google Tag Manager).'])
                    ->dehydrateStateUsing(fn ($state) => preg_match('/GTM-[A-Z0-9]{4,12}/i', (string) $state, $m) ? strtoupper($m[0]) : '')
                    ->helperText('Nhập mã (ví dụ GTM-57TJSRVM) hoặc dán nguyên đoạn mã Google đưa, hệ thống tự lấy mã. Website tự gắn vào đầu <head> và ngay sau <body> trên mọi trang. Để trống là tắt.'),
                // Direct GA4: page views plus the shop events (view_item, add_to_cart, purchase, generate_lead, ...).
                TextInput::make('ga4_id')->label('Mã đo Google Analytics 4')->placeholder('G-XXXXXXXXXX')->maxLength(4000)
                    ->regex('/G-[A-Z0-9]{6,12}/i')->validationMessages(['regex' => 'Không thấy mã dạng G-XXXXXXXXXX (Google Analytics → Quản trị → Luồng dữ liệu).'])
                    ->dehydrateStateUsing(fn ($state) => preg_match('/\bG-[A-Z0-9]{6,12}\b/i', (string) $state, $m) ? strtoupper($m[0]) : '')
                    ->helperText('Website tự gửi lượt xem trang và các sự kiện bán hàng (xem sản phẩm, thêm giỏ, đặt hàng, gửi liên hệ, bấm gọi/Zalo) vào GA4. Nếu trong GTM ở trên đã có thẻ GA4 cùng mã thì để trống ô này, kẻo đếm hai lần.'),
            ]),
            Section::make('Tìm kiếm sản phẩm')->collapsed()->schema([
                Textarea::make('search_synonyms')->label('Từ đồng nghĩa')->rows(8)->maxLength(4000)
                    ->helperText('Mỗi dòng một nhóm, các từ cách nhau bằng dấu phẩy. Khách gõ một từ trong nhóm thì kết quả có cả các từ còn lại, ví dụ: inverter, biến tần.'),
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
        $before = DB::table('settings')->pluck('value', 'key')->map(fn ($v) => json_decode((string) $v, true))->all();
        $after = [];
        foreach (self::textKeys() as $key) {
            $after[$key] = (string) ($data[$key] ?? '');
        }
        if (! self::isOwner()) {
            $after = array_diff_key($after, array_flip(self::OWNER_KEYS));
        }
        // A setting never filled in stays absent rather than becoming an empty row.
        $after = array_filter($after, fn ($value, $key) => $value !== '' || array_key_exists($key, $before), ARRAY_FILTER_USE_BOTH);
        foreach (self::SWITCH_KEYS as $key) {
            $value = ! empty($data[$key]) ? '1' : '0';
            // A switch never turned on stays absent (off), like an empty text setting.
            if ($value === '1' || array_key_exists($key, $before)) {
                $after[$key] = $value;
            }
        }
        $hidden = array_values(array_intersect(SocialNetworks::keys(), (array) ($data['social_hidden'] ?? [])));
        if ($hidden !== [] || array_key_exists(SocialNetworks::HIDDEN_SETTING, $before)) {
            $after[SocialNetworks::HIDDEN_SETTING] = $hidden;
        }
        // Untouched starting groups stay unsaved, so a later change to the defaults still reaches this site.
        $synonyms = array_map(fn ($terms) => implode(', ', $terms), SearchSynonyms::parse(preg_split('/\R/', (string) ($data['search_synonyms'] ?? '')) ?: []));
        $current = is_array($before[SearchSynonyms::SETTING] ?? null) ? $before[SearchSynonyms::SETTING] : SearchSynonyms::DEFAULTS;
        if ($synonyms !== $current) {
            $after[SearchSynonyms::SETTING] = $synonyms;
        }
        DB::transaction(function () use ($before, $after, $now) {
            foreach ($after as $key => $value) {
                $this->store($key, $value, $now);
            }
            [$from, $to] = Audit::diff(array_intersect_key($before, $after), $after);
            if ($to !== []) {
                Audit::record('settings.update', 'Settings', null, $from, $to);
            }
        });
        Notification::make()->title('Đã lưu thông tin website')->success()->send();
    }

    private function store(string $key, string|array $value, mixed $now): void
    {
        DB::table('settings')->updateOrInsert(['key' => $key], ['value' => json_encode($value, JSON_UNESCAPED_UNICODE), 'updated_at' => $now, 'created_at' => $now]);
    }
}
