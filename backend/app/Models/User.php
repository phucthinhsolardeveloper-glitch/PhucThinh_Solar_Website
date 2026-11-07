<?php

namespace App\Models;

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
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_OWNER = 'owner';

    public const ROLE_EDITOR = 'editor';

    /** Permission areas an editor can be given; owners have all of them. */
    public const PERMISSIONS = [
        'catalog' => 'Sản phẩm, danh mục, thương hiệu',
        'content' => 'Bài viết, giải pháp, dự án, nội dung trang',
        'media' => 'Ảnh vị trí, thư viện ảnh, video',
        'orders' => 'Đơn hàng, phí giao hàng, thanh toán',
        'leads' => 'Liên hệ, tư vấn, đăng ký đại lý',
        'reviews' => 'Đánh giá sản phẩm',
        'settings' => 'Thông tin website, SEO trang',
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

    public function getFilamentName(): string
    {
        return $this->name ?: (string) $this->username;
    }
}
