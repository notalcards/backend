<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
    }

    public function interpret(string $chartType, array $chartData): string
    {
        $prompt = "Ты астролог-эксперт. Дай подробную интерпретацию на русском языке для следующих астрологических данных типа {$chartType}: " . json_encode($chartData, JSON_UNESCAPED_UNICODE);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'google/gemini-2.0-flash-lite-001',
            'max_tokens' => 4096,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Ошибка запроса к Claude API: ' . $response->body());
        }

        $data = $response->json();

        return $data['choices'][0]['message']['content'] ?? '';
    }
}
