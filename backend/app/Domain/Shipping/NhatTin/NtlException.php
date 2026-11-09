<?php

namespace App\Domain\Shipping\NhatTin;

use RuntimeException;

/** A Nhất Tín call that failed. $unknown means the request may have reached Nhất Tín: check before retrying. */
final class NtlException extends RuntimeException
{
    public function __construct(string $message, public readonly string $code_name = 'ERROR', public readonly bool $unknown = false)
    {
        parent::__construct($message);
    }
}
