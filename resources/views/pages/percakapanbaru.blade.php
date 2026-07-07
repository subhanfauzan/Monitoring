@extends('layouts.layout')

@section('content')
<style>
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        overflow: hidden !important; /* Mencegah munculnya scrollbar di layar utama */
    }

    /* Sembunyikan navbar dan footer */
    .layout-navbar, .content-footer {
        display: none !important;
    }

    /* Pastikan background gradient menutupi seluruh halaman termasuk area kosong bawaan template */
    .layout-page,
    .content-wrapper {
        height: 100vh !important;
        min-height: 100vh !important;
        background: radial-gradient(circle at center, #e0f2fe 0%, #ffffff 60%) !important;
        overflow: hidden !important;
    }

    /* Hilangkan ruang kosong bawaan fixed navbar dan container template */
    .layout-navbar-fixed .layout-wrapper:not(.layout-horizontal) .layout-page,
    .layout-page {
        padding-top: 0 !important;
    }

    .layout-navbar-fixed .layout-wrapper:not(.layout-horizontal) .layout-page::before {
        display: none !important;
        height: 0 !important;
    }

    .layout-page,
    .content-wrapper,
    .content-wrapper > .container,
    .content-wrapper > .container-xxl,
    .content-wrapper > .container-fluid,
    .container-p-y {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
    }

    .layout-page,
    .content-wrapper,
    .content-wrapper > .container,
    .content-wrapper > .container-xxl,
    .content-wrapper > .container-fluid {
        min-height: 100vh;
    }

    .swal-z-top {
        z-index: 99999 !important;
    }

    .gemini-container {
        height: 100vh;
        width: 100%;
        box-sizing: border-box;
        padding: 30vh 1rem 5rem 1rem; /* Jarak bawah ditambah (5rem) agar input box tidak tenggelam/terpotong layar */
        background: transparent !important;
        overflow: hidden; /* Mencegah seluruh layar scroll, hanya history yang scroll */
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        position: relative;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        transition: padding 0.5s cubic-bezier(0.4, 0, 0.2, 1); /* Animasi mulus saat naik ke mode chat */
    }

    .gemini-history-container {
        width: 100%;
        max-width: 720px;
        flex-grow: 1;
        overflow-y: auto;
        display: none;
        flex-direction: column;
        gap: 1.5rem;
        padding: 1rem 1rem 2rem 1rem;
        margin-bottom: 1rem;
    }
    
    /* Mempercantik scrollbar history */
    .gemini-history-container::-webkit-scrollbar { width: 6px; }
    .gemini-history-container::-webkit-scrollbar-track { background: transparent; }
    .gemini-history-container::-webkit-scrollbar-thumb { background-color: rgba(100, 116, 139, 0.3); border-radius: 10px; }
    .gemini-history-container::-webkit-scrollbar-thumb:hover { background-color: rgba(100, 116, 139, 0.5); }

    .gemini-title {
        font-size: clamp(1.8rem, 4vw, 2.5rem);
        font-weight: 500;
        color: #1e293b;
        margin-bottom: 2.5rem;
        text-align: center;
        letter-spacing: -0.5px;
        flex-shrink: 0;
    }

    .gemini-input-wrapper {
        width: 100%;
        max-width: 720px;
        position: relative;
        background: #ffffff;
        border-radius: 9999px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0,0,0,0.03);
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        transition: box-shadow 0.3s ease;
        flex-shrink: 0;
    }

    .gemini-input-wrapper:focus-within {
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08), 0 2px 5px rgba(0,0,0,0.04);
    }

    .btn-icon-circular {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        background: transparent;
        border: none;
        cursor: pointer;
        transition: background 0.2s;
        flex-shrink: 0;
    }

    .btn-icon-circular:hover {
        background: #f1f5f9;
        color: #0f172a;
    }

    .gemini-input {
        flex: 1;
        border: none;
        outline: none;
        background: transparent;
        font-size: 1.1rem;
        color: #334155;
        padding: 0.75rem 1rem;
    }

    .gemini-input::placeholder {
        color: #94a3b8;
    }

    /* Chat Bubbles */
    .chat-bubble {
        max-width: 100%;
        font-size: 1rem;
        line-height: 1.6;
        word-wrap: break-word;
    }
    .chat-bubble.bot {
        background: transparent;
        color: #1e293b;
        align-self: flex-start;
        padding: 0.5rem 0;
        width: 100%;
    }
    .chat-bubble.user {
        background: #f1f5f9;
        color: #0f172a;
        align-self: flex-end;
        border-radius: 24px;
        padding: 0.75rem 1.25rem;
        max-width: 75%;
    }
    .chat-bubble pre {
        background: #1e293b;
        color: #f8fafc;
        padding: 1rem;
        border-radius: 8px;
        margin-top: 1rem;
        overflow-x: auto;
        font-size: 0.85rem;
    }

    .loading-indicator {
        display: none;
        align-items: center;
        justify-content: center;
        gap: 8px;
        margin-bottom: 1rem;
        color: #64748b;
        font-weight: 500;
        flex-shrink: 0;
    }
    .loading-indicator.show {
        display: flex;
    }
    .spinner {
        width: 18px;
        height: 18px;
        border: 2px solid #cbd5e1;
        border-top-color: #3b82f6;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Select Dropdown Styling */
    .provider-select-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: transparent;
        border-radius: 9999px;
        transition: background 0.2s;
        cursor: pointer;
    }
    .provider-select-wrapper:hover {
        background: #f1f5f9;
    }
    .provider-select {
        appearance: none;
        -webkit-appearance: none;
        background: transparent;
        border: none;
        font-size: 0.85rem;
        font-weight: 500;
        color: #475569;
        padding: 0.4rem 1.8rem 0.4rem 0.8rem;
        cursor: pointer;
        outline: none;
        font-family: inherit;
    }
    .provider-select-icon {
        position: absolute;
        right: 8px;
        pointer-events: none;
        color: #475569;
        display: flex;
        align-items: center;
    }

    /* Modal styles */
    .widget-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(2px);
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .widget-modal-content {
        background: white;
        border-radius: 16px;
        width: 100%;
        max-width: 500px;
        padding: 24px;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        font-family: 'Inter', sans-serif;
    }
    .risk-badge {
        display: inline-block;
        padding: 2px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .risk-low { background: #dcfce7; color: #166534; }
    .risk-medium { background: #fef3c7; color: #92400e; }
    .risk-high { background: #ffe4e6; color: #9f1239; }

    .gemini-disclaimer {
        font-size: 0.75rem;
        color: #64748b;
        margin-top: auto; /* Memaksa teks ini selalu menempel di bagian paling bawah */
        text-align: center;
        padding-top: 1rem;
    }

    /* Responsiveness */
    @media (max-width: 768px) {
        .gemini-container { padding: 1rem; }
        .gemini-title { font-size: 1.8rem; margin-bottom: 1.5rem; }
        .gemini-input-wrapper { padding: 0.25rem 0.5rem; }
        .gemini-input { font-size: 1rem; padding: 0.5rem; }
        .provider-select { font-size: 0.75rem; padding: 0.3rem 1.4rem 0.3rem 0.6rem; }
        .provider-select-icon { right: 4px; }
        .chat-bubble.user { padding: 0.5rem 1rem; font-size: 0.9rem; }
    }
</style>

<div class="gemini-container">
    <div class="gemini-title" id="mainTitle">
        Halo {{ Auth::check() ? explode(' ', Auth::user()->name)[0] : 'User' }}, yuk kita bahas lebih lanjut
    </div>

    <!-- Chat History Container -->
    <div class="gemini-history-container" id="historyContainer">
        <!-- Bubbles will be appended here -->
    </div>

    <!-- Loading Indicator dihapus dan akan dibuat dinamis sebagai chat bubble -->

    <div class="gemini-input-wrapper" id="inputWrapper">
        <div class="dropdown dropup">
            <button class="btn-icon-circular" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Menu Import">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
            </button>
            <ul class="dropdown-menu dropdown-menu-start shadow-sm border-0" style="margin-bottom: 0.5rem; border-radius: 12px;">
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="ti ti-file-import me-2"></i> Import SWFM
                    </a>
                </li>
                <li>
                    <a class="dropdown-item d-flex align-items-center py-2" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#importAlarmModal">
                        <i class="ti ti-alert-triangle me-2"></i> Import Alarm
                    </a>
                </li>
            </ul>
        </div>

        <input type="text" id="geminiPrompt" class="gemini-input" placeholder="Minta Gemini" autocomplete="off" autofocus>

        <div class="provider-select-wrapper" title="Ganti Model AI">
            <select id="provider" class="provider-select">
                <option value="gemini" selected>GEMINI</option>
                <option value="chatgpt">GPT</option>
            </select>
            <div class="provider-select-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </div>
        </div>

        <button class="btn-icon-circular ms-1" id="submitBtn" title="Kirim">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path>
                <path d="M19 10v2a7 7 0 0 1-14 0v-2"></path>
                <line x1="12" y1="19" x2="12" y2="23"></line>
                <line x1="8" y1="23" x2="16" y2="23"></line>
            </svg>
        </button>
    </div>

    <!-- Disclaimer Gemini -->
    <div class="gemini-disclaimer">Gemini Dan ChatGPT adalah AI dan dapat melakukan kesalahan.</div>
</div>

<!-- Oversight Modal -->
<div class="widget-modal-overlay" id="chatWidgetModal">
    <div class="widget-modal-content">
        <h5 style="margin-bottom: 5px; font-weight: 600;">Review Query</h5>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">Sistem memerlukan persetujuan Anda sebelum mengeksekusi query ini.</p>
        
        <div style="background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px solid #f1f5f9;">
            <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; margin-bottom: 5px;">Pertanyaan Anda</div>
            <div id="chatWidgetModalQuestion" style="font-size: 14px; font-weight: 500; color: #334155;"></div>
        </div>
        
        <div style="margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 5px;">
                <span style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Preview SQL</span>
                <span id="chatWidgetRiskBadge" class="risk-badge risk-low">-</span>
            </div>
            <pre id="chatWidgetModalSql" style="background: #1e293b; color: #e2e8f0; padding: 12px; border-radius: 8px; font-size: 12px; max-height: 200px; overflow-y: auto; margin:0;"></pre>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
            <button id="chatWidgetModalCancel" class="btn btn-label-secondary">Batal</button>
            <button id="chatWidgetModalExecute" class="btn btn-primary" style="background: #00adef; border: none;">Jalankan Query Ini</button>
        </div>
    </div>
</div>

<!-- Import Modals -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" action="{{ route('tiket.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import Tiket SWFM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Upload File Excel (SWFM)</label>
                    <input class="form-control" type="file" name="file" accept=".csv, .xls, .xlsx" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">Import</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="importAlarmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" action="{{ route('tiket.importAlarm') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import Alarm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Upload File Excel Alarm (mulai baris 6)</label>
                    <input class="form-control" type="file" name="file" accept=".csv, .xls, .xlsx" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-info">Import Alarm</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        const input = $('#geminiPrompt');
        const submitBtn = $('#submitBtn');
        const historyContainer = $('#historyContainer');
        const mainTitle = $('#mainTitle');
        const providerSelect = $('#provider');
        const geminiContainer = $('.gemini-container');
        
        // Modal elements
        const modal = $('#chatWidgetModal');
        const modalQuestion = $('#chatWidgetModalQuestion');
        const modalSql = $('#chatWidgetModalSql');
        const modalRiskBadge = $('#chatWidgetRiskBadge');
        const modalCancel = $('#chatWidgetModalCancel');
        const modalExecute = $('#chatWidgetModalExecute');
        
        let currentReviewId = null;

        function appendBubble(text, sender) {
            const bubble = $('<div class="chat-bubble ' + sender + '"></div>');
            let formattedText = text
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\n/g, '<br>');
            bubble.html(formattedText);
            historyContainer.append(bubble);
            historyContainer.scrollTop(historyContainer[0].scrollHeight);
        }

        async function postJSON(url, payload) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(payload || {}),
            });

            const raw = await resp.text();
            let data;
            try { data = JSON.parse(raw); } catch (e) { data = { raw }; }

            return { ok: resp.ok, status: resp.status, data };
        }

        let isProcessing = false;

        async function sendQuestion() {
            const question = input.val().trim();
            if (!question || isProcessing) return;

            isProcessing = true;
            submitBtn.prop('disabled', true);
            // Tombol send dimatikan dan request diblok, tapi user tetap bisa mengetik

            const provider = providerSelect.val();

            // Transisi UI ke mode obrolan aktif
            if (mainTitle.is(':visible')) {
                geminiContainer.css({
                    'padding-top': '2rem',
                    'padding-bottom': '5rem' // Angkat input box lebih ke atas agar aman dari batas bawah layar
                });
                mainTitle.slideUp(300);
                historyContainer.css('display', 'flex').hide().fadeIn(300);
            }

            appendBubble(question, 'user');
            input.val('');
            updateIcon(); // reset icon to mic
            
            // Tambahkan indikator loading di dalam area jawaban (sebagai bubble bot)
            const loadingBubbleId = 'loading-' + Date.now();
            const loadingBubble = $('<div class="chat-bubble bot" id="' + loadingBubbleId + '"><div style="display: flex; align-items: center; gap: 8px; color: #64748b;"><div class="spinner"></div><span>Memproses...</span></div></div>');
            historyContainer.append(loadingBubble);
            historyContainer.scrollTop(historyContainer[0].scrollHeight);

            let url = provider === 'gemini' ? '/gemini/ask' : '/chat/ask';

            try {
                const { ok, status, data } = await postJSON(url, { question });
                
                $('#' + loadingBubbleId).remove(); // Hapus loading

                if (!ok) {
                    appendBubble(data?.message || "Terjadi error server", 'bot');
                    return;
                }

                // Jika ada SQL Preview (Oversight Mode)
                if (data.sql_preview && data.review_id) {
                    currentReviewId = data.review_id;
                    modalQuestion.text(question);
                    modalSql.text(data.sql_preview);
                    
                    modalRiskBadge.text(data.risk_level);
                    modalRiskBadge.removeClass('risk-low risk-medium risk-high');
                    if(data.risk_level === 'low') modalRiskBadge.addClass('risk-low');
                    else if(data.risk_level === 'high') modalRiskBadge.addClass('risk-high');
                    else modalRiskBadge.addClass('risk-medium');
                    
                    modal.css('display', 'flex');
                } 
                // Jika hanya jawaban biasa atau ada SQL tapi tidak butuh review
                else if (data.answer) {
                    let content = data.answer
                        .replace(/&/g, "&amp;")
                        .replace(/</g, "&lt;")
                        .replace(/>/g, "&gt;")
                        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                        .replace(/\n/g, '<br>');
                    
                    if (data.sql) {
                        content += '<div style="margin-top: 1rem;"><div style="font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 0.5rem; text-transform: uppercase;">SQL Query</div><pre style="margin:0; font-size: 0.85rem;">' + data.sql + '</pre></div>';
                    }
                    const bubble = $('<div class="chat-bubble bot"></div>');
                    bubble.html(content);
                    historyContainer.append(bubble);
                    historyContainer.scrollTop(historyContainer[0].scrollHeight);
                } else {
                    appendBubble("Terjadi kesalahan, tidak ada respon.", 'bot');
                }
            } catch (err) {
                $('#' + loadingBubbleId).remove();
                appendBubble("Error: " + err.message, 'bot');
            } finally {
                isProcessing = false;
                submitBtn.prop('disabled', false);
            }
        }

        // Modal Action
        modalCancel.on('click', function() {
            modal.hide();
            currentReviewId = null;
            appendBubble("Aksi dibatalkan oleh pengguna.", 'bot');
        });
        
        modalExecute.on('click', async function() {
            if(!currentReviewId) return;
            
            const btn = $(this);
            btn.prop('disabled', true).text("Menjalankan...");
            modal.hide();
            
            // Tambahkan loading saat eksekusi
            const loadingBubbleId = 'loading-' + Date.now();
            const loadingBubble = $('<div class="chat-bubble bot" id="' + loadingBubbleId + '"><div style="display: flex; align-items: center; gap: 8px; color: #64748b;"><div class="spinner"></div><span>Mengeksekusi SQL...</span></div></div>');
            historyContainer.append(loadingBubble);
            historyContainer.scrollTop(historyContainer[0].scrollHeight);
            
            try {
                const url = '/chat/execute/' + currentReviewId;
                const { ok, status, data } = await postJSON(url, {});
                
                $('#' + loadingBubbleId).remove();
                
                if (data.answer) {
                    appendBubble(data.answer, 'bot');
                } else {
                    appendBubble("Berhasil dieksekusi.", 'bot');
                }
            } catch (err) {
                $('#' + loadingBubbleId).remove();
                appendBubble("Terjadi error: " + err.message, 'bot');
            } finally {
                btn.prop('disabled', false).text("Jalankan Query Ini");
            }
        });

        function updateIcon() {
            if (input.val().trim().length > 0) {
                submitBtn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>');
            } else {
                submitBtn.html('<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a3 3 0 0 0-3 3v7a3 3 0 0 0 6 0V5a3 3 0 0 0-3-3z"></path><path d="M19 10v2a7 7 0 0 1-14 0v-2"></path><line x1="12" y1="19" x2="12" y2="23"></line><line x1="8" y1="23" x2="16" y2="23"></line></svg>');
            }
        }

        $('#importModal form, #importAlarmModal form').on('submit', function(e) {
            e.preventDefault();
            
            let $form = $(this);
            let $btn = $form.find('button[type="submit"]');

            if (!this.checkValidity()) return;
            
            $btn.prop('disabled', true);
            
            // Sembunyikan modal agar loading terkesan lebih bersih
            $form.closest('.modal').modal('hide');
            
            Swal.fire({
                title: 'Sedang Memproses Data...',
                html: 'Mohon tunggu sebentar, file Excel sedang diimport.',
                allowOutsideClick: false,
                customClass: {
                    container: 'swal-z-top'
                },
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData(this);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if(response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Import Berhasil!',
                            text: response.message || 'Data berhasil diimport. Halaman akan dimuat ulang...',
                            timer: 2500,
                            showConfirmButton: false,
                            allowOutsideClick: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Import Gagal',
                            text: response.message || 'Gagal mengimport data.'
                        });
                        $btn.prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'Terjadi kesalahan saat import.';
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Import Gagal',
                        text: errorMsg
                    });
                    $btn.prop('disabled', false);
                }
            });
        });

        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                Swal.close();
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                $('#importModal form, #importAlarmModal form').trigger('reset');
                $('#importModal form, #importAlarmModal form').find('button[type="submit"]').prop('disabled', false);
            }
        });

        input.on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                sendQuestion();
            }
        });

        submitBtn.on('click', function(e) {
            e.preventDefault();
            sendQuestion();
        });

        input.on('input', updateIcon);
    });
</script>
@endpush
