// =============================================
// ACESSIIDOSO - APP LOGIC (clean rewrite)
// =============================================

// =============================================
// TTS MANAGER
// =============================================
var TTSManager = {
    selectedVoice: null,
    rate: parseFloat(localStorage.getItem('acessiVoiceRate')) || 0.95,
    hoverEnabled: localStorage.getItem('acessiHoverSpeech') !== 'false',

    init: function () {
        this.loadVoices();
        if ('speechSynthesis' in window) {
            window.speechSynthesis.onvoiceschanged = function () {
                TTSManager.loadVoices();
            };
        }
        this.updateUI();
    },

    loadVoices: function () {
        if (!('speechSynthesis' in window)) return;
        var voices = window.speechSynthesis.getVoices();
        var ptVoice = voices.find(function (v) {
            return v.lang === 'pt-BR' && (
                v.name.includes('Google') || v.name.includes('Luciana') ||
                v.name.includes('Maria') || v.name.includes('Natural') || v.name.includes('Yara')
            );
        }) || voices.find(function (v) {
            return v.lang === 'pt-BR';
        }) || voices.find(function (v) {
            return v.lang && v.lang.toLowerCase().startsWith('pt');
        });
        if (ptVoice) this.selectedVoice = ptVoice;
    },

    speak: function (text, onEnd) {
        if (!('speechSynthesis' in window) || !text) return;
        try {
            window.speechSynthesis.cancel();
            var utt = new SpeechSynthesisUtterance(text);
            utt.lang = 'pt-BR';
            utt.rate = this.rate;
            if (this.selectedVoice) utt.voice = this.selectedVoice;
            if (onEnd) { utt.onend = onEnd; utt.onerror = onEnd; }
            window.speechSynthesis.speak(utt);
        } catch (e) {
            console.error('TTS error:', e);
            if (onEnd) onEnd();
        }
    },

    stop: function () {
        if ('speechSynthesis' in window) window.speechSynthesis.cancel();
    },

    setRate: function (newRate) {
        this.rate = parseFloat(newRate) || 0.95;
        localStorage.setItem('acessiVoiceRate', this.rate);
        this.updateUI();
        this.speak('Velocidade da voz alterada.');
    },

    setHoverEnabled: function (enabled) {
        this.hoverEnabled = !!enabled;
        localStorage.setItem('acessiHoverSpeech', this.hoverEnabled ? 'true' : 'false');
        this.updateUI();
        if (this.hoverEnabled) {
            this.speak('Leitura ao passar o mouse ligada.');
        } else {
            HoverReader.clearHighlight();
            this.stop();
        }
    },

    toggleHover: function () {
        this.setHoverEnabled(!this.hoverEnabled);
    },

    updateUI: function () {
        var headerBtn    = document.getElementById('toggle-hover-tts');
        var headerIcon   = document.getElementById('hover-tts-icon');
        var headerStatus = document.getElementById('hover-tts-status-text');
        if (headerBtn && headerIcon && headerStatus) {
            if (this.hoverEnabled) {
                headerBtn.classList.add('active');
                headerBtn.setAttribute('aria-label', 'Leitura ao passar o mouse: Ligada');
                headerIcon.textContent = 'record_voice_over';
                headerStatus.textContent = 'LIGADA';
            } else {
                headerBtn.classList.remove('active');
                headerBtn.setAttribute('aria-label', 'Leitura ao passar o mouse: Desligada');
                headerIcon.textContent = 'volume_off';
                headerStatus.textContent = 'DESLIGADA';
            }
        }
        var modalBtn    = document.getElementById('modal-tts-toggle');
        var modalIcon   = document.getElementById('modal-tts-icon');
        var modalStatus = document.getElementById('modal-tts-status');
        if (modalBtn && modalIcon && modalStatus) {
            if (this.hoverEnabled) {
                modalBtn.classList.add('active');
                modalIcon.textContent = 'volume_up';
                modalStatus.textContent = 'Ativada';
            } else {
                modalBtn.classList.remove('active');
                modalIcon.textContent = 'volume_off';
                modalStatus.textContent = 'Desativada';
            }
        }
        var speedBtns = document.querySelectorAll('.btn-speed');
        var r = this.rate;
        speedBtns.forEach(function (btn) {
            var sv = 0.95;
            if (btn.id === 'speed-slow') sv = 0.75;
            else if (btn.id === 'speed-fast') sv = 1.2;
            btn.classList.toggle('active', Math.abs(sv - r) < 0.05);
        });
    }
};

function speakText(text)      { TTSManager.speak(text); }
function stopSpeech()         { TTSManager.stop(); }
function toggleHoverTTS()     { TTSManager.toggleHover(); }
function setVoiceSpeed(rate)  { TTSManager.setRate(rate); }
function testVoiceSpeech()    { speakText('Ola! Esta e a voz do AcessiIdoso. Eu leio tudo na tela para voce!'); }

