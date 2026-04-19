<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $now = now();

        $stats = [
            'users' => [
                'total' => User::count(),
                'new_today' => User::whereDate('created_at', $now->toDateString())->count(),
                'new_week' => User::where('created_at', '>=', $now->copy()->subWeek())->count(),
                'new_month' => User::where('created_at', '>=', $now->copy()->subMonth())->count(),
                'active_last_7_days' => User::where('updated_at', '>=', $now->copy()->subDays(7))->count(),
            ],
            'charts' => [
                'by_type' => Chart::select('type', DB::raw('count(*) as total'))
                    ->groupBy('type')
                    ->pluck('total', 'type'),
                'total' => Chart::count(),
            ],
        ];

        return response()->json($stats);
    }
}
