<?php

namespace App\Exceptions;

use RuntimeException;

final class SecurityScheduleImportException extends RuntimeException
{
    /**
     * @param  array<int, string>  $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('Import jadwal security gagal.');
    }
}
