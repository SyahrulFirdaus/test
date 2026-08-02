<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    /** Daftar seluruh layanan beserta uraian lengkapnya. */
    public function index(): View
    {
        return view('pages.services', [
            'services' => Service::query()->active()->ordered()->get(),
        ]);
    }
}
