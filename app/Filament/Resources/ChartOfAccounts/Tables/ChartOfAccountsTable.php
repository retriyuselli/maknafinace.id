<?php

namespace App\Filament\Resources\ChartOfAccounts\Tables;

use App\Filament\Resources\ChartOfAccounts\ChartOfAccountResource;
use App\Models\ChartOfAccount;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('account_code')
            ->columns([
                TextColumn::make('account_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('account_name')
                    ->label('Account Name')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        $prefix = str_repeat('└── ', $record->level - 1);

                        return $prefix.$record->account_name;
                    }),

                TextColumn::make('account_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ChartOfAccount::ACCOUNT_TYPES[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'HARTA' => 'success',
                        'KEWAJIBAN' => 'warning',
                        'MODAL' => 'info',
                        'PENDAPATAN' => 'success',
                        'BEBAN_ATAS_PENDAPATAN' => 'danger',
                        'BEBAN_OPERASIONAL' => 'danger',
                        'PENDAPATAN_LAIN' => 'success',
                        'BEBAN_LAIN' => 'danger',
                        default => 'gray'
                    }),

                TextColumn::make('parent.account_name')
                    ->label('Parent')
                    ->placeholder('Main Account'),

                TextColumn::make('level')
                    ->label('Level')
                    ->badge(),

                TextColumn::make('normal_balance')
                    ->label('Normal Balance')
                    ->badge()
                    ->color(fn ($state) => $state === 'debit' ? 'success' : 'warning'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->placeholder('Not deleted')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('dependencies')
                    ->label('Dependencies')
                    ->getStateUsing(function (ChartOfAccount $record): string {
                        $journalCount = DB::table('journal_entries')->where('account_id', $record->id)->count();
                        $childrenCount = ChartOfAccount::where('parent_id', $record->id)->count();

                        $dependencies = [];
                        if ($journalCount > 0) {
                            $dependencies[] = "{$journalCount} journals";
                        }
                        if ($childrenCount > 0) {
                            $dependencies[] = "{$childrenCount} children";
                        }

                        return empty($dependencies) ? '-' : implode(', ', $dependencies);
                    })
                    ->badge()
                    ->color(function (string $state): string {
                        return $state === '-' ? 'success' : 'warning';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('account_type')
                    ->label('Account Type')
                    ->options(ChartOfAccount::ACCOUNT_TYPES),

                SelectFilter::make('level')
                    ->label('Level')
                    ->options([
                        1 => 'Level 1 (Main)',
                        2 => 'Level 2 (Sub)',
                        3 => 'Level 3 (Detail)',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All accounts')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                TrashedFilter::make()
                    ->label('Deleted Status')
                    ->placeholder('Active accounts only')
                    ->trueLabel('With deleted accounts')
                    ->falseLabel('Deleted accounts only'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete Account')
                    ->modalDescription('This will move the account to trash. If this account has journal entries or child accounts, you can still restore it later.')
                    ->modalSubmitActionLabel('Move to Trash'),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->before(function (ForceDeleteAction $action, ChartOfAccount $record) {
                        $journalEntriesCount = DB::table('journal_entries')
                            ->where('account_id', $record->id)
                            ->count();

                        if ($journalEntriesCount > 0) {
                            Notification::make()
                                ->title('Cannot Force Delete Account')
                                ->body("This account has {$journalEntriesCount} journal entries. Please delete or reassign them first.")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }

                        $childrenCount = ChartOfAccount::where('parent_id', $record->id)->count();

                        if ($childrenCount > 0) {
                            Notification::make()
                                ->title('Cannot Force Delete Account')
                                ->body("This account has {$childrenCount} child accounts. Please delete or reassign them first.")
                                ->danger()
                                ->send();

                            $action->cancel();
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Force Delete Account')
                    ->modalDescription('Are you sure you want to permanently delete this account? This action cannot be undone.')
                    ->modalSubmitActionLabel('Yes, delete permanently'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->before(function (ForceDeleteBulkAction $action, Collection $records) {
                            $blockedAccounts = [];

                            foreach ($records as $record) {
                                $journalEntriesCount = DB::table('journal_entries')
                                    ->where('account_id', $record->id)
                                    ->count();

                                $childrenCount = ChartOfAccount::where('parent_id', $record->id)->count();

                                if ($journalEntriesCount > 0 || $childrenCount > 0) {
                                    $blockedAccounts[] = [
                                        'name' => $record->account_name,
                                        'code' => $record->account_code,
                                        'journal_entries' => $journalEntriesCount,
                                        'children' => $childrenCount,
                                    ];
                                }
                            }

                            if (! empty($blockedAccounts)) {
                                $message = "Cannot force delete the following accounts:\n\n";
                                foreach ($blockedAccounts as $account) {
                                    $reasons = [];
                                    if ($account['journal_entries'] > 0) {
                                        $reasons[] = "{$account['journal_entries']} journal entries";
                                    }
                                    if ($account['children'] > 0) {
                                        $reasons[] = "{$account['children']} child accounts";
                                    }
                                    $message .= "• {$account['code']} - {$account['name']} (".implode(', ', $reasons).")\n";
                                }
                                $message .= "\nPlease delete or reassign related records first.";

                                Notification::make()
                                    ->title('Cannot Force Delete Accounts')
                                    ->body($message)
                                    ->danger()
                                    ->send();

                                $action->cancel();
                            }
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Force Delete Accounts')
                        ->modalDescription('Are you sure you want to permanently delete these accounts? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, delete permanently'),
                ]),
            ])
            ->emptyStateDescription('Silakan buat akun baru untuk memulai.')
            ->emptyStateActions([
                Action::make('create')
                    ->label('Buat Akun Baru')
                    ->url(fn (): string => ChartOfAccountResource::getUrl('create'))
                    ->icon('heroicon-o-plus')
                    ->button(),
            ]);
    }
}
