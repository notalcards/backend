<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withCount(['profiles', 'charts']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(fn($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        if ($request->filled('filter')) {
            match ($request->input('filter')) {
                'blocked' => $query->where('is_blocked', true),
                'active'  => $query->where('is_blocked', false),
                default   => null,
            };
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function show(int $id)
    {
        $user = User::with([
            'profiles',
            'charts' => fn($q) => $q->latest()->limit(10),
            'creditTransactions' => fn($q) => $q->latest()->limit(20),
        ])->findOrFail($id);

        return view('admin.users.show', compact('user'));
    }

    public function addCredits(Request $request, int $id)
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

        return back()->with('success', "Начислено {$validated['amount']} кредитов.");
    }

    public function block(Request $request, int $id)
    {
        User::findOrFail($id)->update(['is_blocked' => true]);
        return back()->with('success', 'Пользователь заблокирован.');
    }

    public function unblock(int $id)
    {
        User::findOrFail($id)->update(['is_blocked' => false]);
        return back()->with('success', 'Пользователь разблокирован.');
    }
}
