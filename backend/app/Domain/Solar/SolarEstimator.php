<?php

namespace App\Domain\Solar;

use App\Support\SiteSettings;

/**
 * Rough sizing of a rooftop system from a monthly electricity bill: power, panels, roof area, monthly
 * output, saving, price range and payback. Every figure it uses lives in one Admin setting
 * ("solar_estimator"); this class is the only place that does the arithmetic, the storefront only shows it.
 */
final class SolarEstimator
{
    public const SETTING = 'solar_estimator';

    public const TYPES = ['household' => 'Hộ gia đình', 'business' => 'Doanh nghiệp, nhà xưởng'];

    /** Share of the day's use that falls in sunshine hours, by habit. */
    public const HABITS = ['day' => ['Dùng nhiều ban ngày', 0.7], 'mixed' => ['Ngày và tối như nhau', 0.5], 'evening' => ['Chủ yếu buổi tối', 0.3]];

    /**
     * Starting values. Household tariff: Decision 2892/QD-BCT, effective 01/11/2026 (before VAT). Sun: typical yield of a
     * well-placed roof, kWh per kWp per day. Prices are placeholders the owner must confirm before switching on.
     */
    public const DEFAULTS = [
        'enabled' => false,
        'vat_percent' => 8,
        'tiers' => [[100, 1901], [200, 2289], [400, 2894], [700, 3456], [null, 3844]],
        'business_price' => 2900,
        'regions' => ['bac' => ['Miền Bắc', 3.2], 'trung' => ['Miền Trung', 4.0], 'tay-nguyen' => ['Tây Nguyên', 4.3], 'nam' => ['Miền Nam', 4.4]],
        'price_per_kwp' => ['household' => 10500000, 'business' => 9000000],
        'hybrid_extra_per_kwp' => 3500000,
        'battery_price_per_kwh' => 6500000,
        'panel_watt' => 620,
        'panel_area_m2' => 2.6,
        'price_spread_percent' => 12,
        // Not every sunny kWh meets a load at that moment (weekends, cloudy runs): only this share counts as saved.
        'match_percent' => 85,
        'min_kwp' => 2,
        'max_kwp' => ['household' => 30, 'business' => 1000],
    ];

    public function __construct(private readonly SiteSettings $settings) {}

    /** @return array<string, mixed> */
    public function config(): array
    {
        $saved = $this->settings->get(self::SETTING);

        return array_replace(self::DEFAULTS, is_array($saved) ? array_intersect_key($saved, self::DEFAULTS) : []);
    }

    public function enabled(): bool
    {
        return (bool) $this->config()['enabled'];
    }

