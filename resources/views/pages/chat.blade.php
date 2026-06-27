<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chatbot Monitoring</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar for pre tags */
        pre::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        pre::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }
        pre::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
        }
        pre::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>

<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 antialiased text-slate-600 font-sans">
    <div class="w-full max-w-3xl bg-white shadow-sm border border-slate-200 rounded-2xl p-6 md:p-8 space-y-6">
        <div class="border-b border-slate-100 pb-4">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Chatbot Monitoring</h1>
            <p class="text-sm text-slate-500 mt-1">Tanyakan informasi seputar monitoring dan tiket.</p>
        </div>

        <div class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Provider</label>
                <select id="provider"
                    class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-slate-900 block p-2.5 outline-none transition-all">
                    <option value="chatgpt">ChatGPT (OpenAI)</option>
                    <option value="gemini">Gemini</option>
                </select>
                <p id="modelHint" class="text-xs text-slate-500 mt-1.5">model: gpt-4o / gemini-1.5-*</p>
            </div>

            <div>
                <label for="question" class="block text-sm font-medium text-slate-700 mb-1.5">Pertanyaan</label>
                <textarea id="question" rows="4"
                    class="w-full bg-slate-50 border border-slate-200 text-slate-900 text-sm rounded-lg focus:ring-2 focus:ring-slate-900 focus:border-slate-900 block p-3 outline-none transition-all resize-none placeholder-slate-400"
                    placeholder="Contoh: tampilkan site down hari ini..."></textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button id="sendBtn"
                    class="px-5 py-2.5 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-slate-800 focus:ring-4 focus:ring-slate-200 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    <span id="sendBtnText">Kirim Pertanyaan</span>
                </button>
                <button id="clearBtn"
                    class="px-5 py-2.5 bg-white text-slate-700 text-sm font-medium rounded-lg border border-slate-200 hover:bg-slate-50 hover:text-slate-900 focus:ring-4 focus:ring-slate-100 transition-all">
                    Bersihkan
                </button>
            </div>
        </div>

        <div class="mt-8">
            <div class="bg-slate-50 border border-slate-100 rounded-xl p-5">
                <div class="flex justify-between items-center mb-4 pb-3 border-b border-slate-200/60">
                    <h2 class="text-base font-semibold text-slate-900">Hasil Respons</h2>
                    <span id="lastProvider" class="px-2.5 py-1 text-xs font-medium rounded-md bg-white border border-slate-200 text-slate-600 shadow-sm">-</span>
                </div>
                <p id="answer" class="text-sm text-slate-700 whitespace-pre-wrap leading-relaxed min-h-[4rem]">Belum ada pertanyaan yang diajukan.</p>
                <div id="sqlWrapper" class="mt-5 pt-4 border-t border-slate-200/60">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">SQL Query</h3>
                    </div>
                    <pre id="sqlBox" class="p-4 rounded-xl bg-slate-800 text-slate-200 text-xs whitespace-pre-wrap hidden font-mono leading-relaxed overflow-x-auto shadow-inner border border-slate-700">(kosong)</pre>
                </div>
            </div>
        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-4" hidden>
            <div class="flex justify-between items-center">
                <h2 class="text-sm font-semibold text-slate-800">Debug Info</h2>
                <button id="toggleDebug"
                    class="text-xs px-3 py-1.5 rounded-md bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-colors">Toggle</button>
            </div>
            <pre id="debugBox" class="mt-3 p-3 text-xs bg-white border border-slate-200 rounded-lg text-slate-600 hidden font-mono">(kosong)</pre>
        </div>
    </div>

    <!-- ========================================================= -->
    <!-- 🟦 MODAL HUMAN OVERSIGHT -->
    <!-- ========================================================= -->
    <div id="sqlModal"
         class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden items-center justify-center z-50 p-4 transition-opacity">
        <div class="bg-white w-full max-w-xl rounded-2xl shadow-2xl border border-slate-100 p-6 space-y-5">

            <div class="flex justify-between items-start pb-4 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Review Query</h2>
                    <p class="text-sm text-slate-500 mt-1">Sistem memerlukan persetujuan Anda sebelum mengeksekusi query ini.</p>
                </div>
                <button id="modalClose" class="text-slate-400 hover:text-slate-600 bg-slate-50 hover:bg-slate-100 rounded-full p-2 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="space-y-4">
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Pertanyaan Anda</p>
                    <p id="modalQuestion" class="text-sm text-slate-800 font-medium"></p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Preview SQL</p>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-500">Risk Level:</span>
                            <span id="modalRiskBadge"
                                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 shadow-sm">
                                -
                            </span>
                        </div>
                    </div>
                    <pre id="modalSql"
                         class="p-4 rounded-xl bg-slate-800 text-slate-200 text-xs whitespace-pre-wrap max-h-64 overflow-y-auto font-mono leading-relaxed border border-slate-700 shadow-inner"></pre>
                </div>

                <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 text-sm text-slate-700">
                    <div class="flex gap-2">
                        <svg class="w-5 h-5 text-slate-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div>
                            <p class="font-semibold mb-1 text-slate-800">Checklist Keamanan:</p>
                            <ul class="list-disc list-inside space-y-1 text-slate-600 text-xs">
                                <li>Hanya operasi <code class="font-mono bg-white border border-slate-200 px-1 py-0.5 rounded">SELECT</code> yang diizinkan (tidak ada UPDATE/DELETE).</li>
                                <li>Pastikan filter sesuai dengan yang diminta.</li>
                                <li>Terdapat batasan jumlah baris (LIMIT) jika diperlukan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-5 border-t border-slate-100">
                <button id="modalCancel"
                        class="px-5 py-2.5 text-sm font-medium rounded-lg text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-900 transition-all">
                    Batal
                </button>
                <button id="modalExecute"
                        class="px-5 py-2.5 text-sm font-medium rounded-lg text-white bg-slate-900 hover:bg-slate-800 focus:ring-4 focus:ring-slate-200 transition-all disabled:opacity-60 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span id="modalExecuteText">Jalankan Query Ini</span>
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
        const sendBtnText = $('#sendBtnText');
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
        const modalExecuteText = $('#modalExecuteText');
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
                "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium shadow-sm border border-slate-200/60";

            if (level === 'low') {
                modalRiskBadge.classList.add('bg-emerald-50', 'text-emerald-700', 'border-emerald-200');
            } else if (level === 'medium') {
                modalRiskBadge.classList.add('bg-amber-50', 'text-amber-700', 'border-amber-200');
            } else if (level === 'high') {
                modalRiskBadge.classList.add('bg-rose-50', 'text-rose-700', 'border-rose-200');
            } else {
                modalRiskBadge.classList.add('bg-slate-100', 'text-slate-700');
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
            answerEl.textContent = 'Belum ada pertanyaan yang diajukan.';
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
            sendBtnText.textContent = 'Memproses...';
            answerEl.textContent = 'Menyusun jawaban...';
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
                sendBtnText.textContent = 'Kirim Pertanyaan';
            }
        });

        /* ===================================================== */
        /* 🟩 STEP 2: Eksekusi query setelah user menekan OK     */
        /* ===================================================== */
        modalExecute.addEventListener('click', async () => {
            if (!currentReviewId) return;

            modalExecute.disabled = true;
            modalExecuteText.textContent = "Menjalankan...";

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
                modalExecuteText.textContent = "Jalankan Query Ini";
            }
        });

        questionEl.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') sendBtn.click();
        });
    </script>

</body>
</html>
