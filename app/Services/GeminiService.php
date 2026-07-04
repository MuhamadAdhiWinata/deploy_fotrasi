<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-2.0-flash';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    public function extractStudents(string $content, string $mimeType = 'text/plain'): array
    {
        $parts = [];

        if (in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp', 'application/pdf'])) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => base64_encode($content),
                ],
            ];
        } else {
            $parts[] = ['text' => $content];
        }

        $kelasList = implode(', ', config('kelas.list'));

        $prompt = <<<PROMPT
Extract student data from the following document. Return ONLY valid JSON array of objects with keys: name, nis, kelas.
Kelas must be exactly one of these values: {$kelasList}.
If a kelas value does not match exactly, map it to the closest match from the list or leave it empty.
Example: [{"name": "Ahmad Fauzi", "nis": "12345", "kelas": "TKR A"}]
PROMPT;

        $parts[] = ['text' => $prompt];

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
            'contents' => [
                'parts' => $parts,
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.1,
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $data = $response->json();
        $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';

        $result = json_decode($text, true) ?? [];

        foreach ($result as &$item) {
            if (isset($item['kelas'])) {
                $item['kelas'] = $this->normalizeKelas($item['kelas']);
            }
        }

        return $result;
    }

    public function normalizeKelas(?string $kelas): string
    {
        if (empty($kelas)) return '';

        $kelas = strtoupper(trim($kelas));
        $kelas = preg_replace('/\s+/', ' ', $kelas);

        $list = config('kelas.list');

        $exact = array_search($kelas, $list);
        if ($exact !== false) {
            return $list[$exact];
        }

        foreach ($list as $valid) {
            $normalized = str_replace(' ', '', $valid);
            $inputNormalized = str_replace(' ', '', $kelas);
            if ($inputNormalized === $normalized) {
                return $valid;
            }
        }

        foreach ($list as $valid) {
            $validPrefix = explode(' ', $valid)[0];
            $inputPrefix = explode(' ', $kelas)[0];
            if ($validPrefix === $inputPrefix && preg_match('/[A-D]/', $kelas)) {
                $letter = preg_match('/[A-D]/', $kelas, $m) ? $m[0] : '';
                if ($letter && in_array("{$validPrefix} {$letter}", $list)) {
                    return "{$validPrefix} {$letter}";
                }
            }
        }

        foreach ($list as $valid) {
            if (stripos($valid, $kelas) !== false || stripos($kelas, $valid) !== false) {
                return $valid;
            }
        }

        return $kelas;
    }
}
