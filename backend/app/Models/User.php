<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * An admin account. Owners may do everything; editors only what their permission list grants.
 *
 * @property int $id
 * @property string $name
 * @property string|null $username
 * @property string $role
 * @property list<string>|null $permissions
 * @property bool $is_active
 */
#[Fillable(['name', 'username', 'email', 'phone', 'password', 'role', 'permissions', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    use Audited;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_EDITOR = 'editor';

    /** Permission areas an editor can be given; owners have all of them. */
    public const PERMISSIONS = [
        'catalog' => 'Sản phẩm, danh mục, thương hiệu',
        'catalog_bulk' => 'Thao tác: nhập CSV và đổi hiển thị hàng loạt',
        'content' => 'Bài viết, giải pháp, dự án, nội dung trang',
        'content_publish' => 'Thao tác: xuất bản và khôi phục phiên bản nội dung',
        'media' => 'Ảnh vị trí, thư viện ảnh, video',
        'orders' => 'Đơn hàng, phí giao hàng, thanh toán',
        'orders_payment' => 'Thao tác: xác nhận thanh toán thủ công',
        'leads' => 'Liên hệ, tư vấn, đăng ký đại lý',
        'reviews' => 'Đánh giá sản phẩm',
        'reviews_moderate' => 'Thao tác: duyệt, ẩn, sửa và trả lời đánh giá',
        'settings' => 'Thông tin website, SEO trang',
        'settings_mail_test' => 'Thao tác: gửi email thử',
    ];

    private const ACTION_AREAS = [
        'catalog_bulk' => 'catalog', 'content_publish' => 'content', 'orders_payment' => 'orders',
        'reviews_moderate' => 'reviews', 'settings_mail_test' => 'settings',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'permissions' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function isOwner(): bool
    {
        return $this->role === self::ROLE_OWNER;
    }

    public function hasPermission(string $key): bool
    {
        return $this->isOwner() || in_array($key, $this->permissions ?? [], true);
    }

    public function canPerform(string $action): bool
    {
        $area = self::ACTION_AREAS[$action] ?? null;

        return $this->is_active && $area !== null && $this->hasPermission($area)
            && ($this->isOwner() || in_array($action, $this->permissions ?? [], true));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getFilamentName(): string
    {
        return $this->name ?: (string) $this->username;
    }

    /** Attributes whose changes are written to the audit log; deletions are always logged. */
    public static function auditedAttributes(): array
    {
        return ['username', 'role', 'permissions', 'is_active', 'password'];
    }
}
