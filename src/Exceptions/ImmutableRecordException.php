<?php

namespace BoldlyGrow\AuditLog\Exceptions;

use RuntimeException;

/**
 * Thrown when an operation violates the immutability policy configured in
 * `config('audit-log.immutable')`.
 */
class ImmutableRecordException extends RuntimeException
{
    public static function update(): self
    {
        return new self(
            'Audit log records are immutable and cannot be updated. Set '
            . 'config("audit-log.immutable.update") to false to allow updates.'
        );
    }

    public static function destroy(): self
    {
        return new self(
            'Audit log records are immutable and cannot be permanently deleted. Soft '
            . 'delete instead, or set config("audit-log.immutable.destroy") to false '
            . 'to allow force deletes.'
        );
    }
}
