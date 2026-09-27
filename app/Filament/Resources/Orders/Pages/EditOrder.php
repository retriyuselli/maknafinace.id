<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Support\OrderFinancialSnapshot;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    /** @var array<string, mixed>|null */
    protected ?array $financialBefore = null;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->visible(Auth::user()->hasRole('super_admin'))
                ->color('danger'),
            Action::make('Invoice')
                ->label('Detail')
                ->color('success')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => OrderResource::getUrl('invoice', ['record' => $record->id]))
                ->openUrlInNewTab(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->getRecord()->loadMissing(['amendments.user', 'lastEditedBy', 'items']);
        $this->financialBefore = OrderFinancialSnapshot::fromOrder($this->getRecord());

        return $data;
    }

    /**
     * Dipanggil setelah amandemen "Ambil ulang dari Produk" tersimpan.
     */
    public function reloadAfterProductSync(): void
    {
        $this->getRecord()->refresh()->load(['items', 'amendments.user', 'lastEditedBy']);
        $this->fillForm();

        $record = $this->getRecord();
        $this->data['amendments_history'] = $record->amendments
            ->take(20)
            ->map(fn ($amendment) => [
                'when' => $amendment->created_at?->format('d M Y H:i') ?? '-',
                'by' => $amendment->user?->name ?? 'Tidak diketahui',
                'reason' => (string) ($amendment->reason ?? '-'),
                'changes' => OrderForm::formatAmendmentDiff(
                    is_array($amendment->diff) ? $amendment->diff : []
                ),
            ])
            ->values()
            ->all();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var \App\Models\Order $order */
        $order = $this->getRecord();

        // Setelah TTD, angka keuangan hanya boleh berubah lewat "Ambil ulang dari Produk".
        // Save biasa (catatan/status/pembayaran/dll) tidak boleh diblokir oleh drift form.
        if ($order->isContractSigned()) {
            $order->loadMissing('items');
            $snapshot = OrderFinancialSnapshot::fromOrder($order);

            $data['total_price'] = $snapshot['total_price'];
            $data['promo'] = $snapshot['promo'];
            $data['penambahan'] = $snapshot['penambahan'];
            $data['pengurangan'] = $snapshot['pengurangan'];
            $data['grand_total'] = $snapshot['grand_total'];

            $data['items'] = $order->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'quantity' => (int) $item->quantity,
                'unit_price' => (int) $item->unit_price,
                'unit_penambahan' => (int) ($item->unit_penambahan ?? 0),
                'unit_pengurangan' => (int) ($item->unit_pengurangan ?? 0),
            ])->values()->all();

            // Pastikan state Livewire tidak menimpa snapshot DB saat save relationship.
            $this->data['total_price'] = $data['total_price'];
            $this->data['promo'] = $data['promo'];
            $this->data['penambahan'] = $data['penambahan'];
            $this->data['pengurangan'] = $data['pengurangan'];
            $this->data['grand_total'] = $data['grand_total'];
            $this->data['items'] = $data['items'];

            $this->financialBefore = $snapshot;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->fresh(['items', 'amendments.user']);
        $this->financialBefore = OrderFinancialSnapshot::fromOrder($record);
        $this->data['amendments_history'] = $record->amendments
            ->take(20)
            ->map(fn ($amendment) => [
                'when' => $amendment->created_at?->format('d M Y H:i') ?? '-',
                'by' => $amendment->user?->name ?? 'Tidak diketahui',
                'reason' => (string) ($amendment->reason ?? '-'),
                'changes' => OrderForm::formatAmendmentDiff(
                    is_array($amendment->diff) ? $amendment->diff : []
                ),
            ])
            ->values()
            ->all();
    }
}
