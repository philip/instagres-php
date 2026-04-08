<?php

declare(strict_types=1);

namespace Philip\Instagres\Exception;

/**
 * Thrown when caller-supplied arguments are invalid (e.g. empty ref or database id).
 */
class InvalidArgumentException extends InstagresException
{
}
