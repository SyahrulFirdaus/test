<?php

namespace Tests\Feature;

use App\Models\QuotationRequest;
use App\Models\User;
use Database\Seeders\ClientSeeder;
use Database\Seeders\CompanyProfileSeeder;
use Database\Seeders\ServiceSeeder;
use Database\Seeders\TechnologySeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Merayapi tautan internal pada seluruh halaman — publik, dashboard
 * pelanggan, dan dashboard admin/superadmin — lalu memastikan tidak ada yang
 * berujung pada 404.
 *
 * Hanya tautan <a href> internal dengan metode GET yang diikuti; formulir
 * tidak pernah dikirim. Halaman yang memerlukan data (mis. detail penawaran)
 * ikut terjangkau lewat satu penawaran contoh milik pelanggan uji.
 */
class BrokenLinkTest extends TestCase
{
    use RefreshDatabase;

    /** Batas halaman per peran agar test tetap cepat. */
    private const MAX_PAGES = 400;

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

    public function test_halaman_publik_tidak_memuat_tautan_404(): void
    {
        $this->assertNoBrokenLinks(['/', '/sitemap.xml']);
    }

    public function test_dashboard_pelanggan_tidak_memuat_tautan_404(): void
    {
        $customer = User::factory()->create();
        $this->quotationFor($customer);

        $this->actingAs($customer);
        $this->assertNoBrokenLinks([route('dashboard')]);
    }

    public function test_dashboard_superadmin_tidak_memuat_tautan_404(): void
    {
        $customer = User::factory()->create();
        $this->quotationFor($customer);

        // Superadmin memiliki seluruh menu admin dan superadmin.
        $this->actingAs(User::factory()->superAdmin()->create());
        $this->assertNoBrokenLinks([route('admin.dashboard')]);
    }

    /**
     * @param  array<int, string>  $start
     */
    private function assertNoBrokenLinks(array $start): void
    {
        $host = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $queue = array_map(fn (string $url) => $this->normalize($url, $host), $start);
        $visited = [];
        $broken = [];

        while ($queue && count($visited) < self::MAX_PAGES) {
            [$path, $source] = is_array($queue[0]) ? array_shift($queue) : [array_shift($queue), 'start'];

            if ($path === null || isset($visited[$path])) {
                continue;
            }

            $visited[$path] = true;
            $response = $this->get($path);
            $status = $response->getStatusCode();

            if ($status === 404) {
                $broken[] = "{$path} (ditautkan dari {$source})";

                continue;
            }

            if ($response->isRedirection() || $status !== 200) {
                continue;
            }

            $type = (string) $response->headers->get('Content-Type');

            if (! str_contains($type, 'html') && ! str_contains($type, 'xml')) {
                continue;
            }

            $content = (string) $response->getContent();
            preg_match_all('/<a\b[^>]*\bhref="([^"#][^"]*)"|<loc>([^<]+)<\/loc>/i', $content, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $href = html_entity_decode($match[1] ?: ($match[2] ?? ''));
                $next = $this->normalize($href, $host);

                if ($next !== null && ! isset($visited[$next])) {
                    $queue[] = [$next, $path];
                }
            }
        }

        $this->assertSame([], $broken, "Tautan yang berujung 404:\n".implode("\n", $broken));
        $this->assertGreaterThan(1, count($visited));
    }

    /** Path internal (dengan query) yang layak dirayapi, atau null. */
    private function normalize(string $href, string $host): ?string
    {
        if (preg_match('/^(mailto|tel|javascript|data|whatsapp):/i', $href)) {
            return null;
        }

        $parts = parse_url($href);

        if ($parts === false || (isset($parts['host']) && $parts['host'] !== $host)) {
            return null;
        }

        $path = $parts['path'] ?? '/';

        // Berkas statis & unduhan tidak dirayapi sebagai halaman.
        if (preg_match('/\.(css|js|png|jpe?g|webp|svg|gif|ico|pdf|stl|obj|3mf|step|stp|zip)$/i', $path)) {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function quotationFor(User $customer): QuotationRequest
    {
        $quotation = QuotationRequest::create([
            'user_id' => $customer->id,
            'tracking_number' => 'QTN-20260729-LINK01',
            'name' => $customer->name,
            'email' => $customer->email,
            'whatsapp' => '081234567890',
            'quantity' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-07/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => [['label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']],
            'technology' => 'FDM',
            'material' => 'PLA',
            'model_volume_cm3' => 120.5,
            'material_volume_cm3' => 54.2,
            'estimated_weight_g' => 67.2,
            'estimated_minutes' => 380,
            'estimated_cost' => 185000,
            'status' => 'reviewing',
        ]);

        $quotation->items()->create([
            'position' => 1,
            'file_name' => 'bracket.stl',
            'file_path' => 'quotations/2026-07/bracket.stl',
            'file_format' => 'STL',
            'file_size' => 2048,
            'model_stats' => ['triangles' => 12, 'vertices' => 36],
            'analysis_status' => QuotationRequest::ANALYSIS_READY,
            'analysis' => [['label' => 'Mesh tertutup', 'status' => 'pass', 'message' => 'Aman.']],
            'technology' => 'FDM',
            'material' => 'PLA',
            'quantity' => 1,
            'resolution' => '0.25',
            'layer_height_mm' => 0.25,
            'model_volume_cm3' => 120.5,
            'material_volume_cm3' => 54.2,
            'estimated_weight_g' => 67.2,
            'estimated_minutes' => 380,
            'estimated_cost' => 185000,
        ]);

        return $quotation;
    }
}
