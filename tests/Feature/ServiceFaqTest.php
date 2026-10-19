<?php

namespace Tests\Feature;

use App\Support\FaqSchema;
use App\Support\ServiceFaqs;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FAQ accordion + FAQPage JSON-LD di setiap halaman tujuan iklan.
 */
class ServiceFaqTest extends TestCase
{
    use RefreshDatabase;

    private const PAGES = [
        '3d-printing' => '/services/3d-printing',
        'reverse-engineering' => '/services/reverse-engineering',
        '3d-scanning' => '/services/3d-scanning',
        'product-development' => '/services/product-development',
        '3d-design' => '/services/3d-design',
        'paint-finishing' => '/services/paint-finishing',
        'miniatur' => '/miniatur-teknik',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([CompanyProfileSeeder::class, ServiceSeeder::class, TechnologySeeder::class]);
    }

    /** @return array<int, array<string, mixed>> seluruh blok JSON-LD halaman */
    private function jsonLd(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        return array_map(fn (string $json) => json_decode($json, true, 512, JSON_THROW_ON_ERROR), $matches[1]);
    }

    /** @return array<int, array{0: string, 1: string}> pertanyaan & jawaban yang tampil */
    private function visibleFaqs(string $html): array
    {
        preg_match('#<section id="faq".*?</section>#s', $html, $section);
        preg_match_all('#<summary[^>]*>\s*<span>(.*?)</span>.*?</summary>\s*<p[^>]*>(.*?)</p>#s', $section[0] ?? '', $items, PREG_SET_ORDER);

        return array_map(fn (array $item) => [
            html_entity_decode(trim($item[1]), ENT_QUOTES),
            html_entity_decode(trim($item[2]), ENT_QUOTES),
        ], $items);
    }

    public function test_setiap_halaman_punya_accordion_dan_lima_topik_wajib(): void
    {
        foreach (self::PAGES as $key => $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $faqs = $this->visibleFaqs($html);
            $questions = strtolower(implode(' | ', array_column($faqs, 0)));

            $this->assertGreaterThanOrEqual(5, count($faqs), $url);
            preg_match('#<section id="faq".*?</section>#s', $html, $section);
            $this->assertSame(count($faqs), substr_count($section[0], '<details'), $url);

            foreach (['format file', 'minimal jumlah', 'berapa lama', 'luar jabodetabek', 'nda'] as $topic) {
                $this->assertStringContainsString($topic, $questions, $url.' → '.$topic);
            }
        }
    }

    public function test_json_ld_valid_tunggal_dan_sama_dengan_yang_tampil(): void
    {
        foreach (self::PAGES as $url) {
            $html = $this->get($url)->getContent();
            $faqPages = array_values(array_filter($this->jsonLd($html), fn (array $data) => ($data['@type'] ?? null) === 'FAQPage'));

            $this->assertCount(1, $faqPages, $url.': FAQPage harus tepat satu');
            $this->assertSame('https://schema.org', $faqPages[0]['@context']);
            $this->assertSame(url($url), $faqPages[0]['url']);

            $schema = array_map(fn (array $q) => [$q['name'], $q['acceptedAnswer']['text']], $faqPages[0]['mainEntity']);
            $this->assertSame($this->visibleFaqs($html), $schema, $url);

            foreach ($faqPages[0]['mainEntity'] as $question) {
                $this->assertSame('Question', $question['@type']);
                $this->assertSame('Answer', $question['acceptedAnswer']['@type']);
            }
        }
    }

    public function test_jawaban_disesuaikan_dengan_layanannya(): void
    {
        $answers = fn (string $key) => implode(' ', array_column(ServiceFaqs::for($key), 'answer'));

        // 3D Printing: format sistem 3D Models + pilihan waktu sistem pemesanan.
        $this->assertStringContainsString('STL, STP, STEP, OBJ, dan 3MF', $answers('3d-printing'));
        $this->assertStringContainsString('Standard (5–7 hari kerja)', $answers('3d-printing'));
        $this->assertStringContainsString('Express (1–2 hari kerja', $answers('3d-printing'));

        // Layanan berbasis objek fisik tidak mengklaim format upload 3D Models.
        $this->assertStringNotContainsString('STL, STP, STEP, OBJ, dan 3MF', $answers('3d-scanning'));
        $this->assertStringContainsString('STL, OBJ, atau point cloud', $answers('3d-scanning'));
        $this->assertStringContainsString('part fisiknya', $answers('reverse-engineering'));
        $this->assertStringContainsString('RAL atau Pantone', $answers('paint-finishing'));

        // Miniatur: ketentuan harga dari owner, persis.
        $this->assertStringContainsString('Rp1 juta per unit', $answers('miniatur'));
        $this->assertStringContainsString('lebih dari 25 pcs, harga khusus diberikan setelah review desain', $answers('miniatur'));

        // Tidak ada placeholder yang tertinggal.
        foreach (array_keys(self::PAGES) as $key) {
            $this->assertStringNotContainsString('{', $answers($key), $key);
        }
    }

    public function test_generator_json_ld_aman_dari_injeksi(): void
    {
        $faqs = FaqSchema::normalize([
            ['question' => 'Apa <b>ini</b>?', 'answer' => '</script><script>alert(1)</script> & "kutip"'],
            ['question' => '', 'answer' => 'tanpa pertanyaan dibuang'],
        ]);

        $json = FaqSchema::toJsonLd($faqs, 'https://nusama3d.com/x');

        $this->assertCount(1, $faqs);
        $this->assertStringNotContainsString('</script>', $json);
        $this->assertStringNotContainsString('<', $json);
        $this->assertSame('Apa ini?', json_decode($json, true)['mainEntity'][0]['name']);
    }

    public function test_faq_tampil_tanpa_javascript_dan_dapat_dipakai_keyboard(): void
    {
        $html = $this->get('/services/3d-scanning')->getContent();

        // <details>/<summary> bawaan browser: tidak bergantung JavaScript dan
        // dapat dibuka-tutup dengan Enter/Spasi.
        $this->assertMatchesRegularExpression('#<details class="group"\s+open\s*>#', $html);
        $this->assertStringContainsString('focus-visible:', $html);
        $this->assertStringNotContainsString('data-faq-toggle', $html);
    }

    public function test_faq_berada_sebelum_cta_penutup(): void
    {
        foreach (['/services/3d-printing', '/miniatur-teknik'] as $url) {
            $html = $this->get($url)->getContent();

            $this->assertLessThan(strrpos($html, 'Konsultasi Gratis'), strpos($html, '<section id="faq"'), $url);
            $this->assertLessThan(strpos($html, '<footer'), strpos($html, '<section id="faq"'), $url);
        }
    }
}
