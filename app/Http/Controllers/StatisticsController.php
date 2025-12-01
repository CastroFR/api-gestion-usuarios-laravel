<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Estadísticas de usuarios por día
     */
    public function daily(Request $request)
    {
        $days = $request->get('days', 30); // Últimos 30 días por defecto

        $statistics = User::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => 'daily',
                'days' => $days,
                'statistics' => $statistics,
                'total_users' => User::count()
            ]
        ]);
    }

    /**
     * Estadísticas de usuarios por semana
     */
    public function weekly(Request $request)
    {
        $weeks = $request->get('weeks', 12); // Últimas 12 semanas por defecto

        $statistics = User::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('WEEK(created_at) as week'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subWeeks($weeks))
            ->groupBy('year', 'week')
            ->orderBy('year', 'desc')
            ->orderBy('week', 'desc')
            ->get();

        // Formatear semana para mejor lectura
        $formattedStats = $statistics->map(function ($item) {
            $date = Carbon::now()->setISODate($item->year, $item->week);
            return [
                'year' => $item->year,
                'week' => $item->week,
                'period' => $date->startOfWeek()->format('d/m/Y') . ' - ' . $date->endOfWeek()->format('d/m/Y'),
                'total' => $item->total
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'period' => 'weekly',
                'weeks' => $weeks,
                'statistics' => $formattedStats,
                'total_users' => User::count()
            ]
        ]);
    }

    /**
     * Estadísticas de usuarios por mes
     */
    public function monthly(Request $request)
    {
        $months = $request->get('months', 12); // Últimos 12 meses por defecto

        $statistics = User::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('MONTHNAME(created_at) as month_name'),
                DB::raw('COUNT(*) as total')
            )
            ->where('created_at', '>=', Carbon::now()->subMonths($months))
            ->groupBy('year', 'month', 'month_name')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => 'monthly',
                'months' => $months,
                'statistics' => $statistics,
                'total_users' => User::count()
            ]
        ]);
    }

    /**
     * Resumen general de estadísticas
     */
    public function summary()
    {
        $today = User::whereDate('created_at', Carbon::today())->count();
        $thisWeek = User::whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ])->count();
        $thisMonth = User::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $today,
                'this_week' => $thisWeek,
                'this_month' => $thisMonth,
                'total' => User::count(),
                'active' => User::count(),
                'deleted' => User::onlyTrashed()->count()
            ]
        ]);
    }
}
