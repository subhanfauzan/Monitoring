<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected Client $client;
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        $this->client = new Client([
            'base_uri'    => 'https://generativelanguage.googleapis.com/',
            'timeout'     => 30,
            'http_errors' => false, // biar bisa baca body error tanpa lempar exception Guzzle
        ]);
    }

    /**
     * Panggilan generik ke Gemini generateContent, return TEKS saja.
     */
    public function generateText(string $prompt, string $model = 'gemini-1.5-flash'): ?string
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY belum di-set di .env');
        }

        $path = "v1beta/models/{$model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [[
                'role'  => 'user',          // penting: beberapa kasus 400 jika role kosong
                'parts' => [['text' => $prompt]],
            ]],
        ];

        $resp = $this->client->post($path, [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json' => $payload,
        ]);

        $status = $resp->getStatusCode();
        $body   = (string) $resp->getBody();
        $json   = json_decode($body, true);

        if ($status >= 400) {
            // ambil pesan error human-friendly
            $msg = $json['error']['message'] ?? $body;
            Log::error('[Gemini] API error', ['status' => $status, 'msg' => $msg, 'body' => $json]);
            // lempar sebagai exception supaya controller bisa balikin JSON rapi (bukan 500 kosong)
            throw new \RuntimeException("Gemini API error ({$status}): ".$msg, $status);
        }

        // sukses → ambil teks candidate pertama
        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($text === null) {
            Log::warning('[Gemini] Response tanpa teks', ['response' => $json]);
        }

        return $text;
    }
}