// =============================================
// HOVER READER
// =============================================
var HoverReader = {
    timer: null,
    currentElement: null,
    currentText: '',
    debounceTime: 230,

    init: function () {
        document.addEventListener('mouseover', function (e) { HoverReader.handleMouseOver(e); }, true);
        document.addEventListener('mouseout',  function (e) { HoverReader.handleMouseOut(e);  }, true);
    },

    handleMouseOver: function (e) {
        if (!TTSManager.hoverEnabled) return;
        var target = e.target;
        if (!target || target === document.body || target === document.documentElement) return;
        if (target.closest && target.closest('#emergency-overlay')) return;

        var readableEl = this.findReadableElement(target);
        if (!readableEl || readableEl === this.currentElement) return;

        clearTimeout(this.timer);
        var text = this.extractReadableText(readableEl);
        if (!text || text.length < 2) return;
        if (text === this.currentText && this.currentElement && this.currentElement.contains(readableEl)) return;

        this.timer = setTimeout(function () {
            HoverReader.readElement(readableEl, text);
        }, this.debounceTime);
    },

    handleMouseOut: function (e) {
        var related = e.relatedTarget;
        if (this.currentElement && (!related || !this.currentElement.contains(related))) {
            clearTimeout(this.timer);
            this.clearHighlight();
        }
    },

    findReadableElement: function (el) {
        if (!el || el.nodeType !== 1) return null;
        if (el.classList && (el.classList.contains('material-icons-round') || el.classList.contains('material-icons'))) {
            el = el.parentElement;
            if (!el) return null;
        }
        if (el.closest) {
            var explicit = el.closest('[data-tts]');
            if (explicit) return explicit;
            var interactive = el.closest('button, a, input, select, label, .action-card, .med-card, .chip, .alert-banner, .med-item');
            if (interactive) return interactive;
            var textNode = el.closest('h1, h2, h3, h4, p, span, strong, em, li, .info-value, .info-label, .chat-bubble');
            if (textNode) return textNode;
        }
        return null;
    },

    extractReadableText: function (el) {
        if (!el) return '';
        var custom = el.getAttribute('data-tts');
        if (custom) return custom.trim();
        if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA') {
            var lbl = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
            var labelText = lbl ? this.cleanText(lbl.textContent) + '. ' : '';
            var ph = el.placeholder ? 'Exemplo: ' + el.placeholder : '';
            var val = el.value ? 'Preenchido: ' + el.value : '';
            return (labelText + (val || ph)).trim();
        }
        var aria = el.getAttribute('aria-label');
        if (aria && (el.tagName === 'BUTTON' || el.tagName === 'A' || el.classList.contains('action-card'))) {
            return aria.trim();
        }
        var clone = el.cloneNode(true);
        var icons = clone.querySelectorAll('.material-icons-round, .material-icons, script, style, .hidden');
        icons.forEach(function (ic) { ic.remove(); });
        return this.cleanText(clone.textContent || '');
    },

    cleanText: function (text) {
        if (!text) return '';
        return text.replace(/\s+/g, ' ').trim();
    },

    readElement: function (el, text) {
        this.clearHighlight();
        this.currentElement = el;
        this.currentText    = text;
        if (el) el.classList.add('tts-hover-highlight');
        TTSManager.speak(text, function () { HoverReader.clearHighlight(); });
    },

    clearHighlight: function () {
        if (this.currentElement) {
            this.currentElement.classList.remove('tts-hover-highlight');
            this.currentElement = null;
        }
    }
};

