<?php

namespace Tests\Unit;

use App\Services\MeshInspector;
use App\Support\ModelFormat;
use RuntimeException;
use Tests\TestCase;

/**
 * Pengukuran geometri di sisi server.
 *
 * Kubus 20 mm dipakai sebagai acuan pada seluruh format: volumenya tepat
 * 8 cm³, luas permukaannya 24 cm², dan dimensinya 20 × 20 × 20 mm. Angka yang
 * sama harus keluar dari format mana pun, karena estimasi biaya bertumpu
 * padanya.
 */
class MeshInspectorTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir().'/mesh-inspector-'.uniqid();
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }

        rmdir($this->directory);

        parent::tearDown();
    }

    /** Delapan sudut kubus bersisi 20 mm. */
    private function corners(): array
    {
        return [
            [0, 0, 0], [20, 0, 0], [20, 20, 0], [0, 20, 0],
            [0, 0, 20], [20, 0, 20], [20, 20, 20], [0, 20, 20],
        ];
    }

    /** Dua belas segitiga yang menutup kubus. */
    private function faces(): array
    {
        return [
            [0, 2, 1], [0, 3, 2], [4, 5, 6], [4, 6, 7],
            [0, 1, 5], [0, 5, 4], [1, 2, 6], [1, 6, 5],
            [2, 3, 7], [2, 7, 6], [3, 0, 4], [3, 4, 7],
        ];
    }

    private function writeStl(): string
    {
        $corners = $this->corners();
        $stl = "solid kubus\n";

        foreach ($this->faces() as $face) {
            $stl .= "  facet normal 0 0 0\n    outer loop\n";

            foreach ($face as $index) {
                [$x, $y, $z] = $corners[$index];
                $stl .= "      vertex {$x} {$y} {$z}\n";
            }

            $stl .= "    endloop\n  endfacet\n";
        }

        $path = $this->directory.'/kubus.stl';
        file_put_contents($path, $stl.'endsolid kubus');

        return $path;
    }

    /**
     * 3MF adalah arsip ZIP; berkasnya dirakit di sini agar pengujian tidak
     * bergantung pada ekstensi zip PHP yang belum tentu aktif.
     */
    private function writeThreeMf(): string
    {
        $vertices = '';

        foreach ($this->corners() as [$x, $y, $z]) {
            $vertices .= "<vertex x=\"{$x}\" y=\"{$y}\" z=\"{$z}\"/>";
        }

        $triangles = '';

        foreach ($this->faces() as [$a, $b, $c]) {
            $triangles .= "<triangle v1=\"{$a}\" v2=\"{$b}\" v3=\"{$c}\"/>";
        }

        $model = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<model unit="millimeter" xmlns="http://schemas.microsoft.com/3dmanufacturing/core/2015/02">'
            .'<resources><object id="1" type="model"><mesh>'
            ."<vertices>{$vertices}</vertices><triangles>{$triangles}</triangles>"
            .'</mesh></object></resources><build><item objectid="1"/></build></model>';

        $path = $this->directory.'/kubus.3mf';
        file_put_contents($path, $this->zip('3D/3dmodel.model', $model));

        return $path;
    }

    /** Arsip ZIP satu entri, disimpan tanpa kompresi. */
    private function zip(string $name, string $contents): string
    {
        $crc = crc32($contents);
        $size = strlen($contents);
        $nameLength = strlen($name);

        $local = "PK\x03\x04".pack('vvvvvVVVvv', 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0).$name;
        $central = "PK\x01\x02".pack('vvvvvvVVVvvvvvVV', 20, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, 0).$name;

        // Central directory berada tepat setelah local header beserta isinya.
        $eocd = "PK\x05\x06".pack('vvvvVVv', 0, 0, 1, 1, strlen($central), strlen($local) + $size, 0);

        return $local.$contents.$central.$eocd;
    }

    private function assertCube(array $stats): void
    {
        $this->assertSame(12, $stats['triangles']);
        $this->assertEqualsWithDelta(8.0, $stats['volume_cm3'], 0.001);
        $this->assertEqualsWithDelta(24.0, $stats['surface_area_cm2'], 0.001);
        $this->assertEqualsWithDelta(20.0, $stats['dimensions']['x'], 0.001);
        $this->assertEqualsWithDelta(20.0, $stats['dimensions']['y'], 0.001);
        $this->assertEqualsWithDelta(20.0, $stats['dimensions']['z'], 0.001);
    }

    public function test_stl_terukur_sebagai_kubus_20_mm(): void
    {
        $this->assertCube(app(MeshInspector::class)->inspect($this->writeStl(), 'stl'));
    }

    public function test_3mf_terukur_sama_persis_dengan_stl(): void
    {
        $this->assertCube(app(MeshInspector::class)->inspect($this->writeThreeMf(), '3mf'));
    }

    public function test_step_ditolak_karena_perlu_kernel_cad(): void
    {
        // STEP diukur di browser memakai OpenCascade, bukan di sini. Pesannya
        // harus jelas agar penanganannya tidak salah dikira berkas rusak.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('tidak dapat diukur di server');

        app(MeshInspector::class)->inspect($this->writeStl(), 'step');
    }

    public function test_daftar_format_meliputi_kelima_ekstensi(): void
    {
        $this->assertSame(['stl', 'stp', 'step', 'obj', '3mf'], ModelFormat::extensions());
        $this->assertSame('STL, STP, STEP, OBJ, 3MF', ModelFormat::label());
        $this->assertSame('extensions:stl,stp,step,obj,3mf', ModelFormat::rule());

        // Hanya format mesh yang dapat diukur PHP.
        $this->assertTrue(ModelFormat::isMeasurable('3mf'));
        $this->assertFalse(ModelFormat::isMeasurable('step'));
    }
}
