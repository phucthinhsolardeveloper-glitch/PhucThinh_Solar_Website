<?php

namespace App\Models\Legacy;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * An admin account in the PHP-era table_user. While both admins run side by side this model is
 * read-only: logging in through Laravel never writes the password, session or token columns,
 * so a session open in the old admin is not thrown out.
 *
 * @property int $id
 * @property string $username
 * @property string|null $password
 * @property string|null $fullname
 * @property string|null $status
 * @property int|null $role
 * @property int|null $id_permission
 */
class AdminUser extends Model implements AuthenticatableContract, FilamentUser, HasName
{
    use Authenticatable;

    protected $connection = 'legacy';

    protected $table = 'table_user';

    public $timestamps = false;

    protected $hidden = ['password', 'login_session', 'user_token', 'secret_key', 'confirm_code'];

    protected static function booted(): void
    {
        $refuse = static fn () => false;
        static::saving($refuse);
        static::deleting($refuse);
    }

    public function isActive(): bool
    {
        return in_array('hienthi', explode(',', (string) $this->status), true);
    }

    public function isSuperAdmin(): bool
    {
        return (int) $this->role === (int) config('legacy.super_admin_role');
    }

    /**
     * Permission keys of the user's active group, as adminLoadPermissions() reads them.
     *
     * @return list<string>
     */
    public function permissionKeys(): array
    {
        if (! $this->id_permission) {
            return [];
        }
        $groupActive = DB::connection('legacy')->table('table_permission_group')
            ->where('id', $this->id_permission)
            ->whereRaw("find_in_set('hienthi', status)")
            ->exists();
        if (! $groupActive) {
            return [];
        }

        return DB::connection('legacy')->table('table_permission')
            ->where('id_permission_group', $this->id_permission)
            ->where('permission', '!=', '')
            ->distinct()
            ->pluck('permission')
            ->map(fn ($key) => (string) $key)
            ->values()
            ->all();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isActive();
    }

    public function getFilamentName(): string
    {
        return $this->fullname ?: $this->username;
    }

    public function getRememberTokenName(): string
    {
        // table_user has no remember-token column; returning '' tells Laravel not to write one.
        return '';
    }
}
