<?php

namespace App\Domain\Shipping\NhatTin;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Calls Nhất Tín, or answers locally in mock mode. Only the two documented hosts can be reached;
 * production needs an explicit unlock on the server, and writes need their own switch.
 */
final class NtlClient
{
    private const WRITES = ['/v3/bill/create', '/v3/bill/destroy', '/v3/bill/update-shipping', '/v3/bill/revert-bill'];

    public function mode(): string
    {
        $mode = (string) config('shipping.nhattin.mode', 'mock');

        return in_array($mode, ['mock', 'sandbox', 'production'], true) ? $mode : 'mock';
    }

    /** @return array{ready: bool, reason: string} */
    public function readiness(): array
    {
        $c = (array) config('shipping.nhattin');
        $mode = $this->mode();
        if ($mode === 'mock') {
            return ['ready' => true, 'reason' => 'Mô phỏng: không gọi Nhất Tín, mã vận đơn và cước chỉ để thử quy trình.'];
        }
        if ($mode === 'production' && ! ($c['production_unlocked'] ?? false)) {
            return ['ready' => false, 'reason' => 'Production đang khóa (cần NTL_PRODUCTION_UNLOCKED=1 trên server).'];
        }
        $missing = array_values(array_filter(['username', 'password', 'partner_id'], fn ($key) => trim((string) ($c[$key] ?? '')) === ''));

        return $missing === [] ? ['ready' => true, 'reason' => ucfirst($mode).': đã có tài khoản.'.(($c['write_enabled'] ?? false) ? '' : ' Tạo/hủy vận đơn đang tắt.')]
            : ['ready' => false, 'reason' => 'Thiếu '.implode(', ', $missing).'.'];
    }

    /** @return array<string, mixed> decoded body of a successful call */
    public function call(string $method, string $path, array $body = []): array
    {
        $mode = $this->mode();
        if ($mode === 'mock') {
            return app(NtlMock::class)->answer($method, $path, $body);
        }
        $ready = $this->readiness();
        if (! $ready['ready']) {
            throw new NtlException($ready['reason'], 'NOT_READY');
        }
        if (in_array($path, self::WRITES, true) && ! config('shipping.nhattin.write_enabled')) {
            throw new NtlException('Tạo/sửa/hủy vận đơn đang tắt (NTL_WRITE_OPERATIONS_ENABLED).', 'WRITE_DISABLED');
        }
        $response = $this->send($method, $path, $body, $this->token());
        if ($response['status'] === 401) {
            Cache::forget($this->cacheKey());
            $response = $this->send($method, $path, $body, $this->token());
        }
        $data = $response['body'];
        if ($response['status'] >= 400 || ! is_array($data) || ($data['success'] ?? true) === false) {
            $message = is_array($data) ? (string) ($data['message'] ?? $data['msg'] ?? '') : '';
            throw new NtlException('Nhất Tín trả lỗi '.$response['status'].($message !== '' ? ': '.mb_substr($message, 0, 200) : '.'), $response['status'] === 429 ? 'RATE_LIMITED' : 'PROVIDER_ERROR');
        }

        return $data;
    }

    /** @return array{status: int, body: mixed} */
    private function send(string $method, string $path, array $body, string $token): array
    {
        $url = rtrim((string) config('shipping.nhattin.hosts.'.$this->mode()), '/').$path;
        try {
            $request = Http::withToken($token)->acceptJson()->timeout((int) config('shipping.nhattin.timeout', 10));
            $response = $method === 'GET' ? $request->get($url, $body) : $request->post($url, $body);
        } catch (ConnectionException) {
            // A create that timed out may still have been made at Nhất Tín.
            throw new NtlException('Không nhận được trả lời từ Nhất Tín.', 'TIMEOUT', in_array($path, self::WRITES, true));
        }

        return ['status' => $response->status(), 'body' => $response->json()];
    }

    private function token(): string
    {
        return Cache::lock($this->cacheKey().':lock', 15)->block(10, function () {
            $cached = Cache::get($this->cacheKey());
            if (is_array($cached) && ($cached['expires'] ?? 0) > time() + 5) {
                return (string) $cached['token'];
            }
            $c = (array) config('shipping.nhattin');
            $response = Http::acceptJson()->timeout((int) $c['timeout'])
                ->post(rtrim((string) $c['hosts'][$this->mode()], '/').'/v1/auth/sign-in', ['username' => $c['username'], 'password' => $c['password']]);
            $data = (array) $response->json('data', []);
            if (! $response->successful() || empty($data['jwt_token'])) {
                throw new NtlException('Đăng nhập Nhất Tín không thành công ('.$response->status().').', 'AUTH_FAILED');
            }
            $ttl = self::seconds($data['token_expires_in'] ?? '60s');
            Cache::put($this->cacheKey(), ['token' => $data['jwt_token'], 'expires' => time() + $ttl], $ttl);

            return (string) $data['jwt_token'];
        });
    }

    /** "1m", "2h", "30s" or a number of seconds. */
    private static function seconds(mixed $value): int
    {
        if (is_numeric($value)) {
            return max(10, (int) $value);
        }
        if (preg_match('/^(\d+)\s*([smhd])$/i', (string) $value, $m)) {
            return max(10, (int) $m[1] * ['s' => 1, 'm' => 60, 'h' => 3600, 'd' => 86400][strtolower($m[2])]);
        }

        return 60;
    }

    private function cacheKey(): string
    {
        return 'ntl:token:'.$this->mode();
    }
}