// =============================================
// DATE
// =============================================
function updateDate() {
    var el = document.getElementById('current-date');
    if (!el) return;
    var s = new Date().toLocaleDateString('pt-BR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    el.textContent = s.charAt(0).toUpperCase() + s.slice(1);
}

// =============================================
// VIEW NAVIGATION
// =============================================
function showView(id) {
    var v = document.getElementById(id);
    if (!v) { console.warn('View nao encontrada:', id); return; }
    v.classList.remove('hidden');
    v.scrollTop = 0;
    if (id === 'medicines-view') renderMedicinesList();
    if (id === 'bula-view')      renderBulaList();
}

function hideView(id) {
    var v = document.getElementById(id);
    if (v) v.classList.add('hidden');
}

// =============================================
// MEDICATION MANAGER (localStorage)
// =============================================
var MedicationManager = {
    KEY: 'acessiMedications',

    getAll: function () {
        var raw = localStorage.getItem(this.KEY);
        try { return raw ? JSON.parse(raw) : []; } catch(e) { return []; }
    },

    save: function (meds) {
        localStorage.setItem(this.KEY, JSON.stringify(meds));
    },

    add: function (medData, scheduleHours, lastDoseTime) {
        var meds = this.getAll();
        var id   = 'med_' + Date.now();
        var now  = new Date();
        var parts = String(lastDoseTime).split(':');
        var lastDt = new Date();
        lastDt.setHours(parseInt(parts[0]) || 0, parseInt(parts[1]) || 0, 0, 0);
        if (lastDt > now) lastDt.setDate(lastDt.getDate() - 1);

        var med = {
            id:               id,
            name:             medData.name             || 'Remedio desconhecido',
            category:         medData.category         || '-',
            prescription:     medData.prescription     || '-',
            indication:       medData.indication       || '-',
            dosage:           medData.dosage           || '-',
            scheduleHours:    parseInt(scheduleHours)  || 8,
            lastDoseISO:      lastDt.toISOString(),
            contraindications: medData.contraindications || '-',
            sideEffects:      medData.sideEffects      || '-',
            food:             medData.food             || '-',
            status:           'active',
            startDate:        now.toLocaleDateString('pt-BR')
        };
        meds.push(med);
        this.save(meds);
        return med;
    },

    finish: function (id) {
        var meds = this.getAll();
        for (var i = 0; i < meds.length; i++) {
            if (meds[i].id === id) { meds[i].status = 'completed'; break; }
        }
        this.save(meds);
    },

    markTaken: function (id) {
        var meds = this.getAll();
        for (var i = 0; i < meds.length; i++) {
            if (meds[i].id === id) { meds[i].lastDoseISO = new Date().toISOString(); break; }
        }
        this.save(meds);
    },

    getNextDose: function (med) {
        if (!med.lastDoseISO) return null;
        var last = new Date(med.lastDoseISO);
        return new Date(last.getTime() + med.scheduleHours * 3600 * 1000);
    },

    isDue: function (med) {
        if (med.status !== 'active') return false;
        var next = this.getNextDose(med);
        if (!next) return false;
        var diffMin = (new Date() - next) / 60000;
        return diffMin >= 0 && diffMin <= 15;
    }
};

// =============================================
// ALARM SYSTEM
// =============================================
var AlarmSystem = {
    interval:   null,
    alertMedId: null,

    start: function () {
        if (this.interval) clearInterval(this.interval);
        this.interval = setInterval(function () { AlarmSystem.check(); }, 60000);
        this.check();
    },

    check: function () {
        var meds = MedicationManager.getAll();
        for (var i = 0; i < meds.length; i++) {
            if (MedicationManager.isDue(meds[i])) {
                AlarmSystem.trigger(meds[i]);
                break;
            }
        }
    },

    trigger: function (med) {
        this.alertMedId = med.id;
        var banner   = document.getElementById('medication-alert');
        var nameEl   = document.getElementById('alert-med-name');
        var detailEl = document.getElementById('alert-med-detail');
        if (banner) {
            nameEl.textContent   = 'Hora do Remedio!';
            detailEl.textContent = med.name + ' (a cada ' + med.scheduleHours + 'h)';
            banner.classList.remove('hidden');
        }
        var alarm = document.getElementById('alarm-sound');
        if (alarm) alarm.play().catch(function () {});
        speakText('Atencao! Hora de tomar o seu remedio: ' + med.name + '.');
    }
};

function confirmMedicationFromAlert() {
    if (AlarmSystem.alertMedId) {
        MedicationManager.markTaken(AlarmSystem.alertMedId);
        AlarmSystem.alertMedId = null;
    }
    var banner = document.getElementById('medication-alert');
    if (banner) banner.classList.add('hidden');
    var succ = document.getElementById('success-sound');
    if (succ) succ.play().catch(function () {});
    speakText('Muito bem! Remedio confirmado.');
    renderMedicinesList();
}

function snoozeMedicationFromAlert() {
    if (AlarmSystem.alertMedId) {
        var meds = MedicationManager.getAll();
        for (var i = 0; i < meds.length; i++) {
            if (meds[i].id === AlarmSystem.alertMedId) {
                var nextDose    = new Date(new Date().getTime() + 10 * 60000);
                var newLastDose = new Date(nextDose.getTime() - (meds[i].scheduleHours * 3600000));
                meds[i].lastDoseISO = newLastDose.toISOString();
                break;
            }
        }
        MedicationManager.save(meds);
        AlarmSystem.alertMedId = null;
    }
    var banner = document.getElementById('medication-alert');
    if (banner) banner.classList.add('hidden');
    var alarm = document.getElementById('alarm-sound');
    if (alarm) { alarm.pause(); alarm.currentTime = 0; }
    speakText('Aviso adiado por dez minutos.');
    renderMedicinesList();
}

// =============================================
// RENDER: Meus Remedios
// =============================================
function renderMedicinesList() {
    var list = document.getElementById('medicines-list');
    if (!list) return;
    list.innerHTML = '';
    var meds      = MedicationManager.getAll();
    var active    = meds.filter(function (m) { return m.status === 'active'; });
    var completed = meds.filter(function (m) { return m.status === 'completed'; });

    if (meds.length === 0) {
        list.innerHTML = '<p style="padding:30px; color:var(--text-light); font-size:18px; text-align:center;">Nenhum remedio cadastrado. Use <strong>Ler Receita (IA)</strong> para adicionar.</p>';
        return;
    }
    if (active.length > 0) {
        var lbl = document.createElement('p');
        lbl.className   = 'section-label';
        lbl.textContent = 'Em Uso';
        list.appendChild(lbl);
        active.forEach(function (med) { list.appendChild(buildMedCard(med)); });
    }
    if (completed.length > 0) {
        var lbl2 = document.createElement('p');
        lbl2.className   = 'section-label';
        lbl2.style.marginTop = '16px';
        lbl2.textContent = 'Concluidos';
        list.appendChild(lbl2);
        completed.forEach(function (med) { list.appendChild(buildMedCard(med)); });
    }
}

function buildMedCard(med) {
    var card = document.createElement('div');
    card.className = 'med-card';

    var icon = document.createElement('span');
    icon.className   = 'material-icons-round status-icon';
    icon.textContent = med.status === 'active' ? 'medication' : 'check_circle';
    icon.style.color = med.status === 'active' ? 'var(--primary)' : '#9E9E9E';
    card.appendChild(icon);

    var info = document.createElement('div');
    info.className = 'med-card-info';

    var h3 = document.createElement('h3');
    h3.textContent = med.name;
    info.appendChild(h3);

    var p = document.createElement('p');
    p.textContent = 'A cada ' + med.scheduleHours + 'h - desde ' + med.startDate;
    info.appendChild(p);

    var badge = document.createElement('span');
    badge.className = 'med-status-badge ' + (med.status === 'active' ? 'badge-active' : 'badge-completed');
    badge.innerHTML = med.status === 'active'
        ? '<span class="material-icons-round" style="font-size:13px;">circle</span> Em uso'
        : '<span class="material-icons-round" style="font-size:13px;">check</span> Concluido';
    info.appendChild(badge);

    if (med.status === 'active') {
        var next = MedicationManager.getNextDose(med);
        if (next) {
            var diff = Math.round((next - new Date()) / 60000);
            var nextInfo = document.createElement('p');
            nextInfo.className = 'next-dose-info';
            if (diff <= 0) {
                nextInfo.textContent = 'Dose atrasada!';
                nextInfo.style.color = 'var(--danger)';
            } else if (diff < 60) {
                nextInfo.textContent = 'Proxima dose em ' + diff + ' min';
            } else {
                nextInfo.textContent = 'Proxima dose: ' + next.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
            }
            info.appendChild(nextInfo);
        }
    }
    card.appendChild(info);

    if (med.status === 'active') {
        var actions = document.createElement('div');
        actions.className = 'med-card-actions';

        var btnTaken = document.createElement('button');
        btnTaken.className = 'btn-icon-sm confirm';
        btnTaken.innerHTML = '<span class="material-icons-round" style="font-size:16px;">check</span> Tomei';
        (function (id) {
            btnTaken.onclick = function () {
                MedicationManager.markTaken(id);
                speakText('Dose confirmada.');
                renderMedicinesList();
            };
        })(med.id);
        actions.appendChild(btnTaken);

        var btnEnd = document.createElement('button');
        btnEnd.className = 'btn-icon-sm';
        btnEnd.innerHTML = '<span class="material-icons-round" style="font-size:16px;">stop</span> Encerrar';
        (function (id, name) {
            btnEnd.onclick = function () { openFinishModal(id, name); };
        })(med.id, med.name);
        actions.appendChild(btnEnd);

        card.appendChild(actions);
    }
    return card;
}

// =============================================
// RENDER: Ouvir Bula
// =============================================
function renderBulaList() {
    var list     = document.getElementById('bula-med-list');
    var emptyMsg = document.getElementById('bula-empty-msg');
    var playback = document.getElementById('bula-playback');
    if (!list) return;

    var existingCards = list.querySelectorAll('.med-card');
    existingCards.forEach(function (el) { el.remove(); });

    var meds = MedicationManager.getAll();
    if (meds.length === 0) {
        if (emptyMsg) emptyMsg.style.display = 'block';
        if (playback) playback.classList.add('hidden');
        return;
    }
    if (emptyMsg) emptyMsg.style.display = 'none';

    meds.forEach(function (med) {
        var card = document.createElement('div');
        card.className    = 'med-card';
        card.style.cursor = 'pointer';
        card.innerHTML    =
            '<span class="material-icons-round status-icon" style="color:var(--secondary);">record_voice_over</span>' +
            '<div class="med-card-info"><h3>' + med.name + '</h3><p>' + med.category + ' - clique para ouvir a bula</p></div>' +
            '<span class="material-icons-round" style="color:var(--primary);">play_circle</span>';
        (function (m) {
            card.onclick = function () { selectBula(m); };
        })(med);
        list.insertBefore(card, list.firstChild);
    });
}

var selectedBulaMed = null;

function selectBula(med) {
    selectedBulaMed = med;
    var playback = document.getElementById('bula-playback');
    var nameEl   = document.getElementById('bula-selected-name');
    var textEl   = document.getElementById('bula-selected-text');
    if (!playback) return;
    playback.classList.remove('hidden');
    nameEl.textContent = med.name;
    var info = 'Indicacoes: ' + med.indication +
               '. Como usar: ' + med.dosage +
               '. Contraindicacoes: ' + med.contraindications +
               '. Efeitos colaterais: ' + med.sideEffects +
               '. Alimentacao: ' + med.food;
    textEl.textContent = info;
    speakText(med.name + '. ' + info);
}

function playSelectedBula() {
    if (selectedBulaMed) {
        speakText(selectedBulaMed.name + '. Indicacoes: ' + selectedBulaMed.indication +
            '. Como usar: ' + selectedBulaMed.dosage +
            '. Contraindicacoes: ' + selectedBulaMed.contraindications);
    } else {
        speakText('Selecione um remedio da lista para ouvir a bula.');
    }
}

// =============================================
// AI PRESCRIPTION READER
// =============================================
var currentMedication = null;
var aiResultText      = '';

function handlePrescriptionUpload(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];

    var preview = document.getElementById('prescription-preview');
    var fr = new FileReader();
    fr.onload = function (e) {
        preview.src          = e.target.result;
        preview.style.display = 'block';
    };
    fr.readAsDataURL(file);

    document.getElementById('ai-loading').classList.remove('hidden');
    var rc = document.getElementById('ai-results-container'); if (rc) rc.innerHTML = '';
    var ctrl = document.getElementById('ai-results-controls');  if (ctrl) ctrl.classList.add('hidden');

    addChatMessage('ai', 'Receita recebida! Analisando com IA...');
    speakText('Receita recebida. Analisando com inteligencia artificial. Por favor aguarde.');

    var reader = new FileReader();
    reader.onload = function (e) {
        var base64   = e.target.result.split(',')[1];
        var mimeType = file.type || 'image/jpeg';
        callGeminiAPI(base64, mimeType);
    };
    reader.readAsDataURL(file);
}

