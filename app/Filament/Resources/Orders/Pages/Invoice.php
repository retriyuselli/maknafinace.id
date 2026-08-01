<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Models\PaymentMethod;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class Invoice extends Page
{
    protected static string $resource = OrderResource::class;

    protected string $view = 'filament.resources.order-resource.pages.invoice';

    protected static ?string $title = 'Detail';

    protected static ?string $slug = 'details';

    public Order $order;

    public function mount(int|string $record): void
    {
        $this->order = Order::with([
            'prospect',
            'user',
            'employee',
            'items.product.vendorItems.vendor',
            'dataPembayaran.paymentMethod',
            'expenses.vendor',
        ])->findOrFail($record);
    }

    protected function getViewData(): array
    {
        return [
            'paymentMethods' => PaymentMethod::where('is_cash', false)->get(),
            'allExpenses' => $this->order->expenses->sortByDesc('date_expense'),
            'totalVendor' => $this->order->expenses->sum('amount'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Invoice '.$this->order->prospect->name_event;
    }
}
