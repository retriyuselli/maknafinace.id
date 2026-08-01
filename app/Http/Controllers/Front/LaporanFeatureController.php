<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class LaporanFeatureController extends Controller
{
    public function index()
    {
        return view('front.laporan_feature');
    }
}
