<?php

namespace App\Console\Commands;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\JournalBatch;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Services\OrderJournalService;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegenerateJournalEntries extends Command
{
    protected $signature = 'journal:regenerate 
                            {--type=all : Type of journals to regenerate (expense|payment|revenue|all)}
                            {--order-id= : Specific order ID to regenerate}
                            {--dry-run : Show what would be changed without making changes}
                            {--force : Force regeneration even if journals exist}';

    protected $description = 'Regenerate journal entries with correct transaction dates';

    protected OrderJournalService $journalService;

    public function __construct(OrderJournalService $journalService)
    {
        parent::__construct();
        $this->journalService = $journalService;
    }

    public function handle()
    {
        $type = $this->option('type');
        $orderId = $this->option('order-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        } else {
            $this->info('🔄 REGENERATING journal entries...');

            // Skip confirmation if running in non-interactive mode or with force flag
            if (! $force && ! $this->option('no-interaction') && ! $this->confirm('This will regenerate journal entries. Continue?')) {
                $this->info('Operation cancelled.');

                return;
            }
        }

        $this->newLine();

        try {
            DB::beginTransaction();

            switch ($type) {
                case 'expense':
                    $this->regenerateExpenseJournals($orderId, $dryRun);
                    break;
                case 'payment':
                    $this->regeneratePaymentJournals($orderId, $dryRun);
                    break;
                case 'revenue':
                    $this->regenerateRevenueJournals($orderId, $dryRun);
                    break;
                case 'all':
                default:
                    $this->regenerateExpenseJournals($orderId, $dryRun);
                    $this->regeneratePaymentJournals($orderId, $dryRun);
                    $this->regenerateRevenueJournals($orderId, $dryRun);
                    break;
            }

            if (! $dryRun) {
                DB::commit();
                $this->info('✅ Journal entries regenerated successfully!');
            } else {
                DB::rollBack();
                $this->info('✅ Dry run completed! Use without --dry-run to apply changes');
            }

        } catch (Exception $e) {
            DB::rollBack();
            $this->error('❌ Error regenerating journals: '.$e->getMessage());
            Log::error('Journal regeneration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    private function regenerateExpenseJournals($orderId = null, $dryRun = false)
    {
        $this->info('💰 Regenerating expense journals...');

        $query = Expense::with(['order', 'journalBatches']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $expenses = $query->get();
        $this->line("   Found {$expenses->count()} expenses to process");

        $processed = 0;
        $regenerated = 0;

        foreach ($expenses as $expense) {
            $existingJournal = JournalBatch::where('reference_type', 'expense')
                ->where('reference_id', $expense->id)
                ->whereIn('status', ['draft', 'posted'])
                ->first();

            if ($existingJournal) {
                $originalDate = $existingJournal->transaction_date?->toDateString();
                $newDate = $this->calculateCorrectExpenseDate($expense);

                if ($originalDate != $newDate || $this->option('force')) {
                    if ($dryRun) {
                        $this->line("   [DRY RUN] Would regenerate Expense {$expense->id} journal: {$originalDate} → {$newDate}");
                    } else {
                        $existingJournal->update(['transaction_date' => $newDate]);
                        JournalEntry::where('journal_batch_id', $existingJournal->id)
                            ->update(['transaction_date' => $newDate]);

                        $this->line("   ✅ Regenerated Expense {$expense->id} journal: {$originalDate} → {$newDate}");

                        Log::info('Regenerated expense journal', [
                            'expense_id' => $expense->id,
                            'original_date' => $originalDate,
                            'new_date' => $newDate,
                            'success' => true,
                        ]);
                    }
                    $regenerated++;
                }
            } else {
                // Generate missing journal
                if ($dryRun) {
                    $this->line("   [DRY RUN] Would create missing journal for Expense {$expense->id}");
                } else {
                    $journal = $this->journalService->generateExpenseJournal($expense);
                    if ($journal) {
                        $this->line("   ✅ Created missing journal for Expense {$expense->id}");
                    } else {
                        $this->error("   ❌ Failed to create journal for Expense {$expense->id}");
                    }
                }
                $regenerated++;
            }

            $processed++;
            if ($processed % 100 === 0) {
                $this->line("   Processed {$processed}/{$expenses->count()} expenses...");
            }
        }

        $this->line("   Processed: {$processed}, Regenerated: {$regenerated}");
    }

    private function regeneratePaymentJournals($orderId = null, $dryRun = false)
    {
        $this->info('💳 Regenerating payment journals...');

        $query = DataPembayaran::with(['order', 'journalBatches']);

        if ($orderId) {
            $query->where('order_id', $orderId);
        }

        $payments = $query->get();
        $this->line("   Found {$payments->count()} payments to process");

        $processed = 0;
        $regenerated = 0;

        foreach ($payments as $payment) {
            $existingJournal = $payment->journalBatches()
                ->where('reference_type', 'payment')
                ->whereIn('status', ['draft', 'posted'])
                ->first();

            if ($existingJournal) {
                $originalDate = $existingJournal->transaction_date?->toDateString();
                $newDate = ($payment->tgl_bayar ?? now())->format('Y-m-d');

                if ($originalDate != $newDate || $this->option('force')) {
                    if ($dryRun) {
                        $this->line("   [DRY RUN] Would regenerate Payment {$payment->id} journal: {$originalDate} → {$newDate}");
                    } else {
                        $existingJournal->update(['transaction_date' => $newDate]);
                        JournalEntry::where('journal_batch_id', $existingJournal->id)
                            ->update(['transaction_date' => $newDate]);

                        $this->line("   ✅ Regenerated Payment {$payment->id} journal: {$originalDate} → {$newDate}");
                    }
                    $regenerated++;
                }
            } else {
                // Generate missing journal
                if ($dryRun) {
                    $this->line("   [DRY RUN] Would create missing journal for Payment {$payment->id}");
                } else {
                    $this->journalService->generatePaymentJournal($payment);
                    $this->line("   ✅ Created missing journal for Payment {$payment->id}");
                }
                $regenerated++;
            }

            $processed++;
        }

        $this->line("   Processed: {$processed}, Regenerated: {$regenerated}");
    }

    private function regenerateRevenueJournals($orderId = null, $dryRun = false)
    {
        $this->info('📈 Regenerating revenue journals...');

        $query = Order::with(['journalBatches']);

        if ($orderId) {
            $query->where('id', $orderId);
        } else {
            // 🚨 CRITICAL FIX: Only regenerate revenue journals for DONE orders
            $query->where('status', 'done');
        }

        $orders = $query->get();
        $this->line("   Found {$orders->count()} orders to process (status filter: ".($orderId ? 'specific order' : 'done only').')');

        $processed = 0;
        $regenerated = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            // Double check: Only process DONE orders with grand_total > 0
            if (! $orderId && ($order->status?->value !== 'done' || $order->grand_total <= 0)) {
                $this->line("   ⏭️  Skipping Order {$order->id}: {$order->name} (Status: ".($order->status?->getLabel() ?? 'NULL').', Amount: '.number_format($order->grand_total, 0).')');
                $skipped++;

                continue;
            }

            $existingJournal = $order->journalBatches()
                ->where('reference_type', 'order_revenue')
                ->whereIn('status', ['draft', 'posted'])
                ->first();

            if ($existingJournal) {
                $originalDate = $existingJournal->transaction_date?->toDateString();
                $newDate = ($order->closing_date ?? now())->format('Y-m-d');

                if ($originalDate != $newDate || $this->option('force')) {
                    if ($dryRun) {
                        $this->line("   [DRY RUN] Would regenerate Order {$order->id} revenue journal: {$originalDate} → {$newDate}");
                    } else {
                        $existingJournal->update(['transaction_date' => $newDate]);
                        JournalEntry::where('journal_batch_id', $existingJournal->id)
                            ->update(['transaction_date' => $newDate]);

                        $this->line("   ✅ Regenerated Order {$order->id} revenue journal: {$originalDate} → {$newDate}");
                    }
                    $regenerated++;
                }
            } else {
                // Generate missing journal
                if ($dryRun) {
                    $this->line("   [DRY RUN] Would create missing revenue journal for Order {$order->id}");
                } else {
                    $this->journalService->generateRevenueRecognitionJournal($order);
                    $this->line("   ✅ Created missing revenue journal for Order {$order->id}");
                }
                $regenerated++;
            }

            $processed++;
        }

        $this->line("   Processed: {$processed}, Regenerated: {$regenerated}, Skipped: {$skipped}");
    }

    private function calculateCorrectExpenseDate($expense)
    {
        $expenseDate = $expense->date_expense;
        $orderClosingDate = $expense->order?->closing_date;
        $today = now();

        $dates = collect([$expenseDate, $orderClosingDate, $today])
            ->filter()
            ->map(fn ($d) => $d->copy()->startOfDay());

        return $dates->min()?->toDateString() ?? now()->toDateString();
    }
}
