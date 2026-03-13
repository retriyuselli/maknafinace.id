<?php

namespace App\Console\Commands;

use App\Models\DataPembayaran;
use App\Models\Expense;
use App\Models\Order;
use App\Services\OrderJournalService;
use Exception;
use Illuminate\Console\Command;

class GenerateOrderJournals extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'generate:order-journals {--dry-run : Show what would be generated without actually creating}';

    /**
     * The console command description.
     */
    protected $description = 'Generate journal entries for existing Orders, Payments, and Expenses';

    protected $orderJournalService;

    public function __construct(OrderJournalService $orderJournalService)
    {
        parent::__construct();
        $this->orderJournalService = $orderJournalService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No journals will be created');
        } else {
            $this->info('🔄 GENERATING journal entries for existing data');
        }

        $this->newLine();

        // 1. Generate Order Revenue Recognition Journals
        $this->generateOrderRevenueJournals($isDryRun);

        // 2. Generate Payment Journals
        $this->generatePaymentJournals($isDryRun);

        // 3. Generate Expense Journals
        $this->generateExpenseJournals($isDryRun);

        $this->newLine();

        if ($isDryRun) {
            $this->info('✅ Dry run completed! Use without --dry-run to actually generate journals');
        } else {
            $this->info('✅ Journal generation completed!');
        }
    }

    private function generateOrderRevenueJournals($isDryRun = false)
    {
        $this->info('📋 Processing Order Revenue Recognition...');

        // Get Orders with status 'done' that don't have revenue journals
        $orders = Order::where('status', 'done')
            ->whereDoesntHave('journalBatches', function ($query) {
                $query->where('reference_type', 'order_revenue')
                    ->whereIn('status', ['draft', 'posted']);
            })
            ->get();

        $this->line("   Found {$orders->count()} orders needing revenue recognition journals");

        if ($isDryRun) {
            $orders->each(function ($order) {
                $this->line("   [DRY RUN] Would create revenue journal for Order {$order->id}: {$order->name} (Rp ".number_format($order->grand_total, 0, ',', '.').')');
            });

            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($orders as $order) {
            try {
                $batch = $this->orderJournalService->generateRevenueRecognitionJournal($order);
                if ($batch) {
                    $this->line("   ✅ Created draft revenue journal for Order {$order->id}: {$order->name} ({$batch->batch_number})");
                    $successCount++;
                } else {
                    $this->line("   ❌ Failed to create revenue journal for Order {$order->id}: {$order->name}");
                    $errorCount++;
                }
            } catch (Exception $e) {
                $this->line("   ❌ Error creating revenue journal for Order {$order->id}: ".$e->getMessage());
                $errorCount++;
            }
        }

        $this->line("   Revenue journals (draft): {$successCount} created, {$errorCount} errors");
    }

    private function generatePaymentJournals($isDryRun = false)
    {
        $this->info('💰 Processing Payment Journals...');

        // Get payments that don't have journals
        $payments = DataPembayaran::where('nominal', '>', 0)
            ->whereHas('order') // Ensure order exists
            ->whereDoesntHave('journalBatches', function ($query) {
                $query->where('reference_type', 'payment')
                    ->whereIn('status', ['draft', 'posted']);
            })
            ->with('order')
            ->get();

        $this->line("   Found {$payments->count()} payments needing journals");

        if ($isDryRun) {
            $payments->each(function ($payment) {
                $this->line("   [DRY RUN] Would create payment journal for Payment {$payment->id}: {$payment->order->name} (Rp ".number_format($payment->nominal, 0, ',', '.').')');
            });

            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($payments as $payment) {
            try {
                $batch = $this->orderJournalService->generatePaymentJournal($payment);
                if ($batch) {
                    $this->line("   ✅ Created draft payment journal for Payment {$payment->id}: {$payment->order->name} ({$batch->batch_number})");
                    $successCount++;
                } else {
                    $this->line("   ❌ Failed to create payment journal for Payment {$payment->id}");
                    $errorCount++;
                }
            } catch (Exception $e) {
                $this->line("   ❌ Error creating payment journal for Payment {$payment->id}: ".$e->getMessage());
                $errorCount++;
            }
        }

        $this->line("   Payment journals (draft): {$successCount} created, {$errorCount} errors");
    }

    private function generateExpenseJournals($isDryRun = false)
    {
        $this->info('💸 Processing Expense Journals...');

        // Get expenses that don't have journals
        $expenses = Expense::where('amount', '>', 0)
            ->whereNotNull('order_id') // Only order-related expenses
            ->whereHas('order') // Ensure order exists
            ->whereDoesntHave('journalBatches', function ($query) {
                $query->where('reference_type', 'expense')
                    ->whereIn('status', ['draft', 'posted']);
            })
            ->with('order')
            ->get();

        $this->line("   Found {$expenses->count()} expenses needing journals");

        if ($isDryRun) {
            $expenses->each(function ($expense) {
                $this->line("   [DRY RUN] Would create expense journal for Expense {$expense->id}: {$expense->order->name} - {$expense->note} (Rp ".number_format($expense->amount, 0, ',', '.').')');
            });

            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($expenses as $expense) {
            try {
                $batch = $this->orderJournalService->generateExpenseJournal($expense);
                if ($batch) {
                    $this->line("   ✅ Created draft expense journal for Expense {$expense->id}: {$expense->order->name} - {$expense->note} ({$batch->batch_number})");
                    $successCount++;
                } else {
                    $this->line("   ❌ Failed to create expense journal for Expense {$expense->id}");
                    $errorCount++;
                }
            } catch (Exception $e) {
                $this->line("   ❌ Error creating expense journal for Expense {$expense->id}: ".$e->getMessage());
                $errorCount++;
            }
        }

        $this->line("   Expense journals (draft): {$successCount} created, {$errorCount} errors");
    }
}
