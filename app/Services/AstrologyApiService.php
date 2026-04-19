<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class AstrologyApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.astrology_api.url');
        $this->apiKey = config('services.astrology_api.key');
    }

    private function post(string $endpoint, array $params): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . $endpoint, $params);

        if ($response->failed()) {
            throw new RuntimeException('Ошибка запроса к астрологическому API: ' . $response->body());
        }

        return $response->json();
    }

    public function natal(array $params): array
    {
        return $this->post('/natal', $params);
    }

    public function solar(array $params): array
    {
        return $this->post('/solar-return', $params);
    }

    public function transit(array $params): array
    {
        return $this->post('/transit', $params);
    }

    public function monthly(array $params): array
    {
        return $this->post('/horoscope/monthly', $params);
    }

    public function progressions(array $params): array
    {
        return $this->post('/secondary-progressions', $params);
    }

    public function venus(array $params): array
    {
        return $this->post('/venus-return', $params);
    }
}
