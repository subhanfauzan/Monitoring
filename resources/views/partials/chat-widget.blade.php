<!-- Chat Widget -->
<style>
    /* CSS for Floating Chat Widget */
    .chat-widget-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #00adef; /* Telkom color */
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        cursor: pointer;
        z-index: 1050;
        transition: transform 0.2s;
        font-size: 28px;
    }
    .chat-widget-btn:hover {
        transform: scale(1.05);
    }

    .chat-widget-panel {
        position: fixed;
        bottom: 30px; /* same bottom as button */
        right: 110px; /* to the left of button (30px + 60px width + 20px gap) */
        width: 420px; /* larger width */
        height: 550px; /* larger height */
        max-height: calc(100vh - 60px);
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        z-index: 1050;
        overflow: hidden;
        border: 1px solid #e5e7eb;
    }

    @media (max-width: 576px) {
        .chat-widget-panel {
            width: calc(100% - 40px);
            right: 20px;
            bottom: 100px; /* Above the button on mobile */
            height: 500px;
        }
    }

    .chat-widget-header {
        background: #00adef;
        color: white;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .chat-widget-header h5 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
        color: white;
    }
    .chat-widget-header .status {
        font-size: 12px;
        opacity: 0.9;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .chat-widget-header .status::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #4ade80;
        border-radius: 50%;
    }
    .chat-widget-close {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        opacity: 0.8;
    }
    .chat-widget-close:hover {
        opacity: 1;
    }

    .chat-widget-body {
        padding: 15px;
        overflow-y: auto;
        background: #f8fafc;
        flex-grow: 1; /* allow it to take available space */
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .chat-bubble {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .chat-bubble.bot {
        background: white;
        color: #334155;
        align-self: flex-start;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }
    .chat-bubble.user {
        background: #00adef;
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    
    .chat-bubble pre {
        background: #1e293b;
        color: #e2e8f0;
        padding: 8px;
        border-radius: 8px;
        font-size: 12px;
        overflow-x: auto;
        margin-top: 8px;
    }

    .chat-widget-footer {
        padding: 15px;
        background: white;
        border-top: 1px solid #e5e7eb;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .chat-controls {
        display: flex;
        gap: 10px;
    }

    .chat-input {
        flex-grow: 1;
        border: 1px solid #cbd5e1;
        border-radius: 20px;
        padding: 8px 15px;
        font-size: 14px;
        outline: none;
        resize: none;
        height: 40px;
    }
    .chat-input:focus {
        border-color: #00adef;
    }

    .chat-send-btn {
        background: #00adef;
        color: white;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
    }
    .chat-send-btn:hover {
        background: #008cc4;
    }
    .chat-send-btn:disabled {
        background: #94a3b8;
        cursor: not-allowed;
    }
    
    /* Modal styles specifically for widget */
    .widget-modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.4);
        backdrop-filter: blur(2px);
        z-index: 1060;
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
</style>

<!-- Floating Button -->
<div class="chat-widget-btn" id="chatWidgetBtn">
    <i class="ti ti-message-dots"></i>
</div>

<!-- Chat Panel -->
<div class="chat-widget-panel" id="chatWidgetPanel">
    <div class="chat-widget-header">
        <div>
            <h5>Customer Service</h5>
            <div class="status">Online</div>
        </div>
        <button class="chat-widget-close" id="chatWidgetClose">
            <i class="ti ti-x"></i>
        </button>
    </div>
    <div class="chat-widget-body" id="chatWidgetBody">
        <div class="chat-bubble bot">
            Halo! Saya asisten monitoring. Ada yang bisa saya bantu terkait tiket atau status site hari ini?
        </div>
    </div>
    <div class="chat-widget-footer">
        <select id="chatWidgetProvider" class="form-select form-select-sm" style="border-radius: 12px;">
            <option value="chatgpt">ChatGPT (OpenAI)</option>
            <option value="gemini">Gemini</option>
        </select>
        <div class="chat-controls">
            <input type="text" id="chatWidgetInput" class="chat-input" placeholder="Ketik pesan..." autocomplete="off">
            <button id="chatWidgetSend" class="chat-send-btn">
                <i class="ti ti-send"></i>
            </button>
        </div>
    </div>
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
            <button id="chatWidgetModalExecute" class="btn btn-primary">Jalankan Query Ini</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('chatWidgetBtn');
    const panel = document.getElementById('chatWidgetPanel');
    const closeBtn = document.getElementById('chatWidgetClose');
    const input = document.getElementById('chatWidgetInput');
    const sendBtn = document.getElementById('chatWidgetSend');
    const provider = document.getElementById('chatWidgetProvider');
    const chatBody = document.getElementById('chatWidgetBody');
    
    // Modal elements
    const modal = document.getElementById('chatWidgetModal');
    const modalQuestion = document.getElementById('chatWidgetModalQuestion');
    const modalSql = document.getElementById('chatWidgetModalSql');
    const modalRiskBadge = document.getElementById('chatWidgetRiskBadge');
    const modalCancel = document.getElementById('chatWidgetModalCancel');
    const modalExecute = document.getElementById('chatWidgetModalExecute');
    
    let currentReviewId = null;
    
    const URLS = {
        chat: "{{ route('chat.ask') }}",
        gemini: "{{ route('gemini.ask') }}",
        executeBase: "{{ url('/chat/execute') }}"
    };
    
    let csrfToken = '';
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag) {
        csrfToken = metaTag.getAttribute('content');
    } else {
        csrfToken = "{{ csrf_token() }}";
    }

    // Toggle chat panel
    btn.addEventListener('click', () => {
        panel.style.display = panel.style.display === 'flex' ? 'none' : 'flex';
        if(panel.style.display === 'flex') input.focus();
    });
    
    closeBtn.addEventListener('click', () => {
        panel.style.display = 'none';
    });

    function addBubble(text, sender) {
        const bubble = document.createElement('div');
        bubble.className = `chat-bubble ${sender}`;
        
        // Render text preserving line breaks and basic markdown (bold)
        let formattedText = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\n/g, '<br>');
            
        bubble.innerHTML = formattedText;
        
        chatBody.appendChild(bubble);
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    async function postJSON(url, payload) {
        const resp = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload),
        });
        const raw = await resp.text();
        let data;
        try { data = JSON.parse(raw); } catch { data = { raw }; }
        return { ok: resp.ok, status: resp.status, data };
    }

    async function handleSend() {
        const question = input.value.trim();
        if(!question) return;
        
        addBubble(question, 'user');
        input.value = '';
        
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<i class="ti ti-loader ti-spin"></i>';
        
        const selectedProvider = provider.value;
        const url = selectedProvider === 'gemini' ? URLS.gemini : URLS.chat;
        
        try {
            const { ok, status, data } = await postJSON(url, { question });
            
            if(!ok) {
                addBubble(data?.message || "Terjadi error server", 'bot');
                return;
            }
            
            if(data.answer && !data.sql_preview) {
                addBubble(data.answer, 'bot');
                return;
            }
            
            // Human Oversight Mode
            currentReviewId = data.review_id;
            modalQuestion.textContent = question;
            modalSql.textContent = data.sql_preview;
            
            modalRiskBadge.textContent = data.risk_level;
            modalRiskBadge.className = 'risk-badge';
            if(data.risk_level === 'low') modalRiskBadge.classList.add('risk-low');
            else if(data.risk_level === 'high') modalRiskBadge.classList.add('risk-high');
            else modalRiskBadge.classList.add('risk-medium');
            
            modal.style.display = 'flex';
            
        } catch(err) {
            addBubble("Error: " + err.message, 'bot');
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="ti ti-send"></i>';
        }
    }
    
    sendBtn.addEventListener('click', handleSend);
    input.addEventListener('keydown', (e) => {
        if(e.key === 'Enter') handleSend();
    });
    
    modalCancel.addEventListener('click', () => {
        modal.style.display = 'none';
        currentReviewId = null;
        addBubble("Aksi dibatalkan oleh pengguna.", 'bot');
    });
    
    modalExecute.addEventListener('click', async () => {
        if(!currentReviewId) return;
        
        modalExecute.disabled = true;
        modalExecute.textContent = "Menjalankan...";
        
        try {
            const url = `${URLS.executeBase}/${currentReviewId}`;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            
            modal.style.display = 'none';
            addBubble(data.answer || "(tidak ada jawaban)", 'bot');
            
        } catch(e) {
            addBubble("Terjadi error: " + e.message, 'bot');
        } finally {
            modalExecute.disabled = false;
            modalExecute.textContent = "Jalankan Query Ini";
        }
    });
});
</script>
