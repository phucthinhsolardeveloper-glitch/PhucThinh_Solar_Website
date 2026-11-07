<?php

namespace App\Auth;

/**
 * Checks admin passwords. Bcrypt/argon hashes go through password_verify(); accounts carried over
 * from the old admin may still hold md5(secret . password . salt), compared in constant time.
 */
class PasswordVerifier
{
    public function __construct(
        private readonly string $secret,
        private readonly string $salt,
    ) {}

    public function check(string $password, ?string $storedHash): bool
    {
        $storedHash = (string) $storedHash;
        if ($storedHash === '') {
            return false;
        }
        if (! empty(password_get_info($storedHash)['algo'])) {
            return password_verify($password, $storedHash);
        }

        return hash_equals($storedHash, md5($this->secret.$password.$this->salt));
    }

    public function needsUpgrade(?string $storedHash): bool
    {
        $info = password_get_info((string) $storedHash);

        return empty($info['algo']) || password_needs_rehash((string) $storedHash, PASSWORD_DEFAULT);
    }
}
