<?php

namespace App\Services;

use App\Enums\ItemPurchaseCodeStatus;
use App\Models\ItemPurchaseCode;
use App\Models\ProspectApp;
use Illuminate\Support\Str;

class ItemPurchaseCodeService
{
    public function isPaid(ProspectApp $app): bool
    {
        return (int) $app->bayar > 0 && (int) $app->sisa_bayar <= 0;
    }

    public function syncFromProspectApp(ProspectApp $app): ?ItemPurchaseCode
    {
        if (! $this->isPaid($app)) {
            return $app->latestCode;
        }

        return $this->issueForProspectApp($app);
    }

    public function issueForProspectApp(ProspectApp $app, bool $forceNew = false): ItemPurchaseCode
    {
        if (! $app->tgl_mulai) {
            $app->forceFill([
                'tgl_mulai' => now()->toDateString(),
                'tgl_berakhir' => $app->tgl_berakhir ?: now()->addYears(2)->toDateString(),
            ])->saveQuietly();
        }

        $existing = $app->currentCode();

        if ($existing && ! $forceNew) {
            $existing->forceFill([
                'company_name' => $app->company_name,
                'package' => $app->service,
                'domain' => $existing->domain ?: static::normalizeDomain($app->name_of_website),
                'starts_at' => $app->tgl_mulai,
                'ends_at' => $app->tgl_berakhir ?: $app->tgl_mulai->copy()->addYears(2),
            ])->save();

            return $existing->refresh();
        }

        if ($forceNew && $existing) {
            $this->revoke($existing, 'Diterbitkan ulang dari maknafinance.id');
        }

        return ItemPurchaseCode::query()->create([
            'prospect_app_id' => $app->id,
            'code' => (string) Str::uuid(),
            'company_name' => $app->company_name,
            'package' => $app->service,
            'domain' => static::normalizeDomain($app->name_of_website),
            'starts_at' => $app->tgl_mulai,
            'ends_at' => $app->tgl_berakhir ?: $app->tgl_mulai->copy()->addYears(2),
            'status' => ItemPurchaseCodeStatus::Unused,
        ]);
    }

    public function revoke(ItemPurchaseCode $code, ?string $notes = null): ItemPurchaseCode
    {
        $code->forceFill([
            'status' => ItemPurchaseCodeStatus::Revoked,
            'revoked_at' => now(),
            'notes' => trim(implode("\n", array_filter([$code->notes, $notes]))),
        ])->save();

        return $code;
    }

    /**
     * @return array{valid: bool, status: string, message: string, code: string|null, company_name: string|null, package: string|null, domain: string|null, starts_at: string|null, ends_at: string|null}
     */
    public function verify(string $code, ?string $domain = null, bool $bindDomain = true): array
    {
        $record = ItemPurchaseCode::query()->where('code', trim($code))->first();
        $domain = static::normalizeDomain($domain);

        if (! $record) {
            return $this->payload(false, 'invalid', 'Item Purchase Code tidak ditemukan.', null);
        }

        $status = $record->resolvedStatus();

        if ($status === ItemPurchaseCodeStatus::Revoked) {
            return $this->payload(false, $status->value, 'Item Purchase Code sudah dicabut.', $record);
        }

        if ($status === ItemPurchaseCodeStatus::Expired) {
            if ($record->status !== ItemPurchaseCodeStatus::Expired) {
                $record->forceFill(['status' => ItemPurchaseCodeStatus::Expired])->saveQuietly();
            }

            return $this->payload(false, ItemPurchaseCodeStatus::Expired->value, 'Masa berlangganan telah berakhir.', $record);
        }

        $bound = static::normalizeDomain($record->activated_domain ?: $record->domain);

        if ($record->status === ItemPurchaseCodeStatus::Active && $bound && $domain && $bound !== $domain) {
            return $this->payload(false, 'domain_mismatch', 'Kode ini sudah terikat ke domain lain.', $record);
        }

        if ($bindDomain && $domain && $record->status === ItemPurchaseCodeStatus::Unused) {
            $record->forceFill([
                'status' => ItemPurchaseCodeStatus::Active,
                'activated_domain' => $domain,
                'activated_at' => now(),
                'last_verified_at' => now(),
            ])->save();
        } else {
            $record->forceFill(['last_verified_at' => now()])->saveQuietly();
        }

        $record->refresh();

        return $this->payload(
            true,
            $record->resolvedStatus()->value,
            'Item Purchase Code valid.',
            $record
        );
    }

    public static function normalizeDomain(?string $value): ?string
    {
        $value = strtolower(trim((string) $value));
        if ($value === '') {
            return null;
        }

        $value = preg_replace('#^https?://#', '', $value) ?? $value;
        $value = preg_replace('#^www\.#', '', $value) ?? $value;
        $value = explode('/', $value)[0];
        $value = explode(':', $value)[0];

        return $value !== '' ? $value : null;
    }

    /**
     * @return array{valid: bool, status: string, message: string, code: string|null, company_name: string|null, package: string|null, domain: string|null, starts_at: string|null, ends_at: string|null}
     */
    private function payload(bool $valid, string $status, string $message, ?ItemPurchaseCode $record): array
    {
        return [
            'valid' => $valid,
            'status' => $status,
            'message' => $message,
            'code' => $record?->code,
            'company_name' => $record?->company_name,
            'package' => $record?->package,
            'domain' => $record?->activated_domain ?: $record?->domain,
            'starts_at' => $record?->starts_at?->toDateString(),
            'ends_at' => $record?->ends_at?->toDateString(),
        ];
    }
}
