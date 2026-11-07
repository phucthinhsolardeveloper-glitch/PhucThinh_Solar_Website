<?php

use App\Auth\PasswordVerifier;

// Old md5 hashes from the previous admin and modern password_hash() values.

it('accepts the old md5 form with secret and salt', function () {
    $verifier = new PasswordVerifier('S3cret', 'Pepper');

    expect($verifier->check('mat-khau', md5('S3cretmat-khauPepper')))->toBeTrue()
        ->and($verifier->check('sai', md5('S3cretmat-khauPepper')))->toBeFalse()
        ->and($verifier->check('mat-khau', md5('mat-khau')))->toBeFalse();
});

it('accepts modern hashes and refuses an empty one', function () {
    $verifier = new PasswordVerifier('S3cret', 'Pepper');
    $hash = password_hash('mat-khau', PASSWORD_DEFAULT);

    expect($verifier->check('mat-khau', $hash))->toBeTrue()
        ->and($verifier->check('sai', $hash))->toBeFalse()
        ->and($verifier->check('mat-khau', ''))->toBeFalse()
        ->and($verifier->check('mat-khau', null))->toBeFalse();
});

it('flags md5 hashes for upgrade but not current ones', function () {
    $verifier = new PasswordVerifier('S3cret', 'Pepper');

    expect($verifier->needsUpgrade(md5('x')))->toBeTrue()
        ->and($verifier->needsUpgrade(password_hash('x', PASSWORD_DEFAULT)))->toBeFalse();
});
