<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Throwable;

/**
 * What a log may say about a failure while customer data is being written. The message is left out:
 * a database error repeats the statement with its values (name, phone, e-mail, address).
 */
final class LogSafe
{
    /** Exception class, database error codes and the line that threw. */
    public static function error(Throwable $e): string
    {
        $codes = $e instanceof QueryException ? ' SQLSTATE '.$e->getCode().' driver '.($e->errorInfo[1] ?? '-') : '';

        return $e::class.$codes.' at '.basename($e->getFile()).':'.$e->getLine();
    }
}
