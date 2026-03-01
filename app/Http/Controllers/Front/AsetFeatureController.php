<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class AsetFeatureController extends Controller
{
    public function index()
    {
        return view('front.aset_feature');
    }
}
