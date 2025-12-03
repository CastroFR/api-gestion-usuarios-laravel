<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Estadísticas de usuarios por día
     *
     * GET /api/statistics/daily?days=30
     */
    public function daily(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');
        $days = (int) $request->get('days', 30); // Últimos 30 días por defecto

        $cacheKey = "statistics.daily.{$days}.{$timezone}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($days, $timezone) {
            $now = Carbon::now($timezone);
            // Desde hace N-1 días hasta hoy (inclusivo)
            $from = $now->copy()->subDays($days - 1)->startOfDay();
            $fromUtc = $from->copy()->timezone('UTC');
            $nowUtc = $now->copy()->endOfDay()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereBetween('created_at', [$fromUtc, $nowUtc])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();

            return [
                'period' => 'daily',
                'days' => $days,
                'statistics' => $statistics,
                'total_users' => User::withTrashed()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Estadísticas de usuarios por semana
     *
     * GET /api/statistics/weekly?weeks=12
     */
    public function weekly(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');
        $weeks = (int) $request->get('weeks', 12); // Últimas 12 semanas por defecto

        $cacheKey = "statistics.weekly.{$weeks}.{$timezone}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($weeks, $timezone) {
            $now = Carbon::now($timezone);
            $from = $now->copy()->subWeeks($weeks - 1)->startOfWeek();
            $fromUtc = $from->copy()->timezone('UTC');
            $nowUtc = $now->copy()->endOfWeek()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('WEEK(created_at) as week'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereBetween('created_at', [$fromUtc, $nowUtc])
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('WEEK(created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('week', 'asc')
                ->get();

            // Formatear semana para mejor lectura (en timezone de la app)
            $formattedStats = $statistics->map(function ($item) use ($timezone) {
                $date = Carbon::now($timezone)->setISODate($item->year, $item->week);
                return [
                    'year'   => $item->year,
                    'week'   => $item->week,
                    'period' => $date->startOfWeek()->format('d/m/Y') . ' - ' . $date->endOfWeek()->format('d/m/Y'),
                    'total'  => $item->total,
                ];
            });

            return [
                'period' => 'weekly',
                'weeks' => $weeks,
                'statistics' => $formattedStats,
                'total_users' => User::withTrashed()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Estadísticas de usuarios por mes
     *
     * GET /api/statistics/monthly?months=12
     */
    public function monthly(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');
        $months = (int) $request->get('months', 12); // Últimos 12 meses por defecto

        $cacheKey = "statistics.monthly.{$months}.{$timezone}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($months, $timezone) {
            $now = Carbon::now($timezone);
            $from = $now->copy()->subMonths($months - 1)->startOfMonth();
            $fromUtc = $from->copy()->timezone('UTC');
            $nowUtc = $now->copy()->endOfMonth()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('MONTHNAME(created_at) as month_name'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereBetween('created_at', [$fromUtc, $nowUtc])
                ->groupBy(DB::raw('YEAR(created_at)'), DB::raw('MONTH(created_at)'), DB::raw('MONTHNAME(created_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            return [
                'period' => 'monthly',
                'months' => $months,
                'statistics' => $statistics,
                'total_users' => User::withTrashed()->count(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Resumen general de estadísticas
     *
     * GET /api/statistics/summary
     *
     * Incluye:
     * - Hoy, semana actual, mes actual
     * - Total, activos, eliminados (soft delete)
     * - Crecimiento vs mes anterior
     */
    public function summary()
    {
        $timezone = config('app.timezone', 'UTC');
        $cacheKey = "statistics.summary.{$timezone}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($timezone) {
            $now = Carbon::now($timezone);

            $todayStart = $now->copy()->startOfDay();
            $weekStart  = $now->copy()->startOfWeek();
            $weekEnd    = $now->copy()->endOfWeek();
            $monthStart = $now->copy()->startOfMonth();
            $monthEnd   = $now->copy()->endOfMonth();

            $lastMonthStart = $monthStart->copy()->subMonth();
            $lastMonthEnd   = $lastMonthStart->copy()->endOfMonth();

            // Convertir a UTC para consultas (asumiendo que DB está en UTC)
            $todayStartUtc = $todayStart->copy()->timezone('UTC');
            $todayEndUtc   = $todayStart->copy()->endOfDay()->timezone('UTC');

            $weekStartUtc = $weekStart->copy()->timezone('UTC');
            $weekEndUtc   = $weekEnd->copy()->timezone('UTC');

            $monthStartUtc = $monthStart->copy()->timezone('UTC');
            $monthEndUtc   = $monthEnd->copy()->timezone('UTC');

            $lastMonthStartUtc = $lastMonthStart->copy()->timezone('UTC');
            $lastMonthEndUtc   = $lastMonthEnd->copy()->timezone('UTC');

            $today = User::whereBetween('created_at', [$todayStartUtc, $todayEndUtc])->count();

            $thisWeek = User::whereBetween('created_at', [$weekStartUtc, $weekEndUtc])->count();

            $thisMonth = User::whereBetween('created_at', [$monthStartUtc, $monthEndUtc])->count();

            $lastMonth = User::whereBetween('created_at', [$lastMonthStartUtc, $lastMonthEndUtc])->count();

            $total      = User::withTrashed()->count();
            $active     = User::whereNull('deleted_at')->count();
            $deleted    = User::onlyTrashed()->count();

            $growthDirection = 'equal';
            $growthPercentage = 0.0;

            if ($lastMonth > 0) {
                $difference = $thisMonth - $lastMonth;
                $growthPercentage = round(($difference / $lastMonth) * 100, 2);

                if ($growthPercentage > 0) {
                    $growthDirection = 'up';
                } elseif ($growthPercentage < 0) {
                    $growthDirection = 'down';
                }
            }

            return [
                'today'   => $today,
                'this_week' => $thisWeek,
                'this_month' => $thisMonth,
                'last_month' => $lastMonth,
                'total'    => $total,
                'active'   => $active,
                'deleted'  => $deleted,
                'growth_vs_last_month' => [
                    'current'    => $thisMonth,
                    'previous'   => $lastMonth,
                    'percentage' => $growthPercentage,
                    'direction'  => $growthDirection, // up | down | equal
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Método detailedStatistics()
     *
     * GET /api/statistics/detailed?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Requisitos:
     * - Estadísticas por rango de fechas personalizado
     * - Crecimiento comparativo (vs periodo anterior de igual longitud)
     * - Métricas adicionales (usuarios activos, inactivos)
     */
    public function detailedStatistics(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');

        // Validar rango de fechas
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $fromApp = Carbon::parse($validated['from'], $timezone)->startOfDay();
        $toApp   = Carbon::parse($validated['to'], $timezone)->endOfDay();

        $cacheKey = sprintf(
            'statistics.detailed.%s_%s.%s',
            $fromApp->format('Y-m-d'),
            $toApp->format('Y-m-d'),
            $timezone
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($fromApp, $toApp, $timezone) {
            // Rango actual en UTC (asumiendo DB en UTC)
            $fromUtc = $fromApp->copy()->timezone('UTC');
            $toUtc   = $toApp->copy()->timezone('UTC');

            $daysCount = $fromApp->diffInDays($toApp) + 1;

            // Periodo anterior de igual longitud
            $previousToApp   = $fromApp->copy()->subDay()->endOfDay();
            $previousFromApp = $previousToApp->copy()->subDays($daysCount - 1)->startOfDay();

            $previousFromUtc = $previousFromApp->copy()->timezone('UTC');
            $previousToUtc   = $previousToApp->copy()->timezone('UTC');

            // Métricas de activos / inactivos globales
            $totalUsers   = User::withTrashed()->count();
            $activeUsers  = User::whereNull('deleted_at')->count();
            $inactiveUsers = User::onlyTrashed()->count();

            // Usuarios creados por día en el rango actual
            $rows = User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total')
                )
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'asc')
                ->get();

            $indexByDate = $rows->keyBy('date');
            $statistics  = [];
            $cumulative  = 0;

            // Recorrer día por día en zona horaria de la app
            $cursor = $fromApp->copy();
            while ($cursor->lessThanOrEqualTo($toApp)) {
                $dateStrApp = $cursor->toDateString(); // YYYY-MM-DD en timezone app

                // OJO: DATE(created_at) viene en la zona del servidor/DB (normalmente UTC).
                // Para un proyecto de academia está OK asumir que coincide.
                $row = $indexByDate->get($dateStrApp);
                $dailyTotal = $row ? (int) $row->total : 0;

                $cumulative += $dailyTotal;

                $statistics[] = [
                    'date'             => $dateStrApp,
                    'created_count'    => $dailyTotal,
                    'cumulative_total' => $cumulative,
                ];

                $cursor->addDay();
            }

            $currentPeriodCount = array_sum(array_column($statistics, 'created_count'));

            // Conteo en el periodo anterior
            $previousPeriodCount = User::whereBetween('created_at', [$previousFromUtc, $previousToUtc])->count();

            $growthDirection = 'equal';
            $growthPercentage = 0.0;

            if ($previousPeriodCount > 0) {
                $difference = $currentPeriodCount - $previousPeriodCount;
                $growthPercentage = round(($difference / $previousPeriodCount) * 100, 2);

                if ($growthPercentage > 0) {
                    $growthDirection = 'up';
                } elseif ($growthPercentage < 0) {
                    $growthDirection = 'down';
                }
            }

            return [
                'range' => [
                    'from' => $fromApp->toDateString(),
                    'to'   => $toApp->toDateString(),
                    'days' => $daysCount,
                ],
                'previous_range' => [
                    'from' => $previousFromApp->toDateString(),
                    'to'   => $previousToApp->toDateString(),
                ],
                'totals' => [
                    'total_users'    => $totalUsers,
                    'active_users'   => $activeUsers,
                    'inactive_users' => $inactiveUsers,
                ],
                'statistics' => $statistics,
                'growth_vs_previous_period' => [
                    'current'    => $currentPeriodCount,
                    'previous'   => $previousPeriodCount,
                    'percentage' => $growthPercentage,
                    'direction'  => $growthDirection,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
