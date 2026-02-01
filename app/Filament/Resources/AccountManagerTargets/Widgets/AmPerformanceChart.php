<?php

namespace App\Filament\Resources\AccountManagerTargets\Widgets;

use App\Models\AccountManagerTarget;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AmPerformanceChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $maxHeight = '500px';

    protected int|string|array $columnSpan = 'full';

    public function mount(): void
    {
        $this->filter = (string) now()->year;
    }

    protected function getFilters(): ?array
    {
        $currentYear = now()->year;
        $options = [];
        
        // Generate options for recent years
        for ($year = $currentYear - 1; $year <= $currentYear + 1; $year++) {
            $options[$year] = $year;
        }
        
        return $options;
    }

    public function getHeading(): ?string
    {
        $user = Auth::user();
        
        $selectedYear = (int) ($this->filter ?? now()->year);
        $selectedUserId = 'all';

        // Check permissions
        $isSuperAdmin = $user && $user->roles->where('name', 'super_admin')->count() > 0;
        
        if (! $isSuperAdmin && $user && $user->roles->where('name', 'Account Manager')->count() > 0) {
            $selectedUserId = $user->id;
        }

        $query = AccountManagerTarget::query()
            ->where('year', $selectedYear);

        $name = 'All Account Managers';

        if ($selectedUserId !== 'all') {
            $query->where('user_id', $selectedUserId);
            $am = User::find($selectedUserId);
            if ($am) {
                $name = $am->name;
            }
        } else {
            $query->whereHas('user', function ($q) {
                $q->whereNull('last_working_date')
                    ->orWhereRaw("(account_manager_targets.year * 100 + account_manager_targets.month) <= (YEAR(last_working_date) * 100 + MONTH(last_working_date))");
            });
        }

        $totalAchievement = $query->sum('achieved_amount');
        
        return "Performance Trend - {$name} (Total Achievement: Rp " . number_format($totalAchievement, 0, ',', '.') . ")";
    }

    protected function getData(): array
    {
        $user = Auth::user();
        
        $selectedYear = (int) ($this->filter ?? now()->year);
        $selectedUserId = 'all';

        // Check permissions: if user is not super_admin, force their own data if they are AM
        $isSuperAdmin = $user && $user->roles->where('name', 'super_admin')->count() > 0;
        
        // If regular AM, force selection to themselves
        if (! $isSuperAdmin && $user && $user->roles->where('name', 'Account Manager')->count() > 0) {
            $selectedUserId = $user->id;
        }

        // Initialize months labels
        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[] = Carbon::create()->month($i)->format('M');
        }

        // If specific user is selected, show Target vs Achievement for that user
        if ($selectedUserId !== 'all') {
            $query = AccountManagerTarget::query()
                ->where('year', $selectedYear)
                ->where('user_id', $selectedUserId);

            $monthlyData = $query->selectRaw('month, SUM(target_amount) as total_target, SUM(achieved_amount) as total_achieved')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $targets = array_fill(0, 12, 0);
            $achievements = array_fill(0, 12, 0);

            foreach ($monthlyData as $data) {
                $monthIndex = $data->month - 1;
                $targets[$monthIndex] = $data->total_target / 1000000; // Convert to millions
                $achievements[$monthIndex] = $data->total_achieved / 1000000;
            }

            return [
                'datasets' => [
                    [
                        'label' => 'Target (Juta Rp)',
                        'data' => $targets,
                        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                        'borderColor' => 'rgb(59, 130, 246)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                    [
                        'label' => 'Achievement (Juta Rp)',
                        'data' => $achievements,
                        'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                        'borderColor' => 'rgb(34, 197, 94)',
                        'borderWidth' => 2,
                        'fill' => true,
                    ],
                ],
                'labels' => $months,
            ];
        }

        // Logic for ALL users: Show Total Target + Individual AM Achievements
        
        // 1. Get Total Target (Sum of all active AMs)
        $targetQuery = AccountManagerTarget::query()
            ->where('year', $selectedYear)
            ->whereHas('user', function ($q) {
                $q->whereNull('last_working_date')
                    ->orWhereRaw("(account_manager_targets.year * 100 + account_manager_targets.month) <= (YEAR(last_working_date) * 100 + MONTH(last_working_date))");
            });

        $monthlyTarget = $targetQuery->selectRaw('month, SUM(target_amount) as total_target')
            ->groupBy('month')
            ->pluck('total_target', 'month')
            ->all();

        $datasets = [];
        
        // Add Total Target Dataset
        $targetsData = [];
        for ($i = 1; $i <= 12; $i++) {
            $targetsData[] = ($monthlyTarget[$i] ?? 0) / 1000000;
        }
        
        $datasets[] = [
            'label' => 'Total Target (Juta Rp)',
            'data' => $targetsData,
            'borderColor' => '#9ca3af', // Gray
            'borderWidth' => 2,
            'borderDash' => [5, 5],
            'fill' => false,
            'pointRadius' => 0,
        ];

        // 2. Get Achievement per User
        $userAchievements = AccountManagerTarget::with('user')
            ->where('year', $selectedYear)
            ->whereHas('user', function ($q) {
                $q->whereNull('last_working_date')
                    ->orWhereRaw("(account_manager_targets.year * 100 + account_manager_targets.month) <= (YEAR(last_working_date) * 100 + MONTH(last_working_date))");
            })
            ->get()
            ->groupBy('user_id');

        $colors = [
            '#3b82f6', // Blue
            '#ef4444', // Red
            '#10b981', // Emerald
            '#f59e0b', // Amber
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#06b6d4', // Cyan
            '#f97316', // Orange
            '#6366f1', // Indigo
            '#84cc16', // Lime
        ];
        $colorIndex = 0;

        foreach ($userAchievements as $userId => $records) {
            $userName = $records->first()->user->name ?? 'Unknown';
            $achievementsData = array_fill(0, 12, 0);
            
            foreach ($records as $record) {
                $monthIndex = $record->month - 1;
                $achievementsData[$monthIndex] = $record->achieved_amount / 1000000;
            }

            $color = $colors[$colorIndex % count($colors)];
            $colorIndex++;

            $datasets[] = [
                'label' => $userName,
                'data' => $achievementsData,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'borderWidth' => 2,
                'fill' => false,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Amount (Millions)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Month',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'maintainAspectRatio' => true,
            'aspectRatio' => 2,
        ];
    }
}