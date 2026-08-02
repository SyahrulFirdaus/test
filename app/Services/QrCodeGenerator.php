<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Writer;

/**
 * Pembuat QR code dalam bentuk SVG.
 *
 * Sengaja memakai backend SVG, bukan PNG: PHP di lingkungan ini tidak memuat
 * ekstensi GD maupun Imagick, sedangkan SVG dihasilkan murni oleh PHP dan tetap
 * dapat disematkan ke dalam PDF melalui data URI.
 */
class QrCodeGenerator
{
    public function __construct(
        private readonly string $foreground = '#95271D',
        private readonly int $size = 320,
        private readonly int $margin = 1,
    ) {}

    public function svg(string $text): string
    {
        [$r, $g, $b] = $this->rgb($this->foreground);

        $style = new RendererStyle(
            size: $this->size,
            margin: $this->margin,
            fill: Fill::uniformColor(new Rgb(255, 255, 255), new Rgb($r, $g, $b)),
        );

        $writer = new Writer(new ImageRenderer($style, new SvgImageBackEnd()));

        // Level M menyisakan ruang koreksi kesalahan ~15%, cukup agar QR tetap
        // terbaca meski dokumen dicetak lalu sedikit kotor atau terlipat.
        return $writer->writeString($text, Encoder::DEFAULT_BYTE_MODE_ENCODING, ErrorCorrectionLevel::M());
    }

    /** Data URI siap dipakai pada atribut src, termasuk di dalam PDF. */
    public function dataUri(string $text): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode($this->svg($text));
    }

    /** @return array{int, int, int} */
    private function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }
}