    /** Reject unsafe settings even when they were written outside Filament. */
    public static function configurationErrors(array $c): array
    {
        $errors = [];
        foreach (['business_price', 'panel_watt', 'panel_area_m2', 'min_kwp'] as $key) {
            if (! is_numeric($c[$key] ?? null) || (float) $c[$key] <= 0) {
                $errors[] = 'Cấu hình '.$key.' phải lớn hơn 0';
            }
        }
        if (! is_numeric($c['vat_percent'] ?? null) || (float) $c['vat_percent'] < 0 || (float) $c['vat_percent'] > 15) {
            $errors[] = 'VAT phải từ 0 đến 15%';
        }
        if (! is_numeric($c['match_percent'] ?? null) || (float) $c['match_percent'] <= 0 || (float) $c['match_percent'] > 100) {
            $errors[] = 'Tỷ lệ sử dụng sản lượng phải từ 0 đến 100%';
        }
        if (! is_numeric($c['price_spread_percent'] ?? null) || (float) $c['price_spread_percent'] < 0 || (float) $c['price_spread_percent'] > 40) {
            $errors[] = 'Khoảng giá phải từ 0 đến 40%';
        }
        foreach (['household', 'business'] as $type) {
            foreach (['price_per_kwp', 'max_kwp'] as $key) {
                if (! is_numeric($c[$key][$type] ?? null) || (float) $c[$key][$type] <= 0) {
                    $errors[] = 'Cấu hình '.$key.'.'.$type.' phải lớn hơn 0';
                }
            }
            if (is_numeric($c['max_kwp'][$type] ?? null) && is_numeric($c['min_kwp'] ?? null)
                && (float) $c['max_kwp'][$type] < (float) $c['min_kwp']) {
                $errors[] = 'Công suất tối đa phải lớn hơn công suất tối thiểu';
            }
        }
        foreach (['hybrid_extra_per_kwp', 'battery_price_per_kwh'] as $key) {
            if (! is_numeric($c[$key] ?? null) || (float) $c[$key] <= 0) {
                $errors[] = 'Cấu hình '.$key.' phải lớn hơn 0';
            }
        }
        $tiers = $c['tiers'] ?? null;
        if (! is_array($tiers) || count($tiers) < 2) {
            $errors[] = 'Cần ít nhất hai bậc giá điện';
        } else {
            $previous = 0;
            foreach (array_values($tiers) as $index => $tier) {
                if (! is_array($tier) || ! is_numeric($tier[1] ?? null) || (float) $tier[1] <= 0) {
                    $errors[] = 'Giá điện mỗi bậc phải lớn hơn 0';

                    continue;
                }
                $limit = $tier[0] ?? null;
                if ($index === count($tiers) - 1) {
                    if ($limit !== null) {
                        $errors[] = 'Bậc cuối phải không giới hạn kWh';
                    }
                } elseif (! is_numeric($limit) || (int) $limit <= $previous) {
                    $errors[] = 'Giới hạn kWh phải tăng dần';
                } else {
                    $previous = (int) $limit;
                }
            }
        }
        if (! is_array($c['regions'] ?? null) || $c['regions'] === []) {
            $errors[] = 'Cần ít nhất một khu vực có sản lượng hợp lệ';
        }
        foreach (is_array($c['regions'] ?? null) ? $c['regions'] : [] as $region) {
            if (! is_array($region) || ! is_numeric($region[1] ?? null) || (float) $region[1] <= 0) {
                $errors[] = 'Sản lượng theo khu vực phải lớn hơn 0';
            }
        }

        return $errors;
    }

