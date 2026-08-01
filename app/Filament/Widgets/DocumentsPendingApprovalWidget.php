<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;
use Filament\Actions\Action;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class DocumentsPendingApprovalWidget extends BaseWidget
{
    use HasWidgetShield {
        canView as canViewShield;
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Dokumen Menunggu Persetujuan';

    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        // Cek permission dari Shield terlebih dahulu
        if (! static::canViewShield()) {
            return false;
        }

        // Cek apakah ada data yang perlu ditampilkan
        return Document::query()
            ->whereHas('approvals', function (Builder $q) {
                $q->where('user_id', Auth::id())
                    ->where('status', 'pending');
            })
            ->exists();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->whereHas('approvals', function (Builder $q) {
                        $q->where('user_id', Auth::id())
                            ->where('status', 'pending');
                    })
                    ->with(['category', 'creator'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Number'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title'),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category'),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime(),
            ])
            ->recordActions([
                Action::make('review')
                    ->label('Review')
                    ->authorize('update')
                    ->visible(function (): bool {
                        /** @var \App\Models\User|null $user */
                        $user = Auth::user();
                        return $user?->hasRole('super_admin') ?? false;
                    })
                    ->url(fn (Document $record): string => route('filament.admin.resources.documents.edit', ['record' => $record->id]))
                    ->icon('heroicon-m-eye'),
                Action::make('print')
                    ->label('Preview PDF')
                    ->authorize('view')
                    ->icon('heroicon-m-printer')
                    ->url(fn (Document $record) => route('document.stream', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
