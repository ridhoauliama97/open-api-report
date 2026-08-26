<?php

namespace App\Exceptions;

class GotenbergConversionException extends GotenbergPdfException
{
    public static function fromStatus(int $status): self
    {
        return new self(sprintf(
            'Gotenberg gagal memproses dokumen (HTTP %d). Coba lagi nanti.',
            $status
        ));
    }
}
