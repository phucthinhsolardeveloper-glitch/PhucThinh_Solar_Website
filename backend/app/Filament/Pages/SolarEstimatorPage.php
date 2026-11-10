<?php

namespace App\Filament\Pages;

use App\Domain\Audit\Audit;
use App\Domain\Solar\SolarEstimator;
use App\Models\User;
use App\Support\SiteSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Figures behind the "Ước tính điện mặt trời" page. Stored as one JSON setting; the page stays hidden
 * on the site until "Hiện trên website" is on.
 *
 * @property-read Schema $form
 */
class SolarEstimatorPage extends Page
{
    protected static ?string $title = 'Công cụ ước tính điện mặt trời';

    protected static ?string $navigationLabel = 'Công cụ ước tính';

    protected static ?string $slug = 'cong-cu-uoc-tinh';

    protected static string|UnitEnum|null $navigationGroup = 'Bán hàng';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = auth('admin')->user();

        return $user instanceof User && $user->hasPermission('settings');
    }

    public function mount(): void
    {
        $c = app(SolarEstimator::class)->config();
        $this->form->fill([
            'enabled' => (bool) $c['enabled'], 'vat_percent' => $c['vat_percent'], 'business_price' => $c['business_price'],
            'price_household' => $c['price_per_kwp']['household'], 'price_business' => $c['price_per_kwp']['business'],
            'hybrid_extra_per_kwp' => $c['hybrid_extra_per_kwp'], 'battery_price_per_kwh' => $c['battery_price_per_kwh'],
            'panel_watt' => $c['panel_watt'], 'panel_area_m2' => $c['panel_area_m2'], 'price_spread_percent' => $c['price_spread_percent'],
            'match_percent' => $c['match_percent'],
            'regions' => collect($c['regions'])->map(fn ($region, $key) => ['key' => $key, 'label' => $region[0], 'sun' => $region[1]])->values()->all(),
            'tiers' => collect($c['tiers'])->map(fn ($tier) => ['up_to' => $tier[0], 'price' => $tier[1]])->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $money = fn (string $name, string $label) => TextInput::make($name)->label($label)->numeric()->required()->minValue(1)->suffix('đ');

        return $schema->statePath('data')->components([
            Section::make('Hiển thị')->schema([
                Toggle::make('enabled')->label('Hiện trang "Ước tính điện mặt trời" trên website')
                    ->helperText('Kiểm các đơn giá bên dưới và bấm "Thử tính" trước khi bật.'),
            ]),
            Section::make('Đơn giá hệ thống')->columns(2)->schema([
                $money('price_household', 'Hộ gia đình, mỗi kWp (hòa lưới)'),
                $money('price_business', 'Doanh nghiệp, mỗi kWp (hòa lưới)'),
                $money('hybrid_extra_per_kwp', 'Cộng thêm mỗi kWp khi dùng inverter hybrid'),
                $money('battery_price_per_kwh', 'Pin lưu trữ, mỗi kWh'),
                TextInput::make('price_spread_percent')->label('Khoảng giá hiển thị ±')->numeric()->required()->minValue(0)->maxValue(40)->suffix('%'),
                TextInput::make('panel_watt')->label('Công suất một tấm pin')->numeric()->required()->minValue(300)->maxValue(800)->suffix('W'),
                TextInput::make('panel_area_m2')->label('Diện tích một tấm')->numeric()->required()->minValue(1)->maxValue(4)->suffix('m²'),
                TextInput::make('match_percent')->label('Phần sản lượng được dùng ngay')->numeric()->required()->minValue(40)->maxValue(100)->suffix('%')
                    ->helperText('Ngày mây, cuối tuần không dùng hết: chỉ phần này được tính là tiết kiệm.'),
            ]),
            Section::make('Giờ nắng theo khu vực')->description('Sản lượng trung bình của 1 kWp lắp đúng hướng, kWh mỗi ngày.')->schema([
                Repeater::make('regions')->hiddenLabel()->addable(false)->deletable(false)->reorderable(false)->columns(3)->schema([
                    Hidden::make('key'),
                    TextInput::make('label')->label('Khu vực')->required()->maxLength(40),
                    TextInput::make('sun')->label('kWh/kWp/ngày')->numeric()->required()->minValue(2)->maxValue(6),
                ]),
            ]),
            Section::make('Giá điện')->columns(2)->schema([
                TextInput::make('vat_percent')->label('VAT trên tiền điện')->numeric()->required()->minValue(0)->maxValue(15)->suffix('%'),
                $money('business_price', 'Doanh nghiệp: đơn giá bình quân mỗi kWh (trước VAT)')
                    ->helperText('Chỉ là giả định nếu khách chưa nhập giá trên hóa đơn; biểu giá thực tế phụ thuộc khung giờ và cấp điện áp.'),
                Repeater::make('tiers')->label('Bậc giá điện sinh hoạt (trước VAT)')->columnSpanFull()->columns(2)->minItems(1)->maxItems(8)->reorderable(false)
                    ->helperText('Bậc cuối để trống "Đến kWh" (không giới hạn). Mặc định: Quyết định 2892/QĐ-BCT, hiệu lực 01/11/2026; kiểm lại cấu hình đã lưu trước khi bật.')
                    ->schema([
                        TextInput::make('up_to')->label('Đến kWh')->numeric()->minValue(1),
                        $money('price', 'Giá mỗi kWh'),
                    ]),
            ]),
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])->id('form')->livewireSubmitHandler('save')
                ->footer([Actions::make([
                    Action::make('save')->label('Lưu thay đổi')->submit('save')->keyBindings(['mod+s']),
                    Action::make('try')->label('Thử tính')->color('gray')->icon('heroicon-o-calculator')
                        ->schema([
                            Select::make('type')->label('Loại công trình')->options(SolarEstimator::TYPES)->default('household')->required(),
                            Select::make('region')->label('Khu vực')->options(fn () => collect($this->data['regions'] ?? [])->pluck('label', 'key')->all())->default('tay-nguyen')->required(),
                            TextInput::make('bill')->label('Tiền điện mỗi tháng')->numeric()->default(2000000)->suffix('đ')->required(),
                            Toggle::make('battery')->label('Có pin lưu trữ'),
                        ])
                        ->action(fn (array $data) => $this->tryOut($data)),
                ])]),
        ]);
    }

    /** Runs the figures on the form (saved or not) so the owner sees what a visitor would see. */
    public function tryOut(array $input): void
    {
        $draft = new class($this->values($this->data ?? [])) extends SiteSettings
        {
            public function __construct(private readonly array $draft) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === SolarEstimator::SETTING ? $this->draft : $default;
            }
        };
        $answer = (new SolarEstimator($draft))->estimate($input + ['habit' => 'mixed']);
        Notification::make()->persistent()
            ->title($answer['ok'] ? 'Kết quả với số liệu đang nhập' : 'Chưa tính được')
            ->body($answer['ok'] ? nl2br(e($answer['result']['summary'])) : implode('. ', $answer['errors']))
            ->{$answer['ok'] ? 'info' : 'danger'}()->send();
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $after = $this->values($data);
        $errors = SolarEstimator::configurationErrors($after);
        if ($errors !== []) {
            throw ValidationException::withMessages(['data.tiers' => implode('. ', $errors)]);
        }
        $before = app(SolarEstimator::class)->config();
        DB::transaction(function () use ($before, $after) {
            DB::table('settings')->updateOrInsert(['key' => SolarEstimator::SETTING],
                ['value' => json_encode($after, JSON_UNESCAPED_UNICODE), 'updated_at' => now(), 'created_at' => now()]);
            [$from, $to] = Audit::diff(['solar_estimator' => json_encode($before)], ['solar_estimator' => json_encode($after)]);
            if ($to !== []) {
                Audit::record('settings.update', 'Settings', null, $from, $to);
            }
        });
        app(SiteSettings::class)->clear();
        Notification::make()->title('Đã lưu công cụ ước tính')->success()->send();
    }

    /** @return array<string, mixed> the stored shape */
    private function values(array $data): array
    {
        $tiers = collect($data['tiers'] ?? [])->map(fn ($tier) => [is_numeric($tier['up_to'] ?? null) ? (int) $tier['up_to'] : null, (int) ($tier['price'] ?? 0)])->values()->all();

        return [
            'enabled' => ! empty($data['enabled']),
            'vat_percent' => (float) $data['vat_percent'], 'business_price' => (int) $data['business_price'],
            'price_per_kwp' => ['household' => (int) $data['price_household'], 'business' => (int) $data['price_business']],
            'hybrid_extra_per_kwp' => (int) $data['hybrid_extra_per_kwp'], 'battery_price_per_kwh' => (int) $data['battery_price_per_kwh'],
            'panel_watt' => (int) $data['panel_watt'], 'panel_area_m2' => (float) $data['panel_area_m2'],
            'price_spread_percent' => (float) $data['price_spread_percent'], 'match_percent' => (float) $data['match_percent'],
            'regions' => collect($data['regions'] ?? [])->mapWithKeys(fn ($region) => [(string) $region['key'] => [(string) $region['label'], (float) $region['sun']]])->all(),
            'tiers' => $tiers,
            'min_kwp' => SolarEstimator::DEFAULTS['min_kwp'], 'max_kwp' => SolarEstimator::DEFAULTS['max_kwp'],
        ];
    }
}
