<?php

namespace App\Http\Controllers;

use App\Models\Technology;
use Illuminate\Contracts\View\View;

class TechnologyController extends Controller
{
    /** Teknologi 3D printing yang dioperasikan beserta kelebihan & aplikasinya. */
    public function index(): View
    {
        return view('pages.technologies', [
            'technologies' => Technology::query()->active()->ordered()->get(),
        ]);
    }
}
