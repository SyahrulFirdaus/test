<?php

namespace App\Services;

use App\Support\ZipReader;
use RuntimeException;

/**
 * Pembacaan geometri berkas 3D di sisi server.
 *
 * Pada halaman 3D Models seluruh pengukuran dikerjakan browser memakai
 * Three.js. Model yang ditambahkan belakangan dari dashboard tidak melewati
 * viewer itu, jadi volume, luas permukaan, dan dimensinya diukur di sini
 * memakai rumus yang sama:
 *
 *   volume = |Σ (v0 · (v1 × v2)) / 6|      (jumlah tetrahedron bertanda)
 *   luas   = Σ |(v1 - v0) × (v2 - v0)| / 2
 *
 * Berkas dibaca mengalir (streaming) supaya model besar tidak perlu dimuat
 * seluruhnya ke memori.
 */
class MeshInspector
{
    /** Satuan berkas STL/OBJ dianggap milimeter, seperti pada viewer. */
    private const MM3_PER_CM3 = 1000.0;

    private const MM2_PER_CM2 = 100.0;

    /**
     * Ukur satu berkas model.
     *
     * @return array{triangles: int, vertices: int, volume_cm3: float, surface_area_cm2: float, dimensions: array{x: float, y: float, z: float}}
     */
    public function inspect(string $path, string $extension): array
    {
        $extension = strtolower($extension);

        $result = match ($extension) {
            'stl' => $this->readStl($path),
            'obj' => $this->readObj($path),
            '3mf' => $this->readThreeMf($path),
            // STEP/STP berisi permukaan matematis, bukan segitiga. Menesselasinya
            // menuntut kernel CAD yang tidak tersedia di sisi PHP — berkas itu
            // diukur di browser pada halaman 3D Models.
            default => throw new RuntimeException("Format {$extension} tidak dapat diukur di server."),
        };

        if ($result['triangles'] === 0) {
            throw new RuntimeException('Berkas tidak memuat geometri yang dapat dibaca.');
        }

        return $result;
    }

    /** STL biner maupun ASCII; keduanya dibedakan dari ukuran berkasnya. */
    private function readStl(string $path): array
    {
        $size = filesize($path);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Berkas model tidak dapat dibuka.');
        }

