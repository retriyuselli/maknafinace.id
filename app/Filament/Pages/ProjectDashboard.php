<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ComingSoonAkadWidget;
use App\Filament\Widgets\ComingSoonResepsiWidget;
use App\Filament\Widgets\DashboardKeuangan;
use Filament\Pages\Page;
use Filament\Pages\Dashboard as BaseDashboard;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use App\Filament\Widgets\StatsOverviewWidget;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Widgets\AccountWidget;

class ProjectDashboard extends Page
{
    use BaseDashboard\Concerns\HasFiltersForm;
    
    protected static ?string $title = 'Project Dashboard';

    protected ?string $heading = 'Project Dashboard';
    
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedBriefcase;
    
    // protected string $view = 'filament.pages.project-dashboard';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        DatePicker::make('startDate')
                            ->default(now()->startOfMonth()->toDateString())
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->default(now()->endOfMonth()->toDateString())
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            AccountWidget::class,
            DashboardKeuangan::class,
            StatsOverviewWidget::class,
            ComingSoonAkadWidget::class,
            ComingSoonResepsiWidget::class,
            // EventManager::class,
            // AccountManagerWidget::class,
        ];
    }
}