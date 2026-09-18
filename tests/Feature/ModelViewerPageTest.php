<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman 3D Viewer publik: /3d-models/{id}/viewer.
 *
 * "Lihat 3D" berpindah ke halaman ini di tab yang sama. Halamannya hanya
 * pratinjau — tanpa spesifikasi cetak, berat, maupun harga — dan `{id}`
 * hanyalah id berkas di browser pengunjung, dibatasi polanya di route.
 */
class ModelViewerPageTest extends TestCase
{
    use RefreshDatabase;

    private const UUID = 'b6f1c2d4-3e5a-4f70-9a1b-2c3d4e5f6a7b';

    public function test_route_viewer_memakai_alamat_per_model(): void
    {
        $this->assertSame(url('/3d-models/'.self::UUID.'/viewer'), route('models.viewer.show', self::UUID));

        $this->get('/3d-models/'.self::UUID.'/viewer')
            ->assertOk()
            ->assertSee('data-model-id="'.self::UUID.'"', false);

        // Id cadangan dari model-store.js ("m-<waktu>-<acak>") juga diterima.
        $this->get('/3d-models/m-lq2x9k0-ab12cd34/viewer')->assertOk();
    }

    public function test_id_yang_bukan_pola_id_model_ditolak(): void
    {
        foreach ([
            '/3d-models/..%2F..%2F.env/viewer',
            '/3d-models/%2Fstorage%2Fapp%2Fprivate/viewer',
            '/3d-models/<script>/viewer',
            '/3d-models/'.str_repeat('a', 65).'/viewer',
            '/3d-models/-awalan-strip/viewer',
        ] as $url) {
            $this->get($url)->assertNotFound();
        }

        // Tidak ada jalur berbasis path berkas.
        $this->get('/3d-models/viewer?file=/storage/app/private/x.stl')->assertRedirect(route('models'));
    }

    public function test_alamat_lama_diteruskan_ke_alamat_baru(): void
    {
        $this->get('/3d-models/viewer?model='.self::UUID)
            ->assertRedirect(route('models.viewer.show', self::UUID));

        $this->get('/3d-models/viewer')->assertRedirect(route('models'));
        $this->get('/3d-models/viewer?model=../etc')->assertRedirect(route('models'));
        $this->get('/cek-barang/viewer')->assertRedirect('/3d-models/viewer');
    }

    public function test_halaman_hanya_pratinjau_tanpa_spesifikasi_berat_maupun_harga(): void
    {
        $response = $this->get(route('models.viewer.show', self::UUID))->assertOk();

        $response->assertSee('3D Viewer')
            ->assertSee('Kembali ke 3D Models')
            ->assertSee('data-viewer-action="rotate"', false)
            ->assertSee('data-viewer-action="zoom-in"', false)
            ->assertSee('data-viewer-action="zoom-out"', false)
            ->assertSee('data-viewer-action="reset"', false)
            ->assertSee('data-model-info="format"', false)
            ->assertSee('data-model-info="dimensions"', false)
            ->assertSee('data-model-info="volume"', false);

        foreach ([
            'data-dropzone', 'data-printing-config', 'data-open-quotation', 'Edit Specification',
            'Estimasi Biaya', 'Harga', 'Berat', 'Infill', 'Resolusi', 'Material', 'data-card-canvas',
        ] as $forbidden) {
            $response->assertDontSee($forbidden, false);
        }

        // Kembali lewat route Laravel, tanpa tab baru.
        $html = $response->getContent();
        $this->assertStringContainsString('href="'.route('models').'"', $html);
        $this->assertStringNotContainsString('target="_blank"', str($html)->between('<main', '</main>')->toString());
    }

    public function test_viewer_menyediakan_rotasi_object_pada_tiga_sumbu(): void
    {
        $response = $this->get(route('models.viewer.show', self::UUID))->assertOk();

        foreach (['x', 'y', 'z'] as $axis) {
            $response->assertSee('data-rotate-object="'.$axis.'"', false);
        }

        $response->assertSee('Rotasi')
            ->assertSee('data-rotation-readout', false)
            // Putar kamera tetap tersedia, dengan nama yang tidak tertukar.
            ->assertSee('data-viewer-action="rotate"', false)
            ->assertSee('Otomatis');
    }

    public function test_viewer_tampil_layar_penuh_tanpa_header_dan_footer(): void
    {
        $html = $this->get(route('models.viewer.show', self::UUID))->assertOk()->getContent();

        // Navbar, footer situs, dan tombol kembali ke atas tidak ikut.
        $this->assertStringNotContainsString('3D Printing Service &amp; Engineering Solutions', $html);
        $this->assertStringNotContainsString('Seluruh hak cipta dilindungi', $html);
        $this->assertStringNotContainsString('Tracking Penawaran', $html);
        $this->assertStringContainsString('h-dvh', $html);

        // Halaman lain tetap memakai header & footer seperti biasa.
        $this->get(route('models'))
            ->assertSee('3D Printing Service &amp; Engineering Solutions', false)
            ->assertSee('Tracking Penawaran');
    }

    public function test_daftar_3d_models_mengarahkan_viewer_ke_tab_yang_sama(): void
    {
        $response = $this->get(route('models'))->assertOk();

        preg_match('/<script type="application\/json" data-printing-config>(.*?)<\/script>/s', $response->getContent(), $m);
        $config = json_decode(html_entity_decode($m[1] ?? ''), true);

        $this->assertSame(url('/3d-models/MODELID/viewer'), $config['viewerUrl']);

        // Kartu model disusun JavaScript; sumbernya tidak lagi membuka tab baru.
        $workspace = file_get_contents(resource_path('js/modules/model-workspace.js'));
        $cardMarkup = str($workspace)->between('cardMarkup(record) {', 'bindUpload')->toString() ?: $workspace;

        $this->assertStringNotContainsString('target="_blank"', $cardMarkup);
        $this->assertStringNotContainsString('window.open', $workspace);
        $this->assertStringContainsString('data-view-model=', $workspace);
    }
}
