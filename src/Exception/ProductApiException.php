<?php

namespace App\Exception;

use RuntimeException;
use Throwable;

final class ProductApiException extends RuntimeException
{
    public function __construct(?Throwable $previous = null)
    {
        parent::__construct('Le catalogue produits est momentanément indisponible. Veuillez réessayer dans quelques instants.', 0, $previous);
    }
}
