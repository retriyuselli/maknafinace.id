<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $products = Product::query()
            ->where('is_approved', true)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%'.$search.'%');
                        });
                });
            })
            ->with('category')
            ->orderByDesc('created_at')
            ->paginate(16)
            ->withQueryString();

        return view('front.product', [
            'products' => $products,
            'search' => $search,
        ]);
    }
}

