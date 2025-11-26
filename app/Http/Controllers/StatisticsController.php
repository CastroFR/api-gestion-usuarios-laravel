<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatisticsController extends Controller
{
    /**
     * Estadísticas de usuarios por DÍA
     */
    public function dailyStatistics()
    {
        try {
            $today = Carbon::today();
            $usersToday = User::whereDate('created_at', $today)->count();

            return response()->json([
                'message' => 'Estadísticas diarias obtenidas',
                'period' => 'day',
                'date' => $today->toDateString(),
                'users_count' => $usersToday,
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al obtener estadísticas diarias',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de usuarios por SEMANA
     */
    public function weeklyStatistics()
    {
        try {
            $startOfWeek = Carbon::now()->startOfWeek();
            $endOfWeek = Carbon::now()->endOfWeek();

            $usersThisWeek = User::whereBetween('created_at', [$startOfWeek, $endOfWeek])->count();

            return response()->json([
                'message' => 'Estadísticas semanales obtenidas',
                'period' => 'week',
                'week_start' => $startOfWeek->toDateString(),
                'week_end' => $endOfWeek->toDateString(),
                'users_count' => $usersThisWeek,
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al obtener estadísticas semanales',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de usuarios por MES
     */
    public function monthlyStatistics()
    {
        try {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $usersThisMonth = User::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

            return response()->json([
                'message' => 'Estadísticas mensuales obtenidas',
                'period' => 'month',
                'month' => $startOfMonth->format('F Y'),
                'month_start' => $startOfMonth->toDateString(),
                'month_end' => $endOfMonth->toDateString(),
                'users_count' => $usersThisMonth,
                'status' => 200
            ], 200);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error al obtener estadísticas mensuales',
                'error' => $error->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas detalladas - PARA MEJORAR
     * (Persona 5 puede expandir esta funcionalidad)
     */
    public function detailedStatistics(Request $request)
    {
        try {
            // TODO: Implementar estadísticas más detalladas
            // Ejemplo: usuarios por rango de fechas, crecimiento, etc.

            return response()->json([
                'message' => 'Endpoint de estadísticas detalladas - Por expandir',
                'suggestions' => [
                    'Filtrar por rango de fechas',
                    'Agregar comparativas con periodo anterior',
                    'Incluir gráficos de crecimiento'
                ],
                'status' => 501
            ], 501);
        } catch (\Exception $error) {
            return response()->json([
                'message' => 'Error en estadísticas detalladas',
                'error' => $error->getMessage()
            ], 500);
        }
    }
}
