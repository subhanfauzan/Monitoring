<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    private Client $client;
    private string $apiKey;
    private string $baseUri = 'https://generativelanguage.googleapis.com/';
    private int $timeout = 45;
    private string $defaultModel;
    private int $defaultMaxTokens;
    private string $apiVersionPref; // 'v1' | 'v1beta' | 'auto'

    private ?string $resolvedModel = null; // hasil discovery: gemini-2.5-pro, dst
    private ?string $resolvedVer = null; // 'v1' / 'v1beta'

    /** @var array<string,int> */
    private array $lastUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

    public function __construct()
    {
        $this->apiKey = (string) env('GEMINI_API_KEY', '');
        if ($this->apiKey === '') {
            throw new \RuntimeException('GEMINI_API_KEY belum di-set di .env');
        }

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'timeout' => $this->timeout,
            'http_errors' => false,
        ]);

        $this->defaultModel = (string) env('GEMINI_MODEL', 'gemini-2.5-pro');
        $this->defaultMaxTokens = (int) env('GEMINI_MAX_OUTPUT', 4096);
        $this->apiVersionPref = (string) env('GEMINI_API_VERSION', 'auto');
    }

    // ================== PUBLIC ==================

    public function chat(array $messages, array $opts = []): string
    {
        $this->lastUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

        [$model, $ver] = $this->ensureModelResolved($opts['model'] ?? $this->defaultModel);

        $payload = $this->buildPayload(
            $messages,
            array_filter([
                'temperature' => $opts['temperature'] ?? 0.0,
                'maxOutputTokens' => $opts['max_tokens'] ?? $this->defaultMaxTokens,
                'responseMimeType' => $opts['response_mime_type'] ?? null,
            ]),
        );

        $attempts = 0;
        while (true) {
            try {
                $json = $this->postGenerateContent($model, $payload, $ver);
                $text = $this->joinText($json);
                if ($text === '') {
                    Log::warning('[Gemini] Response tanpa teks', ['response' => $json]);
                }
                return $text;
            } catch (\RuntimeException $e) {
                $code = $e->getCode();
                $attempts++;
                if ($attempts < 3 && ($code === 429 || $code >= 500)) {
                    usleep(200000 * $attempts);
                    continue;
                }
                throw $e;
            }
        }
    }

    public function generateText(string $prompt, ?string $model = null): string
    {
        return $this->chat(
            [['role' => 'user', 'content' => $prompt]],
            [
                'model' => $model ?: $this->defaultModel,
                'temperature' => 0.0,
                'max_tokens' => $this->defaultMaxTokens,
            ],
        );
    }

    public function generateJson(string $system, string $userPrompt, ?string $model = null, ?int $maxTokens = null): array
    {
        $text = $this->chat(
            [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $userPrompt]],
            [
                'model' => $model ?: $this->defaultModel,
                'temperature' => 0.0,
                'max_tokens' => $maxTokens ?: $this->defaultMaxTokens,
                'response_mime_type' => 'application/json',
            ],
        );

        $clean = preg_replace('/```[a-zA-Z]*\s*/', '', $text);
        $clean = str_replace('```', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            Log::warning('[Gemini] JSON parse gagal', ['raw' => $text, 'clean' => $clean]);
            throw new \RuntimeException('Gemini tidak mengembalikan JSON valid.');
        }
        return $decoded;
    }

    // Opsional: agent tools; otomatis fallback bila endpoint tak mendukung tools/systemInstruction
    public function toolCall(array $messages, array $functionDeclarations, array $opts = []): array
    {
        [$model, $ver] = $this->ensureModelResolved($opts['model'] ?? $this->defaultModel);

        $payload = $this->buildPayload(
            $messages,
            array_filter([
                'temperature' => $opts['temperature'] ?? 0.0,
                'maxOutputTokens' => $opts['max_tokens'] ?? $this->defaultMaxTokens,
            ]),
            ['functionDeclarations' => $functionDeclarations],
        );

        $json = $this->postGenerateContent($model, $payload, $ver);
        $fc = $this->findFunctionCall($json);

        if ($fc) {
            $messages[] = ['role' => 'model', 'parts' => [['functionCall' => $fc]]];
            return ['type' => 'function_call', 'name' => $fc['name'] ?? '', 'args' => $fc['args'] ?? [], 'messages' => $messages, 'raw' => $json];
        }
        return ['type' => 'text', 'text' => $this->joinText($json), 'raw' => $json];
    }

    /** Ambil pemakaian token dari request terakhir */
    public function getLastUsage(): array
    {
        return $this->lastUsage;
    }

    // ================== INTERNAL ==================

    private function ensureModelResolved(string $wantModel): array
    {
        if ($this->resolvedModel && $this->resolvedVer) {
            return [$this->resolvedModel, $this->resolvedVer];
        }

        // prefer v1beta dulu (banyak fitur baru 2.x ada di sini)
        $versions = $this->apiVersionPref === 'auto' ? ['v1beta', 'v1'] : [$this->apiVersionPref, $this->apiVersionPref === 'v1' ? 'v1beta' : 'v1'];

        $errors = [];
        foreach ($versions as $ver) {
            try {
                $models = $this->listModels($ver);
                if (!$models) {
                    continue;
                }

                $pick = $this->pickBestAvailableModel($models, $wantModel, $list);
                if ($pick) {
                    $this->resolvedModel = $pick;
                    $this->resolvedVer = $ver;
                    Log::info('[Gemini] resolved', ['model' => $pick, 'version' => $ver, 'available' => $list]);
                    return [$this->resolvedModel, $this->resolvedVer];
                }
            } catch (\RuntimeException $e) {
                $errors[] = "{$ver}: " . $e->getMessage();
            }
        }
        Log::error('[Gemini] model discovery gagal', ['errors' => $errors]);
        throw new \RuntimeException('Tidak ada kombinasi model+API version yang tersedia untuk generateContent.', 500);
    }

    private function listModels(string $version): array
    {
        $resp = $this->client->get("{$version}/models?key={$this->apiKey}", ['headers' => ['Accept' => 'application/json']]);
        $status = $resp->getStatusCode();
        $json = json_decode((string) $resp->getBody(), true);
        if ($status >= 400) {
            $msg = $json['error']['message'] ?? (string) $resp->getBody();
            throw new \RuntimeException("ListModels error ({$status}): " . $msg, $status);
        }
        return (array) ($json['models'] ?? []);
    }

    private function pickBestAvailableModel(array $models, string $wantModel, ?array &$debugList = null): ?string
    {
        // normalize → daftar id yang support generateContent
        $items = [];
        foreach ($models as $m) {
            $id = $m['name'] ?? '';
            if (str_starts_with($id, 'models/')) {
                $id = substr($id, 7);
            }
            $methods = $m['supportedGenerationMethods'] ?? [];
            if (!in_array('generateContent', (array) $methods, true)) {
                continue;
            }
            $items[] = $id;
        }
        $debugList = $items;

        // exact request
        if (in_array($wantModel, $items, true)) {
            return $wantModel;
        }

        // preference (paling “pinter” dulu)
        $prefs = ['gemini-2.5-pro', 'gemini-2.0-pro', 'gemini-1.5-pro-latest', 'gemini-1.5-pro', 'gemini-1.5-pro-002', 'gemini-2.5-flash', 'gemini-1.5-flash-latest', 'gemini-1.5-flash'];
        foreach ($prefs as $p) {
            if (in_array($p, $items, true)) {
                return $p;
            }
        }

        return $items[0] ?? null;
    }

    private function postGenerateContent(string $model, array $payload, string $version): array
    {
        $url = "{$version}/models/{$model}:generateContent?key={$this->apiKey}";
        $resp = $this->client->post($url, [
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'json' => $payload,
        ]);

        $status = $resp->getStatusCode();
        $body = (string) $resp->getBody();
        $json = json_decode($body, true);

        // sukses
        if ($status < 400) {
            if (!empty($json['promptFeedback']['blockReason'])) {
                $reason = $json['promptFeedback']['blockReason'];
                throw new \RuntimeException("Gemini safety block: {$reason}", 422);
            }
            $this->lastUsage = $this->extractUsageFromGemini($json);
            return $json ?? [];
        }

        // ---- Fallback pintar untuk error 400 unknown fields (systemInstruction/tools) ----
        $msg = $json['error']['message'] ?? $body;
        $unknownField = stripos($msg, 'Unknown name "systemInstruction"') !== false || stripos($msg, 'Unknown name "tools"') !== false;

        // 1) kalau di v1 dan unknown field -> coba ulang di v1beta
        if ($status === 400 && $unknownField && $version === 'v1') {
            $altUrl = "v1beta/models/{$model}:generateContent?key={$this->apiKey}";
            $resp2 = $this->client->post($altUrl, [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'json' => $payload,
            ]);
            $st2 = $resp2->getStatusCode();
            $bd2 = (string) $resp2->getBody();
            $js2 = json_decode($bd2, true);
            if ($st2 < 400) {
                Log::info('[Gemini] fallback ke v1beta untuk systemInstruction/tools');
                $this->lastUsage = $this->extractUsageFromGemini($js2 ?? []);
                return $js2 ?? [];
            }
            // 2) jika tetap 400 -> kirim ulang TANPA systemInstruction & tools
            $payload2 = $this->sanitizePayloadNoSystemTools($payload);
            $resp3 = $this->client->post($altUrl, [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'json' => $payload2,
            ]);
            $st3 = $resp3->getStatusCode();
            $bd3 = (string) $resp3->getBody();
            $js3 = json_decode($bd3, true);
            if ($st3 < 400) {
                Log::info('[Gemini] compat tanpa systemInstruction/tools (v1beta)');
                $this->lastUsage = $this->extractUsageFromGemini($js3 ?? []);
                return $js3 ?? [];
            }
            $msg = $js3['error']['message'] ?? $bd3 ?: $msg;
        }

        // 3) kalau langsung di v1beta dan unknown field -> kirim ulang TANPA systemInstruction/tools
        if ($status === 400 && $unknownField && $version === 'v1beta') {
            $payload2 = $this->sanitizePayloadNoSystemTools($payload);
            $resp2 = $this->client->post("{$version}/models/{$model}:generateContent?key={$this->apiKey}", [
                'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
                'json' => $payload2,
            ]);
            $st2 = $resp2->getStatusCode();
            $bd2 = (string) $resp2->getBody();
            $js2 = json_decode($bd2, true);
            if ($st2 < 400) {
                Log::info('[Gemini] compat tanpa systemInstruction/tools (v1beta)');
                $this->lastUsage = $this->extractUsageFromGemini($js2 ?? []);
                return $js2 ?? [];
            }
            $msg = $js2['error']['message'] ?? $bd2;
        }

        // jika masih gagal → lempar error asli
        Log::error('[Gemini] API error', ['status' => $status, 'model' => $model, 'version' => $version, 'msg' => $msg]);
        throw new \RuntimeException("Gemini API error ({$status}): " . $msg, $status);
    }

    // ===== Helper: compat tanpa systemInstruction/tools =====
    private function sanitizePayloadNoSystemTools(array $payload): array
    {
        // pindahkan systemInstruction (kalau ada) ke awal contents sebagai teks biasa
        if (!empty($payload['systemInstruction']['parts'][0]['text'])) {
            $sysText = (string) $payload['systemInstruction']['parts'][0]['text'];
            unset($payload['systemInstruction']);
            $prefix = "## SYSTEM\n" . $sysText . "\n\n";
            if (!empty($payload['contents'][0]['parts'][0]['text'])) {
                $payload['contents'][0]['parts'][0]['text'] = $prefix . $payload['contents'][0]['parts'][0]['text'];
            } else {
                array_unshift($payload['contents'], [
                    'role' => 'user',
                    'parts' => [['text' => $prefix]],
                ]);
            }
        }
        // hapus tools (functionDeclarations)
        if (isset($payload['tools'])) {
            unset($payload['tools']);
        }
        return $payload;
    }

    private function buildPayload(array $messages, ?array $generationConfig = null, ?array $tools = null): array
    {
        $systemInstruction = null;
        $contents = [];

        foreach ($messages as $m) {
            if (isset($m['parts'])) {
                // untuk functionResponse
                $contents[] = ['role' => $m['role'] ?? 'user', 'parts' => $m['parts']];
                continue;
            }

            $role = strtolower($m['role'] ?? 'user');
            $content = (string) ($m['content'] ?? '');

            if ($role === 'system') {
                $systemInstruction = ['role' => 'system', 'parts' => [['text' => $content]]];
                continue;
            }

            $contents[] = [
                'role' => $role === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => $content]],
            ];
        }

        $payload = ['contents' => $contents];
        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }
        if ($generationConfig) {
            $payload['generationConfig'] = $generationConfig;
        }
        if ($tools) {
            $payload['tools'] = $tools;
        }
        return $payload;
    }

    private function findFunctionCall(array $json): ?array
    {
        if (empty($json['candidates'][0]['content']['parts'])) {
            return null;
        }
        foreach ($json['candidates'][0]['content']['parts'] as $p) {
            if (!empty($p['functionCall'])) {
                return $p['functionCall'];
            }
        }
        return null;
    }

    private function joinText(array $json): string
    {
        $texts = [];
        if (!empty($json['candidates'])) {
            foreach ($json['candidates'] as $cand) {
                if (!empty($cand['content']['parts'])) {
                    foreach ($cand['content']['parts'] as $p) {
                        if (isset($p['text'])) {
                            $texts[] = (string) $p['text'];
                        }
                    }
                }
                if ($texts) {
                    break;
                }
            }
        }
        return trim(implode("\n", $texts));
    }

    private function extractUsageFromGemini(array $json): array
    {
        $u = $json['usageMetadata'] ?? null;
        if (!$u && !empty($json['candidates'][0]['usageMetadata'])) {
            $u = $json['candidates'][0]['usageMetadata'];
        }
        if (is_array($u)) {
            $prompt = (int) ($u['promptTokenCount'] ?? 0);
            $comp = (int) ($u['candidatesTokenCount'] ?? 0);
            $total = (int) ($u['totalTokenCount'] ?? $prompt + $comp);
            return [
                'prompt_tokens' => $prompt,
                'completion_tokens' => $comp,
                'total_tokens' => $total,
            ];
        }
        return ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];
    }
}
