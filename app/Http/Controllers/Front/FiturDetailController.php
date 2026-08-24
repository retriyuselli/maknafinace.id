<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class FiturDetailController extends Controller
{
    public function show(string $slug): View
    {
        $features = config('wofins_features', []);
        abort_unless(isset($features[$slug]), 404);

        $feature = $features[$slug];
        $related = collect($features)
            ->reject(fn (array $item) => $item['slug'] === $slug)
            ->take(3)
            ->values()
            ->all();

        return view('front.fitur-detail', compact('feature', 'related'));
    }
}
