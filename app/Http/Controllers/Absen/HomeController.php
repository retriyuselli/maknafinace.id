<?php

namespace App\Http\Controllers\Absen;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function home(Request $request): View
    {
        $hour = Carbon::now(config('app.timezone', 'Asia/Jakarta'))->hour;
        $greeting = match (true) {
            $hour < 11 => 'Good Morning',
            $hour < 15 => 'Good Afternoon',
            $hour < 18 => 'Good Evening',
            default => 'Good Evening',
        };

        return view('absen.home', [
            'user' => $request->user(),
            'greeting' => $greeting,
        ]);
    }

    public function more(Request $request): View
    {
        return view('absen.more', [
            'user' => $request->user(),
        ]);
    }
}
