<?php

namespace App\Legacy\Auth;

use App\Models\Legacy\AdminUser;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;

/**
 * Finds active admins in table_user and checks passwords with the PHP admin's rules. Nothing here
 * writes to table_user: no hash upgrade, no remember token. That waits until Laravel owns identity.
 */
class LegacyAdminUserProvider implements UserProvider
{
    public function __construct(private readonly LegacyPasswordVerifier $verifier) {}

    public function retrieveById($identifier): ?Authenticatable
    {
        $user = AdminUser::query()->find($identifier);

        return $user && $user->isActive() ? $user : null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token): void {}

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $username = trim((string) ($credentials['username'] ?? ''));
        if ($username === '') {
            return null;
        }

        return AdminUser::query()
            ->where('username', $username)
            ->whereRaw("find_in_set('hienthi', status)")
            ->first();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        return $this->verifier->check((string) ($credentials['password'] ?? ''), $user->getAuthPassword());
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // Deliberately empty while the PHP admin still owns table_user.
    }
}
