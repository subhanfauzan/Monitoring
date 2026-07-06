<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GeminiChatController;

class TestAiApisFormal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:ai-formal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test GPT and Gemini APIs against a set of FORMAL questions and export to CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting AI API Testing (Formal Questions)...');

        // Daftar 20 soal pengujian formal
        $questions = [
            'Berapa total site yang sedang down?',
            'Berapa jumlah site down dengan kategori Power?',
            'Berapa jumlah site down dengan kategori Transport?',
            'Berapa jumlah site down dengan kategori RAN?',
            'Tampilkan seluruh data site down di NOP Surabaya.',
            'Tampilkan seluruh data site down di NOP Madiun.',
            'Tampilkan seluruh data site down di NOP Lamongan.',
            'Tampilkan seluruh data site down di NOP Sidoarjo.',
            'Tampilkan seluruh data site down di NOP Jember.',
            'Tampilkan seluruh data site down di NOP Malang.',
            'Berapa jumlah site down dengan severity Critical?',
            'Berapa jumlah site down dengan severity Major?',
            'Berapa jumlah site down dengan severity Minor?',
            'Berapa jumlah site down dengan severity Low?',
            'Tampilkan site down dengan site class Diamond.',
            'Tampilkan site down dengan site class Platinum.',
            'Tampilkan site down dengan site class Gold.',
            'Tampilkan site down dengan site class Silver.',
            'Tampilkan site down dengan site class Bronze.',
            'Berapa jumlah tiket dengan status Open?'
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
            
            $responseGpt = $chatController->askQuestion($requestGpt);
            $waktuGpt = round(microtime(true) - $startGpt, 2);
            
            $dataGpt = $responseGpt->getData(true); 
            $tokenGpt = $dataGpt['usage']['total_tokens'] ?? 0;
            $promptGpt = $dataGpt['sql'] ?? ($dataGpt['answer'] ?? '');


            // === 2. TESTING GEMINI ===
            $requestGemini = Request::create('/api/chat/gemini', 'POST', ['question' => $pertanyaan]);
            $startGemini = microtime(true);
            
            $responseGemini = $geminiController->askQuestion($requestGemini);
            $waktuGemini = round(microtime(true) - $startGemini, 2);
            
            $dataGemini = $responseGemini->getData(true);
            $tokenGemini = $dataGemini['usage']['total_tokens'] ?? 0;
            $promptGemini = $dataGemini['sql'] ?? ($dataGemini['answer'] ?? '');

            // Simpan baris data
            $results[] = [
                'No' => $index + 1,
                'Pertanyaan Formal' => $pertanyaan,
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
        $csvPath = storage_path('app/hasil_pengujian_ai_formal.csv');
        $fp = fopen($csvPath, 'w');

        // Write header (disesuaikan dengan excel tabel formal)
        fputcsv($fp, [
            'No', 
            'Pertanyaan Formal', 
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
                $row['Pertanyaan Formal'],
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
    }
}