    /** What the form needs: choices and the ranges the inputs accept. */
    public function form(): array
    {
        $c = $this->config();

        return [
            'enabled' => (bool) $c['enabled'],
            'types' => collect(self::TYPES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values()->all(),
            'habits' => collect(self::HABITS)->map(fn ($habit, $key) => ['key' => $key, 'label' => $habit[0]])->values()->all(),
            'regions' => collect($c['regions'])->map(fn ($region, $key) => ['key' => (string) $key, 'label' => (string) $region[0]])->values()->all(),
            'bill' => ['household' => ['min' => 300000, 'max' => 20000000, 'default' => 2000000], 'business' => ['min' => 3000000, 'max' => 2000000000, 'default' => 30000000]],
            'businessPrice' => (int) $c['business_price'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input  type, bill (VND incl. VAT), region, habit, battery (bool), roof_m2 (optional), price (business VND/kWh, optional)
     * @return array{ok: bool, errors?: list<string>, result?: array<string, mixed>}
     */
    public function estimate(array $input): array
    {
        $c = $this->config();
        $type = isset(self::TYPES[$input['type'] ?? '']) ? (string) $input['type'] : '';
        $region = isset($c['regions'][$input['region'] ?? '']) ? (string) $input['region'] : '';
        $habit = isset(self::HABITS[$input['habit'] ?? '']) ? (string) $input['habit'] : 'mixed';
        $bill = is_numeric($input['bill'] ?? null) ? (float) $input['bill'] : 0.0;
        $limits = $this->form()['bill'][$type] ?? null;
        $errors = [];
        if ($type === '') {
            $errors[] = 'Chọn loại công trình';
        }
        if ($region === '') {
            $errors[] = 'Chọn khu vực lắp đặt';
        }
        if ($limits && ($bill < $limits['min'] || $bill > $limits['max'])) {
            $errors[] = 'Tiền điện mỗi tháng từ '.self::money($limits['min']).' đến '.self::money($limits['max']);
        }
        $errors = array_merge($errors, self::configurationErrors($c));
        if (array_key_exists('roof_m2', $input) && (! is_numeric($input['roof_m2']) || (float) $input['roof_m2'] <= 0)) {
            $errors[] = 'Diện tích mái phải lớn hơn 0';
        }
        if ($type === 'business' && array_key_exists('price', $input)
            && (! is_numeric($input['price']) || (float) $input['price'] < 1000 || (float) $input['price'] > 10000)) {
            $errors[] = 'Giá điện doanh nghiệp phải từ 1.000 đến 10.000đ/kWh';
        }
        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        $vat = 1 + (float) $c['vat_percent'] / 100;
        $price = $type === 'business' ? (is_numeric($input['price'] ?? null) && (float) $input['price'] >= 1000 && (float) $input['price'] <= 10000 ? (float) $input['price'] : (float) $c['business_price']) : 0.0;
        $usage = $type === 'household' ? $this->kwhForBill($bill / $vat, $c['tiers']) : $bill / $vat / $price;
        $share = self::HABITS[$habit][1];
        $sun = (float) $c['regions'][$region][1];
        $battery = ! empty($input['battery']);

        // Size for what is used while the sun is up (plus what the battery stores for the evening).
        $target = $usage * ($battery ? min(0.9, $share + 0.3) : $share);
        $kwp = $target / ($sun * 30);
        $roofPanels = array_key_exists('roof_m2', $input)
            ? (int) floor((float) $input['roof_m2'] / ((float) $c['panel_area_m2'] * 1.15)) : null;
        $minimumPanels = (int) ceil((float) $c['min_kwp'] * 1000 / (float) $c['panel_watt']);
        if ($roofPanels !== null && $roofPanels < $minimumPanels) {
            return ['ok' => false, 'code' => 'roof_insufficient', 'errors' => ['Diện tích mái chưa đủ cho hệ tối thiểu. Vui lòng để lại thông tin để được khảo sát thực tế.']];
        }
        $targetPanels = max($minimumPanels, (int) ceil(round($kwp * 2) / 2 * 1000 / (float) $c['panel_watt']));
        $limitedByRoof = $roofPanels !== null && $roofPanels < $targetPanels;
        $panels = min($targetPanels, $roofPanels ?? PHP_INT_MAX, (int) floor((float) $c['max_kwp'][$type] * 1000 / (float) $c['panel_watt']));
        $kwp = round($panels * (float) $c['panel_watt'] / 1000, 2);
        $output = $kwp * $sun * 30;
        $used = min($output, $target) * (float) $c['match_percent'] / 100;

        $newBill = $type === 'household' ? $this->billFor(max(0, $usage - $used), $c['tiers']) * $vat : max(0, $usage - $used) * $price * $vat;
        $saving = max(0, $bill - $newBill);
        $batteryKwh = $battery ? max(5, round($usage * (1 - $share) / 30 / 5) * 5) : 0;
        $cost = $kwp * ((float) $c['price_per_kwp'][$type] + ($battery ? (float) $c['hybrid_extra_per_kwp'] : 0)) + $batteryKwh * (float) $c['battery_price_per_kwh'];
        $spread = (float) $c['price_spread_percent'] / 100;
        $low = self::round($cost * (1 - $spread));
        $high = self::round($cost * (1 + $spread));
        $yearly = $saving * 12;

        $result = [
            'type' => $type, 'typeLabel' => self::TYPES[$type], 'region' => $c['regions'][$region][0], 'habit' => self::HABITS[$habit][0], 'battery' => $battery,
            'bill' => (int) round($bill), 'usageKwh' => (int) round($usage), 'kwp' => $kwp, 'panels' => $panels, 'panelWatt' => (int) $c['panel_watt'],
            'roofM2' => (int) ceil($panels * (float) $c['panel_area_m2'] * 1.15), 'limitedByRoof' => $limitedByRoof, 'batteryKwh' => (int) $batteryKwh,
            'outputKwh' => (int) round($output), 'saving' => self::round($saving, 1000), 'newBill' => self::round($newBill, 1000),
            'savingPercent' => $bill > 0 ? (int) round($saving / $bill * 100) : 0,
            'cost' => ['low' => $low, 'high' => $high],
            'payback' => $yearly > 0 ? ['low' => round($low / $yearly, 1), 'high' => round($high / $yearly, 1)] : null,
            'co2Tonnes' => round($output * 12 * 0.6766 / 1000, 1), // grid emission factor Việt Nam 2023: 0.6766 tCO2/MWh
            'basis' => sprintf('Nắng %s kWh/kWp/ngày, tấm %dW, đơn giá %s/kWp%s, VAT %s%%.', rtrim(rtrim(number_format($sun, 1, ',', '.'), '0'), ','), (int) $c['panel_watt'],
                self::money((float) $c['price_per_kwp'][$type] + ($battery ? (float) $c['hybrid_extra_per_kwp'] : 0)),
                $battery ? ', pin lưu trữ '.self::money((float) $c['battery_price_per_kwh']).'/kWh' : '', rtrim(rtrim(number_format((float) $c['vat_percent'], 1, ',', '.'), '0'), ',')),
        ];
        $result['summary'] = $this->summary($result);

        return ['ok' => true, 'result' => $result];
    }

    /** Plain text kept with the lead so staff see what the customer saw. */
    private function summary(array $r): string
    {
        return implode("\n", array_filter([
            'Ước tính điện mặt trời ('.$r['typeLabel'].', '.$r['region'].', '.mb_strtolower($r['habit']).($r['battery'] ? ', có pin lưu trữ' : '').')',
            'Tiền điện hiện tại: '.self::money($r['bill']).'/tháng (~'.number_format($r['usageKwh'], 0, ',', '.').' kWh)',
            'Đề xuất: '.str_replace('.', ',', (string) $r['kwp']).' kWp, '.$r['panels'].' tấm '.$r['panelWatt'].'W, mái ~'.$r['roofM2'].' m²'.($r['batteryKwh'] ? ', pin '.$r['batteryKwh'].' kWh' : ''),
            'Sản lượng ~'.number_format($r['outputKwh'], 0, ',', '.').' kWh/tháng, tiết kiệm ~'.self::money($r['saving']).'/tháng',
            'Chi phí dự kiến: '.self::money($r['cost']['low']).' – '.self::money($r['cost']['high']),
            $r['payback'] ? 'Hoàn vốn ~'.str_replace('.', ',', (string) $r['payback']['low']).'–'.str_replace('.', ',', (string) $r['payback']['high']).' năm' : null,
        ]));
    }

    /** Units bought for a bill before VAT under rising tiers. */
    private function kwhForBill(float $amount, array $tiers): float
    {
        $kwh = 0.0;
        $previous = 0;
        foreach ($tiers as [$upTo, $price]) {
            $span = $upTo === null ? INF : $upTo - $previous;
            $cost = $span * $price;
            if ($amount <= $cost) {
                return $kwh + $amount / $price;
            }
            $amount -= $cost;
            $kwh += $span;
            $previous = $upTo;
        }

        return $kwh;
    }

    private function billFor(float $kwh, array $tiers): float
    {
        $bill = 0.0;
        $previous = 0;
        foreach ($tiers as [$upTo, $price]) {
            $span = $upTo === null ? INF : $upTo - $previous;
            $take = min($kwh, $span);
            $bill += $take * $price;
            $kwh -= $take;
            if ($kwh <= 0) {
                break;
            }
            $previous = $upTo;
        }

        return $bill;
    }

    private static function round(float $value, int $step = 100000): int
    {
        return (int) (round($value / $step) * $step);
    }

    public static function money(float|int $value): string
    {
        return number_format((float) $value, 0, ',', '.').'đ';
    }
}
