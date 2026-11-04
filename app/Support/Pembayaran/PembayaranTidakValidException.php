<?php

namespace App\Support\Pembayaran;

use RuntimeException;

class PembayaranTidakValidException extends RuntimeException
{
    public function __construct(
        string $message,
        public ?int $barisId = null,
    ) {
        parent::__construct($message);
    }
}
