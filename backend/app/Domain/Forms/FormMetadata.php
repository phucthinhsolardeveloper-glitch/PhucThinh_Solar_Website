<?php

namespace App\Domain\Forms;

use App\Domain\Pages\PageSupport;
use App\Support\FormToken;

/** What a public form needs before it is shown: a token, the province list and the reCAPTCHA site key. */
class FormMetadata extends PageSupport
{
    /** @return list<array{id: int, name: string}> visible provinces in Admin order */
    public function cities(): array
    {
        $cities = [];
        foreach ($this->rows('select id,name from provinces where is_visible=1 order by sort_order asc,id asc') as $row) {
            $name = is_scalar($row['name']) ? trim(strip_tags((string) $row['name'])) : '';
            if ((int) $row['id'] > 0 && $name !== '') {
                $cities[] = ['id' => (int) $row['id'], 'name' => $name];
            }
        }

        return $cities;
    }

    /** @return array{0: int, 1: array<string, mixed>} GET /lead-form (api/pts_v1_lead_form.php GET branch) */
    public function leadForm(array $get): array
    {
        if ($get) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        $active = (bool) config('site.recaptcha.active');

        return [200, ['schemaVersion' => 1, 'csrfToken' => FormToken::issue(), 'cities' => $this->cities(),
            'recaptcha' => ['active' => $active, 'siteKey' => $active ? (string) config('site.recaptcha.site_key') : '']]];
    }

    /** @return array{0: int, 1: array<string, mixed>} GET /checkout-wards?city= (wards of one visible province) */
    public function wards(array $get): array
    {
        $city = isset($get['city']) && is_scalar($get['city']) ? (int) $get['city'] : 0;
        if ($city <= 0 || count($get) !== 1) {
            return [400, ['error' => ['code' => 'BAD_QUERY']]];
        }
        if (! $this->row('select id from provinces where id=? and is_visible=1 limit 1', [$city])) {
            return [404, ['error' => ['code' => 'NOT_FOUND']]];
        }
        $wards = [];
        foreach ($this->rows('select id,name from wards where province_id=? and is_visible=1 order by sort_order asc,id asc', [$city]) as $row) {
            $name = is_scalar($row['name']) ? trim(strip_tags((string) $row['name'])) : '';
            if ((int) $row['id'] > 0 && $name !== '') {
                $wards[] = ['id' => (int) $row['id'], 'name' => $name];
            }
        }

        return [200, ['schemaVersion' => 1, 'locale' => 'vi', 'cityId' => $city, 'wards' => $wards]];
    }
}
