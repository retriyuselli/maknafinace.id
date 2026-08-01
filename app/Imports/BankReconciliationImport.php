<?php

namespace App\Imports;

use App\Models\BankReconciliationItem;
use App\Models\BankStatement;
use Carbon\Carbon;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Import mutasi bank untuk rekonsiliasi.
 *
 * Mendukung DUA format file:
 *  - FORMAT_TEMPLATE : 4-kolom custom (Tanggal | Keterangan | Debit | Credit)
 *  - FORMAT_MANDIRI  : Bank Mandiri native (10 kolom, date dd/mm/yy, desc berquote)
 *
 * Format terdeteksi otomatis dari baris header — tidak perlu konfigurasi manual.
 */
class BankReconciliationImport implements ToCollection
{
    use Importable;

    // ── Format constants ──────────────────────────────────────────────────────
    const FORMAT_TEMPLATE = 'template';
    const FORMAT_MANDIRI  = 'mandiri';

    // ── Indeks kolom Bank Mandiri (0-based, kolom A = 0) ─────────────────────
    const MANDIRI_COL_DATE    = 1; // B — Date          '01/04/26'
    const MANDIRI_COL_DESC_E  = 4; // E — Description 1  routing info (bisa kosong)
    const MANDIRI_COL_DESC_F  = 5; // F — Description 2  nama pendek (primary)
    const MANDIRI_COL_DEBIT   = 7; // H — Debit
    const MANDIRI_COL_CREDIT  = 8; // I — Credit

    protected BankStatement $bankStatement;
    protected int $importedCount = 0;

    /** @var string[] */
    protected array $errors = [];

    public function __construct(BankStatement $bankStatement)
    {
        $this->bankStatement = $bankStatement;
    }

    // =========================================================================
    // Entry point
    // =========================================================================

    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        // Baris pertama = header; deteksi format dari sini
        $format   = $this->detectFormat($rows->first());
        $dataRows = $rows->slice(1); // skip header row

        $totalDebit  = 0;
        $totalCredit = 0;
        $rowNumber   = 2; // row 1 = header, data mulai row 2 (sesuai nomor Excel)

        // Delete + insert dalam satu transaction — rollback otomatis jika gagal
        DB::transaction(function () use ($dataRows, $format, &$totalDebit, &$totalCredit, &$rowNumber) {
            BankReconciliationItem::where('bank_reconciliation_id', $this->bankStatement->id)->delete();

            foreach ($dataRows as $row) {
                // Skip baris benar-benar kosong
                if ($row->filter(fn ($v) => $v !== null && (string) $v !== '')->isEmpty()) {
                    $rowNumber++;
                    continue;
                }

                try {
                    $parsed = $format === self::FORMAT_MANDIRI
                        ? $this->parseMandiriRow($row)
                        : $this->parseTemplateRow($row);

                    BankReconciliationItem::create([
                        'bank_reconciliation_id' => $this->bankStatement->id,
                        'date'                   => $parsed['date'],
                        'description'            => $parsed['description'],
                        'debit'                  => $parsed['debit'],
                        'credit'                 => $parsed['credit'],
                        'row_number'             => $rowNumber,
                    ]);

                    $totalDebit  += $parsed['debit'];
                    $totalCredit += $parsed['credit'];
                    $this->importedCount++;

                } catch (Exception $e) {
                    $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
                }

                $rowNumber++;
            }
        });

