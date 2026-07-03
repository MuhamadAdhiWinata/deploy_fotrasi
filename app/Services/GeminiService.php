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

        $prompt = <<<PROMPT
Extract student data from the following document. Return ONLY valid JSON array of objects with keys: name, nis, kelas.
Use these exact kelas values: TSM A, TSM B, TSM C, TKR A, TKR B, TKR C, TKR D, PBS, RPL, DPIB, Animasi.
Example: [{"name": "Ahmad Fauzi", "nis": "12345", "kelas": "TKR A"}]
If unsure about kelas, leave it as empty string.
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

        return json_decode($text, true) ?? [];
    }
}
