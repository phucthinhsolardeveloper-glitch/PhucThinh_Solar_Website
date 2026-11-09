<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

// Every feature test runs inside a transaction on the app database and leaves nothing behind.
pest()->extend(TestCase::class)->use(DatabaseTransactions::class)->in('Feature');

/** Sends a request carrying the session cookie of the previous one, like a browser. */
function shop(string $method, string $uri, array $body = [])
{
    // Cookies come back already encrypted, so they are replayed as they are.
    $test = test()->withUnencryptedCookies($GLOBALS['shopCookies']);
    $response = $method === 'GET' ? $test->getJson($uri) : $test->postJson($uri, $body);
    foreach ($response->headers->getCookies() as $cookie) {
        $GLOBALS['shopCookies'][$cookie->getName()] = $cookie->getValue();
    }

    return $response;
}
