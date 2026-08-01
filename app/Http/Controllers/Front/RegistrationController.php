<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Support\Facades\Cache;

class RegistrationController extends Controller
{
    public function pendaftaran()
    {
        $industries = Cache::remember('front.industries', 3600, function () {
            return Industry::query()->get();
        });

        return view('front.pendaftaran', [
            'industries' => $industries,
        ]);
    }
}

