<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

// Every feature test runs inside a transaction on the app database and leaves nothing behind.
pest()->extend(TestCase::class)->use(DatabaseTransactions::class)->in('Feature');
