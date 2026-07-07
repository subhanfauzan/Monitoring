<?php

namespace App\Http\Controllers;

use App\Models\AiQueryReview;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Human oversight helper: klasifikasi risiko query
     */
    private function assessRisk(string $sql): string
    {
        $normalized = strtolower($sql);

        // Query yang sangat berbahaya (sebenarnya sudah diblok di validator, tapi kita double check)
        if (str_contains($normalized, ' drop ')
            || str_contains($normalized, ' truncate ')
            || str_contains($normalized, ' alter table ')
            || str_contains($normalized, ' information_schema ')
        ) {
            return 'high';
        }

        // Karena kita hanya mengizinkan SELECT, kasus UPDATE/DELETE harusnya sudah tertolak.
        // Tapi kalau sampai lolos, anggap high.
        if (preg_match('/\b(update|delete|insert|create|replace|grant|revoke)\b/', $normalized)) {
            return 'high';
        }

        // SELECT besar tanpa LIMIT → medium
        if (preg_match('/\bselect\b/', $normalized) && !preg_match('/\blimit\s+\d+/i', $normalized)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Human oversight helper: boleh akses schema apa saja
     * Untuk sekarang: hanya tabel daftar_tiket.
     */
    private function isSafeSchema(string $sql): bool
    {
        $normalized = strtolower($sql);

        // Harus dari daftar_tiket
        if (!preg_match('/\bfrom\s+daftar_tiket\b/', $normalized)) {
            return false;
        }

        // Tidak boleh sentuh information_schema, sys, dll
        if (preg_match('/\binformation_schema\b/', $normalized)) {
            return false;
        }

        return true;
    }

    /**
     * Step 1: Generate SQL dari LLM + simpan sebagai review (BELUM eksekusi)
     */
    public function askQuestion(Request $request)
    {
        $question = $request->input('question');

        $client = new Client(); // dipakai untuk dua panggilan OpenAI
        $totalUsage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'total_tokens' => 0];

        // === VALIDASI INTENT (YA/TIDAK) ===
        $validationSystem = <<<SYS
        Anda adalah *router* intent yang hanya menjawab "YA" atau "TIDAK".
        Tujuan: tentukan apakah pertanyaan berkaitan dengan data monitoring jaringan/tiket.

        Kriteria BERKAITAN (jawab "YA"):
        - Status site (down/up), masalah power/telkom, lokasi/NOP/cluster, jumlah/statistik tiket,
          tim/penanganan FOP, status jaringan, severity, site class.

        Kriteria TIDAK BERKAITAN (jawab "TIDAK"):
        - Programming (Laravel, PHP, dll), pertanyaan umum (cuaca, politik), pertanyaan pribadi,
          definisi teknologi non monitoring.

        Contoh yang BERKAITAN:
        - "Tampilkan semua site yang sedang mengalami gangguan hari ini"
        - "Berapa banyak gangguan di 'NOP SURABAYA' saat ini?"
        - "Tampilkan site down dengan kategori 'Power Issue'"
        - "Urutkan 10 site paling sering gangguan 3 bulan terakhir"

        Contoh yang TIDAK BERKAITAN:
        - "Apa itu controller di Laravel?"
        - "Cuaca hari ini apa?"

        Jawab tepat "YA" atau "TIDAK".
        SYS;

        try {
            $validationResponse = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                ],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => $validationSystem],
                        ['role' => 'user', 'content' => $question],
                    ],
                    'temperature' => 0,
                    'max_tokens' => 5,
                ],
            ]);

            $validationData = json_decode($validationResponse->getBody()->getContents(), true);
            $isDataRelated = strtoupper(trim($validationData['choices'][0]['message']['content'] ?? 'TIDAK'));

            if (isset($validationData['usage'])) {
                $totalUsage['prompt_tokens'] += $validationData['usage']['prompt_tokens'] ?? 0;
                $totalUsage['completion_tokens'] += $validationData['usage']['completion_tokens'] ?? 0;
                $totalUsage['total_tokens'] += $validationData['usage']['total_tokens'] ?? 0;
            }

            if ($isDataRelated !== 'YA') {
                return response()->json([
                    'answer' => "Maaf, saya adalah asisten khusus untuk monitoring jaringan dan data tiket. Saya hanya bisa membantu Anda dengan pertanyaan seputar:\n\n• Status site (down/up)\n• Masalah jaringan (power, telkom, dll)\n• Data lokasi dan NOP\n• Statistik tiket\n• Informasi tim FOP\n\nSilakan tanyakan sesuatu tentang data monitoring jaringan Anda.",
                    'usage'  => $totalUsage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Validation API Error: ' . $e->getMessage());
            // fallback: lanjut proses seperti biasa
        }

        // === PROMPT SQL: OUTPUT WAJIB JSON {"sql":"..."} ===
        $masterPrompt = <<<SYS
        PERAN: Anda ahli SQL. Ubah pertanyaan bahasa natural menjadi **SATU** query SQL mentah MySQL.
        Output **WAJIB** JSON valid persis: {"sql":"<QUERY>"} tanpa teks lain.

        SKEMA TABEL
        - Tabel: daftar_tiket
        - Kolom: id, site_id, site_class, savERity (ejaan sebenarnya di DB: "saverity"),
                 suspect_problem, time_down, status_site, tim_fop, remark, ticket_swfm,
                 nop, cluster_to, nossa, status_ticket, created_at, updated_at

        KETENTUAN WAJIB
        1) Hanya boleh SELECT dari tabel daftar_tiket (tanpa CTE, tanpa menulis ke tabel lain).
        2) Selalu tambahkan LIMIT 50 di akhir.
        3) JANGAN gunakan SELECT *. Secara default gunakan SELECT site_id, nop, suspect_problem, time_down, ticket_swfm kecuali user meminta kolom spesifik.
        4) Bandingkan string dengan LOWER(...).
        4) Tanggal:
           - "hari ini"   -> DATE(created_at) = CURDATE()
           - "bulan ini"  -> MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
           - "minggu ini" -> YEARWEEK(created_at)=YEARWEEK(CURDATE())
        5) Statistik gunakan COUNT()/GROUP BY/ORDER BY sesuai konteks.
        6) Pemetaan istilah user -> kolom:
           - "severity" -> savERity (DB: "saverity")
           - "site class" -> site_class
           - "status tiket / ticket status" (open/close) -> status_ticket
           - "status tiket/workflow" (assigned, in progress, canceled, resolved, submitted, escalated)
               -> cari dengan LOWER(remark) (fallback), atau padankan ke LOWER(status_site) bila cocok.
        7) Kategori masalah (power, telkom, fiber, cell) -> cari pada LOWER(suspect_problem).
        8) Jika pertanyaan ambigu tapi relevan, pilih interpretasi umum dan batasi LIMIT 50.
        9) Jika tidak relevan/invalid -> kembalikan {"sql":""}.

        CONTOH
        - "site down hari ini"
          -> {"sql":"SELECT site_id, nop, suspect_problem, time_down, ticket_swfm FROM daftar_tiket WHERE LOWER(status_site)='down' AND DATE(created_at)=CURDATE() LIMIT 50"}
        - "masalah power bulan ini"
          -> {"sql":"SELECT site_id, nop, suspect_problem, time_down, ticket_swfm FROM daftar_tiket WHERE LOWER(suspect_problem) LIKE '%power%' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) LIMIT 50"}
        - "berapa banyak gangguan di NOP SURABAYA minggu ini"
          -> {"sql":"SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(nop) LIKE '%surabaya%' AND YEARWEEK(created_at)=YEARWEEK(CURDATE()) LIMIT 50"}

        Sekarang ubah pertanyaan berikut ke JSON {"sql": "..."}:
        SYS;

        // === PEMANGGILAN API UNTUK SQL + PARSING JSON ===
        $sqlQuery = '';

        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                ],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [
                        ['role' => 'system', 'content' => $masterPrompt],
                        ['role' => 'user', 'content' => $question],
                    ],
                    'temperature' => 0,
                    'max_tokens' => 500,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $raw = trim($data['choices'][0]['message']['content'] ?? '');

            if (isset($data['usage'])) {
                $totalUsage['prompt_tokens'] += $data['usage']['prompt_tokens'] ?? 0;
                $totalUsage['completion_tokens'] += $data['usage']['completion_tokens'] ?? 0;
                $totalUsage['total_tokens'] += $data['usage']['total_tokens'] ?? 0;
            }

            // Parse JSON {"sql":"..."}
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded['sql'])) {
                $sqlQuery = trim((string) $decoded['sql']);
            } else {
                // Fallback kalau model masih mengembalikan SQL polos
                $sqlQuery = preg_replace('/```sql\s*/i', '', $raw);
                $sqlQuery = preg_replace('/```\s*/', '', $sqlQuery);
                $sqlQuery = trim($sqlQuery, " \t\n\r\0\x0B`;");
            }
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return response()->json([
                'answer' => 'Maaf, terjadi kesalahan saat berkomunikasi dengan AI. Silakan coba lagi.',
                'usage'  => $totalUsage,
            ]);
        }

        // === VALIDASI QUERY KETAT (SEBELUM DISIMPAN) ===
        if (empty($sqlQuery) || strlen($sqlQuery) < 15 || strtoupper(trim($sqlQuery)) === 'INVALID') {
            return response()->json([
                'answer' => "Maaf, saya tidak dapat memproses pertanyaan tersebut sebagai query data tiket. Silakan tanyakan hal-hal seperti:\n\n• \"tampilkan site down hari ini\"\n• \"masalah power bulan ini\"\n• \"berapa total tiket minggu ini\"\n• \"site di NOP Surabaya yang bermasalah\"",
                'usage'  => $totalUsage,
            ]);
        }

        // Harus SELECT, hanya dari daftar_tiket, tidak boleh statement berbahaya, tidak multi-statement
        if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)
            || !preg_match('/\bFROM\s+daftar_tiket\b/i', $sqlQuery)
            || preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $sqlQuery)
            || preg_match('/\bINFORMATION_SCHEMA\b/i', $sqlQuery)
            || substr_count($sqlQuery, ';') > 0
        ) {
            Log::warning('Blocked non-SELECT/unsafe query (pre-save): ' . $sqlQuery);

            return response()->json([
                'answer' => 'Maaf, hanya query SELECT ke tabel daftar_tiket yang diizinkan.',
                'usage'  => $totalUsage,
            ]);
        }

        // Pastikan ada LIMIT
        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sqlQuery)) {
            $sqlQuery .= ' LIMIT 50';
        }

        // === SIMPAN KE TABEL REVIEW DAN LANGSUNG EKSEKUSI ===
        $riskLevel = $this->assessRisk($sqlQuery);
        $user = Auth::user();

        $review = AiQueryReview::create([
            'user_id'       => $user?->id,
            'provider'      => 'openai-gpt-4o',
            'user_question' => $question,
            'generated_sql' => $sqlQuery,
            'risk_level'    => $riskLevel,
            'status'        => 'executed', 
            'meta'          => [
                'source' => 'chatbot_monitoring',
            ],
        ]);

        try {
            // Force DISTINCT to avoid duplicate rows
            if (preg_match('/^\s*SELECT\s+/i', $sqlQuery) && !preg_match('/^\s*SELECT\s+DISTINCT\s+/i', $sqlQuery)) {
                $sqlQuery = preg_replace('/^\s*SELECT\s+/i', 'SELECT DISTINCT ', $sqlQuery);
            }

            Log::info('Executing SQL directly (GPT): ' . $sqlQuery);

            $results = DB::select($sqlQuery);
            
            $review->update([
                'reviewer_id'     => $user?->id,
                'reviewed_at'     => now(),
                'execution_result'=> json_encode($results),
            ]);

            if (empty($results)) {
                $answer = 'Saya sudah mencari di database, namun tidak ada data yang cocok dengan kriteria yang Anda berikan. Coba gunakan kata kunci yang berbeda.';
            } else {
                // Handle single aggregate result like COUNT(*)
                $first = (array)$results[0];
                if (array_key_exists('total', $first) && count($results) === 1 && count($first) === 1) {
                    return response()->json([
                        'answer' => (string)$first['total'],
                        'sql'    => $sqlQuery,
                        'usage'  => $totalUsage,
                    ]);
                }

                $answer = "Berikut adalah hasil pencarian data tiket:\n\n";

                $preferredOrder = [
                    'site_id', 'nop', 'suspect_problem', 'time_down', 'ticket_swfm'
                ];

                $maxShow = min(10, count($results));
                for ($i = 0; $i < $maxShow; $i++) {
                    $row = (array) $results[$i];
                    $num = $i + 1;
                    $lineItems = [];

                    foreach ($preferredOrder as $k) {
                        if (array_key_exists($k, $row)) {
                            $label = strtoupper($this->getColumnLabel($k));
                            $val = $row[$k] ?? '';
                            
                            if ($k === 'time_down' && is_numeric($val)) {
                                $unixTime = ($val - 25569) * 86400;
                                $val = gmdate("d M Y H i s", (int)$unixTime);
                            }
                            
                            $lineItems[] = "**{$label}**: {$val}";
                            unset($row[$k]);
                        }
                    }

                    unset($row['id'], $row['created_at'], $row['updated_at']);

                    foreach ($row as $key => $value) {
                        $label = strtoupper($this->getColumnLabel($key));
                        $lineItems[] = "**{$label}**: " . ($value ?? '');
                    }

                    $answer .= "{$num}. " . implode(' | ', $lineItems) . "\n\n";
                }

                if (count($results) > $maxShow) {
                    $answer .= '... dan ' . (count($results) - $maxShow) . " baris lainnya.\n";
                }
            }

            return response()->json([
                'answer' => $answer,
                'sql'    => $sqlQuery,
                'usage'  => $totalUsage,
            ]);
        } catch (\Exception $e) {
            Log::error('Database Query Error: ' . $e->getMessage() . ' | Failed Query: ' . $sqlQuery);
            
            $review->update([
                'error_message'=> $e->getMessage(),
            ]);

            return response()->json([
                'answer' => 'Maaf, terjadi kesalahan saat mengambil data dari database. Query yang dihasilkan mungkin tidak valid.',
                'sql'    => $sqlQuery,
                'usage'  => $totalUsage,
            ], 500);
        }
    }

    /**
     * Step 2: Eksekusi query yang sudah direview (human oversight)
     * Route contoh: POST /chat/execute/{id}
     */
    public function executeReview(Request $request, $id)
    {
        $user = Auth::user();
        $review = AiQueryReview::findOrFail($id);

        // Cek status
        if ($review->status === 'executed') {
            return response()->json([
                'answer' => 'Query ini sudah pernah dieksekusi.',
                'sql'    => $review->generated_sql,
            ]);
        }

        if ($review->status === 'rejected') {
            return response()->json([
                'answer' => 'Query ini sudah ditolak dan tidak dapat dieksekusi.',
            ], 400);
        }

        $sqlQuery = $review->generated_sql;

        // Double-check safety sebelum eksekusi
        if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)
            || !$this->isSafeSchema($sqlQuery)
            || preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $sqlQuery)
            || substr_count($sqlQuery, ';') > 0
        ) {
            Log::warning('Blocked non-SELECT/unsafe query at execute: ' . $sqlQuery);

            $review->update([
                'status'       => 'rejected',
                'reviewer_id'  => $user?->id,
                'reviewed_at'  => now(),
                'error_message'=> 'Unsafe query saat eksekusi',
            ]);

            return response()->json([
                'answer' => 'Maaf, query ini dianggap tidak aman dan diblokir saat eksekusi.',
            ], 400);
        }

        // Pastikan masih ada LIMIT
        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sqlQuery)) {
            $sqlQuery .= ' LIMIT 50';
        }

        try {
            // Force DISTINCT to avoid duplicate rows
            if (preg_match('/^\s*SELECT\s+/i', $sqlQuery) && !preg_match('/^\s*SELECT\s+DISTINCT\s+/i', $sqlQuery)) {
                $sqlQuery = preg_replace('/^\s*SELECT\s+/i', 'SELECT DISTINCT ', $sqlQuery);
            }

            Log::info('Executing SQL from review #' . $review->id . ': ' . $sqlQuery);

            $results = DB::select($sqlQuery);

            // Format jawaban sama seperti sebelumnya
            if (empty($results)) {
                $answer = 'Saya sudah mencari di database, namun tidak ada data yang cocok dengan kriteria yang Anda berikan. Coba gunakan kata kunci yang berbeda.';
            } else {
                $answer = "Berikut adalah hasil pencarian data tiket:\n\n";

                $preferredOrder = [
                    'site_id', 'nop', 'suspect_problem', 'time_down', 'ticket_swfm'
                ];

                $maxShow = min(10, count($results));
                $headerBuilt = false;

                for ($i = 0; $i < $maxShow; $i++) {
                    $row = (array) $results[$i];
                    $rowCols = [];

                    foreach ($preferredOrder as $k) {
                        if (array_key_exists($k, $row)) {
                            $val = $row[$k] ?? '';
                            
                            if ($k === 'time_down' && is_numeric($val)) {
                                $unixTime = ($val - 25569) * 86400;
                                $val = gmdate("d M Y H i s", (int)$unixTime);
                            }
                            
                            $rowCols[$k] = $val;
                            unset($row[$k]);
                        }
                    }

                    unset($row['id'], $row['created_at'], $row['updated_at']);

                    foreach ($row as $key => $value) {
                        $rowCols[$key] = $value;
                    }

                    if (!$headerBuilt) {
                        $headers = array_map(function($k) { return strtoupper($this->getColumnLabel($k)); }, array_keys($rowCols));
                        $answer .= "| " . implode(" | ", $headers) . " |\n";
                        $answer .= "| " . implode(" | ", array_fill(0, count($headers), "---")) . " |\n";
                        $headerBuilt = true;
                    }

                    $values = array_values($rowCols);
                    $values = array_map(function($v) { return str_replace('|', '&#124;', $v ?? ''); }, $values);
                    $answer .= "| " . implode(" | ", $values) . " |\n";
                }

                if (count($results) > $maxShow) {
                    $answer .= "\n*... dan " . (count($results) - $maxShow) . " baris lainnya.*\n";
                }
            }

            // Simpan hasil eksekusi di log tabel
            $review->update([
                'status'          => 'executed',
                'reviewer_id'     => $user?->id,
                'reviewed_at'     => now(),
                'execution_result'=> json_encode($results),
            ]);

            return response()->json([
                'answer'     => $answer,
                'sql'        => $sqlQuery,
                'review_id'  => $review->id,
                'risk_level' => $review->risk_level,
            ]);
        } catch (\Exception $e) {
            Log::error('Database Query Error: ' . $e->getMessage() . ' | Failed Query: ' . $sqlQuery);

            $review->update([
                'status'       => 'executed',
                'reviewer_id'  => $user?->id,
                'reviewed_at'  => now(),
                'error_message'=> $e->getMessage(),
            ]);

            return response()->json([
                'answer' => 'Maaf, terjadi kesalahan saat mengambil data dari database. Query yang dihasilkan mungkin tidak valid.',
                'sql'    => $sqlQuery,
            ], 500);
        }
    }

    private function getColumnLabel($columnName)
    {
        $labels = [
            'id'             => 'ID',
            'site_id'        => 'Site ID',
            'site_class'     => 'Kelas Site',
            'saverity'       => 'Tingkat Keparahan',
            'suspect_problem'=> 'Kategori',
        ];
    }
}