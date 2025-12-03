<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Estadísticas de usuarios por día
     */
    public function daily(Request $request)
    {
        $days = (int) $request->get('days', 30); // Últimos 30 días por defecto
        $timezone = config('app.timezone', 'UTC');

        $cacheKey = "statistics.daily.{$days}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($days, $timezone) {
            $from = Carbon::now($timezone)->subDays($days)->startOfDay();
            $fromUtc = $from->copy()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $fromUtc)
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date', 'desc')
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
     */
    public function weekly(Request $request)
    {
        $weeks = (int) $request->get('weeks', 12); // Últimas 12 semanas por defecto
        $timezone = config('app.timezone', 'UTC');

        $cacheKey = "statistics.weekly.{$weeks}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($weeks, $timezone) {
            $from = Carbon::now($timezone)->subWeeks($weeks)->startOfWeek();
            $fromUtc = $from->copy()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('WEEK(created_at, 1) as week'), // modo ISO
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $fromUtc)
                ->groupBy('year', 'week')
                ->orderBy('year', 'desc')
                ->orderBy('week', 'desc')
                ->get();

            // Formatear semana para mejor lectura
            $formattedStats = $statistics->map(function ($item) use ($timezone) {
                $date = Carbon::now($timezone)->setISODate($item->year, $item->week);
                return [
                    'year'   => $item->year,
                    'week'   => $item->week,
                    'period' => $date->copy()->startOfWeek()->format('d/m/Y') . ' - ' . $date->copy()->endOfWeek()->format('d/m/Y'),
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
     */
    public function monthly(Request $request)
    {
        $months = (int) $request->get('months', 12); // Últimos 12 meses por defecto
        $timezone = config('app.timezone', 'UTC');

        $cacheKey = "statistics.monthly.{$months}";

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($months, $timezone) {
            $from = Carbon::now($timezone)->subMonths($months)->startOfMonth();
            $fromUtc = $from->copy()->timezone('UTC');

            $statistics = User::select(
                    DB::raw('YEAR(created_at) as year'),
                    DB::raw('MONTH(created_at) as month'),
                    DB::raw('MONTHNAME(created_at) as month_name'),
                    DB::raw('COUNT(*) as total')
                )
                ->where('created_at', '>=', $fromUtc)
                ->groupBy('year', 'month', 'month_name')
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
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
     * - Total usuarios (incluye eliminados)
     * - Usuarios activos (deleted_at = NULL)
     * - Usuarios eliminados (soft deleted)
     * - Nuevos hoy / esta semana / este mes
     */
    public function summary()
    {
        $timezone = config('app.timezone', 'UTC');

        $cacheKey = 'statistics.summary.' . Carbon::now($timezone)->format('Y-m-d');

        $data = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($timezone) {
            $now = Carbon::now($timezone);

            $todayStart = $now->copy()->startOfDay();
            $weekStart  = $now->copy()->startOfWeek();
            $weekEnd    = $now->copy()->endOfWeek();
            $monthStart = $now->copy()->startOfMonth();

            // Convertimos los límites al timezone de la BD (asumimos UTC)
            $todayStartUtc = $todayStart->copy()->timezone('UTC');
            $todayEndUtc   = $todayStart->copy()->endOfDay()->timezone('UTC');

            $weekStartUtc  = $weekStart->copy()->timezone('UTC');
            $weekEndUtc    = $weekEnd->copy()->timezone('UTC');

            $monthStartUtc = $monthStart->copy()->timezone('UTC');
            $nowUtc        = $now->copy()->timezone('UTC');

            $today = User::whereBetween('created_at', [$todayStartUtc, $todayEndUtc])->count();

            $thisWeek = User::whereBetween('created_at', [$weekStartUtc, $weekEndUtc])->count();

            $thisMonth = User::whereBetween('created_at', [$monthStartUtc, $nowUtc])->count();

            // Totales considerando SoftDeletes
            $totals = User::withTrashed()
                ->selectRaw('
                    COUNT(*) as total,
                    SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as deleted
                ')
                ->first();

            return [
                'today'     => $today,
                'this_week' => $thisWeek,
                'this_month'=> $thisMonth,
                'total'     => (int) $totals->total,
                'active'    => (int) $totals->active,
                'deleted'   => (int) $totals->deleted,
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
     * - Estadísticas por rango de fechas personalizado (from, to)
     * - Crecimiento comparativo vs periodo anterior
     * - Métricas adicionales (usuarios activos, inactivos)
     *
     * GET /api/statistics/detailed?from=YYYY-MM-DD&to=YYYY-MM-DD
     */
    public function detailedStatistics(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');

        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $fromInput = $request->query('from');
        $toInput   = $request->query('to');

        // Rango en timezone de la app
        $from = Carbon::parse($fromInput, $timezone)->startOfDay();
        $to   = Carbon::parse($toInput, $timezone)->endOfDay();

        $cacheKey = sprintf(
            'statistics.detailed.%s_%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($from, $to, $timezone) {
            // Convertimos el rango actual a UTC para la consulta
            $fromUtc = $from->copy()->timezone('UTC');
            $toUtc   = $to->copy()->timezone('UTC');

            // Rango anterior (misma cantidad de días)
            $daysDiff = $from->diffInDays($to) + 1; // incluye ambos extremos

            $previousTo   = $from->copy()->subDay()->endOfDay();
            $previousFrom = $previousTo->copy()->subDays($daysDiff - 1)->startOfDay();

            $previousFromUtc = $previousFrom->copy()->timezone('UTC');
            $previousToUtc   = $previousTo->copy()->timezone('UTC');

            // Usuarios activos / inactivos globales
            $activeUsers   = User::whereNull('deleted_at')->count();
            $inactiveUsers = User::onlyTrashed()->count();

            // Usuarios creados por día en el rango actual (agregación)
            $rows = User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as created_count')
                )
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            $indexByDate = $rows->keyBy('date');

            $perDay = [];
            $cumulative = 0;

            $cursor = $from->copy();
            while ($cursor->lessThanOrEqualTo($to)) {
                $dateStr = $cursor->toDateString(); // en timezone app

                // Ojo: DATE(created_at) se calcula en la BD (usualmente UTC).
                // Para la mayoría de casos prácticos, esto es suficiente. Si quisieras
                // ser ultra preciso, podrías usar CONVERT_TZ en la consulta SQL.
                $row = $indexByDate->get($dateStr);
                $createdCount = $row ? (int) $row->created_count : 0;

                $cumulative += $createdCount;

                $perDay[] = [
                    'date'             => $dateStr,
                    'created_count'    => $createdCount,
                    'cumulative_total' => $cumulative,
                ];

                $cursor->addDay();
            }

            // Totales del periodo actual
            $currentPeriodTotal = array_sum(array_column($perDay, 'created_count'));

            // Totales del periodo anterior (misma longitud)
            $previousPeriodTotal = User::whereBetween('created_at', [
                    $previousFromUtc,
                    $previousToUtc,
                ])
                ->count();

            // Crecimiento comparativo
            $growthDirection = 'equal';
            $growthPercentage = 0.0;

            if ($previousPeriodTotal > 0) {
                $difference = $currentPeriodTotal - $previousPeriodTotal;
                $growthPercentage = round(($difference / $previousPeriodTotal) * 100, 2);

                if ($growthPercentage > 0) {
                    $growthDirection = 'up';
                } elseif ($growthPercentage < 0) {
                    $growthDirection = 'down';
                }
            }

            return [
                'range' => [
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
                'previous_range' => [
                    'from' => $previousFrom->toDateString(),
                    'to'   => $previousTo->toDateString(),
                ],
                'active_users'   => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'statistics'     => $perDay,
                'current_period_total'  => $currentPeriodTotal,
                'previous_period_total' => $previousPeriodTotal,
                'growth_vs_previous_period' => [
                    'current'    => $currentPeriodTotal,
                    'previous'   => $previousPeriodTotal,
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
}