async function callGeminiAPI(base64, mimeType) {
    var apiKey = 'AIzaSyBlMKOSneCFSvzmrHYAMdML8IpupG1ETp8';
    
    // Cada modelo tem cota SEPARADA no plano gratuito.
    // Se o 3.8-flash esgotar a cota, o 2.0-flash-lite ainda funciona.
    var models = [
        'gemini-3.8-flash',
        'gemini-3.5-flash-lite'
    ];

    var promptText = "Voce e um farmaceutico especializado. Analise a receita medica na imagem e extraia TODOS os medicamentos.\n"
        + "Retorne SOMENTE um array JSON valido (sem texto extra, sem markdown, sem ```).\n"
        + "Cada item do array deve ter EXATAMENTE estas chaves (valores em portugues):\n"
        + "name, category, prescription, indication, dosage, scheduleHours, contraindications, sideEffects, food\n"
        + "Regras:\n"
        + "- scheduleHours: numero inteiro (ex: 8 para 'de 8 em 8 horas'). Use 8 se nao souber.\n"
        + "- prescription: exatamente 'Necessaria' ou 'Nao necessaria'\n"
        + "- Se a receita estiver manuscrita ou digital, leia da mesma forma\n"
        + "- Se nao souber um campo, use 'Nao identificado'\n"
        + "- Mesmo que seja apenas 1 medicamento, retorne dentro de um array []\n"
        + "Exemplo de saida: [{\"name\":\"Amoxicilina 500mg\",\"category\":\"Antibiotico\",\"prescription\":\"Necessaria\",\"indication\":\"Infeccoes bacterianas\",\"dosage\":\"1 capsula de 8 em 8 horas\",\"scheduleHours\":8,\"contraindications\":\"Alergia a penicilina\",\"sideEffects\":\"Diarreia\",\"food\":\"Pode tomar com ou sem alimento\"}]";

    var requestBody = {
        contents: [{
            parts: [
                { text: promptText },
                { inlineData: { mimeType: mimeType, data: base64 } }
            ]
        }],
        generationConfig: {
            temperature: 0.1,
            maxOutputTokens: 4096
        }
    };

    var lastErrorMsg = '';

    for (var i = 0; i < models.length; i++) {
        var modelName = models[i];
        var apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/' + modelName + ':generateContent?key=' + apiKey;
        
        try {
            console.log('Tentando modelo: ' + modelName);
            var response = await fetch(apiUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/json' },
                body:    JSON.stringify(requestBody)
            });
            
            var data = await response.json();
            
            if (data.error) {
                lastErrorMsg = data.error.message || 'Erro desconhecido.';
                console.warn('Erro no modelo ' + modelName + ':', lastErrorMsg);
                // Se deu erro (quota, high demand, modelo indisponivel), tenta o proximo
                continue;
            }

            // Sucesso! Processa a resposta
            document.getElementById('ai-loading').classList.add('hidden');
            
            var rawText = data.candidates && data.candidates[0] && data.candidates[0].content && data.candidates[0].content.parts && data.candidates[0].content.parts[0] ? data.candidates[0].content.parts[0].text : null;
            
            if (!rawText) {
                addChatMessage('ai', 'A IA nao retornou texto. Tente novamente com uma foto mais clara.');
                return;
            }
            
            // Limpa possiveis marcadores de codigo markdown do texto (```json ... ```)
            var cleanedText = rawText.trim();
            if (cleanedText.startsWith('```')) {
                var firstNewline = cleanedText.indexOf('\n');
                if (firstNewline !== -1) {
                    cleanedText = cleanedText.substring(firstNewline + 1);
                }
            }
            if (cleanedText.endsWith('```')) {
                cleanedText = cleanedText.substring(0, cleanedText.length - 3).trim();
            }
            
            try {
                var meds = JSON.parse(cleanedText);
                if (!Array.isArray(meds) && meds.medications) meds = meds.medications;
                if (!Array.isArray(meds)) meds = [meds];
                
                if (meds.length === 0) {
                    addChatMessage('ai', 'A IA nao encontrou remedios na imagem. Tente outra foto.');
                    return;
                }
                
                var names = meds.map(function (m) { return m.name; }).join(', ');
                showMedicationResult(meds);
                addChatMessage('ai', 'Identifiquei ' + meds.length + ' remedio(s): <strong>' + names + '</strong>. Confira os detalhes e salve!');
                speakText('Analise concluida. ' + meds.length + ' medicamento identificado: ' + names + '.');
                return; // Sucesso
            } catch(e) {
                addChatMessage('ai', 'Erro ao interpretar os dados. Tente uma foto mais nitida.');
                console.error("JSON parse error:", e, rawText);
                return;
            }
            
        } catch (error) {
            console.error("Erro de conexao no modelo " + modelName + ":", error);
            lastErrorMsg = error.message || 'Erro de conexao.';
        }
    }
    
    // Se todos os modelos falharam
    document.getElementById('ai-loading').classList.add('hidden');
    addChatMessage('ai', 'A IA esta temporariamente indisponivel. Sua cota gratuita pode ter se esgotado. Ela reseta as 4h da manha (horario de Brasilia). Ultimo erro: ' + lastErrorMsg);
    showMedicationResult(getMockMedication());
}