        try {
            $header = fread($handle, 84);

            // STL biner: 80 byte header + 4 byte jumlah segitiga + 50 byte per
            // segitiga. Bila ukurannya cocok persis, berkasnya pasti biner —
            // pemeriksaan kata "solid" tidak dapat diandalkan karena sebagian
            // eksportir menuliskannya juga pada berkas biner.
            if ($header !== false && strlen($header) === 84) {
                $count = unpack('V', substr($header, 80, 4))[1] ?? 0;

                if ($size === 84 + $count * 50) {
                    return $this->readBinaryStl($handle, $count);
                }
            }

            rewind($handle);

            return $this->readAsciiStl($handle);
        } finally {
            fclose($handle);
        }
    }

    /** @param  resource  $handle */
    private function readBinaryStl($handle, int $count): array
    {
        $accumulator = $this->freshAccumulator();

        for ($i = 0; $i < $count; $i++) {
            $chunk = fread($handle, 50);

            if ($chunk === false || strlen($chunk) < 50) {
                break;
            }

            // 12 float little-endian: 3 untuk normal (dilewati) + 9 untuk verteks.
            $values = unpack('g12', substr($chunk, 0, 48));

            if ($values === false) {
                break;
            }

            $this->accumulate($accumulator, [
                [$values[4], $values[5], $values[6]],
                [$values[7], $values[8], $values[9]],
                [$values[10], $values[11], $values[12]],
            ]);
        }

        return $this->summarise($accumulator, $accumulator->triangles * 3);
    }

    /** @param  resource  $handle */
    private function readAsciiStl($handle): array
    {
        $accumulator = $this->freshAccumulator();
        $triangle = [];

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if (! str_starts_with($line, 'vertex')) {
                continue;
            }

            $parts = preg_split('/\s+/', $line);

            if ($parts === false || count($parts) < 4) {
                continue;
            }

            $triangle[] = [(float) $parts[1], (float) $parts[2], (float) $parts[3]];

            if (count($triangle) === 3) {
                $this->accumulate($accumulator, $triangle);
                $triangle = [];
            }
        }

        return $this->summarise($accumulator, $accumulator->triangles * 3);
    }

    /**
     * OBJ dibaca dua tahap: verteks lebih dulu, lalu face yang menunjuk padanya.
     * Face dengan lebih dari tiga sudut dipecah menjadi kipas segitiga, sama
     * seperti yang dilakukan loader Three.js.
     */
    private function readObj(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('Berkas model tidak dapat dibuka.');
        }

        $accumulator = $this->freshAccumulator();
        $vertices = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = trim($line);

                if (str_starts_with($line, 'v ')) {
                    $parts = preg_split('/\s+/', $line);

                    if ($parts !== false && count($parts) >= 4) {
                        $vertices[] = [(float) $parts[1], (float) $parts[2], (float) $parts[3]];
                    }

                    continue;
                }

                if (! str_starts_with($line, 'f ')) {
                    continue;
                }

                $parts = preg_split('/\s+/', $line);

                if ($parts === false || count($parts) < 4) {
                    continue;
                }

                $corners = [];

                foreach (array_slice($parts, 1) as $token) {
                    $index = (int) strtok($token, '/');

                    if ($index === 0) {
                        continue;
                    }

                    // Indeks negatif menghitung mundur dari verteks terakhir.
                    $corner = $index > 0 ? ($vertices[$index - 1] ?? null) : ($vertices[count($vertices) + $index] ?? null);

                    if ($corner !== null) {
                        $corners[] = $corner;
                    }
                }

                for ($i = 1; $i + 1 < count($corners); $i++) {
                    $this->accumulate($accumulator, [$corners[0], $corners[$i], $corners[$i + 1]]);
                }
            }
        } finally {
            fclose($handle);
        }

        return $this->summarise($accumulator, count($vertices));
    }

    /**
     * 3MF adalah arsip ZIP berisi XML model.
     *
     * Yang dibaca hanya `<vertices>` dan `<triangles>` pada tiap `<object>`;
     * transformasi build item, warna, dan metadata lainnya diabaikan karena
     * pengukuran ini hanya butuh volume, luas permukaan, dan dimensinya.
     */
    private function readThreeMf(string $path): array
    {
        $xml = ZipReader::firstEntryEndingWith($path, '.model');

        if ($xml === null) {
            throw new RuntimeException('Berkas 3MF tidak memuat model.');
        }

        // Namespace 3MF membuat XPath menuntut prefiks; dilepas lebih dulu agar
        // pembacaannya sederhana dan tahan terhadap perbedaan versi skema.
        $document = simplexml_load_string(preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $xml, 1) ?? '');

        if ($document === false) {
            throw new RuntimeException('Isi berkas 3MF tidak dapat dibaca.');
        }

        $accumulator = $this->freshAccumulator();
        $vertexCount = 0;

        foreach ($document->xpath('//object/mesh') ?: [] as $mesh) {
            $vertices = [];

            foreach ($mesh->vertices->vertex ?? [] as $vertex) {
                $vertices[] = [
                    (float) $vertex['x'],
                    (float) $vertex['y'],
                    (float) $vertex['z'],
                ];
            }

            $vertexCount += count($vertices);

            foreach ($mesh->triangles->triangle ?? [] as $triangle) {
                $corners = [];

                foreach (['v1', 'v2', 'v3'] as $attribute) {
                    $corner = $vertices[(int) $triangle[$attribute]] ?? null;

                    if ($corner === null) {
                        continue 2;
                    }

                    $corners[] = $corner;
                }

                $this->accumulate($accumulator, $corners);
            }
        }

        return $this->summarise($accumulator, $vertexCount);
    }

    private function freshAccumulator(): object
    {
        return new class
        {
            public float $volume = 0.0;

            public float $area = 0.0;

            public int $triangles = 0;

            public array $min = [INF, INF, INF];

            public array $max = [-INF, -INF, -INF];
        };
    }

    /** @param  array<int, array<int, float>>  $triangle */
    private function accumulate(object $accumulator, array $triangle): void
    {
        [$a, $b, $c] = $triangle;

        // Volume bertanda tetrahedron terhadap titik asal.
        $accumulator->volume += (
            $a[0] * ($b[1] * $c[2] - $b[2] * $c[1])
            - $a[1] * ($b[0] * $c[2] - $b[2] * $c[0])
            + $a[2] * ($b[0] * $c[1] - $b[1] * $c[0])
        ) / 6.0;

        // Luas segitiga = setengah panjang hasil kali silang dua sisinya.
        $u = [$b[0] - $a[0], $b[1] - $a[1], $b[2] - $a[2]];
        $v = [$c[0] - $a[0], $c[1] - $a[1], $c[2] - $a[2]];

        $cross = [
            $u[1] * $v[2] - $u[2] * $v[1],
            $u[2] * $v[0] - $u[0] * $v[2],
            $u[0] * $v[1] - $u[1] * $v[0],
        ];

        $accumulator->area += sqrt($cross[0] ** 2 + $cross[1] ** 2 + $cross[2] ** 2) / 2.0;

        foreach ($triangle as $point) {
            for ($axis = 0; $axis < 3; $axis++) {
                $accumulator->min[$axis] = min($accumulator->min[$axis], $point[$axis]);
                $accumulator->max[$axis] = max($accumulator->max[$axis], $point[$axis]);
            }
        }

        $accumulator->triangles++;
    }

    private function summarise(object $accumulator, int $vertices): array
    {
        $dimension = fn (int $axis) => $accumulator->triangles > 0
            ? round($accumulator->max[$axis] - $accumulator->min[$axis], 3)
            : 0.0;

        return [
            'triangles' => $accumulator->triangles,
            'vertices' => $vertices,
            'volume_cm3' => round(abs($accumulator->volume) / self::MM3_PER_CM3, 4),
            'surface_area_cm2' => round($accumulator->area / self::MM2_PER_CM2, 4),
            'dimensions' => [
                'x' => $dimension(0),
                'y' => $dimension(1),
                'z' => $dimension(2),
            ],
        ];
    }
}
