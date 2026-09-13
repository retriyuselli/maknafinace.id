<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * Fitur yang dikenali aplikasi iOS — instalasi Makna tidak memakai paket WOFINS.
     *
     * @return list<string>
     */
    public static function iosFeatureKeys(): array
    {
        return [
            'projects',
            'basic_finance',
            'nota_dinas',
            'simulasi',
            'fixed_assets',
            'reconciliation',
            'payroll',
            'documents',
            'crew_freelance',
            'advanced_reports',
            'role_management',
            'multi_approval',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarPath = $this->avatar_url;

        return [
            'id' => $this->id,
            'employee_id' => $this->resource->getAttribute('employee_id'),
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'address' => $this->address,
            'date_of_birth' => optional($this->date_of_birth)?->toDateString(),
            'gender' => $this->gender,
            'department' => $this->department,
            'hire_date' => optional($this->hire_date)?->toDateString(),
            'emergency_contact' => $this->emergency_contact,
            'notes' => $this->notes,
            'status' => $this->status,
            'last_working_date' => optional($this->last_working_date)?->toDateString(),
            'avatar_url' => $avatarPath
                ? url(Storage::url($avatarPath))
                : null,
            'roles' => $this->whenLoaded('roles', fn () => $this->getRoleNames()->values()->all()),
            'expire_date' => optional($this->expire_date)?->toIso8601String(),
            'is_expired' => $this->isExpired(),
            'is_expiring_soon' => $this->isExpiringSoon(),
            'days_until_expiration' => $this->getDaysUntilExpiration(),
            'company' => $this->companyPayload(),
            'entitlements' => $this->entitlementsPayload(),
        ];
    }

    /**
     * @return array{plan: string, plan_label: string, features: list<string>, seat_limit: int|null}
     */
    private function entitlementsPayload(): array
    {
        return [
            'plan' => 'makna',
            'plan_label' => 'Makna Finance',
            'features' => self::iosFeatureKeys(),
            'seat_limit' => null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function companyPayload(): ?array
    {
        $company = null;
        if (method_exists($this->resource, 'company') && $this->relationLoaded('company')) {
            $company = $this->company;
        }
        if (! $company && Schema::hasTable('companies')) {
            $company = Company::query()->orderBy('id')->first();
        }

        if (! $company) {
            return [
                'id' => 0,
                'name' => config('app.name', 'Makna Finance'),
                'inisial' => 'MF',
                'logo_url' => null,
                'email' => null,
                'phone' => null,
                'address' => null,
                'city' => null,
                'province' => null,
                'website' => null,
                'description' => null,
                'owner_name' => null,
                'jabatan_owner' => null,
                'established_year' => null,
                'is_active' => true,
                'subscription_plan' => 'makna',
                'subscription_label' => 'Makna Finance',
                'subscription_expires_at' => null,
            ];
        }

        $logo = trim((string) ($company->logo_url ?? ''));
        $logoUrl = null;
        if ($logo !== '') {
            $logoUrl = str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://')
                ? $logo
                : url(Storage::url($logo));
        }

        $isActive = true;
        if (method_exists($company, 'isActive')) {
            $isActive = $company->isActive();
        }

        return [
            'id' => (int) $company->id,
            'name' => $company->company_name,
            'inisial' => $company->inisial_wo,
            'logo_url' => $logoUrl,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'city' => $company->city,
            'province' => $company->province,
            'website' => $company->website,
            'description' => $company->description,
            'owner_name' => $company->owner_name,
            'jabatan_owner' => $company->jabatan_owner,
            'established_year' => $company->established_year,
            'is_active' => $isActive,
            'subscription_plan' => $company->subscription_plan ?? 'makna',
            'subscription_label' => 'Makna Finance',
            'subscription_expires_at' => optional($company->subscription_expires_at ?? null)?->toIso8601String(),
        ];
    }
}
