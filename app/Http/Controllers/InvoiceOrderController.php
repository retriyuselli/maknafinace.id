<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentMethod;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class InvoiceOrderController extends Controller
{
    /**
     * Display the invoice for the given order.
     *
     * @return \Illuminate\View\View
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);
        
        // Get payment methods for the view
        $paymentMethods = PaymentMethod::where('is_cash', false)->get();

        // Get order details with eager loading of all relationships needed for the view
        $order = Order::with([
            'items.product.category',
            'items.product.vendorItems.vendor',
            'prospect',
            'employee',
            'user',
            'dataPembayaran.paymentMethod',
            'expenses.vendor',
        ])->findOrFail($order->id);

        // Calculate total quantity across all items
        $totalQuantity = $order->items->sum('quantity');

        // Calculate additional order statistics
        $averageUnitPrice = $order->items->count() > 0
            ? $order->items->sum(function ($item) {
                return $item->unit_price;
            }) / $order->items->count()
            : 0;

        // Get order date details
        $eventDate = $order->prospect->date_resepsi;
        $daysUntilEvent = $eventDate ? now()->diffInDays($eventDate, false) : null;

        // Format dates for display
        $formattedEventDate = $eventDate ? date('d F Y', strtotime($eventDate)) : 'Not set';

        $totalVendor = $order->expenses->sum('amount');
        $allExpenses = $order->expenses->sortByDesc('date_expense');

        return view('invoices.show', compact(
            'order',
            'paymentMethods',
            'totalQuantity',
            'averageUnitPrice',
            'daysUntilEvent',
            'formattedEventDate',
            'totalVendor',
            'allExpenses'
        ));
    }

    /**
     * Generate and download PDF invoice for the given order.
     *
     * @return Response
     */
    public function download(Order $order)
    {
        Gate::authorize('view', $order);

        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);
        
        // Get order details with eager loading for improved performance
        $order = Order::with([
            'items.product.category',
            'items.product.vendorItems.vendor',
            'items.product.penambahanHarga.vendor',
            'items.product.pengurangans',
            'prospect',
            'employee',
            'user',
            'dataPembayaran.paymentMethod',
            'expenses.vendor',
        ])->findOrFail($order->id);

        $company = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('companies')) {
            $company = \App\Models\Company::with('paymentMethod')->first();
        }

        $paymentDetails = 'Please contact us for payment details.';
        if ($company && $company->paymentMethod) {
            $paymentDetails =
                $company->paymentMethod->no_rekening.' '.
                $company->paymentMethod->bank_name.' '.
                '('.$company->paymentMethod->name.')';
        }

        $logoCacheKey = 'invoice:logo:'.
            ($company?->id ?? 'none').':'.
            ($company?->updated_at?->timestamp ?? '0').':'.
            md5((string) ($company?->logo_url ?? ''));

        $logoBase64 = Cache::remember($logoCacheKey, 3600, function () use ($company): string {
            $logoPath = $company && $company->logo_url
                ? Storage::disk('public')->path($company->logo_url)
                : public_path(config('invoice.logo', 'images/logo.png'));

            if (! is_string($logoPath) || ! file_exists($logoPath)) {
                return '';
            }

            $logoType = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoData = @file_get_contents($logoPath);
            if (! is_string($logoData) || $logoData === '') {
                return '';
            }

            return 'data:image/'.$logoType.';base64,'.base64_encode($logoData);
        });

        $totalAdditionAmount = 0;
        $allProductPenambahanHarga = collect();
        $allProductPengurangans = collect();

        foreach ($order->items ?? [] as $orderItem) {
            $quantity = (int) ($orderItem->quantity ?? 1);
            $product = $orderItem->product;
            if (! $product) {
                continue;
            }

            $penambahanList = $product->penambahanHarga ?? collect();
            if ($penambahanList->isNotEmpty()) {
                $totalAdditionAmount += ((int) $penambahanList->sum('harga_publish')) * $quantity;
                foreach ($penambahanList as $penambahan) {
                    $penambahan->product_name = $product->name;
                    $allProductPenambahanHarga->push($penambahan);
                }
            }

            $penguranganList = $product->pengurangans ?? collect();
            if ($penguranganList->isNotEmpty()) {
                foreach ($penguranganList as $pengurangan) {
                    $pengurangan->product_name = $product->name;
                    $allProductPengurangans->push($pengurangan);
                }
            }
        }

        // Configure PDF options to handle page breaks properly
        $pdf = PDF::loadView('invoices.pdf', compact(
            'order',
            'company',
            'paymentDetails',
            'logoBase64',
            'totalAdditionAmount',
            'allProductPenambahanHarga',
            'allProductPengurangans',
        ));

        // Set PDF options for better rendering
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'dpi' => 96,
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isFontSubsettingEnabled' => true,
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        // return $pdf->stream("Invoice-{$order->prospect->name_event}.pdf");
        return $pdf->download("Invoice-{$order->prospect->name_event}.pdf");
    }

    /**
     * Generate PDF for simulation package.
     *
     * @return Response
     */
    public function downloadSimulation(Order $order)
    {
        Gate::authorize('view', $order);

        @ini_set('max_execution_time', '300');
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);
        
        // Get order details with eager loading
        $order = Order::with([
            'items.product.category',
            'items.product.vendorItems.vendor',
            'prospect',
            'employee',
            'dataPembayaran.paymentMethod',
        ])->findOrFail($order->id);

        // Configure PDF options
        $pdf = PDF::loadView('invoices.simulation-pdf', compact('order'));

        // Set PDF options for better rendering of multi-page documents
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'dpi' => 150,
            'defaultFont' => 'sans-serif',
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isFontSubsettingEnabled' => true,
        ]);

        return $pdf->download("wedding-package-simulation-{$order->number}.pdf");
    }

    /**
     * Print the invoice for the given order.
     *
     * @return \Illuminate\View\View
     */
    public function print(Order $order)
    {
        Gate::authorize('view', $order);

        $order = Order::with([
            'items.product.vendorItems.vendor',
            'prospect',
            'employee',
            'dataPembayaran.paymentMethod',
            'expenses.vendor',
        ])->findOrFail($order->id);

        return view('invoices.print', compact('order'));
    }

    /**
     * Update the payment status of the order.
     *
     * @return RedirectResponse
     */
    public function updatePayment(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        // Validate CSRF token
        if (! $request->hasValidSignature() && ! $request->filled('_token')) {
            return redirect()->route('invoice.show', $order)
                ->with('error', 'Invalid request. Please try again.');
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'nominal' => 'required|numeric|min:1',
            'image' => 'nullable|image|max:2048',
            'tgl_bayar' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        // Handle file upload if present
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('payment-proofs', 'public');
            $validated['image'] = $path;
        }

        // Create payment record
        $order->dataPembayaran()->create($validated);

        // Check if payment completed
        $totalPaid = $order->dataPembayaran()->sum('nominal');
        if ($totalPaid >= $order->grand_total) {
            $order->update(['is_paid' => true]);
        }

        return redirect()->route('invoice.show', $order)
            ->with('success', 'Payment recorded successfully!');
    }
}
