<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Models\ChartPrecalculation;
use App\Models\CreditTransaction;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'precalc_token' => ['nullable', 'string'],
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => $validated['password'],
            'credits'  => 200,
        ]);

        CreditTransaction::create([
            'user_id'     => $user->id,
            'amount'      => 200,
            'type'        => 'credit',
            'description' => 'Бонус за регистрацию',
        ]);

        // Attach pre-calculated chart if token provided
        if (!empty($validated['precalc_token'])) {
            $precalc = ChartPrecalculation::where('token', $validated['precalc_token'])
                ->where('expires_at', '>', now())
                ->first();

            if ($precalc && $precalc->result_data) {
                $birthData = $precalc->birth_data;

                $profile = Profile::create([
                    'user_id'     => $user->id,
                    'name'        => $validated['name'],
                    'birth_date'  => $birthData['birth_date'],
                    'birth_time'  => $birthData['birth_time'] ?? null,
                    'birth_place' => $birthData['birth_place'] ?? 'Не указано',
                    'lat'         => $birthData['lat'] ?? null,
                    'lng'         => $birthData['lng'] ?? null,
                ]);

                if ($user->hasEnoughCredits(100)) {
                    Chart::create([
                        'user_id'        => $user->id,
                        'profile_id'     => $profile->id,
                        'type'           => 'natal',
                        'input_data'     => ['profile' => $profile->toArray()],
                        'result_data'    => $precalc->result_data,
                        'interpretation' => $precalc->interpretation,
                        'credits_spent'  => 100,
                    ]);

                    $user->deductCredits(100);

                    CreditTransaction::create([
                        'user_id'     => $user->id,
                        'amount'      => 100,
                        'type'        => 'debit',
                        'description' => 'Расчёт карты: natal',
                    ]);
                }

                $precalc->delete();
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Неверный email или пароль.'],
            ]);
        }

        if ($user->is_blocked) {
            return response()->json(['message' => 'Ваш аккаунт заблокирован.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Выход выполнен успешно.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('profiles');

        return response()->json($user);
    }
}
