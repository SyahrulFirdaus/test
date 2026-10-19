<?php

namespace Tests\Feature;

use Database\Seeders\ClientSeeder;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TechnologySeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Gambar pratinjau tautan (og:image / twitter:image) harus JPG 1200×630 —
 * SVG tidak ditampilkan WhatsApp, Facebook, maupun LinkedIn.
 */
class SocialPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            CompanyProfileSeeder::class,
            ServiceSeeder::class,
            TechnologySeeder::class,
            ClientSeeder::class,
            TestimonialSeeder::class,
        ]);
    }

    public static function pages(): array
    {
        return [
            'home' => ['/', 'default'],
            'services' => ['/services', 'services'],
            'technologies' => ['/technologies', 'technologies'],
            'industri' => ['/industri', 'industries'],
            'detail industri' => ['/industri/otomotif-motorsport', 'industries'],
            '3d models' => ['/3d-models', 'models'],
            'panduan' => ['/3d-models/panduan', 'models'],
            'about' => ['/about', 'about'],
            'tracking' => ['/tracking', 'tracking'],
            'kebijakan privasi' => ['/kebijakan-privasi', 'legal'],
            'syarat ketentuan' => ['/syarat-ketentuan', 'legal'],
        ];
    }

    #[DataProvider('pages')]
    public function test_halaman_memakai_gambar_pratinjau_jpg_yang_sesuai(string $path, string $image): void
    {
        $html = $this->get($path)->assertOk()->getContent();

        $url = asset("images/og/{$image}.jpg");

        $this->assertStringContainsString('<meta property="og:image" content="'.$url.'?v=', $html);
        $this->assertStringContainsString('<meta name="twitter:image" content="'.$url.'?v=', $html);
        $this->assertStringContainsString('<meta property="og:image:width" content="1200">', $html);
        $this->assertStringContainsString('<meta property="og:image:height" content="630">', $html);
        $this->assertStringContainsString('<meta property="og:image:type" content="image/jpeg">', $html);
        $this->assertStringNotContainsString('.svg"', $this->metaImages($html));
    }

    public function test_seluruh_gambar_pratinjau_berukuran_1200x630_dan_ringan(): void
    {
        foreach (['default', 'services', 'technologies', 'industries', 'models', 'about', 'tracking', 'legal'] as $name) {
            $file = public_path("images/og/{$name}.jpg");

            $this->assertFileExists($file);
            [$width, $height, $type] = getimagesize($file);

            $this->assertSame([1200, 630, IMAGETYPE_JPEG], [$width, $height, $type], $name);
            // WhatsApp kerap tidak menampilkan pratinjau di atas ±300 KB.
            $this->assertLessThan(300 * 1024, filesize($file), $name);
        }
    }

    private function metaImages(string $html): string
    {
        preg_match_all('/<meta (?:property|name)="(?:og|twitter):image[^"]*" content="[^"]*"/', $html, $matches);

        return implode("\n", $matches[0]);
    }
}
