<?php

namespace App\Console\Commands;

use App\Models\DataPembayaran;
use App\Models\DocumentAttachment;
use App\Models\LeaveRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateSensitiveFilesToPrivate extends Command
{
    protected $signature = 'files:migrate-sensitive-private
        {--dry-run : Report files without copying or deleting them}
        {--keep-public : Keep the public copy after a successful copy}';

    protected $description = 'Idempotently move sensitive files from public to private storage';

    private int $copied = 0;

    private int $alreadyPrivate = 0;

    private int $missing = 0;

    public function handle(): int
    {
        Order::query()->select(['id', 'doc_kontrak', 'agreement_product'])->chunkById(100, function ($orders): void {
            foreach ($orders as $order) {
                $this->migrateValue($order->doc_kontrak);
                $this->migrateValue($order->agreement_product);
            }
        });

        DataPembayaran::query()->select(['id', 'image'])->chunkById(100, function ($payments): void {
            foreach ($payments as $payment) {
                $this->migrateValue($payment->image);
            }
        });

        LeaveRequest::query()->select(['id', 'documents'])->chunkById(100, function ($requests): void {
            foreach ($requests as $request) {
                $this->migrateValue($request->documents);
            }
        });

        User::query()->select(['id', 'signature_url'])->chunkById(100, function ($users): void {
            foreach ($users as $user) {
                $this->migrateValue($user->signature_url);
            }
        });

        DocumentAttachment::query()->select(['id', 'file_path'])->chunkById(100, function ($attachments): void {
            foreach ($attachments as $attachment) {
                $this->migrateValue($attachment->file_path);
            }
        });

        $this->info("Copied: {$this->copied}; already private: {$this->alreadyPrivate}; missing: {$this->missing}");

        return self::SUCCESS;
    }

    private function migrateValue(mixed $value): void
    {
        foreach ($this->paths($value) as $path) {
            if (Storage::disk('private')->exists($path)) {
                $this->alreadyPrivate++;
                if (! $this->option('dry-run') && ! $this->option('keep-public')) {
                    Storage::disk('public')->delete($path);
                }

                continue;
            }

            if (! Storage::disk('public')->exists($path)) {
                $this->missing++;
                $this->warn("Missing: {$path}");

                continue;
            }

            $this->line(($this->option('dry-run') ? 'Would copy: ' : 'Copying: ').$path);
            if ($this->option('dry-run')) {
                $this->copied++;

                continue;
            }

            $stream = Storage::disk('public')->readStream($path);
            if ($stream === null || ! Storage::disk('private')->writeStream($path, $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                $this->missing++;
                $this->error("Failed: {$path}");

                continue;
            }
            if (is_resource($stream)) {
                fclose($stream);
            }

            $this->copied++;
            if (! $this->option('keep-public')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * @return list<string>
     */
    private function paths(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)->flatMap(fn ($item) => $this->paths($item))->values()->all();
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $value = trim($value);
        if (str_starts_with($value, '[') || str_starts_with($value, '{')) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->paths($decoded);
            }
        }

        $path = ltrim(str_replace('\\', '/', $value), '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        return $path !== '' && ! str_contains($path, '..') ? [$path] : [];
    }
}
