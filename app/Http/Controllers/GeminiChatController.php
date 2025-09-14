<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Services\GeminiService;

class GeminiChatController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    public function askQuestion(Request $request)
    {
        $request->validate(['question' => 'required|string|min:1']);
        $question = trim($request->input('question'));

        // ========== UTIL ==========
        $normalizeYesNo = function (?string $txt): string {
            $t = preg_replace('/[^A-Z]/u', '', mb_strtoupper((string) $txt, 'UTF-8'));
            if (str_starts_with($t, 'YA')) {
                return 'YA';
            }
            if (str_starts_with($t, 'TIDAK')) {
                return 'TIDAK';
            }
            return $t ?: 'TIDAK';
        };
        $stripFences = function (string $raw): string {
            $raw = preg_replace('/```[a-zA-Z]*\s*/', '', $raw);
            $raw = str_replace('```', '', $raw);
            return trim($raw);
        };

        // ========== 1) VALIDASI INTENT (pakai LLM + bias heuristik) ==========
        $monitoringKeywords = ['site', 'down', 'up', 'tiket', 'ticket', 'gangguan', 'power', 'telkom', 'fiber', 'fop', 'nop', 'cluster', 'severity', 'saverity', 'site class', 'status', 'nossa', 'berapa', 'jumlah', 'total', 'statistik', 'minggu', 'bulan', 'hari'];
        $hit = false;
        foreach ($monitoringKeywords as $kw) {
            if (stripos($question, $kw) !== false) {
                $hit = true;
                break;
            }
        }
        $isDataRelated = $hit ? 'YA' : 'TIDAK';

        if (!$hit) {
            $validationSystem = <<<SYS
            Anda adalah *router* intent yang hanya menjawab "YA" atau "TIDAK".
            Tentukan apakah pertanyaan berkaitan monitoring jaringan/tiket:
            • site down/up, jumlah/total gangguan
            • power/telkom/fiber/cell
            • NOP/cluster/lokasi/nossa
            • statistik tiket (hari/minggu/bulan)
            • FOP, severity/saverity, site_class, status_ticket/status_site
            Selain itu "TIDAK".
            SYS;
            try {
                $prompt = $validationSystem . "\n\nPertanyaan:\n" . $question . "\n\nJawab:";
                $raw = $this->gemini->generateText($prompt, 'gemini-1.5-flash');
                $isDataRelated = $normalizeYesNo($raw);
                Log::info('Intent check', ['raw' => $raw, 'norm' => $isDataRelated]);
            } catch (\Throwable $e) {
                Log::error('Gemini Validation Error: ' . $e->getMessage());
                $isDataRelated = 'YA'; // fallback agar tidak buntu
            }
        }

        if ($isDataRelated !== 'YA') {
            return response()->json([
                'answer' => "Maaf, saya asisten khusus monitoring jaringan & data tiket. Saya bisa bantu:
• Status site (down/up)
• Masalah jaringan (power/telkom/fiber)
• Data NOP/cluster/lokasi/nossa
• Statistik tiket (hari ini/minggu ini/bulan ini)
• Tim FOP, severity/saverity, site class",
            ]);
        }

        $askTotalDown = preg_match('/\b(berapa|jumlah|total)\b.*\bsite\b.*\bdown\b/i', $question);
        $hasQualifier = preg_match('/\b(nop|cluster|site\s*class|severity|saverity|power|telkom|fiber|cell|hari|minggu|bulan)\b/i', $question);

        if ($askTotalDown && !$hasQualifier) {
            $sqlQuery = "SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(status_site)='down' LIMIT 50";
        } else {
            $masterPrompt = <<<SYS
            PERAN: Anda ahli SQL. Ubah pertanyaan bahasa natural menjadi **SATU** query SQL MySQL mentah.
            Output **WAJIB** hanya JSON valid: {"sql":"<QUERY>"} tanpa teks lain.

            Tabel: daftar_tiket
            Kolom: id, site_id, site_class, savERity (nama kolom: "saverity"),
                   suspect_problem, time_down, status_site, tim_fop, remark, ticket_swfm,
                   nop, cluster_to, nossa, status_ticket, created_at, updated_at

            Aturan:
            - Hanya SELECT dari daftar_tiket, akhiri LIMIT 50.
            - Bandingkan string pakai LOWER(...).
            - Tanggal: "hari ini"=DATE(created_at)=CURDATE(); "bulan ini"=MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE());
              "minggu ini"=YEARWEEK(created_at)=YEARWEEK(CURDATE()).
            - Mapping: severity→saverity; site class→site_class; status tiket→status_ticket (fallback remark);
              power/telkom/fiber/cell→LIKE pada LOWER(suspect_problem).
            - Kata kunci jumlah/total/berapa: gunakan COUNT(*) AS total.
            - Jika ambigu tetapi relevan, pilih interpretasi paling umum dan tetap LIMIT 50.
            - Jika tidak relevan, kembalikan {"sql":""}.

            Contoh:
            - "berapa total site down?" , "berapa site down dengan NOP Surabaya?"
              -> {"sql":"SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(status_site)='down' LIMIT 50", "sql":"SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(status_site)='down' AND LOWER(nop) LIKE '%surabaya%' LIMIT 50"}
            SYS;

            try {
                $sqlPrompt = $masterPrompt . "\n\nPertanyaan:\n" . $question . "\n\nJawab hanya JSON:";
                $raw = (string) $this->gemini->generateText($sqlPrompt, 'gemini-1.5-pro');
                $raw = $stripFences($raw);
                if (!str_starts_with(trim($raw), '{') && preg_match('/\{.*\}/s', $raw, $m)) {
                    $raw = $m[0];
                }
                Log::info('RAW Gemini SQL JSON(clean): ' . $raw);

                $decoded = json_decode($raw, true);
                $sqlQuery = is_array($decoded) && isset($decoded['sql']) ? trim((string) $decoded['sql']) : '';

                if ($sqlQuery === '' && stripos($raw, 'select ') !== false) {
                    $sqlQuery = trim(preg_replace('/^```[a-z]*|```$/i', '', $raw));
                }
            } catch (\Throwable $e) {
                Log::error('Gemini SQL Error: ' . $e->getMessage());
                return response()->json(
                    [
                        'answer' => 'Maaf, terjadi kesalahan saat berkomunikasi dengan AI (Gemini) untuk pembuatan SQL.',
                        'debug' => app()->isProduction() ? null : $e->getMessage(),
                    ],
                    200,
                );
            }
        }

        // ========== 3) VALIDASI SQL & soft-delete ==========
        if ($sqlQuery === '' || strlen($sqlQuery) < 15 || !preg_match('/^\s*SELECT\s+/i', $sqlQuery) || !preg_match('/\bFROM\s+daftar_tiket\b/i', $sqlQuery)) {
            return response()->json(
                [
                    'answer' => "Maaf, saya tidak dapat memproses pertanyaan tersebut sebagai query data tiket. Contoh yang bisa:
• \"tampilkan site down hari ini\"
• \"masalah power bulan ini\"
• \"berapa total tiket minggu ini\"
• \"site di NOP Surabaya yang bermasalah\"",
                ],
                200,
            );
        }

        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sqlQuery)) {
            $sqlQuery .= ' LIMIT 50';
        }

        $hasDeletedAt = Schema::hasColumn('daftar_tiket', 'deleted_at');
        if ($hasDeletedAt && !preg_match('/\bdeleted_at\s+IS\s+NULL\b/i', $sqlQuery)) {
            if (preg_match('/\bWHERE\b/i', $sqlQuery)) {
                $sqlQuery = preg_replace('/\s+LIMIT\s+(\d+)\s*$/i', ' AND deleted_at IS NULL LIMIT $1', $sqlQuery);
            } else {
                $sqlQuery = preg_replace('/\s+LIMIT\s+(\d+)\s*$/i', ' WHERE deleted_at IS NULL LIMIT $1', $sqlQuery);
            }
        }

        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $sqlQuery) || preg_match('/\bINFORMATION_SCHEMA\b/i', $sqlQuery) || substr_count($sqlQuery, ';') > 0) {
            Log::warning('Blocked unsafe SQL (Gemini): ' . $sqlQuery);
            return response()->json(['answer' => 'Maaf, hanya query SELECT ke tabel daftar_tiket yang diizinkan.'], 200);
        }

        // ========== 4) EKSEKUSI ==========
        try {
            Log::info('Generated SQL (Gemini): ' . $sqlQuery);
            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return response()->json(['answer' => 'Tidak ada data yang cocok dengan kriteria. Coba kata kunci lain.'], 200);
            }

            // Jika query COUNT(*) AS total → kirim angka saja (cocok buat UI kamu)
            $first = (array) $results[0];
            if (array_key_exists('total', $first) && count($results) === 1 && count($first) === 1) {
                return response()->json(['answer' => (string) $first['total']], 200);
            }

            $preferredOrder = ['id', 'site_id', 'status_site', 'status_ticket', 'suspect_problem', 'site_class', 'saverity', 'nop', 'cluster_to', 'time_down', 'tim_fop', 'remark', 'ticket_swfm', 'created_at', 'updated_at'];

            $out = "Berikut adalah hasil pencarian data tiket:\n\n";
            $out .= 'Ditemukan ' . count($results) . " baris\n\n";

            $maxShow = min(10, count($results));
            for ($i = 0; $i < $maxShow; $i++) {
                $row = (array) $results[$i];
                $out .= 'Tiket ' . ($i + 1) . ":\n";
                foreach ($preferredOrder as $k) {
                    if (array_key_exists($k, $row)) {
                        $out .= '• ' . $this->label($k) . ': ' . ($row[$k] ?? 'N/A') . "\n";
                        unset($row[$k]);
                    }
                }
                foreach ($row as $k => $v) {
                    $out .= '• ' . $this->label($k) . ': ' . ($v ?? 'N/A') . "\n";
                }
                $out .= "\n";
            }
            if (count($results) > $maxShow) {
                $out .= '... dan ' . (count($results) - $maxShow) . " baris lainnya.\n";
            }

            return response()->json(['answer' => $out], 200);
        } catch (\Throwable $e) {
            Log::error('DB Error (Gemini): ' . $e->getMessage(), ['sql' => $sqlQuery]);
            return response()->json(
                [
                    'answer' => 'Maaf, terjadi kesalahan saat mengambil data dari database.',
                    'debug' => app()->isProduction() ? null : $e->getMessage(),
                    'sql' => app()->isProduction() ? null : $sqlQuery,
                ],
                200,
            );
        }
    }

    private function label(string $column): string
    {
        return [
            'id' => 'ID',
            'site_id' => 'Site ID',
            'site_class' => 'Kelas Site',
            'saverity' => 'Tingkat Keparahan',
            'suspect_problem' => 'Kategori Masalah',
            'time_down' => 'Waktu Down',
            'status_site' => 'Status Site',
            'status_ticket' => 'Status Ticket',
            'tim_fop' => 'Tim FOP',
            'remark' => 'Catatan',
            'ticket_swfm' => 'Tiket SWFM',
            'nop' => 'NOP',
            'cluster_to' => 'Cluster',
            'nossa' => 'Nossa',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diupdate Pada',
        ][$column] ?? ucfirst(str_replace('_', ' ', $column));
    }
}
