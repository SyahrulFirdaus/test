<?php

namespace App\Support;

/** Asal lead: formulir landing page layanan atau quotation dari 3D Models. */
class LeadSource
{
    public const FORM = 'form';

    public const QUOTATION = 'quotation';

    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            self::FORM => 'Form',
            self::QUOTATION => 'Quotation',
        ];
    }

    public static function label(?string $source): string
    {
        return self::all()[$source] ?? '—';
    }
}
