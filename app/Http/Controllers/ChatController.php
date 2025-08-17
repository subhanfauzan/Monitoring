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

        // PERBAIKAN 1: Validasi apakah pertanyaan berkaitan dengan data tiket
        $validationPrompt = "
Anda adalah asisten yang membantu menentukan apakah sebuah pertanyaan berkaitan dengan data monitoring jaringan/tiket atau bukan.

Pertanyaan yang BERKAITAN dengan data tiket contohnya:
- Pertanyaan tentang site down/up
- Pertanyaan tentang masalah power/telkom
- Pertanyaan tentang lokasi/NOP/cluster
- Pertanyaan tentang jumlah/statistik tiket
- Pertanyaan tentang tim FOP
- Pertanyaan tentang status jaringan

Pertanyaan yang TIDAK BERKAITAN contohnya:
- Pertanyaan tentang programming (Laravel, PHP, dll)
- Pertanyaan umum (cuaca, politik, dll)
- Pertanyaan pribadi
- Definisi teknologi yang tidak berkaitan monitoring

Jawab dengan 'YA' jika pertanyaan berkaitan dengan data tiket/monitoring, atau 'TIDAK' jika tidak berkaitan.

Pertanyaan: \"$question\"
";

        try {
            $client = new Client();
            $validationResponse = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => ['Authorization' => 'Bearer ' . env('OPENAI_API_KEY')],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [['role' => 'system', 'content' => $validationPrompt]],
                    'temperature' => 0,
                ],
            ]);

            $validationData = json_decode($validationResponse->getBody()->getContents(), true);
            $isDataRelated = trim($validationData['choices'][0]['message']['content']);

            // Jika pertanyaan tidak berkaitan dengan data tiket, berikan respons umum
            if (strtoupper($isDataRelated) === 'TIDAK') {
                return response()->json([
                    'answer' => 'Maaf, saya adalah asisten khusus untuk monitoring jaringan dan data tiket. Saya hanya bisa membantu Anda dengan pertanyaan seputar:\n\n• Status site (down/up)\n• Masalah jaringan (power, telkom, dll)\n• Data lokasi dan NOP\n• Statistik tiket\n• Informasi tim FOP\n\nSilakan tanyakan sesuatu tentang data monitoring jaringan Anda.',
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Validation API Error: ' . $e->getMessage());
            // Jika validasi gagal, lanjutkan ke query (fallback)
        }

        // PERBAIKAN 2: Gunakan struktur tabel yang benar sesuai dengan model Tiket
        $masterPrompt = "
# PERAN DAN TUJUAN
Anda adalah seorang ahli SQL yang bertugas untuk membuat query SQL mentah (raw SQL query) untuk digunakan dalam aplikasi Laravel. Anda akan mengubah pertanyaan bahasa natural menjadi **satu query SQL tunggal** yang siap pakai.

# KONTEKS TABEL DATABASE
Nama Tabel: `daftar_tiket`
Kolom yang tersedia:
- `id` (integer): Primary key auto increment
- `site_id` (string): Kode unik site
- `site_class` (string): Kelas site (contoh: Silver, Gold, Platinum)
- `saverity` (string): Tingkat keparahan (contoh: Low, Medium, High, Critical)
- `suspect_problem` (string): Kategori masalah (contoh: 'POWER', 'TELKOM')
- `time_down` (string/numeric): Waktu downtime
- `status_site` (string): Status site (contoh: 'Down', 'Up')
- `tim_fop` (string): Tim yang menangani
- `remark` (string): Catatan tambahan
- `ticket_swfm` (string): Nomor tiket SWFM
- `nop` (string): Nama Operator Pendukung atau wilayah (contoh: 'NOP JEMBER', 'NOP SURABAYA')
- `cluster_to` (string): Nama cluster jaringan (contoh: 'TO JEMBER')
- `nossa` (string): Informasi Nossa
- `created_at` (timestamp): Waktu tiket dibuat
- `updated_at` (timestamp): Waktu tiket diupdate

# ATURAN OUTPUT
1. **HANYA KODE**: Berikan HANYA kode query SQL mentah tanpa penjelasan, backticks, atau format markdown.
2. **GUNAKAN KOLOM YANG BENAR**: Pastikan hanya menggunakan kolom yang ada di tabel `daftar_tiket`.
3. **CASE-INSENSITIVE**: Untuk perbandingan string, gunakan `LOWER()` untuk pencarian tidak sensitif huruf besar/kecil.
4. **OPERASI TANGGAL**:
   - \"Hari ini\": `DATE(created_at) = CURDATE()`
   - \"Bulan ini\": `MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())`
   - \"Minggu ini\": `YEARWEEK(created_at) = YEARWEEK(CURDATE())`
5. **GABUNGKAN KONDISI**: Jika ada beberapa kriteria, gunakan `AND` atau `OR` sesuai konteks.
6. **UNTUK ANALISIS**: Gunakan `COUNT()`, `GROUP BY`, dan `ORDER BY` untuk pertanyaan statistik.
7. **LIMIT HASIL**: Tambahkan `LIMIT 50` untuk mencegah terlalu banyak data.
8. **JIKA TIDAK RELEVAN**: Jika pertanyaan tidak bisa diubah menjadi query yang masuk akal, kembalikan string kosong atau 'INVALID'.

# CONTOH QUERY:
- \"site down hari ini\": `SELECT * FROM daftar_tiket WHERE LOWER(status_site) = 'down' AND DATE(created_at) = CURDATE() LIMIT 50`
- \"masalah power\": `SELECT * FROM daftar_tiket WHERE LOWER(suspect_problem) LIKE '%power%' LIMIT 50`
- \"site di NOP Jember\": `SELECT * FROM daftar_tiket WHERE LOWER(nop) LIKE '%jember%' LIMIT 50`

---

# PERMINTAAN
Buatkan query SQL untuk permintaan berikut:
\"$question\"
";

        $sqlQuery = '';
        try {
            $client = new Client();
            $response = $client->post('https://api.openai.com/v1/chat/completions', [
                'headers' => ['Authorization' => 'Bearer ' . env('OPENAI_API_KEY')],
                'json' => [
                    'model' => 'gpt-4o',
                    'messages' => [['role' => 'system', 'content' => $masterPrompt]],
                    'temperature' => 0,
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $sqlQuery = trim($data['choices'][0]['message']['content']);

            // Bersihkan query dari format markdown atau karakter tidak perlu
            $sqlQuery = preg_replace('/```sql\s*/', '', $sqlQuery);
            $sqlQuery = preg_replace('/```\s*/', '', $sqlQuery);
            $sqlQuery = trim($sqlQuery, " \t\n\r\0\x0B`;'");
        } catch (\Exception $e) {
            Log::error('OpenAI API Error: ' . $e->getMessage());
            return response()->json(['answer' => 'Maaf, terjadi kesalahan saat berkomunikasi dengan AI. Silakan coba lagi.']);
        }

        // Validasi query - perbaiki logika untuk mendeteksi query yang tidak valid
        if (empty($sqlQuery) || strlen($sqlQuery) < 15 || strtoupper(trim($sqlQuery)) === 'INVALID') {
            return response()->json(['answer' => 'Maaf, saya tidak dapat memproses pertanyaan tersebut sebagai query data tiket. Silakan tanyakan hal-hal seperti:\n\n• "tampilkan site down hari ini"\n• "masalah power bulan ini"\n• "berapa total tiket minggu ini"\n• "site di NOP Jakarta yang bermasalah"']);
        }

        try {
            Log::info('Generated SQL Query: ' . $sqlQuery);

            // Validasi query untuk keamanan - pastikan hanya SELECT
            if (!preg_match('/^\s*SELECT\s+/i', $sqlQuery)) {
                Log::warning('Non-SELECT query attempted: ' . $sqlQuery);
                return response()->json(['answer' => 'Maaf, hanya query pencarian data yang diizinkan.']);
            }

            $results = DB::select($sqlQuery);

            if (empty($results)) {
                return response()->json(['answer' => 'Saya sudah mencari di database, namun tidak ada data yang cocok dengan kriteria yang Anda berikan. Coba gunakan kata kunci yang berbeda.']);
            }

            // Format jawaban dengan lebih baik
            $formattedAnswer = "Berikut adalah hasil pencarian data tiket:\n\n";
            $formattedAnswer .= '📊 **Ditemukan ' . count($results) . " data tiket**\n\n";

            foreach ($results as $index => $row) {
                if ($index >= 10) {
                    // Batasi tampilan maksimal 10 data untuk readability
                    $formattedAnswer .= '... dan ' . (count($results) - 10) . " data lainnya.\n";
                    break;
                }

                $formattedAnswer .= '🎫 **Tiket ' . ($index + 1) . ":**\n";
                foreach ($row as $key => $value) {
                    $label = $this->getColumnLabel($key);
                    $formattedAnswer .= "• $label: " . ($value ?? 'N/A') . "\n";
                }
                $formattedAnswer .= "\n";
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