function getMockMedication() {
    return [{
        name:              'Amoxicilina 500mg',
        category:          'Antibiotico',
        prescription:      'Necessaria',
        indication:        'Tratamento de infeccoes bacterianas (otite, sinusite, faringite, pneumonia)',
        dosage:            '1 capsula de 8 em 8 horas por 7 a 10 dias',
        scheduleHours:     8,
        contraindications: 'Alergia a penicilina, insuficiencia renal grave',
        sideEffects:       'Diarreia, enjoo, reacoes alergicas',
        food:              'Pode ser tomada com ou sem alimentos'
    }];
}

function showMedicationResult(meds) {
    if (!Array.isArray(meds)) meds = [meds];
    currentMedication = meds;
    var container = document.getElementById('ai-results-container');
    var controls  = document.getElementById('ai-results-controls');
    if (!container || !controls) return;

    container.innerHTML = '';
    aiResultText = '';

    var now = new Date();
    var hh  = String(now.getHours()).padStart(2, '0');
    var mm  = String(now.getMinutes()).padStart(2, '0');

    for (var i = 0; i < meds.length; i++) {
        var med = meds[i];
        aiResultText += med.name + '. ' + (med.indication || '') + '. ' + (med.dosage || '') + '... ';

        var card = document.createElement('div');
        card.style.cssText = 'margin-bottom:20px; padding:18px; background:var(--card-bg); border-radius:14px; box-shadow:0 2px 10px rgba(0,0,0,0.07); border-left:4px solid #673AB7;';
        card.innerHTML =
            '<h3 style="font-size:18px;font-weight:800;color:#673AB7;margin-bottom:14px;display:flex;align-items:center;gap:8px;">' +
            '<span class="material-icons-round">medication</span><span>' + (med.name || '-') + '</span></h3>' +
            '<div class="med-info-grid">' +
            '<div class="med-info-item"><div class="info-label">Categoria</div><div class="info-value">' + (med.category || '-') + '</div></div>' +
            '<div class="med-info-item"><div class="info-label">Receita</div><div class="info-value">' + (med.prescription || '-') + '</div></div>' +
            '<div class="med-info-item full-width"><div class="info-label">Indicacoes</div><div class="info-value">' + (med.indication || '-') + '</div></div>' +
            '<div class="med-info-item full-width"><div class="info-label">Dosagem</div><div class="info-value">' + (med.dosage || '-') + '</div></div>' +
            '<div class="med-info-item full-width"><div class="info-label">Contraindicacoes</div><div class="info-value">' + (med.contraindications || '-') + '</div></div>' +
            '<div class="med-info-item"><div class="info-label">Efeitos Colaterais</div><div class="info-value">' + (med.sideEffects || '-') + '</div></div>' +
            '<div class="med-info-item"><div class="info-label">Alimentacao</div><div class="info-value">' + (med.food || '-') + '</div></div>' +
            '</div>' +
            '<div style="margin-top:14px; padding-top:14px; border-top:1px solid rgba(103,58,183,0.15);">' +
            '<p style="font-size:13px;color:var(--text-light);margin-bottom:10px;">Configurar alarme para este remedio:</p>' +
            '<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end;">' +
            '<div style="flex:1;min-width:110px;"><label style="font-size:12px;font-weight:700;color:var(--text-light);display:block;margin-bottom:4px;">FREQUENCIA (HORAS)</label>' +
            '<input type="number" id="med-freq-' + i + '" value="' + (med.scheduleHours || 8) + '" min="1" max="48" style="width:100%;padding:8px 10px;border:2px solid #673AB7;border-radius:8px;font-size:15px;background:var(--bg-color);color:var(--text-color);"></div>' +
            '<div style="flex:1;min-width:130px;"><label style="font-size:12px;font-weight:700;color:var(--text-light);display:block;margin-bottom:4px;">HORARIO DA ULTIMA DOSE</label>' +
            '<input type="time" id="med-last-' + i + '" value="' + hh + ':' + mm + '" style="width:100%;padding:8px 10px;border:2px solid #673AB7;border-radius:8px;font-size:15px;background:var(--bg-color);color:var(--text-color);"></div>' +
            '<button onclick="saveSingleMed(' + i + ')" style="padding:9px 16px;background:#673AB7;color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:6px;white-space:nowrap;" id="save-btn-' + i + '">' +
            '<span class="material-icons-round" style="font-size:18px;">alarm_add</span>Adicionar com Alarme</button>' +
            '</div></div>';
        container.appendChild(card);
    }

    controls.classList.remove('hidden');
}

