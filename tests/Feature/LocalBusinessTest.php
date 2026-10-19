<?php

namespace Tests\Feature;

use App\Support\BusinessSchema;
use Database\Seeders\CompanyProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocalBusinessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CompanyProfileSeeder::class);
    }

    public function test_halaman_about_menyematkan_peta_google_maps(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('data-company-map', false)
            ->assertSee('src="https://www.google.com/maps?q=Jl.+Cibadak+3+No.+42', false)
            ->assertSee('output=embed', false)
            ->assertSee('loading="lazy"', false);
    }

    public function test_schema_local_business_memuat_data_bisnis_yang_valid(): void
    {
        $html = $this->get(route('about'))->assertOk()->getContent();

        preg_match_all('/<script type="application\/ld\+json">\s*(.*?)\s*<\/script>/s', $html, $matches);
        $schemas = array_map(fn ($json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
        $business = collect($schemas)->firstWhere('@type', 'LocalBusiness');

        $this->assertNotNull($business);
        // Wajib menurut Google: name & address.
        $this->assertSame('NUSAMA3D', $business['name']);
        $this->assertSame([
            '@type' => 'PostalAddress',
            'streetAddress' => 'Jl. Cibadak 3 No. 42, Jatibaru, Cikarang Timur',
            'addressCountry' => 'ID',
            'addressLocality' => 'Kabupaten Bekasi',
            'postalCode' => '17533',
            'addressRegion' => 'Jawa Barat',
        ], $business['address']);
        // Disarankan: telepon & jam buka.
        $this->assertSame('+6281292388805', $business['telephone']);
        $this->assertSame([[
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '08:00',
            'closes' => '17:00',
        ]], $business['openingHoursSpecification']);
        // Data yang belum tersedia tidak dikarang.
        $this->assertArrayNotHasKey('geo', $business);
        $this->assertArrayNotHasKey('priceRange', $business);
        // Hanya satu entitas LocalBusiness per halaman.
        $this->assertCount(1, collect($schemas)->where('@type', 'LocalBusiness'));
    }

    public function test_format_yang_tidak_dikenali_dilewati_bukan_ditebak(): void
    {
        $this->assertNull(BusinessSchema::openingHours('Hubungi kami untuk jadwal'));
        $this->assertNull(BusinessSchema::openingHours(null));

        $this->assertSame(
            [['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Saturday'], 'opens' => '09:00', 'closes' => '13:00']],
            BusinessSchema::openingHours('Sabtu, 09.00 – 13.00 WIB'),
        );

        $this->assertSame(
            ['@type' => 'PostalAddress', 'streetAddress' => 'Jl. Contoh 1', 'addressCountry' => 'ID', 'addressLocality' => 'Bandung'],
            BusinessSchema::address('Jl. Contoh 1', 'Bandung'),
        );
    }
}
