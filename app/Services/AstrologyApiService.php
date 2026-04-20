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

    private function post(string $endpoint, array $body): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($this->baseUrl . $endpoint, $body);

        if ($response->failed()) {
            throw new RuntimeException('Ошибка запроса к астрологическому API: ' . $response->body());
        }

        return $response->json();
    }

    private function buildSubject(array $profile, ?array $extra = []): array
    {
        [$year, $month, $day] = explode('-', $profile['birth_date']);
        [$hour, $minute] = explode(':', $profile['birth_time'] ?? '00:00:00');

        return array_merge([
            'name' => $profile['name'] ?? 'Subject',
            'birth_data' => array_filter([
                'year'      => (int) $year,
                'month'     => (int) $month,
                'day'       => (int) $day,
                'hour'      => (int) $hour,
                'minute'    => (int) $minute,
                'city'      => $profile['birth_place'] ?? null,
                'latitude'  => $profile['lat'] ?? null,
                'longitude' => $profile['lng'] ?? null,
            ], fn($v) => $v !== null),
        ], $extra ?? []);
    }

    public function natal(array $params): array
    {
        return $this->post('/charts/natal', [
            'subject' => $this->buildSubject($params['profile']),
        ]);
    }

    public function solar(array $params): array
    {
        return $this->post('/charts/solar-return', [
            'subject' => $this->buildSubject($params['profile']),
            'year'    => $params['year'] ?? (int) date('Y'),
        ]);
    }

    public function transit(array $params): array
    {
        return $this->post('/charts/transit', [
            'subject' => $this->buildSubject($params['profile']),
        ]);
    }

    public function monthly(array $params): array
    {
        return $this->post('/horoscope/personal/monthly', [
            'subject' => $this->buildSubject($params['profile']),
            'year'    => $params['year'] ?? (int) date('Y'),
            'month'   => $params['month'] ?? (int) date('m'),
        ]);
    }

    public function progressions(array $params): array
    {
        return $this->post('/charts/progressions', [
            'subject'           => $this->buildSubject($params['profile']),
            'progression_date'  => $params['progression_date'] ?? date('Y-m-d'),
        ]);
    }

    public function venus(array $params): array
    {
        return $this->post('/charts/venus-return', [
            'subject' => $this->buildSubject($params['profile']),
            'year'    => $params['year'] ?? (int) date('Y'),
        ]);
    }

    public function synastry(array $params): array
    {
        return $this->post('/charts/synastry', [
            'subject1' => $this->buildSubject($params['profile']),
            'subject2' => $this->buildSubject($params['profile2']),
        ]);
    }
}
