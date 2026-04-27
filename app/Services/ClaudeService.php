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

    private function markdownToHtml(string $text): string
    {
        // Убираем блоки ```html ... ``` или ``` ... ```
        $text = preg_replace('/^```[a-z]*\s*/im', '', $text);
        $text = preg_replace('/\s*```/m', '', $text);
        $text = trim($text);

        // Если уже HTML — возвращаем как есть
        if (str_contains($text, '<h2>') || str_contains($text, '<p>') || str_contains($text, '<ul>')) {
            return $text;
        }

        // Конвертируем markdown → HTML построчно
        $lines = explode("\n", $text);
        $html = '';
        $inList = false;

        foreach ($lines as $line) {
            $line = rtrim($line);

            // ### Заголовок 3
            if (preg_match('/^###\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h3>' . $this->inlineMarkdown($m[1]) . "</h3>\n";
            }
            // ## Заголовок 2
            elseif (preg_match('/^##\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h2>' . $this->inlineMarkdown($m[1]) . "</h2>\n";
            }
            // # Заголовок 1 → тоже h2
            elseif (preg_match('/^#\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h2>' . $this->inlineMarkdown($m[1]) . "</h2>\n";
            }
            // * item или - item
            elseif (preg_match('/^[*\-]\s+(.+)/', $line, $m)) {
                if (!$inList) { $html .= "<ul>\n"; $inList = true; }
                $html .= '<li>' . $this->inlineMarkdown($m[1]) . "</li>\n";
            }
            // Пустая строка
            elseif ($line === '') {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
            }
            // Обычный текст
            else {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<p>' . $this->inlineMarkdown($line) . "</p>\n";
            }
        }

        if ($inList) { $html .= "</ul>\n"; }

        return trim($html);
    }

    private function inlineMarkdown(string $text): string
    {
        // **bold** → <strong>
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        // *italic* → <em>
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        return $text;
    }

    public function interpret(string $chartType, array $chartData): string
    {
        $prompt = "Ты астролог-эксперт. Дай подробную интерпретацию на русском языке для следующих астрологических данных типа {$chartType}.\n\nКРИТИЧЕСКИ ВАЖНО: отвечай ТОЛЬКО чистым HTML без какого-либо markdown. Запрещено использовать: звёздочки (*), решётки (#), тире-списки (- item), обратные кавычки (`), блоки ```html```. Разрешены только HTML-теги: <h2>, <h3>, <p>, <ul>, <li>, <strong>. Без оборачивающих тегов <html>, <body>, <head>.\n\nДанные: " . json_encode($chartData, JSON_UNESCAPED_UNICODE);

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

        $content = $data['choices'][0]['message']['content'] ?? '';
        return $this->markdownToHtml($content);
    }
}