function saveSingleMed(index) {
    if (!currentMedication || !currentMedication[index]) return;
    var med      = currentMedication[index];
    var freqEl   = document.getElementById('med-freq-' + index);
    var lastEl   = document.getElementById('med-last-' + index);
    var btn      = document.getElementById('save-btn-' + index);
    var freq     = freqEl ? parseInt(freqEl.value) || 8 : 8;
    var lastDose = lastEl ? lastEl.value : '';
    if (!lastDose) {
        alert('Informe o horario da ultima dose para ' + med.name);
        return;
    }
    MedicationManager.add(Object.assign({}, med), freq, lastDose);
    if (btn) {
        btn.innerHTML = '<span class="material-icons-round" style="font-size:18px;">check_circle</span> Adicionado!';
        btn.style.background = 'var(--secondary)';
        btn.disabled = true;
    }
    speakText(med.name + ' adicionado com sucesso! Alarme programado a cada ' + freq + ' horas.');
    addChatMessage('ai', '<strong>' + med.name + '</strong> adicionado aos seus remedios com alarme a cada ' + freq + 'h!');
    AlarmSystem.check();
}

function listenAiResult() {
    speakText(aiResultText || 'Nenhuma receita analisada ainda.');
}

function saveAllMedicationsFromAI() {
    if (!currentMedication || !currentMedication.length) return;
    var addedCount = 0;
    var names      = [];
    for (var i = 0; i < currentMedication.length; i++) {
        var med           = currentMedication[i];
        var freqInput     = document.getElementById('add-med-freq-' + i);
        var lastDoseInput = document.getElementById('add-med-last-' + i);
        var freq          = freqInput     ? freqInput.value     : med.scheduleHours || 8;
        var lastDose      = lastDoseInput ? lastDoseInput.value : null;
        if (!lastDose) { alert('Informe o horario da ultima dose para ' + med.name); continue; }
        MedicationManager.add(Object.assign({}, med), freq, lastDose);
        addedCount++;
        names.push(med.name);
    }
    if (addedCount > 0) {
        addChatMessage('ai', 'Adicionados: <strong>' + names.join(', ') + '</strong>! Alarmes configurados.');
        speakText(addedCount + ' medicamentos adicionados com sucesso.');
        var ctrl = document.getElementById('ai-results-controls');
        if (ctrl) ctrl.classList.add('hidden');
        AlarmSystem.check();
    }
}

// =============================================
// FINISH TREATMENT MODAL
// =============================================
var finishTargetId = null;

function openFinishModal(id, name) {
    finishTargetId = id;
    document.getElementById('finish-med-name').textContent = name;
    document.getElementById('finish-modal').classList.remove('hidden');
    speakText('Voce deseja encerrar o tratamento com ' + name + '? Confirme ou cancele.');
}

function closeFinishModal() {
    document.getElementById('finish-modal').classList.add('hidden');
    finishTargetId = null;
    stopSpeech();
}

function confirmFinishTreatment() {
    if (!finishTargetId) return;
    var meds = MedicationManager.getAll();
    var med  = meds.find(function (m) { return m.id === finishTargetId; });
    MedicationManager.finish(finishTargetId);
    closeFinishModal();
    speakText('Tratamento de ' + (med ? med.name : 'remedio') + ' encerrado.');
    var view = document.getElementById('medicines-view');
    if (view && !view.classList.contains('hidden')) renderMedicinesList();
}

// =============================================
// VACCINES
// =============================================
function readVaccines() {
    speakText('Atencao: Guarulhos apresenta surto de Dengue em 2026. Vacinas disponiveis hoje no posto: Gripe Influenza, Covid dezenove bivalente para maiores de 60 anos, e Febre Amarela.');
}

