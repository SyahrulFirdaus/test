<?php

namespace App\Services\WhatsApp;

final class SendResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
    ) {}

    public static function sent(?string $messageId = null): self
    {
        return new self(true, $messageId);
    }

    public static function failed(string $error): self
    {
        return new self(false, null, $error);
    }
}
