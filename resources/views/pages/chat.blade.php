<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Chatbot Monitoring</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-3xl bg-white shadow-lg rounded-2xl p-6 space-y-6">
        <h1 class="text-2xl font-bold text-gray-800">Chatbot Monitoring</h1>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Provider</label>
            <select id="provider"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="chatgpt">ChatGPT (OpenAI)</option>
                <option value="gemini">Gemini</option>
            </select>
            <p id="modelHint" class="text-sm text-gray-500 mt-1">model: gpt-4o / gemini-1.5-*</p>
        </div>

        <div>
            <label for="question" class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan</label>
            <textarea id="question" rows="4"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="contoh: tampilkan site down hari ini"></textarea>
        </div>

        <div class="flex justify-between">
            <button id="sendBtn"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none">Kirim</button>
            <button id="clearBtn"
                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 focus:outline-none">Bersihkan</button>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Hasil</h2>
                <span id="lastProvider" class="px-2 py-1 text-sm rounded bg-gray-200 text-gray-700">-</span>
            </div>
            <p id="answer" class="mt-3 text-gray-700 whitespace-pre-wrap">–</p>
            <div id="sqlWrapper" class="mt-4">
                <h3 class="text-sm font-semibold text-gray-700">SQL</h3>
                <pre id="sqlBox" class="mt-2 p-3 rounded bg-gray-100 text-sm text-gray-800 whitespace-pre-wrap hidden">(kosong)</pre>
            </div>

        </div>

        <div class="bg-gray-50 rounded-lg p-4" hidden>
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">Debug</h2>
                <button id="toggleDebug"
                    class="text-sm px-3 py-1 rounded bg-gray-200 hover:bg-gray-300">Tampilkan/Sembunyikan</button>
            </div>
            <pre id="debugBox" class="mt-3 text-sm text-gray-600 hidden">(kosong)</pre>
        </div>
    </div>

    <script>
        const $ = (q) => document.querySelector(q);
        const providerEl = $('#provider');
        const questionEl = $('#question');
        const sendBtn = $('#sendBtn');
        const clearBtn = $('#clearBtn');
        const answerEl = $('#answer');
        const lastProviderEl = $('#lastProvider');
        const debugBox = $('#debugBox');
        const toggleDebugBtn = $('#toggleDebug');
        const sqlBox = $('#sqlBox');

        // URL aman dari route() (hindari typo path)
        const URLS = {
            chat: @json(route('chat.ask')),
            gemini: @json(route('gemini.ask')),
        };

        providerEl.addEventListener('change', () => {
            $('#modelHint').textContent =
                providerEl.value === 'gemini' ?
                'model: gemini-1.5-flash/pro' :
                'model: gpt-4o';
        });

        toggleDebugBtn.addEventListener('click', () => {
            debugBox.classList.toggle('hidden');
        });

        clearBtn.addEventListener('click', () => {
            questionEl.value = '';
            answerEl.textContent = '–';
            debugBox.textContent = '(kosong)';
            lastProviderEl.textContent = '-';
            sqlBox.textContent = '(kosong)';
            sqlBox.classList.add('hidden');
        });

        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        async function postJSON(url, payload) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin', // kirim cookie sesi utk verifikasi CSRF
                body: JSON.stringify(payload),
            });

            const raw = await resp.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                data = {
                    raw
                };
            }

            return {
                ok: resp.ok,
                status: resp.status,
                data
            };
        }

        sendBtn.addEventListener('click', async () => {
            const question = questionEl.value.trim();
            if (!question) return alert('Pertanyaan tidak boleh kosong');

            const provider = providerEl.value;
            const url = provider === 'gemini' ? URLS.gemini : URLS.chat;

            sendBtn.disabled = true;
            sendBtn.textContent = 'Memproses...';
            lastProviderEl.textContent = provider.toUpperCase();
            answerEl.textContent = '…';

            try {
                const {
                    ok,
                    status,
                    data
                } = await postJSON(url, {
                    question
                });
                if (!ok) {
                    const msg = data?.message || data?.error || data?.raw || 'Terjadi kesalahan';
                    answerEl.textContent = `Error ${status}: ${msg}`;
                    // Tampilkan SQL jika server masih mengirimkannya bersama error
                    const sqlText = data?.sql ?? data?.query ?? null;
                    if (sqlText) {
                        sqlBox.textContent = sqlText;
                        sqlBox.classList.remove('hidden');
                    } else {
                        sqlBox.textContent = '(tidak ada SQL)';
                        sqlBox.classList.remove('hidden');
                    }
                } else {
                    answerEl.textContent = data?.answer ?? '(tidak ada jawaban)';
                    // coba beberapa properti umum untuk SQL: sql / query / sql_query
                    const sqlText = data?.sql ?? data?.query ?? data?.sql_query ?? null;
                    if (sqlText) {
                        sqlBox.textContent = sqlText;
                        sqlBox.classList.remove('hidden');
                    } else {
                        // jika Anda ingin menyembunyikan saat tidak ada SQL, uncomment baris berikut:
                        // sqlBox.classList.add('hidden');
                        // pilihan: tampilkan pesan jelas bahwa tidak ada SQL
                        sqlBox.textContent = '(tidak ada SQL)';
                        sqlBox.classList.remove('hidden');
                    }
                }
                debugBox.textContent = JSON.stringify({
                    status,
                    data
                }, null, 2);
            } catch (e) {
                answerEl.textContent = 'Error: ' + e.message;
                debugBox.textContent = String(e);
                sqlBox.textContent = '(tidak ada SQL karena error)';
                sqlBox.classList.remove('hidden');
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Kirim';
            }
        });

        // Ctrl+Enter kirim
        questionEl.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') sendBtn.click();
        });
    </script>
</body>

</html>
