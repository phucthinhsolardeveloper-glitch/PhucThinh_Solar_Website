<?php

namespace App\Legacy\Auth;

/**
 * The PHP admin's password check (libraries/login_security.php): modern hashes through
 * password_verify(), older ones as md5(secret . password . salt) compared in constant time.
 * It only verifies. Upgrading the stored hash belongs to whichever side owns table_user.
 */
class LegacyPasswordVerifier
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
