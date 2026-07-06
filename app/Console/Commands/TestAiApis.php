<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeminiChatController;
use Illuminate\Support\Facades\Log;

class TestAiApis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ai-apis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test GPT and Gemini APIs against a set of questions and export to CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting AI API Testing...');

        // Daftar 20 soal pengujian non-formal sesuai gambar
        $questions = [
            'Coba hitung dulu berapa site yang lagi down ya.',
            'Ada berapa site yang down karena masalah power?',
            'Coba cek site down yang gangguannya transport ada berapa.',
            'Site yang down gara-gara RAN ada berapa ya?',
            'Tampilin site down yang ada di Surabaya dong.',
            'Coba munculin site down area Madiun.',
            'Ada data site down Lamongan nggak? tampilin ya.',
            'Bisa cek site down di Sidoarjo nggak?',
            'Tampilkan site yang lagi down di Jember ya.',
            'Coba cariin site down area Malang.',
            'Site down yang critical ada berapa?',
            'Berapa site down yang masuk major?',
            'Coba hitung site down minor.',
            'Site down yang low ada berapa ya?',
            'Tampilin site down kelas diamond.',
            'Ada site down platinum nggak? tampilkan.',
            'Coba tampilkan site down gold.',
            'List site down silver dong.',
            'Carikan site down bronze.',
            'Tiket yang masih open ada berapa?'
        ];

        $results = [];

        // Resolving the controllers so dependencies are injected automatically
        $chatController = app(ChatController::class);
        $geminiController = app(GeminiChatController::class);

        $bar = $this->output->createProgressBar(count($questions));
        $bar->start();

        foreach ($questions as $index => $pertanyaan) {

            // === 1. TESTING GPT ===
            $requestGpt = Request::create('/api/chat', 'POST', ['question' => $pertanyaan]);
            $startGpt = microtime(true);
            
            // Invoke the askQuestion method directly for GPT
            // Note: chat controller is returning a JsonResponse.
            $responseGpt = $chatController->askQuestion($requestGpt);
            $waktuGpt = round(microtime(true) - $startGpt, 2);
            
            // Parse response data
            $dataGpt = $responseGpt->getData(true); 
            $tokenGpt = $dataGpt['usage']['total_tokens'] ?? 0;
            // Dapatkan text output (SQL atau answer text)
            $promptGpt = $dataGpt['sql'] ?? ($dataGpt['answer'] ?? '');


            // === 2. TESTING GEMINI ===
            $requestGemini = Request::create('/api/chat/gemini', 'POST', ['question' => $pertanyaan]);
            $startGemini = microtime(true);
            
            // Invoke Gemini controller directly
            $responseGemini = $geminiController->askQuestion($requestGemini);
            $waktuGemini = round(microtime(true) - $startGemini, 2);
            
            // Parse response data
            $dataGemini = $responseGemini->getData(true);
            $tokenGemini = $dataGemini['usage']['total_tokens'] ?? 0;
            $promptGemini = $dataGemini['sql'] ?? ($dataGemini['answer'] ?? '');

            // Simpan baris data
            $results[] = [
                'No' => $index + 1,
                'Pertanyaan Non-Formal' => $pertanyaan,
                'Token ChatGPT' => $tokenGpt,
                'Waktu ChatGPT (detik)' => $waktuGpt,
                'Token Gemini' => $tokenGemini,
                'Waktu Gemini (detik)' => $waktuGemini,
                'Prompt GPT' => $promptGpt,
                'Prompt Gemini' => $promptGemini,
            ];

            // Tunda dikit supaya tidak limit rate API
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Testing complete! Exporting to CSV...');

        // === 3. EXPORT KE CSV ===
        $csvPath = storage_path('app/hasil_pengujian_ai.csv');
        $fp = fopen($csvPath, 'w');

        // Write header
        fputcsv($fp, [
            'No', 
            'Pertanyaan Non-Formal', 
            'Token ChatGPT', 
            'Waktu ChatGPT (detik)', 
            'Token Gemini', 
            'Waktu Gemini (detik)', 
            'Prompt GPT', 
            'Prompt Gemini'
        ]);

        // Write data rows
        foreach ($results as $row) {
            fputcsv($fp, [
                $row['No'],
                $row['Pertanyaan Non-Formal'],
                $row['Token ChatGPT'],
                $row['Waktu ChatGPT (detik)'],
                $row['Token Gemini'],
                $row['Waktu Gemini (detik)'],
                $row['Prompt GPT'],
                $row['Prompt Gemini'],
            ]);
        }

        fclose($fp);
        
        $this->info("Berhasil! File CSV tersimpan di: " . $csvPath);
        $this->line("Silakan buka file CSV tersebut dan copy paste isinya ke Excel Anda.");
    }
}
