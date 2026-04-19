<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount(['profiles', 'charts']);

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('filter')) {
            match ($request->input('filter')) {
                'blocked' => $query->where('is_blocked', true),
                'active' => $query->where('is_blocked', false),
                default => null,
            };
        }

        $users = $query->latest()->paginate(20);

        return response()->json($users);
    }

    public function show(int $id): JsonResponse
    {
        $user = User::with([
            'profiles',
            'charts' => fn($q) => $q->latest()->limit(10),
            'creditTransactions' => fn($q) => $q->latest()->limit(20),
        ])->findOrFail($id);

        return response()->json(['user' => $user]);
    }

    public function addCredits(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::findOrFail($id);
        $user->addCredits($validated['amount']);

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => $validated['amount'],
            'type' => 'credit',
            'description' => $validated['description'] ?? 'Начисление кредитов администратором',
        ]);

        return response()->json([
            'message' => 'Кредиты начислены.',
            'credits' => $user->fresh()->credits,
        ]);
    }

    public function block(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user = User::findOrFail($id);
        $user->update(['is_blocked' => true]);

        return response()->json(['message' => 'Пользователь заблокирован.']);
    }

    public function unblock(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $user->update(['is_blocked' => false]);

        return response()->json(['message' => 'Пользователь разблокирован.']);
    }
}
