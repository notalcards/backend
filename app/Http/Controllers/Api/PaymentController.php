<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use YooKassa\Client;

class PaymentController extends Controller
{
    private static array $tariffs = [
        'start' => [
            'name' => 'Старт',
            'credits' => 500,
            'amount' => 299,
            'description' => '500 кредитов',
        ],
        'base' => [
            'name' => 'Базовый',
            'credits' => 1500,
            'amount' => 699,
            'description' => '1500 кредитов',
        ],
        'pro' => [
            'name' => 'Профи',
            'credits' => 3000,
            'amount' => 990,
            'description' => '3000 кредитов',
        ],
    ];

    public function tariffs(): JsonResponse
    {
        return response()->json(self::$tariffs);
    }

    public function create(Request $request): JsonResponse
    {
        $request->validate(['tariff' => 'required|in:start,base,pro']);

        $tariff = self::$tariffs[$request->tariff];
        $user = $request->user();

        $client = $this->makeClient();

        $payment = $client->createPayment([
            'amount' => [
                'value' => number_format($tariff['amount'], 2, '.', ''),
                'currency' => 'RUB',
            ],
            'confirmation' => [
                'type' => 'redirect',
                'return_url' => config('app.frontend_url') . '/dashboard/credits?status=success',
            ],
            'capture' => true,
            'description' => $tariff['description'] . ' — NatalCharts',
            'metadata' => [
                'user_id' => $user->id,
                'tariff' => $request->tariff,
                'credits' => $tariff['credits'],
            ],
        ], Str::uuid());

        Payment::create([
            'user_id' => $user->id,
            'yookassa_id' => $payment->getId(),
            'status' => 'pending',
            'amount' => $tariff['amount'],
            'credits' => $tariff['credits'],
            'tariff' => $request->tariff,
        ]);

        return response()->json([
            'confirmation_url' => $payment->getConfirmation()->getConfirmationUrl(),
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $body = $request->getContent();
        $data = json_decode($body, true);

        if (($data['event'] ?? '') !== 'payment.succeeded') {
            return response()->json(['ok' => true]);
        }

        $yookassaId = $data['object']['id'] ?? null;
        if (!$yookassaId) {
            return response()->json(['ok' => true]);
        }

        $payment = Payment::where('yookassa_id', $yookassaId)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return response()->json(['ok' => true]);
        }

        $payment->update(['status' => 'succeeded']);
        $payment->user->addCredits($payment->credits);

        $payment->user->creditTransactions()->create([
            'amount' => $payment->credits,
            'type' => 'credit',
            'description' => 'Пополнение баланса: тариф «' . self::$tariffs[$payment->tariff]['name'] . '»',
        ]);

        return response()->json(['ok' => true]);
    }

    private function makeClient(): Client
    {
        $client = new Client();
        $client->setAuth(
            config('services.yookassa.shop_id'),
            config('services.yookassa.secret_key')
        );
        return $client;
    }
}
