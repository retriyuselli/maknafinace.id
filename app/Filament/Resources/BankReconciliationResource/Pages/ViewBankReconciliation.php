<?php

namespace App\Filament\Resources\BankReconciliationResource\Pages;

/**
 * File ini adalah sisa legacy — BankReconciliationResource tidak ada.
 * Rekonsiliasi bank kini ditangani oleh:
 *   - BankStatementResource (Filament resource utama)
 *   - ViewReconciliation page (halaman perbandingan rekonsiliasi)
 *   - ReconciliationController (auto-match & unmark API)
 *
 * Kelas ini dibiarkan kosong agar tidak melempar fatal error jika
 * ada referensi lama ke namespace ini.
 */
class ViewBankReconciliation
{
    // Dead code — tidak didaftarkan ke Filament Panel
}
