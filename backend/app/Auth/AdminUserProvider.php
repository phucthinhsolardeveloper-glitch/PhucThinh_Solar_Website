<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Hash;

/**
 * Admins sign in with their username. A carried-over md5 hash is accepted once and replaced with
 * bcrypt on that sign-in; inactive accounts are never found.
 */
class AdminUserProvider extends EloquentUserProvider
{
    public function __construct(Hasher $hasher, private readonly PasswordVerifier $verifier)
    {
        parent::__construct($hasher, User::class);
    }

    public function retrieveById($identifier): ?Authenticatable
    {
        $user = parent::retrieveById($identifier);

        return $user instanceof User && $user->is_active ? $user : null;
    }

    public function retrieveByToken($identifier, $token): ?Authenticatable
    {
        $user = parent::retrieveByToken($identifier, $token);

        return $user instanceof User && $user->is_active ? $user : null;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $username = trim((string) ($credentials['username'] ?? ''));
        if ($username === '') {
            return null;
        }

        return User::query()->where('username', $username)->where('is_active', true)->first();
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
        if (! $force && ! $this->verifier->needsUpgrade($user->getAuthPassword())) {
            return;
        }
        $user->forceFill([$user->getAuthPasswordName() => Hash::make((string) $credentials['password'])])->save();
    }
}
