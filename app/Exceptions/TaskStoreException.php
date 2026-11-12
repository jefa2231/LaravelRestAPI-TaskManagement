<?php

namespace App\Exceptions;

use RuntimeException;

class TaskStoreException extends RuntimeException
{
    public static function unreadable(string $path, ?string $reason = null): self
    {
        $message = "Penyimpanan tugas tidak dapat dibaca: {$path}";

        if ($reason !== null) {
            $message .= " ({$reason})";
        }

        return new self($message);
    }

    public static function unwritable(string $path): self
    {
        return new self("Penyimpanan tugas tidak dapat ditulis: {$path}");
    }
}
