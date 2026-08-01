<?php

namespace App\Http\Controllers;

use App\Models\Documentation;
use App\Models\DocumentationCategory;
use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    public function index($slug = null)
    {
        // Ambil semua kategori yang aktif beserta artikelnya yang dipublish
        $categories = DocumentationCategory::with(['documentations' => function ($query) {
            $query->where('is_published', true)->orderBy('order');
        }])
        ->where('is_active', true)
        ->orderBy('order')
        ->get();

        if ($slug) {
            // Jika ada slug, cari artikelnya dan tampilkan view detail (dengan sidebar)
            $currentArticle = Documentation::where('slug', $slug)
                ->where('is_published', true)
                ->firstOrFail();
            
            return view('front.documentation.show', compact('categories', 'currentArticle'));
        }

        // Jika tidak ada slug (halaman utama /docs), tampilkan landing page (tanpa sidebar)
        return view('front.documentation.index', compact('categories'));
    }
}
