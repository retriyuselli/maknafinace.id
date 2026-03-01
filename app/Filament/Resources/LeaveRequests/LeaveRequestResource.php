<?php

namespace App\Filament\Resources\LeaveRequests;

use App\Filament\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\EditLeaveRequest;
use App\Filament\Resources\LeaveRequests\Pages\ListLeaveRequests;
use App\Filament\Resources\LeaveRequests\Schemas\LeaveRequestForm;
use App\Filament\Resources\LeaveRequests\Tables\LeaveRequestsTable;
use App\Filament\Resources\LeaveRequests\Widgets\LeaveRequestChart;
use App\Filament\Resources\LeaveRequests\Widgets\LeaveRequestOverview;
use App\Filament\Resources\LeaveRequests\Widgets\LeaveTypeStats;
use App\Models\LeaveRequest;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Permohonan Cuti';

    protected static ?string $pluralModelLabel = 'Permohonan Cuti';

    protected static string|\UnitEnum|null $navigationGroup = 'Manajemen Cuti';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return LeaveRequestForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LeaveRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            LeaveRequestOverview::class,
            LeaveRequestChart::class,
            LeaveTypeStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeaveRequests::route('/'),
            'create' => CreateLeaveRequest::route('/create'),
            'edit' => EditLeaveRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'user:id,name',
                'leaveType:id,name',
                'replacementEmployee:id,name',
                'approver:id,name',
            ]);
    }
}
