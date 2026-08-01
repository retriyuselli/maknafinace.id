<?php

namespace App\Filament\Resources\NotaDinas\Pages;

use App\Filament\Resources\NotaDinas\NotaDinasResource;
use App\Models\NotaDinas;
use Exception;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\HtmlString;

class EditNotaDinas extends EditRecord
{
    protected static string $resource = NotaDinasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Nota Dinas')
                ->modalDescription('Apakah Anda yakin ingin menghapus Nota Dinas ini? Tindakan ini tidak dapat dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->visible(function (): bool {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->count();

                    return $detailCount === 0;
                })
                ->before(function () {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->count();

                    if ($detailCount > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Tidak Dapat Menghapus Nota Dinas')
                            ->body("Nota Dinas ini tidak dapat dihapus karena memiliki {$detailCount} detail terkait. Silakan hapus detail terlebih dahulu.")
                            ->persistent()
                            ->send();

                        return false;
                    }
                }),
            Action::make('cannot_delete_info')
                ->label('Tidak Dapat Dihapus')
                ->icon('heroicon-m-shield-exclamation')
                ->color('warning')
                ->tooltip('Nota Dinas ini tidak dapat dihapus karena memiliki detail terkait')
                ->visible(function (): bool {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->count();

                    return $detailCount > 0;
                })
                ->action(function () {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->count();

                    Notification::make()
                        ->warning()
                        ->title('Tidak Dapat Menghapus Nota Dinas')
                        ->body("'{$record->no_nd}' tidak dapat dihapus karena memiliki {$detailCount} detail terkait. Silakan hapus detail terlebih dahulu.")
                        ->persistent()
                        ->send();
                }),
            Action::make('view_details')
                ->label('Lihat Detail')
                ->icon('heroicon-o-list-bullet')
                ->color('info')
                ->visible(function (): bool {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->count();

                    return $detailCount > 0;
                })
                ->modalHeading(function (): string {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();

                    return 'Detail Nota Dinas - '.$record->no_nd;
                })
                ->modalDescription('Nota Dinas ini memiliki detail terkait dan tidak dapat dihapus.')
                ->modalContent(function (): HtmlString {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $details = $record->details()->with('vendor', 'order.prospect')->get();

                    $content = '<div class="space-y-4">';
                    $content .= '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">';
                    $content .= '<h3 class="font-semibold text-yellow-800 mb-3">⚠️ Penghapusan Diblokir</h3>';
                    $content .= '<p class="text-sm text-yellow-700 mb-3">Nota Dinas ini memiliki '.$details->count().' detail terkait dan tidak dapat dihapus.</p>';

                    if ($details->count() > 0) {
                        $content .= '<div class="space-y-2">';
                        foreach ($details as $detail) {
                            $content .= '<div class="border-l-4 border-yellow-400 pl-3 py-2 bg-white rounded">';
                            $content .= '<p class="text-sm font-medium">Keperluan: '.($detail->keperluan ?? 'Tidak ditentukan').'</p>';
                            $content .= '<p class="text-sm text-gray-600">Jumlah: Rp '.number_format($detail->jumlah_transfer, 0, ',', '.').'</p>';
                            if ($detail->vendor) {
                                $content .= '<p class="text-sm text-gray-600">Vendor: '.$detail->vendor->name.'</p>';
                            }
                            if ($detail->order) {
                                $orderNumber = $detail->order->number ?? 'Tidak tersedia';
                                $prospekName = $detail->order->prospect->name_event ?? '';
                                $prospekText = $prospekName ? ' (Prospek: '.$prospekName.')' : '';
                                $content .= '<p class="text-sm text-gray-600">Order: '.$orderNumber.$prospekText.'</p>';
                            }
                            $content .= '</div>';
                        }
                        $content .= '</div>';
                    }

                    $content .= '</div>';
                    $content .= '</div>';

                    return new HtmlString($content);
                })
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup'),
            RestoreAction::make()
                ->requiresConfirmation()
                ->modalHeading('Pulihkan Nota Dinas')
                ->modalDescription('Apakah Anda yakin ingin memulihkan Nota Dinas yang dihapus ini?')
                ->modalSubmitActionLabel('Ya, pulihkan')
                ->modalIcon('heroicon-o-arrow-path')
                ->modalIconColor('success')
                ->successNotificationTitle('Nota Dinas Dipulihkan'),
            ForceDeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Hapus Permanen Nota Dinas')
                ->modalDescription('Apakah Anda yakin ingin MENGHAPUS PERMANEN Nota Dinas ini? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua detail terkait.')
                ->modalSubmitActionLabel('Ya, hapus permanen')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->before(function () {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    $detailCount = $record->details()->withTrashed()->count();
                    if ($detailCount > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Peringatan Penghapusan Bertingkat')
                            ->body("⚠️ Tindakan ini akan menghapus permanen Nota Dinas dan {$detailCount} detail terkait. Tindakan ini TIDAK DAPAT DIBATALKAN.")
                            ->persistent()
                            ->send();
                    }
                })
                ->action(function () {
                    /** @var NotaDinas $record */
                    $record = $this->getRecord();
                    try {
                        $detailCount = $record->details()->withTrashed()->count();
                        $record->forceDelete(); // Uses our custom method with cascade

                        Notification::make()
                            ->success()
                            ->title('Dihapus Permanen')
                            ->body("Nota Dinas dan {$detailCount} detail terkait dihapus permanen.")
                            ->send();
                    } catch (Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Gagal Hapus Permanen')
                            ->body('Terjadi kesalahan: '.$e->getMessage())
                            ->persistent()
                            ->send();
                    }
                }),
        ];
    }
}
