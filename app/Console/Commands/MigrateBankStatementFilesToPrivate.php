<?php

namespace App\Console\Commands;

use App\Models\BankStatement;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateBankStatementFilesToPrivate extends Command
{
    protected $signature = 'bank-statements:migrate-private {--dry-run : Tampilkan perubahan tanpa memindahkan file}';

    protected $description = 'Pindahkan file rekening koran & rekonsiliasi dari disk public ke private';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '🔍 DRY RUN - tidak ada file yang dipindahkan' : '🔄 Memindahkan file ke disk private');

        $moved = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;

        BankStatement::query()
            ->whereNotNull('file_path')
            ->orWhereNotNull('reconciliation_file')
            ->orderBy('id')
            ->chunkById(200, function ($statements) use (&$moved, &$skipped, &$missing, &$failed, $dryRun) {
                foreach ($statements as $statement) {
                    foreach (['file_path', 'reconciliation_file'] as $field) {
                        $path = $statement->{$field};
                        if (! $path) {
                            continue;
                        }

                        if (str_contains($path, '..')) {
                            $this->error("❌ Skip invalid path (..): BankStatement {$statement->id} {$field}={$path}");
                            $failed++;
                            continue;
                        }

                        if (Storage::disk('private')->exists($path)) {
                            $skipped++;
                            continue;
                        }

                        if (! Storage::disk('public')->exists($path)) {
                            $this->warn("⚠️  Missing file: BankStatement {$statement->id} {$field}={$path}");
                            $missing++;
                            continue;
                        }

                        $this->line(($dryRun ? '[DRY RUN] ' : '')."Move: {$path}");

                        if ($dryRun) {
                            $moved++;
                            continue;
                        }

                        $read = Storage::disk('public')->readStream($path);
                        if (! is_resource($read)) {
                            $this->error("❌ Failed to read: {$path}");
                            $failed++;
                            continue;
                        }

                        try {
                            $ok = Storage::disk('private')->writeStream($path, $read);
                            if (! $ok) {
                                $this->error("❌ Failed to write: {$path}");
                                $failed++;
                                continue;
                            }

                            Storage::disk('public')->delete($path);
                            $moved++;
                        } catch (Exception $e) {
                            $this->error("❌ Error moving {$path}: ".$e->getMessage());
                            $failed++;
                        } finally {
                            fclose($read);
                        }
                    }
                }
            });

        $this->newLine();
        $this->info("Done. moved={$moved}, skipped={$skipped}, missing={$missing}, failed={$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}

