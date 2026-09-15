<?php

namespace Tests;

use App\Models\PrintTechnology;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Daftar teknologi diingat pada properti statis App\Models\Technology agar
     * satu permintaan tidak membacanya berulang kali. Properti itu bertahan
     * antar pengujian dalam satu proses, sedangkan `RefreshDatabase` membangun
     * ulang tabelnya dengan id yang berbeda — jadi penampungnya digugurkan di
     * sini supaya tiap pengujian mulai dari basis data miliknya sendiri.
     */
    protected function setUp(): void
    {
        parent::setUp();

        PrintTechnology::forgetCache();
    }
}