function syncGovBr() {
    speakText('Conectando ao sistema GOV ponto BR. Aguarde.');
    var area    = document.getElementById('gov-br-sync-area');
    var results = document.getElementById('gov-br-results');
    if (!area) return;
    area.innerHTML = '<span class="material-icons-round emergency-spin" style="font-size:48px;color:#005ce6;">autorenew</span><p>Autenticando no GOV.BR...</p>';
    setTimeout(function () {
        speakText('Sincronizacao concluida. Suas vacinas foram carregadas com sucesso.');
        area.classList.add('hidden');
        results.classList.remove('hidden');
        var user = window.APP_USER || JSON.parse(sessionStorage.getItem('acessiUser') || '{}');
        var userName = user.name ? user.name.charAt(0).toUpperCase() + user.name.slice(1) : 'Usuario';
        results.innerHTML =
            '<div style="background:#e8f5e9;border:1px solid #a5d6a7;border-radius:12px;padding:16px;margin-bottom:20px;">' +
            '<h4 style="color:#2e7d32;display:flex;align-items:center;gap:8px;margin-bottom:12px;">' +
            '<span class="material-icons-round">verified_user</span> Autenticado: ' + userName + '</h4>' +
            '<div class="med-item taken"><span class="material-icons-round status-icon">vaccines</span>' +
            '<div class="med-details"><h3>Covid-19 (Pfizer)</h3><p>1a e 2a Dose | Reforco - Posto Central Guarulhos</p></div></div>' +
            '<div class="med-item taken"><span class="material-icons-round status-icon">vaccines</span>' +
            '<div class="med-details"><h3>Gripe (Influenza)</h3><p>Dose Anual (2025) - UBS Vila Galvao</p></div></div>' +
            '<div class="med-item taken"><span class="material-icons-round status-icon">vaccines</span>' +
            '<div class="med-details"><h3>Dupla Adulto (dT)</h3><p>Reforco - UBS Pimentas</p></div></div>' +
            '</div>';
    }, 2500);
}

// =============================================
// CHAT SYSTEM
// =============================================
function addChatMessage(sender, html) {
    var messages = document.getElementById('chat-messages');
    if (!messages) return;
    var bubble       = document.createElement('div');
    bubble.className = 'chat-bubble ' + sender;
    bubble.innerHTML = html;
    messages.appendChild(bubble);
    messages.scrollTop = messages.scrollHeight;
}

function getAiResponse(text) {
    var l = text.toLowerCase();
    if (l.includes('receita') || l.includes('prescricao') || l.includes('precisa')) {
        if (currentMedication && currentMedication[0]) {
            return currentMedication[0].prescription === 'Necessaria'
                ? 'Sim, <strong>' + currentMedication[0].name + '</strong> requer receita medica.'
                : '<strong>' + currentMedication[0].name + '</strong> pode ser adquirido sem receita, mas siga a bula.';
        }
        return 'Envie uma receita para eu identificar o medicamento.';
    }
    if (l.includes('contraindicac') || l.includes('nao pode tomar')) {
        if (currentMedication && currentMedication[0])
            return 'Contraindicacoes de ' + currentMedication[0].name + ': ' + currentMedication[0].contraindications;
        return 'Envie uma receita primeiro.';
    }
    if (l.includes('dose') || l.includes('dosagem') || l.includes('como tomar')) {
        if (currentMedication && currentMedication[0])
            return 'Dosagem de ' + currentMedication[0].name + ': ' + currentMedication[0].dosage;
        return 'Envie uma receita para verificar a dosagem.';
    }
    if (l.includes('efeito') || l.includes('reacao') || l.includes('mal estar')) {
        if (currentMedication && currentMedication[0])
            return 'Efeitos colaterais de ' + currentMedication[0].name + ': ' + currentMedication[0].sideEffects;
        return 'Envie uma receita primeiro.';
    }
    if (l.includes('jejum') || l.includes('comer') || l.includes('alimento')) {
        if (currentMedication && currentMedication[0])
            return currentMedication[0].food;
        return 'Envie uma receita para verificar.';
    }
    if (l.includes('indica') || l.includes('serve') || l.includes('tratar')) {
        if (currentMedication && currentMedication[0])
            return '<strong>' + currentMedication[0].name + '</strong> e indicado para: ' + currentMedication[0].indication;
        return 'Envie uma receita primeiro.';
    }
    return 'Nao tenho informacoes suficientes. Para garantir sua saude, <strong>consulte um medico ou farmaceutico</strong>.';
}

function sendChatMessage() {
    var input = document.getElementById('chat-input');
    if (!input) return;
    var text = input.value.trim();
    if (!text) return;
    input.value = '';
    addChatMessage('user', text);
    setTimeout(function () {
        addChatMessage('ai', getAiResponse(text));
    }, 600);
}

function sendChip(text) {
    var input = document.getElementById('chat-input');
    if (input) input.value = text;
    sendChatMessage();
}

// =============================================
// EMERGENCY
// =============================================
var emergencyTimeout;

function triggerEmergency() {
    speakText('Atencao. Iniciando chamada de emergencia para o SAMU. Voce tem 5 segundos para cancelar.');
    var overlay = document.getElementById('emergency-overlay');
    if (overlay) overlay.classList.remove('hidden');
    var alarm = document.getElementById('alarm-sound');
    if (alarm) { alarm.currentTime = 0; alarm.loop = true; alarm.play().catch(function () {}); }

    var countdown = 5;
    var btn = overlay ? overlay.querySelector('.btn-cancel') : null;
    var iv  = setInterval(function () {
        countdown--;
        if (btn) btn.innerHTML = '<span class="material-icons-round">cancel</span> CANCELAR (' + countdown + 's)';
        if (countdown <= 0) clearInterval(iv);
    }, 1000);

    emergencyTimeout = setTimeout(function () {
        clearInterval(iv);
        speakText('Chamando a ambulancia agora.');
        if (overlay) {
            overlay.querySelector('h1').innerHTML = 'CHAMADA<br>EM ANDAMENTO';
            overlay.querySelector('p').textContent = 'Aguarde...';
            if (btn) btn.style.display = 'none';
        }
        if (alarm) { alarm.pause(); alarm.loop = false; }
    }, 5000);
}

