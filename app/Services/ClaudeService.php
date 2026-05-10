<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeService
{
    private string $apiKey;

    private const MODEL_NATAL = 'anthropic/claude-haiku-4-5';
    private const MODEL_DEFAULT = 'google/gemini-2.0-flash-lite-001';

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
    }

    public function interpret(string $chartType, array $chartData, array $profile = []): string
    {
        $prompt = $this->buildPrompt($chartType, $chartData, $profile);
        $model = $chartType === 'natal' ? self::MODEL_NATAL : self::MODEL_DEFAULT;
        $temperature = $chartType === 'natal' ? 0.8 : 0.7;

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(120)->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => 4096,
            'temperature' => $temperature,
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
        $html = $this->markdownToHtml($content);

        if ($chartType === 'natal') {
            $html = $this->addAnchorsAndToc($html);
        }

        return $html;
    }

    private function buildPrompt(string $chartType, array $chartData, array $profile): string
    {
        if ($chartType === 'natal') {
            return $this->buildNatalPrompt($chartData, $profile);
        }

        return "Ты астролог-эксперт. Дай подробную интерпретацию на русском языке для следующих астрологических данных типа {$chartType}.\n\n"
            . "КРИТИЧЕСКИ ВАЖНО: отвечай ТОЛЬКО чистым HTML без какого-либо markdown. Запрещено использовать: звёздочки (*), решётки (#), тире-списки (- item), обратные кавычки (`), блоки ```html```. Разрешены только HTML-теги: <h2>, <h3>, <p>, <ul>, <li>, <strong>. Без оборачивающих тегов <html>, <body>, <head>.\n\n"
            . "Данные: " . json_encode($chartData, JSON_UNESCAPED_UNICODE);
    }

    private function buildNatalPrompt(array $chartData, array $profile): string
    {
        $name = trim((string) ($profile['name'] ?? '')) ?: 'друг';
        $birthDate = $profile['birth_date'] ?? '';
        $birthTime = $profile['birth_time'] ?? '';
        $birthPlace = $profile['birth_place'] ?? '';

        $accents = $this->scoreNatalAccents($chartData);
        $accentsBlock = $this->formatAccentsForPrompt($accents);

        $dataJson = json_encode($chartData, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
Ты — внимательный астролог-психолог. Пишешь живым русским языком интерпретацию натальной карты для конкретного человека по имени {$name}.

ДАННЫЕ ЧЕЛОВЕКА:
- Имя: {$name}
- Дата рождения: {$birthDate}
- Время рождения: {$birthTime}
- Место рождения: {$birthPlace}

ОПОРНЫЕ АКЦЕНТЫ КАРТЫ (рассчитаны заранее, ОБЯЗАТЕЛЬНО используй их в разделе «Акценты карты», в том же порядке и с теми же звёздами):
{$accentsBlock}

ДАННЫЕ КАРТЫ (JSON от astrology-api.io):
{$dataJson}

КРИТИЧЕСКИ ВАЖНО — ФОРМАТ:
Отвечай ТОЛЬКО чистым HTML. Запрещено: markdown (* # - ` ```), оборачивающие <html><body><head>.
Разрешены теги: <h2>, <h3>, <p>, <ul>, <li>, <strong>, <em>.
НЕ ДОБАВЛЯЙ id-атрибуты к h2 — они проставятся автоматически.

СТРУКТУРА ОТВЕТА (СТРОГО в этом порядке):

1. ВВОДНАЯ ЧАСТЬ (3–5 предложений, без заголовка, обычные <p>):
   — обратись по имени ({$name});
   — задай интригующий тон: что делает эту карту уникальной, какая «главная нота» личности, опираясь на стихию Солнца, знак Луны и Асцендент;
   — закончи фразой, которая мотивирует прочитать всё до конца.
   ЗАПРЕЩЕНО: «звёзды говорят», «вселенная подарила», «во вселенной написано», слащавые штампы. Тон — тёплый, точный, человечный.

2. <h2>Акценты карты</h2>
   <ul> с 3–5 пунктами из ОПОРНЫХ АКЦЕНТОВ выше. Каждый пункт начинается со звёзд-маркера важности (⭐⭐⭐ / ⭐⭐ / ⭐), затем <strong>краткая суть</strong>, затем 1 предложение-объяснение живым языком, без сухой терминологии.

3. <h2>Сильные стороны</h2>
   <ul> из 4–6 пунктов. Опирайся на: планеты в обители/экзальтации, гармоничные аспекты (трин, секстиль), хорошее положение управителя ASC. Каждый пункт: <strong>конкретная сильная сторона</strong> — затем 1 предложение объяснения. Тон — позитивный, конкретный, без приторности.

4. <h2>Слабые стороны</h2>
   <ul> из 3–5 пунктов. Опирайся на: планеты в изгнании/падении, напряжённые аспекты (квадрат, оппозиция), ретроградные личные планеты. Формулируй как «зоны роста», полезный фидбэк, а не приговор. Никаких «у вас плохая Венера». Каждый пункт: <strong>зона роста</strong> — затем 1 предложение, что с этим делать.

5. <h2>Солнце</h2> — суть личности, ядро мотивации (1–2 абзаца).

6. <h2>Луна</h2> — эмоциональный мир, базовые потребности (1–2 абзаца).

7. <h2>Асцендент</h2> — как проявляешься внешне, первое впечатление (1–2 абзаца).

8. <h2>Планеты в знаках</h2> — пройдись по Меркурию, Венере, Марсу, Юпитеру, Сатурну (по 2–4 предложения на каждую). Используй <h3> для имени планеты.

9. <h2>Планеты в домах</h2> — где какая планета проявляется, какие сферы жизни в фокусе. Используй <h3> для номеров домов с планетами.

10. <h2>Ключевые аспекты</h2> — топ-5 значимых аспектов (тесные мажорные). По 1–2 предложения на каждый. Объясняй ЧТО это даёт человеку, а не «квадрат Марса к Сатурну».

11. <h2>Заключение</h2> — короткое резюме общего портрета (1 абзац) + одна мотивирующая фраза в конце, обращённая к {$name} по имени, без штампов.

ПРАВИЛА ПО ВСЕМУ ТЕКСТУ:
- Обращайся на «ты», по имени {$name}.
- Никаких клише и эзотерического тумана. Пиши как умный психолог-друг.
- Конкретика > общие слова. Вместо «у вас творческий потенциал» — «ты быстро придумываешь нестандартные решения, особенно когда есть дедлайн».
- В разделах после «Акцентов карты» при случае ссылайся на акценты («как мы уже отметили в акцентах…»), чтобы текст читался связно.
PROMPT;
    }

    /**
     * @return array<int, array{stars:int, title:string, hint:string}>
     */
    private function scoreNatalAccents(array $chartData): array
    {
        $accents = [];

        $sun = $this->extractPoint($chartData, 'sun', ['Sun', 'sun', 'СОЛНЦЕ', 'Солнце']);
        $moon = $this->extractPoint($chartData, 'moon', ['Moon', 'moon', 'Луна']);
        $asc = $this->extractAscendant($chartData);

        if ($sun) {
            $sign = $sun['sign'] ?? '';
            $house = $sun['house'] ?? null;
            $accents[] = [
                'score' => 100 + ($this->isAngularHouse($house) ? 20 : 0),
                'stars' => 3,
                'title' => 'Солнце' . ($sign ? " в {$sign}" : '') . ($house ? ", {$house} дом" : ''),
                'hint' => 'ядро личности и главный двигатель',
            ];
        }

        if ($moon) {
            $sign = $moon['sign'] ?? '';
            $house = $moon['house'] ?? null;
            $accents[] = [
                'score' => 95 + ($this->isAngularHouse($house) ? 15 : 0),
                'stars' => 3,
                'title' => 'Луна' . ($sign ? " в {$sign}" : '') . ($house ? ", {$house} дом" : ''),
                'hint' => 'эмоциональный мир и базовые потребности',
            ];
        }

        if ($asc) {
            $accents[] = [
                'score' => 90,
                'stars' => 3,
                'title' => 'Асцендент' . ($asc ? " в {$asc}" : ''),
                'hint' => 'как ты проявляешься во внешнем мире',
            ];
        }

        $stelliums = $this->detectStelliums($chartData);
        foreach ($stelliums as $stellium) {
            $angular = $this->isAngularHouse($stellium['house'] ?? null);
            $accents[] = [
                'score' => 80 + ($angular ? 15 : 0) + (($stellium['count'] ?? 0) - 3) * 5,
                'stars' => $angular ? 3 : 2,
                'title' => 'Стеллиум' . (isset($stellium['house']) ? " в {$stellium['house']} доме" : '')
                    . (isset($stellium['sign']) ? " ({$stellium['sign']})" : ''),
                'hint' => 'концентрация энергии в одной сфере жизни',
            ];
        }

        $tightAspects = $this->detectTightMajorAspects($chartData);
        foreach ($tightAspects as $aspect) {
            $orb = (float) ($aspect['orb'] ?? 5.0);
            $accents[] = [
                'score' => 70 + max(0, (3.0 - $orb) * 5),
                'stars' => $orb < 1.5 ? 3 : ($orb < 3 ? 2 : 1),
                'title' => $this->formatAspectTitle($aspect),
                'hint' => 'ключевая внутренняя динамика',
            ];
        }

        usort($accents, fn($a, $b) => ($b['score'] ?? 0) <=> ($a['score'] ?? 0));

        $top = array_slice($accents, 0, 5);

        return array_map(fn($a) => [
            'stars' => $a['stars'],
            'title' => $a['title'],
            'hint' => $a['hint'],
        ], $top);
    }

    private function formatAccentsForPrompt(array $accents): string
    {
        if (empty($accents)) {
            return "(данных недостаточно — ИИ, выбери 3–5 главных акцентов сам по критериям: Солнце/Луна/Асцендент, стеллиумы, угловые дома, тесные мажорные аспекты)";
        }

        $lines = [];
        foreach ($accents as $a) {
            $stars = str_repeat('⭐', $a['stars']);
            $lines[] = "- {$stars} {$a['title']} — {$a['hint']}";
        }
        return implode("\n", $lines);
    }

    private function extractPoint(array $chartData, string $key, array $aliases): ?array
    {
        if (isset($chartData[$key]) && is_array($chartData[$key])) {
            return $chartData[$key];
        }

        if (isset($chartData['planets']) && is_array($chartData['planets'])) {
            foreach ($chartData['planets'] as $p) {
                if (!is_array($p)) continue;
                $name = $p['name'] ?? '';
                if (in_array($name, $aliases, true)) {
                    return $p;
                }
            }
        }
        return null;
    }

    private function extractAscendant(array $chartData): ?string
    {
        if (isset($chartData['ascendant']['sign'])) return $chartData['ascendant']['sign'];
        if (isset($chartData['ascendant']) && is_string($chartData['ascendant'])) return $chartData['ascendant'];
        if (isset($chartData['houses'][0]['sign'])) return $chartData['houses'][0]['sign'];
        return null;
    }

    private function isAngularHouse(mixed $house): bool
    {
        $h = (int) $house;
        return in_array($h, [1, 4, 7, 10], true);
    }

    /**
     * @return array<int, array{house?:string|int, sign?:string, count:int}>
     */
    private function detectStelliums(array $chartData): array
    {
        if (!isset($chartData['planets']) || !is_array($chartData['planets'])) {
            return [];
        }

        $byHouse = [];
        $bySign = [];
        foreach ($chartData['planets'] as $p) {
            if (!is_array($p)) continue;
            $h = $p['house'] ?? null;
            $s = $p['sign'] ?? null;
            if ($h !== null) $byHouse[(string) $h] = ($byHouse[(string) $h] ?? 0) + 1;
            if ($s !== null) $bySign[(string) $s] = ($bySign[(string) $s] ?? 0) + 1;
        }

        $out = [];
        foreach ($byHouse as $h => $count) {
            if ($count >= 3) $out[] = ['house' => $h, 'count' => $count];
        }
        foreach ($bySign as $s => $count) {
            if ($count >= 3) $out[] = ['sign' => $s, 'count' => $count];
        }
        return $out;
    }

    /**
     * @return array<int, array{planet1:string, planet2:string, type:string, orb:float}>
     */
    private function detectTightMajorAspects(array $chartData): array
    {
        if (!isset($chartData['aspects']) || !is_array($chartData['aspects'])) {
            return [];
        }

        $major = ['conjunction', 'opposition', 'square', 'trine', 'sextile',
                  'соединение', 'оппозиция', 'квадрат', 'трин', 'секстиль'];

        $out = [];
        foreach ($chartData['aspects'] as $a) {
            if (!is_array($a)) continue;
            $type = strtolower((string) ($a['type'] ?? $a['aspect'] ?? ''));
            $orb = abs((float) ($a['orb'] ?? $a['orbit'] ?? 99));
            if (!in_array($type, $major, true)) continue;
            if ($orb > 3.0) continue;

            $out[] = [
                'planet1' => (string) ($a['planet1'] ?? $a['from'] ?? ''),
                'planet2' => (string) ($a['planet2'] ?? $a['to'] ?? ''),
                'type'    => $type,
                'orb'     => $orb,
            ];
        }

        usort($out, fn($a, $b) => $a['orb'] <=> $b['orb']);
        return array_slice($out, 0, 3);
    }

    private function formatAspectTitle(array $aspect): string
    {
        $map = [
            'conjunction' => 'соединение', 'opposition' => 'оппозиция',
            'square' => 'квадрат', 'trine' => 'трин', 'sextile' => 'секстиль',
        ];
        $type = $map[$aspect['type']] ?? $aspect['type'];
        $p1 = $aspect['planet1'] ?? '';
        $p2 = $aspect['planet2'] ?? '';
        $orb = number_format($aspect['orb'] ?? 0, 1);
        return "Тесный {$type} {$p1}–{$p2} (орбис {$orb}°)";
    }

    private function addAnchorsAndToc(string $html): string
    {
        if (!preg_match_all('/<h2>(.+?)<\/h2>/u', $html, $matches, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $usedIds = [];
        $tocItems = [];

        $html = preg_replace_callback('/<h2>(.+?)<\/h2>/u', function ($m) use (&$usedIds, &$tocItems) {
            $title = strip_tags($m[1]);
            $id = $this->slugify($title);
            $base = $id;
            $i = 2;
            while (in_array($id, $usedIds, true)) {
                $id = $base . '-' . $i++;
            }
            $usedIds[] = $id;
            $tocItems[] = ['id' => $id, 'title' => $title];
            return '<h2 id="' . $id . '">' . $m[1] . '</h2>';
        }, $html);

        if (empty($tocItems)) {
            return $html;
        }

        $toc = '<nav class="toc"><strong>Краткое содержание</strong><ul>';
        foreach ($tocItems as $item) {
            $toc .= '<li><a href="#' . $item['id'] . '">' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</a></li>';
        }
        $toc .= '</ul></nav>';

        $pos = strpos($html, '<h2');
        if ($pos === false) {
            return $html;
        }

        return substr($html, 0, $pos) . $toc . substr($html, $pos);
    }

    private function slugify(string $text): string
    {
        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'zh',
            'з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o',
            'п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c',
            'ч'=>'ch','ш'=>'sh','щ'=>'sch','ъ'=>'','ы'=>'y','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
        ];
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, $map);
        $text = preg_replace('/[^a-z0-9\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        $text = trim($text, '-');
        return $text ?: 'section';
    }

    private function markdownToHtml(string $text): string
    {
        $text = preg_replace('/^```[a-z]*\s*/im', '', $text);
        $text = preg_replace('/\s*```/m', '', $text);
        $text = trim($text);

        if (str_contains($text, '<h2>') || str_contains($text, '<p>') || str_contains($text, '<ul>')) {
            return $text;
        }

        $lines = explode("\n", $text);
        $html = '';
        $inList = false;

        foreach ($lines as $line) {
            $line = rtrim($line);

            if (preg_match('/^###\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h3>' . $this->inlineMarkdown($m[1]) . "</h3>\n";
            }
            elseif (preg_match('/^##\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h2>' . $this->inlineMarkdown($m[1]) . "</h2>\n";
            }
            elseif (preg_match('/^#\s+(.+)/', $line, $m)) {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
                $html .= '<h2>' . $this->inlineMarkdown($m[1]) . "</h2>\n";
            }
            elseif (preg_match('/^[*\-]\s+(.+)/', $line, $m)) {
                if (!$inList) { $html .= "<ul>\n"; $inList = true; }
                $html .= '<li>' . $this->inlineMarkdown($m[1]) . "</li>\n";
            }
            elseif ($line === '') {
                if ($inList) { $html .= "</ul>\n"; $inList = false; }
            }
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
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
        return $text;
    }
}
