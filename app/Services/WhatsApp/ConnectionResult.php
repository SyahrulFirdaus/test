<?php

namespace App\Services\WhatsApp;

final class ConnectionResult
{
    private function __construct(
        public readonly bool $connected,
        public readonly string $message,
        public readonly ?string $senderId = null,
    ) {}

    public static function connected(string $message, ?string $senderId = null): self
    {
        return new self(true, $message, $senderId);
    }

    public static function disconnected(string $message): self
    {
        return new self(false, $message);
    }
}
