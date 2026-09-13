<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Payroll */
class PayrollResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'period_month' => $this->period_month,
            'period_year' => $this->period_year,
            'period_name' => $this->period_name,
            'gaji_pokok' => (int) ($this->gaji_pokok ?? 0),
            'tunjangan' => (int) ($this->tunjangan ?? 0),
            'pengurangan' => (int) ($this->pengurangan ?? 0),
            'bonus' => (int) ($this->bonus ?? 0),
            'monthly_salary' => (int) ($this->monthly_salary ?? 0),
            'annual_salary' => (int) ($this->calculated_annual_salary ?? 0),
            'total_compensation' => (int) ($this->total_compensation ?? 0),
            'formatted' => [
                'monthly_salary' => $this->formatted_monthly_salary_with_prefix,
                'annual_salary' => $this->formatted_calculated_annual_salary_with_prefix,
                'bonus' => $this->formatted_bonus_with_prefix,
                'total_compensation' => $this->formatted_total_compensation_with_prefix,
            ],
            'updated_at' => optional($this->updated_at)?->toIso8601String(),
        ];
    }
}
