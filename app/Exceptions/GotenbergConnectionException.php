<?php

namespace App\Exceptions;

class GotenbergConnectionException extends GotenbergPdfException
{
    public static function unreachable(): self
    {
        return new self('Gotenberg tidak dapat dihubungi. Coba lagi nanti.');
    }
}
