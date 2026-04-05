<?php

namespace App\Http\Controllers;

use App\Exports\ProductExport;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProductDisplayController extends Controller
{
    public function show(Product $product)
    {
        Gate::authorize('view', $product);

        // Eager load relasi yang dibutuhkan
        $product->load(['category', 'items.vendor']);

        // Siapkan URL gambar
        $product->image_url = $product->image ? Storage::url($product->image) : asset('images/placeholder-product.png'); // Sesuaikan path placeholder

        // Kembalikan view dengan data produk
        return view('products.detail', compact('product'));
    }

    public function details(Product $product, string $action)
    {
        Gate::authorize('view', $product);

        // Eager load necessary relationships if needed
        $product->load(['category', 'items.vendor', 'pengurangans', 'penambahanHarga.vendor']);

        if ($action === 'preview' || $action === 'print') {
            // Return a view for previewing/printing
            // You might have slightly different views or logic for print vs preview
            return view('products.details-preview', compact('product', 'action'));
        } elseif ($action === 'download') {
            $pdf = Pdf::loadView('products.details-preview', compact('product', 'action'));
            return $pdf->download($product->slug.'-details.pdf');
        }

        // Handle invalid action
        abort(404, 'Invalid action specified.');
    }

    public function downloadPdf(Product $product)
    {
        Gate::authorize('view', $product);

        // Load relasi yang mungkin dibutuhkan di view PDF (opsional tapi bagus untuk performa)
        $product->load(['category', 'items.vendor', 'pengurangans', 'penambahanHarga.vendor']);

        // Data yang akan dikirim ke view PDF
        $data = [
            'product' => $product,
            // Anda bisa menambahkan data lain di sini jika perlu
        ];

        // Load view 'products.pdf' dengan data
        $pdf = Pdf::loadView('products.pdf', $data);

        // (Opsional) Konfigurasi PDF
        // $pdf->setPaper('A4', 'portrait'); // Contoh: set ukuran kertas dan orientasi

        // Buat nama file yang dinamis
        $fileName = 'product-'.$product->slug.'-'.now()->format('Ymd').'.pdf';

        // Kembalikan sebagai unduhan
        return $pdf->download($fileName);

        // Atau jika ingin menampilkan di browser dulu (inline)
        // return $pdf->stream($fileName);
    }

    public function exportDetailToExcel(Product $product)
    {
        Gate::authorize('view', $product);

        return Excel::download(
            new ProductExport([$product->id]), // Menggunakan ProductExport yang sudah ada
            'product_detail_'.Str::slug($product->name).'_'.now()->format('YmdHis').'.xlsx'
        );
    }
}
