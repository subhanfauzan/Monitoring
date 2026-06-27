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
PERAN: Anda ahli SQL. Ubah pertanyaan bahasa natural menjadi **SATU** query SQL MySQL mentah.
Output **WAJIB** JSON valid persis: {"sql":"<QUERY>"} tanpa teks lain.

Tabel: daftar_tiket
Kolom: id, site_id, site_class, savERity (nama kolom: "saverity"),
       suspect_problem, time_down, status_site, tim_fop, remark, ticket_swfm,
       nop, cluster_to, nossa, status_ticket, created_at, updated_at

Aturan:
- Hanya SELECT dari daftar_tiket, akhiri LIMIT 50.
- Bandingkan string pakai LIKE .
- Tanggal: "hari ini"=DATE(created_at)=CURDATE();
  "bulan ini"=MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE());
  "minggu ini"=YEARWEEK(created_at)=YEARWEEK(CURDATE()).
- Mapping: severity→saverity; site class→site_class; status tiket→status_ticket (fallback remark bila perlu).
- Kategori power/telkom/fiber/cell → LIKE pada LOWER(suspect_problem).
- Kata kunci jumlah/total/berapa: gunakan COUNT(*) AS total.
- Jika ambigu tetapi relevan, pilih interpretasi paling umum dan tetap LIMIT 50.
- Jika tidak relevan, kembalikan {"sql":""}.
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

            // Daftar baris + dataset
            $preferredOrder = $this->preferredOrder();
            $out  = "Berikut adalah hasil pencarian data tiket:\n\n";

            $maxShow = min(10, count($results));
            for ($i = 0; $i < $maxShow; $i++) {
                $row = (array)$results[$i];
                foreach ($preferredOrder as $k) {
                    if (array_key_exists($k, $row)) {
                        $label = $this->label($k);
                        $val = $row[$k] ?? '';
                        if ($k === 'time_down' && is_numeric($val)) {
                            $unixTime = ($val - 25569) * 86400;
                            $val = gmdate("Y-m-d H:i:s", (int)$unixTime);
                        }
                        if ($k === 'site_id') {
                            $out .= " Site ID: " . $val . "\n";
                        } else {
                            $out .= '• ' . $label . ': ' . $val . "\n";
                        }
                        unset($row[$k]);
                    }
                }
                unset($row['id'], $row['created_at'], $row['updated_at']);
                foreach ($row as $k => $v) {
                    $out .= '• ' . $this->label($k) . ': ' . ($v ?? '') . "\n";
                }
                $out .= "\n";
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
            'suspect_problem' => 'Kategori Masalah',
            'time_down'       => 'Waktu Down',
            'status_site'     => 'Status Site',
            'status_ticket'   => 'Status Ticket',
            'tim_fop'         => 'Tim FOP',
            'remark'          => 'Catatan',
            'ticket_swfm'     => 'Tiket SWFM',
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
            'site_id','status_site','status_ticket','suspect_problem','site_class',
            'saverity','nop','cluster_to','time_down','tim_fop','remark','ticket_swfm',
            'nossa'
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
