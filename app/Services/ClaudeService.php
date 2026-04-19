<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
    }

    public function interpret(string $chartType, array $chartData): string
    {
        $prompt = "Ты астролог-эксперт. Дай подробную интерпретацию на русском языке для следующих астрологических данных типа {$chartType}: " . json_encode($chartData, JSON_UNESCAPED_UNICODE);

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model' => 'claude-sonnet-4-6',
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

        return $data['content'][0]['text'] ?? '';
    }
}
