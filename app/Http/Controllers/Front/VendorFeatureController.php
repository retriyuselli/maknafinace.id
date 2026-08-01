<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;

class VendorFeatureController extends Controller
{
    public function index()
    {
        return view('front.vendor_feature');
    }
}
