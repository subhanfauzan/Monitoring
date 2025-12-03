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

    <!-- ========================================================= -->
    <!-- 🟦 MODAL HUMAN OVERSIGHT -->
    <!-- ========================================================= -->
    <div id="sqlModal"
         class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-xl p-5 space-y-4">

            <div class="flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">Review Query</h2>
                <button id="modalClose" class="text-gray-400 hover:text-gray-600 text-sm">✕</button>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Pertanyaan:</p>
                <p id="modalQuestion" class="text-sm text-gray-800"></p>
            </div>

            <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                    <span class="text-gray-500">Risk level:</span>
                    <span id="modalRiskBadge"
                          class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-gray-200 text-gray-700">
                        -
                    </span>
                </div>
                <p class="text-gray-500">
                    Query tidak dijalankan sebelum Anda klik <span class="font-semibold">Jalankan</span>.
                </p>
            </div>

            <div>
                <p class="text-xs text-gray-500 mb-1">Preview SQL:</p>
                <pre id="modalSql"
                     class="mt-1 p-3 rounded bg-gray-100 text-xs text-gray-800 whitespace-pre-wrap max-h-64 overflow-y-auto"></pre>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-900">
                <p class="font-semibold mb-1">Checklist:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Hanya SELECT dari <code>daftar_tiket</code>.</li>
                    <li>WHERE sesuai kebutuhan.</li>
                    <li>Ada LIMIT (default 50).</li>
                </ul>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button id="modalCancel"
                        class="px-3 py-1.5 text-xs rounded-lg border border-gray-300 hover:bg-gray-100">
                    Batal
                </button>
                <button id="modalExecute"
                        class="px-4 py-1.5 text-xs rounded-lg font-medium bg-emerald-600 text-white hover:bg-emerald-700 disabled:opacity-60">
                    Jalankan Query Ini
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 🟦 JAVASCRIPT -->
    <!-- ========================================================= -->
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

        /* === HUMAN OVERSIGHT ELEMENTS === */
        const sqlModal       = $('#sqlModal');
        const modalQuestion  = $('#modalQuestion');
        const modalSql       = $('#modalSql');
        const modalRiskBadge = $('#modalRiskBadge');
        const modalClose     = $('#modalClose');
        const modalCancel    = $('#modalCancel');
        const modalExecute   = $('#modalExecute');
        let currentReviewId  = null;

        /* === ROUTES === */
        const URLS = {
            chat: @json(route('chat.ask')),
            gemini: @json(route('gemini.ask')),
            executeBase: @json(url('/chat/execute')),
        };

        function openModal() {
            sqlModal.classList.remove('hidden');
            sqlModal.classList.add('flex');
        }

        function closeModal() {
            sqlModal.classList.add('hidden');
            sqlModal.classList.remove('flex');
            currentReviewId = null;
        }

        modalClose.addEventListener('click', closeModal);
        modalCancel.addEventListener('click', closeModal);

        function setRiskBadge(level) {
            modalRiskBadge.textContent = level;

            modalRiskBadge.className =
                "inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium";

            if (level === 'low') {
                modalRiskBadge.classList.add('bg-green-100', 'text-green-700');
            } else if (level === 'medium') {
                modalRiskBadge.classList.add('bg-yellow-100', 'text-yellow-700');
            } else if (level === 'high') {
                modalRiskBadge.classList.add('bg-red-100', 'text-red-700');
            } else {
                modalRiskBadge.classList.add('bg-gray-200', 'text-gray-700');
            }
        }

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
                credentials: 'same-origin',
                body: JSON.stringify(payload),
            });

            const raw = await resp.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch {
                data = { raw };
            }

            return { ok: resp.ok, status: resp.status, data };
        }

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

        /* ===================================================== */
        /* 🟧 STEP 1: Generate SQL → tampilkan modal review       */
        /* ===================================================== */
        sendBtn.addEventListener('click', async () => {
            const question = questionEl.value.trim();
            if (!question) return alert('Pertanyaan tidak boleh kosong');

            const provider = providerEl.value;
            const url = provider === 'gemini' ? URLS.gemini : URLS.chat;

            sendBtn.disabled = true;
            sendBtn.textContent = 'Memproses...';
            answerEl.textContent = '…';
            lastProviderEl.textContent = provider.toUpperCase();

            try {
                const { ok, status, data } = await postJSON(url, { question });

                if (!ok) {
                    answerEl.textContent = data?.message || "Terjadi error";
                    return;
                }

                // Jika AI menolak pertanyaan (bukan data monitoring)
                if (data.answer && !data.sql_preview) {
                    answerEl.textContent = data.answer;
                    sqlBox.classList.remove('hidden');
                    sqlBox.textContent = data.sql || '(tidak ada SQL)';
                    return;
                }

                // === HUMAN OVERSIGHT MODE ===
                currentReviewId = data.review_id;
                modalQuestion.textContent = question;
                modalSql.textContent = data.sql_preview;
                setRiskBadge(data.risk_level);

                openModal();

            } catch (err) {
                answerEl.textContent = "Error: " + err.message;
            } finally {
                sendBtn.disabled = false;
                sendBtn.textContent = 'Kirim';
            }
        });

        /* ===================================================== */
        /* 🟩 STEP 2: Eksekusi query setelah user menekan OK     */
        /* ===================================================== */
        modalExecute.addEventListener('click', async () => {
            if (!currentReviewId) return;

            modalExecute.disabled = true;
            modalExecute.textContent = "Menjalankan...";

            try {
                const url = `${URLS.executeBase}/${currentReviewId}`;
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json'
                    },
                });

                const data = await res.json();

                answerEl.textContent = data.answer || "(tidak ada jawaban)";
                sqlBox.classList.remove('hidden');
                sqlBox.textContent = data.sql || "(kosong)";

                closeModal();
            } catch (e) {
                answerEl.textContent = "Terjadi error: " + e.message;
            } finally {
                modalExecute.disabled = false;
                modalExecute.textContent = "Jalankan Query Ini";
            }
        });

        questionEl.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') sendBtn.click();
        });
    </script>

</body>
</html>
