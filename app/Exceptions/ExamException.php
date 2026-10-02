<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception métier du moteur d'examen.
 *
 * Le code HTTP par défaut (422) peut être surchargé ; 423 = verrouillé,
 * 403 = non autorisé, 409 = conflit d'état.
 */
class ExamException extends RuntimeException
{
    public function __construct(string $message, int $status = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }

    public static function locked(string $message = 'Cet exercice est verrouillé.'): self
    {
        return new self($message, 423);
    }

    public static function expired(string $message = 'Le temps imparti pour cet exercice est écoulé.'): self
    {
        return new self($message, 423);
    }

    public static function unauthorized(string $message = 'Accès non autorisé à cet exercice.'): self
    {
        return new self($message, 403);
    }
}
