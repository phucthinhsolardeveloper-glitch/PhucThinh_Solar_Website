<?php

namespace App\Domain\Shipping\NhatTin;

/**
 * Local stand-in for Nhất Tín with the documented response shapes. Fees and codes are invented and
 * marked MOCK; nothing leaves the server.
 */
final class NtlMock
{
    /** @return array<string, mixed> */
    public function answer(string $method, string $path, array $body): array
    {
        return match ($path) {
            '/v3/bill/calc-fee' => ['success' => true, 'data' => $this->fees($body)],
            '/v3/bill/create' => ['success' => true, 'data' => [
                'bill_code' => 'MOCK'.strtoupper(substr(hash('sha256', (string) ($body['ref_code'] ?? '')), 0, 10)),
                'ref_code' => $body['ref_code'] ?? '', 'status_id' => ! empty($body['is_draft']) ? 12 : 2,
                'total_fee' => $this->fee($body, (string) ($body['service_id'] ?? '91')), 'expected_at' => now()->addDays(3)->toDateString(),
            ]],
            '/v3/bill/tracking' => ['success' => true, 'data' => [[
                'bill_code' => $body['bill_code'] ?? '', 'bill_status_id' => 15, 'bill_status_desc' => 'Đang vận chuyển (mô phỏng)',
                'histories' => [['status_id' => 2, 'status_name' => 'Chờ lấy hàng', 'time' => now()->subDay()->timestamp],
                    ['status_id' => 15, 'status_name' => 'Đang vận chuyển', 'time' => now()->timestamp]],
            ]]],
            '/v3/bill/destroy' => ['success' => true, 'data' => ['bill_code' => $body['bill_code'] ?? []]],
            default => throw new NtlException('Mô phỏng chưa hỗ trợ '.$method.' '.$path.'.', 'NOT_SUPPORTED'),
        };
    }

    /** @return list<array<string, mixed>> */
    private function fees(array $body): array
    {
        return array_map(fn (string $service) => ['service_id' => (int) $service, 'service_name' => config('shipping.nhattin.services.'.$service),
            'total_fee' => $this->fee($body, $service), 'lead_time' => ['91' => '3-5 ngày', '90' => '2-3 ngày', '81' => '1 ngày'][$service]], ['91', '90', '81']);
    }

    private function fee(array $body, string $service): int
    {
        $weight = max(0.5, (float) ($body['weight'] ?? 1));
        $base = ['91' => 30000, '90' => 42000, '81' => 85000][$service] ?? 40000;

        return (int) (round(($base + max(0, $weight - 1) * 6000) / 1000) * 1000);
    }
}
