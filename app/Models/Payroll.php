<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_month',
        'period_year',
        'gaji_pokok',
        'tunjangan',
        'pengurangan',
        'monthly_salary',
        'annual_salary',
        'bonus',
        'last_review_date',
        'next_review_date',
        'notes',
    ];

    protected $casts = [
        'period_month' => 'integer',
        'period_year' => 'integer',
        'gaji_pokok' => 'integer',
        'tunjangan' => 'integer',
        'pengurangan' => 'integer',
        'monthly_salary' => 'integer',
        'annual_salary' => 'integer',
        'bonus' => 'integer',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($payroll) {
            // Set default period jika belum diisi
            if (! $payroll->period_month) {
                $payroll->period_month = now()->month;
            }
            if (! $payroll->period_year) {
                $payroll->period_year = now()->year;
            }

            // Otomatis hitung monthly_salary dari (gaji_pokok + tunjangan + bonus) - pengurangan
            if ($payroll->gaji_pokok !== null || $payroll->tunjangan !== null || $payroll->bonus !== null || $payroll->pengurangan !== null) {
                $gajiPokok = $payroll->gaji_pokok ?? 0;
                $tunjangan = $payroll->tunjangan ?? 0;
                $bonus = $payroll->bonus ?? 0;
                $pengurangan = $payroll->pengurangan ?? 0;
                $payroll->monthly_salary = $gajiPokok + $tunjangan + $bonus - $pengurangan;
            }

            // Otomatis hitung annual_salary setiap kali monthly_salary berubah
            $payroll->annual_salary = self::computeAnnualBase($payroll->gaji_pokok ?? 0, $payroll->tunjangan ?? 0);
        });
    }

    public static function toInt($value): int
    {
        return (int) str_replace(',', '', (string) $value);
    }

    public static function computeMonthly($gajiPokok, $tunjangan, $bonus, $pengurangan): int
    {
        return self::toInt($gajiPokok) + self::toInt($tunjangan) + self::toInt($bonus) - self::toInt($pengurangan);
    }

    public static function computeAnnual($monthly): int
    {
        return self::toInt($monthly) * 12;
    }

    public static function computeAnnualBase($gajiPokok, $tunjangan): int
    {
        return (self::toInt($gajiPokok) + self::toInt($tunjangan)) * 12;
    }

    public static function computeTotalCompensationBase($gajiPokok, $tunjangan, $pengurangan): int
    {
        return self::computeAnnual(
            self::computeMonthly($gajiPokok, $tunjangan, 0, $pengurangan)
        );
    }

    // Accessor untuk menghitung annual salary berdasarkan monthly salary
    public function getCalculatedAnnualSalaryAttribute(): float
    {
        return (float) self::computeAnnualBase($this->gaji_pokok ?? 0, $this->tunjangan ?? 0);
    }

    // Accessor untuk mendapatkan total kompensasi (bonus sudah termasuk dalam monthly_salary)
    public function getTotalCompensationAttribute(): float
    {
        $baseMonthly = ($this->gaji_pokok ?? 0) + ($this->tunjangan ?? 0) - ($this->pengurangan ?? 0);
        return (float) $baseMonthly * 12;
    }

    // Accessor untuk periode yang mudah dibaca
    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $monthName = $months[$this->period_month] ?? 'Unknown';

        return "{$monthName} {$this->period_year}";
    }

    // Accessor untuk format currency dengan titik
    public function getFormattedMonthlySalaryAttribute(): string
    {
        return number_format((float) $this->monthly_salary, 0, '.', '.');
    }

    public function getFormattedAnnualSalaryAttribute(): string
    {
        return number_format($this->calculated_annual_salary, 0, '.', '.');
    }

    public function getFormattedBonusAttribute(): string
    {
        return number_format((float) ($this->bonus ?? 0), 0, '.', '.');
    }

    public function getFormattedTotalCompensationAttribute(): string
    {
        return number_format($this->total_compensation, 0, '.', '.');
    }

    // Accessor untuk format currency dengan Rp prefix
    public function getFormattedMonthlySalaryWithPrefixAttribute(): string
    {
        return 'Rp '.$this->formatted_monthly_salary;
    }

    public function getFormattedAnnualSalaryWithPrefixAttribute(): string
    {
        return 'Rp '.$this->formatted_annual_salary;
    }

    public function getFormattedCalculatedAnnualSalaryWithPrefixAttribute(): string
    {
        return 'Rp '.number_format($this->calculated_annual_salary, 0, '.', '.');
    }

    public function getFormattedBonusWithPrefixAttribute(): string
    {
        return 'Rp '.$this->formatted_bonus;
    }

    public function getFormattedTotalCompensationWithPrefixAttribute(): string
    {
        return 'Rp '.$this->formatted_total_compensation;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
