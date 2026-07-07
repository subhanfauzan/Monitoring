<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\GeminiService;

class GeminiChatController extends Controller
{
    public function __construct(private GeminiService $gemini) {}

    public function askQuestion(Request $request)
    {
        $request->validate(['question' => 'required|string|min:1']);
        $question = trim($request->input('question'));
        $usedLLM  = true; // SELALU pakai LLM

        // ====== Prompt ke LLM → JSON {"sql":"..."} ======
        $masterPrompt = <<<SYS
        PERAN: Anda adalah ahli SQL (MySQL).
        TUGAS: Ubah pertanyaan bahasa natural menjadi SATU query SQL mentah.
        OUTPUT WAJIB: JSON valid persis dengan format: {"sql": "<QUERY_SQL>"} tanpa teks awalan/akhiran apa pun.

        Tabel: daftar_tiket
        Kolom yang tersedia:
        id, site_id, site_class, savERity (di database bernama "saverity"),
        suspect_problem, time_down, status_site, tim_fop, remark, ticket_swfm,
        nop, cluster_to, nossa, status_ticket, created_at, updated_at

        ATURAN KETAT:
        1) HANYA gunakan query SELECT dari tabel daftar_tiket.
        2) Selalu tambahkan LIMIT 50 di akhir.
        3) JANGAN gunakan SELECT *. Secara default gunakan SELECT site_id, nop, suspect_problem, time_down, ticket_swfm kecuali user meminta kolom spesifik. Jika user meminta detail sebuah tiket, pastikan Anda menggunakan GROUP BY ticket_swfm agar tidak terjadi duplikasi.
        4) Bandingkan string dengan LOWER(...).
        5) Tanggal:
           - "hari ini"   -> DATE(created_at) = CURDATE()
           - "bulan ini"  -> MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
           - "minggu ini" -> YEARWEEK(created_at)=YEARWEEK(CURDATE())
        6) Statistik gunakan COUNT()/GROUP BY/ORDER BY sesuai konteks.
        7) Pemetaan istilah user -> kolom:
           - "severity" -> savERity (DB: "saverity")
           - "site class" -> site_class
           - "status tiket / ticket status" (open/close) -> status_ticket
           - "status tiket/workflow" (assigned, in progress, canceled, resolved, submitted, escalated)
               -> cari dengan LOWER(remark) (fallback), atau padankan ke LOWER(status_site) bila cocok.
        8) Kategori masalah (power, telkom, fiber, cell) -> cari pada LOWER(suspect_problem).
        9) Jika pertanyaan ambigu tapi relevan, pilih interpretasi umum dan batasi LIMIT 50.
        10) Jika tidak relevan/invalid -> kembalikan {"sql":""}.

        CONTOH
        - "site down hari ini"
          -> {"sql":"SELECT site_id, nop, suspect_problem, time_down, ticket_swfm FROM daftar_tiket WHERE LOWER(status_site)='down' AND DATE(created_at)=CURDATE() LIMIT 50"}
        - "masalah power bulan ini"
          -> {"sql":"SELECT site_id, nop, suspect_problem, time_down, ticket_swfm FROM daftar_tiket WHERE LOWER(suspect_problem) LIKE '%power%' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) LIMIT 50"}
        - "berapa banyak gangguan di NOP SURABAYA minggu ini"
          -> {"sql":"SELECT COUNT(*) AS total FROM daftar_tiket WHERE LOWER(nop) LIKE '%surabaya%' AND YEARWEEK(created_at)=YEARWEEK(CURDATE()) LIMIT 50"}
        - "detail tiket IM-20260706-00003437"
          -> {"sql":"SELECT site_id, nop, suspect_problem, time_down, ticket_swfm FROM daftar_tiket WHERE LOWER(ticket_swfm) = 'im-20260706-00003437' GROUP BY ticket_swfm LIMIT 50"}
        SYS;

        try {
            $json = $this->gemini->generateJson(
                $masterPrompt,
                "Pertanyaan:\n".$question."\n\nJawab hanya JSON:"
            );
            $sqlQuery = trim((string)($json['sql'] ?? ''));
            return $this->validateThenExecute($sqlQuery, $usedLLM);
        } catch (\Throwable $e) {
            Log::error('Gemini SQL Error: ' . $e->getMessage(), ['q' => $question]);
            return response()->json([
                'answer'   => 'Maaf, terjadi kesalahan saat berkomunikasi dengan AI (Gemini) untuk pembuatan SQL.',
                'dataset'  => ['columns'=>[], 'rows'=>[]],
                'sql'      => null,
                'llm_used' => $usedLLM,
                'usage'    => $this->gemini->getLastUsage(),
            ], 200);
        }
    }

    // ================== Eksekusi & Response ==================

    private function validateThenExecute(string $sqlQuery, bool $usedLLM)
    {
        if ($sqlQuery === '' || strlen($sqlQuery) < 15
            || !preg_match('/^\s*SELECT\s+/i', $sqlQuery)
            || !preg_match('/\bFROM\s+daftar_tiket\b/i', $sqlQuery)) {
            return response()->json([
                'answer'   => "Maaf, saya tidak dapat memproses pertanyaan tersebut sebagai query data tiket.",
                'dataset'  => ['columns'=>[], 'rows'=>[]],
                'sql'      => null,
                'llm_used' => $usedLLM,
                'usage'    => $this->gemini->getLastUsage(),
            ], 200);
        }

        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sqlQuery)) $sqlQuery .= ' LIMIT 50';

        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|ALTER|TRUNCATE|CREATE|REPLACE|GRANT|REVOKE)\b/i', $sqlQuery)
            || preg_match('/\bINFORMATION_SCHEMA\b/i', $sqlQuery)
            || substr_count($sqlQuery, ';') > 0) {
            Log::warning('Blocked unsafe SQL (Gemini): ' . $sqlQuery);
            return response()->json([
                'answer'   => 'Maaf, hanya query SELECT ke tabel daftar_tiket yang diizinkan.',
                'dataset'  => ['columns'=>[], 'rows'=>[]],
                'sql'      => $this->prettySql($sqlQuery),
                'llm_used' => $usedLLM,
                'usage'    => $this->gemini->getLastUsage(),
            ], 200);
        }

        return $this->executeAndRespond($sqlQuery, $usedLLM);
    }

    private function executeAndRespond(string $sqlQuery, bool $usedLLM)
    {
        // Force DISTINCT to avoid duplicate rows
        if (preg_match('/^\s*SELECT\s+/i', $sqlQuery) && !preg_match('/^\s*SELECT\s+DISTINCT\s+/i', $sqlQuery)) {
            $sqlQuery = preg_replace('/^\s*SELECT\s+/i', 'SELECT DISTINCT ', $sqlQuery);
        }

        try {
            Log::info('Generated SQL (Gemini): ' . $sqlQuery);
            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return response()->json([
                    'answer'   => 'Tidak ada data yang cocok dengan kriteria.',
                    'dataset'  => ['columns'=>[], 'rows'=>[]],
                    'sql'      => $this->prettySql($sqlQuery),
                    'llm_used' => $usedLLM,
                    'usage'    => $this->gemini->getLastUsage(),
                ], 200);
            }

            // Jika query COUNT(*) AS total
            $first = (array)$results[0];
            if (array_key_exists('total', $first) && count($results) === 1 && count($first) === 1) {
                return response()->json([
                    'answer'   => (string)$first['total'],
                    'dataset'  => ['columns'=>['total'], 'rows'=>[['total'=>$first['total']]]],
                    'sql'      => $this->prettySql($sqlQuery),
                    'llm_used' => $usedLLM,
                    'usage'    => $this->gemini->getLastUsage(),
                ], 200);
            }

            $preferredOrder = $this->preferredOrder();
            $out  = "Berikut adalah hasil pencarian data tiket:\n\n";

            $maxShow = min(10, count($results));
            for ($i = 0; $i < $maxShow; $i++) {
                $row = (array)$results[$i];
                $num = $i + 1;
                $lineItems = [];

                foreach ($preferredOrder as $k) {
                    if (array_key_exists($k, $row)) {
                        $label = strtoupper($this->label($k));
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
                foreach ($row as $k => $v) {
                    $label = strtoupper($this->label($k));
                    $lineItems[] = "**{$label}**: " . ($v ?? '');
                }

                $out .= "{$num}. " . implode(' | ', $lineItems) . "\n\n";
            }

            if (count($results) > $maxShow) {
                $out .= '... dan ' . (count($results) - $maxShow) . " baris lainnya.\n";
            }

            $dataset = $this->makeDataset($results, $preferredOrder);

            return response()->json([
                'answer'   => $out,
                'dataset'  => $dataset,
                'sql'      => $this->prettySql($sqlQuery),
                'llm_used' => $usedLLM,
                'usage'    => $this->gemini->getLastUsage(),
            ], 200);

        } catch (\Throwable $e) {
            Log::error('DB Error (Gemini): ' . $e->getMessage(), ['sql' => $sqlQuery]);
            return response()->json([
                'answer'   => 'Maaf, terjadi kesalahan saat mengambil data dari database.',
                'dataset'  => ['columns'=>[], 'rows'=>[]],
                'sql'      => $this->prettySql($sqlQuery),
                'llm_used' => $usedLLM,
                'usage'    => $this->gemini->getLastUsage(),
            ], 200);
        }
    }

    // ================== Util ==================
    private function label(string $column): string
    {
        return [
            'id'              => 'ID',
            'site_id'         => 'Site ID',
            'site_class'      => 'Kelas Site',
            'saverity'        => 'Tingkat Keparahan',
            'suspect_problem' => 'Kategori',
            'time_down'       => 'Time Down',
            'status_site'     => 'Status Site',
            'status_ticket'   => 'Status Ticket',
            'tim_fop'         => 'Tim FOP',
            'remark'          => 'Catatan',
            'ticket_swfm'     => 'Tiket',
            'nop'             => 'NOP',
            'cluster_to'      => 'Cluster',
            'nossa'           => 'Nossa',
            'created_at'      => 'Dibuat Pada',
            'updated_at'      => 'Diupdate Pada',
        ][$column] ?? ucfirst(str_replace('_', ' ', $column));
    }

    private function preferredOrder(): array
    {
        return [
            'site_id', 'nop', 'suspect_problem', 'time_down', 'ticket_swfm'
        ];
    }

    private function makeDataset(array $results, array $preferredOrder): array
    {
        $rowsArray = array_map(fn($r) => (array)$r, $results);
        if (!$rowsArray) return ['columns'=>[], 'rows'=>[]];

        $allCols = array_keys($rowsArray[0]);
        $ordered = [];
        foreach ($preferredOrder as $k) if (in_array($k, $allCols, true)) $ordered[] = $k;
        foreach ($allCols as $k) if (!in_array($k, $ordered, true)) $ordered[] = $k;

        $rowsOut = [];
        foreach ($rowsArray as $r) {
            $row = [];
            foreach ($ordered as $k) $row[$k] = $r[$k] ?? null;
            $rowsOut[] = $row;
        }
        return ['columns'=>$ordered, 'rows'=>$rowsOut];
    }

    // SQL one-line (tanpa <br>)
    private function prettySql(string $sql): string
    {
        $s = trim($sql);
        $s = preg_replace_callback(
            '/\b(select|from|where|and|or|on|join|inner|left|right|group|by|order|having|union|like|as|limit)\b/i',
            fn($m) => strtoupper($m[0]),
            $s
        );
        $s = preg_replace('/\s+/', ' ', $s);
        $s = preg_replace('/\s*,\s*/', ', ', $s);
        $s = preg_replace('/\s*\(\s*/', '(', $s);
        $s = preg_replace('/\s*\)\s*/', ')', $s);
        $s = trim($s);
        if ($s !== '' && !str_ends_with($s,';')) $s .= ';';
        return $s;
    }
}
