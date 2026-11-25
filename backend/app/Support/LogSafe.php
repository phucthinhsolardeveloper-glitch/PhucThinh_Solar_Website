<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Throwable;

/**
 * What a log may say about a failure while customer data is being written or sent. The message is left out:
 * a database error repeats the statement with its values, a mail server error may name the recipient.
 */
final class LogSafe
{
    /** Exception class, its error codes (database, or the mail server's reply) and the line that threw. */
    public static function error(Throwable $e): string
    {
        $codes = match (true) {
            $e instanceof QueryException => ' SQLSTATE '.$e->getCode().' driver '.($e->errorInfo[1] ?? '-'),
            is_int($e->getCode()) && $e->getCode() !== 0 => ' code '.$e->getCode(),
            default => '',
        };

        return $e::class.$codes.' at '.basename($e->getFile()).':'.$e->getLine();
    }
}
