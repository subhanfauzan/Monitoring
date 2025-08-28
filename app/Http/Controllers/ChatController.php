<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function askQuestion(Request $request)
    {
        $question = $request->input('question');

        // === PERBAIKAN VALIDASI INTENT (YA/TIDAK) ===
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
            $client = new Client();
            $validationResponse = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => ['Authorization' => 'Bearer ' . env('OPENAI_API_KEY')],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [['role' => 'system', 'content' => $validationSystem], ['role' => 'user', 'content' => $question]],
                    'temperature' => 0,
                    'max_tokens' => 5,
                ],
            ]);

            $validationData = json_decode($validationResponse->getBody()->getContents(), true);
            $isDataRelated = strtoupper(trim($validationData['choices'][0]['message']['content'] ?? 'TIDAK'));

            if ($isDataRelated !== 'YA') {
                return response()->json([
                    'answer' => "Maaf, saya adalah asisten khusus untuk monitoring jaringan dan data tiket. Saya hanya bisa membantu Anda dengan pertanyaan seputar:\n\n• Status site (down/up)\n• Masalah jaringan (power, telkom, dll)\n• Data lokasi dan NOP\n• Statistik tiket\n• Informasi tim FOP\n\nSilakan tanyakan sesuatu tentang data monitoring jaringan Anda.",
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Validation API Error: ' . $e->getMessage());
            // Jika validasi gagal, lanjutkan ke query (fallback)
        }

        // === PERBAIKAN PROMPT SQL: OUTPUT WAJIB JSON {"sql":"..."} ===
        $masterPrompt = <<<SYS
        PERAN: Anda ahli SQL. Ubah pertanyaan bahasa natural menjadi **SATU** query SQL mentah MySQL.
        Output **WAJIB** JSON valid persis: {"sql":"<QUERY>"} tanpa teks lain.

        SKEMA TABEL
        - Tabel: daftar_tiket
        - Kolom: id, site_id, site_class, savERity (ejaan sebenarnya di DB: "saverity"),
                 suspect_problem, time_down, status_site, tim_fop, remark, ticket_swfm,
                 nop, cluster_to, nossa, status_ticket, created_at, updated_at

        KETENTUAN WAJIB
        0) Selalu filter data yang belum terhapus: tambahkan kondisi deleted_at IS NULL.
        1) Hanya boleh SELECT dari tabel daftar_tiket (tanpa CTE, tanpa menulis ke tabel lain).
        2) Selalu tambahkan LIMIT 50 di akhir.
        3) Bandingkan string dengan LOWER(...).
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
          -> {"sql":"SELECT * FROM daftar_tiket WHERE LOWER(status_site)='down' AND DATE(created_at)=CURDATE() LIMIT 50"}
        - "masalah power bulan ini"
          -> {"sql":"SELECT * FROM daftar_tiket WHERE LOWER(suspect_problem) LIKE '%power%' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) LIMIT 50"}
        - "berapa banyak gangguan di NOP SURABAYA minggu ini"
          -> {"sql":"SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(nop) LIKE '%surabaya%' AND YEARWEEK(created_at)=YEARWEEK(CURDATE()) LIMIT 50"}

        Sekarang ubah pertanyaan berikut ke JSON {"sql": "..."}:
        SYS;

        // === PEMANGGILAN API UNTUK SQL + PARSING JSON ===
        $sqlQuery = '';
        try {
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => ['Authorization' => 'Bearer ' . env('OPENAI_API_KEY')],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [['role' => 'system', 'content' => $masterPrompt], ['role' => 'user', 'content' => $question]],
                    'temperature' => 0,
                    'max_tokens' => 500,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $raw = trim($data['choices'][0]['message']['content'] ?? '');

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
            return response()->json(['answer' => 'Maaf, terjadi kesalahan saat berkomunikasi dengan AI. Silakan coba lagi.']);
        }

        // === VALIDASI QUERY KETAT ===
        if (empty($sqlQuery) || strlen($sqlQuery) < 15 || strtoupper(trim($sqlQuery)) === 'INVALID') {
            return response()->json(['answer' => "Maaf, saya tidak dapat memproses pertanyaan tersebut sebagai query data tiket. Silakan tanyakan hal-hal seperti:\n\n• \"tampilkan site down hari ini\"\n• \"masalah power bulan ini\"\n• \"berapa total tiket minggu ini\"\n• \"site di NOP Surabaya yang bermasalah\""]);
        }

        // Hanya SELECT, hanya dari daftar_tiket, tidak boleh statement berbahaya, tidak multi-statement
        if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery) || !preg_match('/\bFROM\s+daftar_tiket\b/i', $sqlQuery) || preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $sqlQuery) || preg_match('/\bINFORMATION_SCHEMA\b/i', $sqlQuery) || substr_count($sqlQuery, ';') > 0) {
            Log::warning('Blocked non-SELECT/unsafe query: ' . $sqlQuery);
            return response()->json(['answer' => 'Maaf, hanya query SELECT ke tabel daftar_tiket yang diizinkan.']);
        }

        // Pastikan ada LIMIT
        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sqlQuery)) {
            $sqlQuery .= ' LIMIT 50';
        }

        // ⬇️ Inject filter soft delete bila belum ada
        if (!preg_match('/\bdeleted_at\s+IS\s+NULL\b/i', $sqlQuery)) {
            if (preg_match('/\bWHERE\b/i', $sqlQuery)) {
                // sisipkan "AND deleted_at IS NULL" sebelum LIMIT
                $sqlQuery = preg_replace('/\s+LIMIT\s+(\d+)\s*$/i', ' AND deleted_at IS NULL LIMIT $1', $sqlQuery);
            } else {
                // tambahkan WHERE baru sebelum LIMIT
                $sqlQuery = preg_replace('/\s+LIMIT\s+(\d+)\s*$/i', ' WHERE deleted_at IS NULL LIMIT $1', $sqlQuery);
            }
        }

        try {
            Log::info('Generated SQL Query: ' . $sqlQuery);
            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return response()->json(['answer' => 'Saya sudah mencari di database, namun tidak ada data yang cocok dengan kriteria yang Anda berikan. Coba gunakan kata kunci yang berbeda.']);
            }

            // === FORMAT OUTPUT: kolom prioritas + batasi 10 baris ===
            $formattedAnswer = "Berikut adalah hasil pencarian data tiket:\n\n";
            $formattedAnswer .= '📊 **Ditemukan ' . count($results) . " baris**\n\n";

            $preferredOrder = ['id', 'site_id', 'status_site', 'status_ticket', 'suspect_problem', 'site_class', 'saverity', 'nop', 'cluster_to', 'time_down', 'tim_fop', 'remark', 'ticket_swfm', 'created_at', 'updated_at'];

            $maxShow = min(10, count($results));
            for ($i = 0; $i < $maxShow; $i++) {
                $row = (array) $results[$i];
                $formattedAnswer .= '🎫 **Tiket ' . ($i + 1) . ":**\n";
                foreach ($preferredOrder as $k) {
                    if (array_key_exists($k, $row)) {
                        $label = $this->getColumnLabel($k);
                        $formattedAnswer .= "• $label: " . ($row[$k] ?? 'N/A') . "\n";
                        unset($row[$k]);
                    }
                }
                foreach ($row as $key => $value) {
                    $label = $this->getColumnLabel($key);
                    $formattedAnswer .= "• $label: " . ($value ?? 'N/A') . "\n";
                }
                $formattedAnswer .= "\n";
            }
            if (count($results) > $maxShow) {
                $formattedAnswer .= '... dan ' . (count($results) - $maxShow) . " baris lainnya.\n";
            }

            return response()->json(['answer' => $formattedAnswer]);
        } catch (\Exception $e) {
            Log::error('Database Query Error: ' . $e->getMessage() . ' | Failed Query: ' . $sqlQuery);
            return response()->json(['answer' => 'Maaf, terjadi kesalahan saat mengambil data dari database. Query yang dihasilkan mungkin tidak valid. Tim teknis sudah diberitahu untuk memperbaiki masalah ini.']);
        }
    }

    private function getColumnLabel($columnName)
    {
        $labels = [
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
        ];

        return $labels[$columnName] ?? ucfirst(str_replace('_', ' ', $columnName));
    }
}