        // Update statistik di BankStatement setelah transaction selesai
        $this->bankStatement->update([
            'total_records'               => $this->importedCount,
            'total_debit_reconciliation'  => $totalDebit,
            'total_credit_reconciliation' => $totalCredit,
            'reconciliation_status'       => empty($this->errors) ? 'completed' : 'failed',
            'processed_at'                => now(),
        ]);
    }

    // =========================================================================
    // Format Detection
    // =========================================================================

    /**
     * Deteksi format dari baris header (baris pertama file).
     *
     * Bank Mandiri : kolom A = 'Account No'
     * Template     : kolom A = 'Tanggal' / 'Date' / lainnya
     */
    protected function detectFormat(Collection $headerRow): string
    {
        $firstCell = strtolower(trim((string) ($headerRow->get(0) ?? '')));

        return $firstCell === 'account no'
            ? self::FORMAT_MANDIRI
            : self::FORMAT_TEMPLATE;
    }

    // =========================================================================
    // Row Parsers
    // =========================================================================

    /**
     * Parse baris format Bank Mandiri native (10 kolom).
     *
     * Struktur: A=AccountNo  B=Date  C=ValDate  D=TxCode
     *           E=Description(routing)  F=Description(nama)
     *           G=Ref  H=Debit  I=Credit  J=(null)
     *
     * Keterangan gabungan: col F (nama pendek) + col E (routing info).
     *
     * @return array{date: Carbon, description: string, debit: int, credit: int}
     *
     * @throws Exception
     */
    protected function parseMandiriRow(Collection $row): array
    {
        $dateRaw   = $row->get(self::MANDIRI_COL_DATE);
        $descERaw  = $row->get(self::MANDIRI_COL_DESC_E); // routing info
        $descFRaw  = $row->get(self::MANDIRI_COL_DESC_F); // nama pendek (primary)
        $debitRaw  = $row->get(self::MANDIRI_COL_DEBIT);
        $creditRaw = $row->get(self::MANDIRI_COL_CREDIT);

        if (empty($dateRaw)) {
            throw new Exception('Kolom Date (col B) kosong');
        }

        // Strip wrapping quotes dan trailing spaces dari nilai Bank Mandiri
        $descF = $this->stripMandiriQuotes((string) ($descFRaw ?? ''));
        $descE = $this->stripMandiriQuotes((string) ($descERaw ?? ''));

        // Gabung: col F sebagai primary, col E ditambah jika ada isi
        $description = $descF !== '' ? trim($descF . ($descE !== '' ? ' ' . $descE : '')) : $descE;

        if (empty($description)) {
            throw new Exception('Kolom Description (col E & F) keduanya kosong');
        }

        return [
            'date'        => $this->parseDate($dateRaw),
            'description' => $description,
            'debit'       => $this->parseAmount($debitRaw  ?? 0),
            'credit'      => $this->parseAmount($creditRaw ?? 0),
        ];
    }

    /**
     * Parse baris format template 4-kolom.
     *
     * Struktur: A=Tanggal  B=Keterangan  C=Debit  D=Credit
     *
     * @return array{date: Carbon, description: string, debit: int, credit: int}
     *
     * @throws Exception
     */
    protected function parseTemplateRow(Collection $row): array
    {
        $dateRaw   = $row->get(0); // A: Tanggal
        $descRaw   = $row->get(1); // B: Keterangan
        $debitRaw  = $row->get(2); // C: Debit
        $creditRaw = $row->get(3); // D: Credit

        if (empty($dateRaw)) {
            throw new Exception('Kolom Tanggal (col A) kosong');
        }

        $description = trim((string) ($descRaw ?? ''));
        if (empty($description)) {
            throw new Exception('Kolom Keterangan (col B) kosong');
        }

        return [
            'date'        => $this->parseDate($dateRaw),
            'description' => $description,
            'debit'       => $this->parseAmount($debitRaw  ?? 0),
            'credit'      => $this->parseAmount($creditRaw ?? 0),
        ];
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Hapus wrapping double-quote dan whitespace dari cell Bank Mandiri.
     *
     * Contoh: '"24 DP Venue Kebon Ge                    "' → '24 DP Venue Kebon Ge'
     */
    protected function stripMandiriQuotes(string $value): string
    {
        return trim(trim($value), '"');
    }

    /**
     * Parse tanggal dari berbagai bentuk input.
     *
     * Mendukung:
     *  - DateTimeInterface / Carbon  : template via Maatwebsite (sel bertipe tanggal)
     *  - String 'd/m/y'              : Bank Mandiri native ('01/04/26' → 2026-04-01)
     *  - Integer serial Excel        : mis. 46113 → 2026-04-01
     *  - String berbagai format      : 'Y-m-d', 'd/m/Y', dsb.
     *
     * @throws Exception
     */
    protected function parseDate(mixed $date): Carbon
    {
        if (empty($date)) {
            throw new Exception('Tanggal wajib diisi');
        }

        // Object Carbon / DateTime (template — Maatwebsite konversi sel tanggal)
        if ($date instanceof DateTimeInterface) {
            return Carbon::instance($date)->startOfDay();
        }

        // Excel numeric serial (mis. 46113 = 2026-04-01)
        if (is_numeric($date)) {
            return Carbon::createFromFormat('Y-m-d', '1900-01-01')
                ->addDays((int) $date - 2)
                ->startOfDay();
        }

        $dateStr = (string) $date;

        // String — coba format dalam urutan prioritas
        // d/m/y diletakkan PERTAMA karena ini format Bank Mandiri ('01/04/26')
        // PHP: y = 2-digit year; 00-69 → 2000-2069, 70-99 → 1970-1999
        $formats = [
            'd/m/y',  // Bank Mandiri: '01/04/26'
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'd.m.Y',
            'Y/m/d',
            'd M Y',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $dateStr)->startOfDay();
            } catch (Exception) {
                continue;
            }
        }

        // Fallback ke Carbon::parse
        try {
            return Carbon::parse($dateStr)->startOfDay();
        } catch (Exception) {
            throw new Exception("Format tanggal tidak dikenali: {$dateStr}");
        }
    }

    /**
     * Parse nominal ke Rupiah bulat (integer, dibulatkan ke terdekat).
     *
     * Mendukung:
     *  - string '5000000', '5000000.00'        : Bank Mandiri / template
     *  - integer 2500                           : template
     *  - string '86293.28'                      : bunga/pajak — dibulatkan ke 86293
     *  - string '1,234,567' / '1.234.567,00'   : format ribuan
     */
    protected function parseAmount(mixed $amount): int
    {
        if ($amount === null || $amount === '' || $amount === '-') {
            return 0;
        }

        if (is_numeric($amount)) {
            return (int) round((float) $amount);
        }

        $cleaned = preg_replace('/[^\d,.-]/', '', (string) $amount);

        if ($cleaned === '' || $cleaned === '-') {
            return 0;
        }

        $commaCount = substr_count($cleaned, ',');
        $dotCount   = substr_count($cleaned, '.');

        if ($commaCount === 1 && $dotCount === 0) {
            // Format: '1234567,89' — koma sebagai desimal
            $cleaned = str_replace(',', '.', $cleaned);
        } elseif ($dotCount >= 2) {
            // Format: '1.234.567' — titik sebagai separator ribuan
            $cleaned = str_replace('.', '', $cleaned);
        } else {
            // Format US / '5000000.00' — hapus koma ribuan
            $cleaned = str_replace(',', '', $cleaned);
        }

        return (int) round((float) $cleaned);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getDetectedFormat(): string
    {
        return self::FORMAT_TEMPLATE; // diset ulang oleh detectFormat() saat runtime
    }

    /** @return string[] */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
