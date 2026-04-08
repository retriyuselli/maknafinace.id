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
use Illuminate\Support\Facades\Cache;

class DocumentInboxWidget extends BaseWidget
{
    use HasWidgetShield {
        canView as canViewShield;
    }

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Inbox Dokumen';

    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        if (! static::canViewShield()) {
            return false;
        }

        $userId = Auth::id();
        if (! $userId) {
            return false;
        }

        return Cache::remember(
            'dashboard:document_inbox:exists:'.$userId,
            60,
            fn () => Document::query()
                ->where('status', '!=', 'draft')
                ->whereHas('recipientsList', fn (Builder $q) => $q->where('users.id', $userId))
                ->exists()
        );
    }

    public function table(Table $table): Table
    {
        $userId = Auth::id();

        return $table
            ->defaultPaginationPageOption(10)
            ->query(
                Document::query()
                    ->where('status', '!=', 'draft')
                    ->whereHas('recipientsList', fn (Builder $q) => $q->where('users.id', $userId))
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
                Action::make('print')
                    ->label('Preview PDF')
                    ->authorize('view')
                    ->icon('heroicon-m-printer')
                    ->url(fn (Document $record) => route('document.stream', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
