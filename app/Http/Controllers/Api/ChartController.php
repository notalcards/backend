<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chart;
use App\Models\CreditTransaction;
use App\Models\Profile;
use App\Services\AstrologyApiService;
use App\Services\ClaudeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    public function __construct(
        private AstrologyApiService $astrologyApi,
        private ClaudeService $claude
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->charts()->with('profile')->latest();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $charts = $query->paginate(15);

        return response()->json($charts);
    }

    public function show(Request $request, Chart $chart): JsonResponse
    {
        if ($chart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        $chart->load('profile');

        return response()->json(['chart' => $chart]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:natal,solar,transit,monthly,progressions,venus,synastry'],
            'profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'params' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $profile = Profile::find($validated['profile_id']);
        if ($profile->user_id !== $user->id) {
            return response()->json(['message' => 'Нет доступа к профилю.'], 403);
        }

        if (!$user->hasEnoughCredits(100)) {
            return response()->json(['message' => 'Недостаточно кредитов.'], 402);
        }

        $inputData = array_merge([
            'profile' => $profile->toArray(),
        ], $validated['params'] ?? []);

        $type = $validated['type'];

        $resultData = match ($type) {
            'natal' => $this->astrologyApi->natal($inputData),
            'solar' => $this->astrologyApi->solar($inputData),
            'transit' => $this->astrologyApi->transit($inputData),
            'monthly' => $this->astrologyApi->monthly($inputData),
            'progressions' => $this->astrologyApi->progressions($inputData),
            'venus' => $this->astrologyApi->venus($inputData),
            default => $this->astrologyApi->natal($inputData),
        };

        try {
            $interpretation = $this->claude->interpret($type, $resultData, $profile->toArray());
        } catch (\Throwable $e) {
            $interpretation = '';
        }

        $user->deductCredits(100);

        CreditTransaction::create([
            'user_id' => $user->id,
            'amount' => 100,
            'type' => 'debit',
            'description' => "Расчёт карты: {$type}",
        ]);

        $chart = Chart::create([
            'user_id' => $user->id,
            'profile_id' => $profile->id,
            'type' => $type,
            'input_data' => $inputData,
            'result_data' => $resultData,
            'interpretation' => $interpretation,
            'credits_spent' => 100,
        ]);

        return response()->json(['chart' => $chart], 201);
    }

    public function destroy(Request $request, Chart $chart): JsonResponse
    {
        if ($chart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Нет доступа.'], 403);
        }

        $chart->delete();

        return response()->json(['message' => 'Карта удалена.']);
    }
}