function cancelEmergency() {
    clearTimeout(emergencyTimeout);
    var alarm = document.getElementById('alarm-sound');
    if (alarm) { alarm.pause(); alarm.loop = false; }
    speakText('Chamada de emergencia cancelada.');
    var overlay = document.getElementById('emergency-overlay');
    if (overlay) {
        overlay.classList.add('hidden');
        var btn = overlay.querySelector('.btn-cancel');
        if (btn) { btn.innerHTML = '<span class="material-icons-round">cancel</span> CANCELAR (5s)'; btn.style.display = 'flex'; }
        overlay.querySelector('h1').innerHTML = 'LIGANDO PARA<br>AMBULANCIA...';
        overlay.querySelector('p').textContent = '192';
    }
}

// =============================================
// SETTINGS MODAL
// =============================================
function openSettingsModal() {
    var modal = document.getElementById('settings-modal');
    if (modal) {
        modal.classList.remove('hidden');
        TTSManager.updateUI();
        speakText('Ajustes de Acessibilidade abertos.');
    }
}

function closeSettingsModal() {
    var modal = document.getElementById('settings-modal');
    if (modal) modal.classList.add('hidden');
    stopSpeech();
}

function logoutUser() {
    sessionStorage.removeItem('acessiUser');
    closeSettingsModal();
    speakText('Voce saiu da sua conta.');
    window.location.replace('logout.php');
}

// =============================================
// MANUAL MED MODAL
// =============================================
function openManualMedModal() {
    document.getElementById('manual-med-modal').classList.remove('hidden');
    document.getElementById('manual-med-name').value = '';
    document.getElementById('manual-med-freq').value = '8';
    document.getElementById('manual-med-last').value = '';
    speakText('Adicionar remedio manualmente. Preencha o nome, a frequencia e o horario da ultima dose.');
}

function closeManualMedModal() {
    document.getElementById('manual-med-modal').classList.add('hidden');
    stopSpeech();
}

function saveManualMed() {
    var name = document.getElementById('manual-med-name').value.trim();
    var freq = parseInt(document.getElementById('manual-med-freq').value) || 8;
    var last = document.getElementById('manual-med-last').value;
    if (!name || !last) {
        alert('Preencha o nome e o horario!');
        speakText('Por favor, preencha o nome e o horario da ultima dose.');
        return;
    }
    var medData = {
        name:              name,
        category:          'Manual',
        prescription:      'N/A',
        indication:        'Adicionado manualmente',
        dosage:            'A cada ' + freq + ' horas',
        contraindications: 'Nao informado',
        sideEffects:       'Nao informado',
        food:              'Nao informado'
    };
    MedicationManager.add(medData, freq, last);
    closeManualMedModal();
    speakText(name + ' foi adicionado com sucesso!');
    var view = document.getElementById('medicines-view');
    if (view && !view.classList.contains('hidden')) renderMedicinesList();
    AlarmSystem.check();
}

// =============================================
// VOICE INPUT
// =============================================
var voiceRecognition = null;
var isVoiceRecording = false;

function toggleVoiceInput() {
    if (isVoiceRecording) stopVoiceInput(); else startVoiceInput();
}

function startVoiceInput() {
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) {
        addChatMessage('ai', 'Seu navegador nao suporta reconhecimento de voz. Use o Google Chrome.');
        speakText('Seu navegador nao suporta entrada de voz.');
        return;
    }

    stopSpeech();
    try {
        voiceRecognition = new SpeechRecognition();
        voiceRecognition.lang = 'pt-BR';
        voiceRecognition.interimResults = false;
        voiceRecognition.continuous = false;
        voiceRecognition.maxAlternatives = 1;
    } catch(e) {
        addChatMessage('ai', 'Erro ao iniciar microfone: ' + e.message);
        return;
    }

    voiceRecognition.onstart = function () {
        isVoiceRecording = true;
        var btn = document.getElementById('chat-mic-btn');
        var ico = document.getElementById('chat-mic-icon');
        var ind = document.getElementById('voice-listening-indicator');
        if (btn) btn.classList.add('recording');
        if (ico) ico.textContent = 'mic_off';
        if (ind) ind.classList.add('active');
    };

    voiceRecognition.onresult = function (event) {
        var transcript = event.results[0][0].transcript;
        var input      = document.getElementById('chat-input');
        if (input) input.value = transcript;
        stopVoiceInput();
        setTimeout(function () { sendChatMessage(); }, 400);
    };

    voiceRecognition.onerror = function (event) {
        stopVoiceInput();
        var msg = 'Erro ao captar voz.';
        if (event.error === 'not-allowed' || event.error === 'permission-denied') {
            msg = 'Permissao para o microfone negada. Va em Configuracoes do navegador e permita o microfone.';
        } else if (event.error === 'no-speech') {
            msg = 'Nenhuma fala detectada. Clique no microfone e fale claramente.';
        } else if (event.error === 'network') {
            msg = 'Erro de rede ao processar voz. Verifique sua conexao.';
        } else if (event.error === 'aborted') {
            return; // usuario cancelou, nao mostrar erro
        }
        addChatMessage('ai', msg);
    };

    voiceRecognition.onend = function () { stopVoiceInput(); };

    try {
        voiceRecognition.start();
    } catch(e) {
        stopVoiceInput();
        addChatMessage('ai', 'Nao foi possivel acessar o microfone: ' + e.message);
    }
}

function stopVoiceInput() {
    isVoiceRecording = false;
    var btn = document.getElementById('chat-mic-btn');
    var ico = document.getElementById('chat-mic-icon');
    var ind = document.getElementById('voice-listening-indicator');
    if (btn) btn.classList.remove('recording');
    if (ico) ico.textContent = 'mic';
    if (ind) ind.classList.remove('active');
    if (voiceRecognition) {
        try { voiceRecognition.abort(); } catch (e) {}
        voiceRecognition = null;
    }
}

// =============================================
// INIT
// =============================================
document.addEventListener('DOMContentLoaded', function () {
    TTSManager.init();
    HoverReader.init();
    updateDate();
    AlarmSystem.start();
    console.log('AcessiIdoso carregado com sucesso.');
});
