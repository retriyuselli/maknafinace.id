<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class HrisFeatureController extends Controller
{
    public function index()
    {
        return view('front.hris_feature');
    }
}
