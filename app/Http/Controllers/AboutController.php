<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    /**
     * Profil perusahaan, visi & misi, keunggulan, dan informasi kontak.
     * Data profil di-share ke seluruh view melalui AppServiceProvider.
     */
    public function index(): View
    {
        return view('pages.about');
    }
}
