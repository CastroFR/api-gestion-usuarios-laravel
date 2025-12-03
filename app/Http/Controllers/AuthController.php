<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Resumen general de estadísticas de usuarios.
     *
     * GET /api/statistics/summary
     *
     * Respuesta ejemplo:
     * {
     *   "success": true,
     *   "message": "Resumen de estadísticas de usuarios",
     *   "data": {
     *     "total_users": 100,
     *     "active_users": 95,
     *     "inactive_users": 5,
     *     "created_today": 3,
     *     "created_this_week": 10,
     *     "created_this_month": 25,
     *     "created_last_month": 20,
     *     "growth_vs_last_month": {
     *       "current": 25,
     *       "previous": 20,
     *       "percentage": 25.0,
     *       "direction": "up"
     *     }
     *   }
     * }
     */
    public function summary(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');

        // Cache por 10 minutos, separado por mes para evitar datos muy viejos
        $cacheKey = 'statistics.summary.' . Carbon::now($timezone)->format('Y-m');

        $summary = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($timezone) {
            $now = Carbon::now($timezone);

            $todayStart = $now->copy()->startOfDay();
            $todayEnd   = $now->copy()->endOfDay();

            $weekStart = $now->copy()->startOfWeek(); // lunes por defecto
            $monthStart = $now->copy()->startOfMonth();

            $lastMonthStart = $monthStart->copy()->subMonth();
            $lastMonthEnd   = $monthStart->copy()->subSecond(); // último instante del mes anterior

            // Convertir rangos a UTC para consultas (asumiendo timestamps en UTC)
            $todayStartUtc = $todayStart->copy()->timezone('UTC');
            $todayEndUtc   = $todayEnd->copy()->timezone('UTC');

            $weekStartUtc = $weekStart->copy()->timezone('UTC');
            $nowUtc       = $now->copy()->timezone('UTC');

            $monthStartUtc = $monthStart->copy()->timezone('UTC');

            $lastMonthStartUtc = $lastMonthStart->copy()->timezone('UTC');
            $lastMonthEndUtc   = $lastMonthEnd->copy()->timezone('UTC');

            // Totales con soft deletes incluidos
            $totalUsers = User::withTrashed()->count();

            // Activos = deleted_at NULL, Inactivos = deleted_at NOT NULL
            $activeUsers   = User::whereNull('deleted_at')->count();
            $inactiveUsers = User::whereNotNull('deleted_at')->count();

            // Usuarios creados por rangos
            $createdToday = User::whereBetween('created_at', [$todayStartUtc, $todayEndUtc])->count();

            $createdThisWeek = User::whereBetween('created_at', [$weekStartUtc, $nowUtc])->count();

            $createdThisMonth = User::whereBetween('created_at', [$monthStartUtc, $nowUtc])->count();

            $createdLastMonth = User::whereBetween('created_at', [$lastMonthStartUtc, $lastMonthEndUtc])->count();

            // Crecimiento vs mes anterior
            $growthDirection = 'equal';
            $growthPercentage = 0.0;

            if ($createdLastMonth > 0) {
                $difference = $createdThisMonth - $createdLastMonth;
                $growthPercentage = round(($difference / $createdLastMonth) * 100, 2);

                if ($growthPercentage > 0) {
                    $growthDirection = 'up';
                } elseif ($growthPercentage < 0) {
                    $growthDirection = 'down';
                }
            }

            return [
                'total_users'        => $totalUsers,
                'active_users'       => $activeUsers,
                'inactive_users'     => $inactiveUsers,
                'created_today'      => $createdToday,
                'created_this_week'  => $createdThisWeek,
                'created_this_month' => $createdThisMonth,
                'created_last_month' => $createdLastMonth,
                'growth_vs_last_month' => [
                    'current'    => $createdThisMonth,
                    'previous'   => $createdLastMonth,
                    'percentage' => $growthPercentage,
                    'direction'  => $growthDirection, // up | down | equal
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Resumen de estadísticas de usuarios',
            'data'    => $summary,
        ]);
    }

    /**
     * Estadísticas detalladas por rango de fechas.
     *
     * GET /api/statistics/detailed?from=YYYY-MM-DD&to=YYYY-MM-DD
     *
     * Requerimientos cubiertos:
     * - Estadísticas por rango de fechas personalizado
     * - Crecimiento comparativo (vs periodo anterior del mismo tamaño)
     * - Métricas adicionales (usuarios activos, inactivos)
     * - Agregaciones de base de datos (GROUP BY / COUNT)
     * - Cache de resultados
     * - Manejo de timezones
     *
     * Respuesta ejemplo:
     * {
     *   "success": true,
     *   "message": "Estadísticas detalladas de usuarios",
     *   "data": {
     *     "range": { "from": "2025-01-01", "to": "2025-01-31" },
     *     "active_users": 95,
     *     "inactive_users": 5,
     *     "per_day": [
     *       { "date": "2025-01-01", "created_count": 3, "cumulative_total": 3 },
     *       { "date": "2025-01-02", "created_count": 1, "cumulative_total": 4 }
     *     ],
     *     "growth_vs_previous_period": {
     *       "current": 30,
     *       "previous": 20,
     *       "percentage": 50.0,
     *       "direction": "up"
     *     },
     *     "previous_range": {
     *       "from": "2024-12-02",
     *       "to": "2024-12-31"
     *     }
     *   }
     * }
     */
    public function detailedStatistics(Request $request)
    {
        $timezone = config('app.timezone', 'UTC');

        // Validación de rangos de fecha
        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        $fromInput = $request->query('from');
        $toInput   = $request->query('to');

        // Rango en timezone de la app
        $from = Carbon::parse($fromInput, $timezone)->startOfDay();
        $to   = Carbon::parse($toInput, $timezone)->endOfDay();

        // Convertimos a UTC para consultas a la DB
        $fromUtc = $from->copy()->timezone('UTC');
        $toUtc   = $to->copy()->timezone('UTC');

        // Longitud del periodo (en días, incluyendo ambos extremos)
        $daysDiff = $from->diffInDays($to) + 1;

        // Rango anterior de igual longitud
        $previousTo   = $from->copy()->subDay()->endOfDay();
        $previousFrom = $previousTo->copy()->subDays($daysDiff - 1)->startOfDay();

        $previousFromUtc = $previousFrom->copy()->timezone('UTC');
        $previousToUtc   = $previousTo->copy()->timezone('UTC');

        // Cache específica por rango
        $cacheKey = sprintf(
            'statistics.detailed.%s_%s',
            $from->format('Y-m-d'),
            $to->format('Y-m-d')
        );

        $data = Cache::remember($cacheKey, now()->addMinutes(10), function () use (
            $from,
            $to,
            $fromUtc,
            $toUtc,
            $previousFrom,
            $previousTo,
            $previousFromUtc,
            $previousToUtc
        ) {
            // Métricas adicionales (usuarios activos / inactivos actuales)
            $activeUsers   = User::whereNull('deleted_at')->count();
            $inactiveUsers = User::whereNotNull('deleted_at')->count();

            // Usuarios creados por día en el rango actual (agregación en DB)
            $rows = User::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as created_count')
                )
                ->whereBetween('created_at', [$fromUtc, $toUtc])
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('date')
                ->get();

            // Indexar por fecha
            $indexByDate = $rows->keyBy('date');

            $perDay = [];
            $cumulative = 0;

            // Construir el rango día a día (incluyendo días sin registros)
            $cursor = $from->copy();
            while ($cursor->lessThanOrEqualTo($to)) {
                $dateStr = $cursor->toDateString();

                // IMPORTANTE:
                // DATE(created_at) devuelve fecha en la zona horaria de la DB (normalmente UTC).
                // Si la app está en otro timezone, esto puede producir pequeñas diferencias,
                // pero para una API académica / demo suele ser suficiente.
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

            // Conteo total del periodo actual
            $currentPeriodCount = array_sum(array_column($perDay, 'created_count'));

            // Conteo del periodo anterior
            $previousPeriodCount = User::whereBetween('created_at', [
                    $previousFromUtc,
                    $previousToUtc,
                ])
                ->count();

            // Crecimiento vs periodo anterior
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
                    'from' => $from->toDateString(),
                    'to'   => $to->toDateString(),
                ],
                'active_users'   => $activeUsers,
                'inactive_users' => $inactiveUsers,
                'per_day'        => $perDay,
                'growth_vs_previous_period' => [
                    'current'    => $currentPeriodCount,
                    'previous'   => $previousPeriodCount,
                    'percentage' => $growthPercentage,
                    'direction'  => $growthDirection,
                ],
                'previous_range' => [
                    'from' => $previousFrom->toDateString(),
                    'to'   => $previousTo->toDateString(),
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Estadísticas detalladas de usuarios',
            'data'    => $data,
        ]);
    }
}
