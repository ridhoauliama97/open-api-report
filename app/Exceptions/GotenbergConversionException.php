<?php

namespace App\Exceptions;

class GotenbergConversionException extends GotenbergPdfException
{
    public function __construct(string $message, private readonly ?int $status = null)
    {
        parent::__construct($message);
    }

    public static function fromStatus(int $status): self
    {
        return new self(sprintf(
            'Gotenberg gagal memproses dokumen (HTTP %d). Coba lagi nanti.',
            $status
        ), $status);
    }

    public function status(): ?int
    {
        return $this->status;
    }
}
