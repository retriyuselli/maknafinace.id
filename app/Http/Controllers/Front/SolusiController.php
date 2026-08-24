<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SolusiController extends Controller
{
    public function show(string $slug): View
    {
        $solutions = config('wofins_solusi', []);
        abort_unless(isset($solutions[$slug]), 404);

        return view('front.solusi', [
            'solution' => $solutions[$slug],
            'slug' => $slug,
            'allSolutions' => $solutions,
        ]);
    }
}
