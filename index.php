<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}
// Pass user info to JS via a hidden script tag
$nome = htmlspecialchars($_SESSION['nome_usuario'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcessiIdoso - Menu Principal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<script>
// Injeta dados da sessao PHP no JS
window.APP_USER = {
    name: <?php echo json_encode($nome); ?>,
    email: <?php echo json_encode($_SESSION['email_usuario'] ?? ''); ?>
};
// Sincroniza com sessionStorage para compatibilidade
sessionStorage.setItem('acessiUser', JSON.stringify(window.APP_USER));
</script>

<div id="app">

    <!-- HEADER -->
    <header class="header">
        <img src="assets/logo_tcc.jpg" alt="Logo AcessiIdoso" class="logo">
        <div class="user-greeting">
            <h1 id="greeting">Ola, <?php echo $nome; ?>!</h1>
            <p id="current-date" aria-live="polite">Carregando data...</p>
        </div>
        <div class="header-actions">
            <button id="toggle-hover-tts" class="btn-hover-tts active" onclick="toggleHoverTTS()"
                aria-label="Leitura ao passar o mouse: Ligada" title="Ligar/desligar leitura ao passar o mouse">
                <span class="material-icons-round" id="hover-tts-icon">record_voice_over</span>
                <span class="btn-hover-tts-text">Voz no Mouse: <strong id="hover-tts-status-text">LIGADA</strong></span>
            </button>
        </div>
    </header>

    <!-- MAIN -->
    <main class="main-content">
        <!-- Medication Alert Banner -->
        <div id="medication-alert" class="alert-banner hidden">
            <div class="alert-icon-container pulse-animation">
                <span class="material-icons-round alert-icon">notifications_active</span>
            </div>
            <div class="alert-text">
                <h2 id="alert-med-name">Hora do Remedio!</h2>
                <p id="alert-med-detail">-</p>
            </div>
            <div style="display:flex; gap:12px; margin-top:12px;">
                <button class="btn-confirm-med" onclick="snoozeMedicationFromAlert()"
                    aria-label="Adiar aviso por 10 minutos" style="background-color:var(--warning); flex:1; min-width:110px;">
                    <span class="material-icons-round">snooze</span> Adiar
                </button>
                <button class="btn-confirm-med" onclick="confirmMedicationFromAlert()"
                    aria-label="Confirmar que tomou o remedio" style="flex:1;">
                    <span class="material-icons-round">check_circle</span> Ja tomei
                </button>
            </div>
        </div>

        <!-- Action Grid -->
        <div class="action-grid">
            <button class="action-card color-primary" onclick="showView('medicines-view')" aria-label="Meus Remedios">
                <div class="card-icon"><span class="material-icons-round">medication</span></div>
                <h2>Meus Remedios</h2>
            </button>

            <button class="action-card color-secondary" onclick="showView('bula-view')" aria-label="Ouvir Bula">
                <div class="card-icon"><span class="material-icons-round">record_voice_over</span></div>
                <h2>Ouvir Bula</h2>
            </button>

            <button class="action-card color-danger" onclick="triggerEmergency()" aria-label="Pedir Ajuda de Emergencia">
                <div class="card-icon pulse-danger"><span class="material-icons-round">emergency</span></div>
                <h2>Pedir Ajuda</h2>
            </button>

            <button class="action-card color-neutral" onclick="openSettingsModal()" aria-label="Ajustes e Acessibilidade">
                <div class="card-icon"><span class="material-icons-round">settings</span></div>
                <h2>Ajustes</h2>
            </button>

            <button class="action-card" style="background-color:var(--card-bg);"
                onclick="showView('ai-prescription-view')" aria-label="Ler Receita com IA">
                <div class="card-icon" style="background-color:#EDE7F6; color:#673AB7;">
                    <span class="material-icons-round">document_scanner</span>
                </div>
                <h2>Ler Receita (IA)</h2>
            </button>

            <button class="action-card" style="background-color:var(--card-bg);"
                onclick="showView('vaccines-view')" aria-label="Saude Guarulhos">
                <div class="card-icon" style="background-color:#E0F7FA; color:#00BCD4;">
                    <span class="material-icons-round">vaccines</span>
                </div>
                <h2>Guarulhos</h2>
            </button>
        </div>
    </main>

    <!-- MEDICINES VIEW -->
    <div id="medicines-view" class="view hidden">
        <header class="view-header">
            <button class="btn-back" onclick="hideView('medicines-view')" aria-label="Voltar">
                <span class="material-icons-round">arrow_back</span> Voltar
            </button>
            <h2>Meus Remedios</h2>
        </header>
        <div style="padding:16px 20px; text-align:center;">
            <button class="btn-large-action" onclick="openManualMedModal()" aria-label="Adicionar remedio manualmente"
                style="width:100%; font-size:16px; background-color:#673AB7;">
                <span class="material-icons-round">add</span> Adicionar Manualmente
            </button>
        </div>
        <div class="med-list" id="medicines-list"></div>
    </div>

    <!-- BULA VIEW -->
    <div id="bula-view" class="view hidden">
        <header class="view-header">
            <button class="btn-back" onclick="stopSpeech(); hideView('bula-view')" aria-label="Voltar">
                <span class="material-icons-round">arrow_back</span> Voltar
            </button>
            <h2>Ouvir Bula</h2>
        </header>
        <div class="med-list" id="bula-med-list">
            <p id="bula-empty-msg" style="padding:30px; color:var(--text-light); font-size:18px; text-align:center;">
                Nenhum remedio cadastrado ainda. Use o <strong>Ler Receita (IA)</strong> para adicionar.
            </p>
        </div>
        <div id="bula-playback" class="hidden" style="padding:30px 40px; max-width:700px; margin:0 auto; width:100%;">
            <div class="bula-card">
                <h3 id="bula-selected-name" style="margin-bottom:16px; font-size:22px; color:var(--primary);">-</h3>
                <p id="bula-selected-text" style="font-size:16px; line-height:1.6; color:var(--text-light); margin-bottom:24px;">-</p>
                <div style="display:flex; gap:12px; flex-wrap:wrap;">
                    <button class="btn-large-action" style="flex:1; min-width:160px;" onclick="playSelectedBula()">
                        <span class="material-icons-round">play_circle_filled</span> Ouvir Agora
                    </button>
                    <button class="btn-large-action btn-outline" style="flex:1; min-width:130px;" onclick="stopSpeech()">
                        <span class="material-icons-round">stop_circle</span> Parar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI PRESCRIPTION VIEW -->
    <div id="ai-prescription-view" class="view hidden">
        <header class="view-header">
            <button class="btn-back" onclick="hideView('ai-prescription-view'); stopSpeech();" aria-label="Voltar">
                <span class="material-icons-round">arrow_back</span> Voltar
            </button>
            <h2>Leitura de Receita por IA</h2>
        </header>
        <div class="ai-layout">
            <div class="ai-panel-left">
                <input type="file" id="prescription-upload" accept="image/*" style="display:none;" onchange="handlePrescriptionUpload(this)">
                <label for="prescription-upload" class="ai-upload-area" id="ai-upload-label">
                    <span class="material-icons-round">upload_file</span>
                    <strong style="font-size:18px; display:block; margin-bottom:6px;">Enviar Foto da Receita</strong>
                    <p>Clique aqui para enviar uma imagem e analisar</p>
                </label>
                <img id="prescription-preview" src="" alt="Pre-visualizacao da receita"
                    style="display:none; border-radius:12px; width:100%; max-height:240px; object-fit:contain; border:2px solid #673AB7; margin-top:8px;">
                <div id="ai-loading" class="hidden" style="text-align:center; padding:24px;">
                    <span class="material-icons-round" style="font-size:48px; color:#673AB7; display:block; animation:spin 1.2s linear infinite; margin-bottom:10px;">autorenew</span>
                    <p style="font-size:17px; font-weight:700; color:var(--text-light);">Analisando com IA...</p>
                </div>
                <div id="ai-results-container"></div>
                <div id="ai-results-controls" class="hidden" style="margin-top:16px;">
                    <button class="btn-large-action" style="background-color:#673AB7; margin-top:16px; font-size:16px; padding:14px;" onclick="listenAiResult()">
                        <span class="material-icons-round">volume_up</span> Ouvir em Voz Alta
                    </button>
                    <button class="btn-large-action" style="font-size:16px; padding:14px; margin-top:10px;" onclick="saveAllMedicationsFromAI()">
                        <span class="material-icons-round">save_all</span> Salvar Todos os Remedios
                    </button>
                </div>
            </div>
            <div class="ai-panel-right">
                <div class="chat-header">
                    <span class="material-icons-round">smart_toy</span>
                    <div>
                        <h3>Assistente de Medicamentos</h3>
                        <p>Tire duvidas sobre o remedio identificado</p>
                    </div>
                </div>
                <div class="chat-suggestions">
                    <span class="chip" onclick="sendChip('Precisa de receita medica?')">Precisa de receita?</span>
                    <span class="chip" onclick="sendChip('Quais sao as contraindicacoes?')">Contraindicacoes</span>
                    <span class="chip" onclick="sendChip('Qual a dosagem correta?')">Dosagem</span>
                    <span class="chip" onclick="sendChip('Quais sao os efeitos colaterais?')">Efeitos colaterais</span>
                    <span class="chip" onclick="sendChip('Pode tomar em jejum?')">Pode em jejum?</span>
                </div>
                <div class="chat-messages" id="chat-messages">
                    <div class="chat-bubble ai">
                        Ola! Envie a foto de uma receita e eu analisarei o medicamento com IA. Depois pode me perguntar qualquer coisa sobre ele aqui!
                    </div>
                </div>
                <div id="voice-listening-indicator" class="voice-listening-indicator">
                    <div class="voice-wave"><span></span><span></span><span></span><span></span><span></span></div>
                    Ouvindo... fale agora
                </div>
                <div class="chat-input-area">
                    <input type="text" id="chat-input" placeholder="Faca uma pergunta sobre o remedio..."
                        onkeydown="if(event.key==='Enter') sendChatMessage()">
                    <button class="chat-mic-btn" id="chat-mic-btn" onclick="toggleVoiceInput()">
                        <span class="material-icons-round" id="chat-mic-icon">mic</span>
                    </button>
                    <button class="chat-send-btn" onclick="sendChatMessage()">
                        <span class="material-icons-round">send</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- VACCINES VIEW -->
    <div id="vaccines-view" class="view hidden">
        <header class="view-header">
            <button class="btn-back" onclick="hideView('vaccines-view')" aria-label="Voltar">
                <span class="material-icons-round">arrow_back</span> Voltar
            </button>
            <h2>Posto Guarulhos</h2>
        </header>
        <div class="med-list">
            <div class="med-item pending" style="background-color:var(--danger-light);">
                <span class="material-icons-round status-icon" style="color:var(--danger);">warning</span>
                <div class="med-details">
                    <h3 style="color:var(--danger);">Surto em Guarulhos</h3>
                    <p>Dengue (Casos em alta em 2026)</p>
                </div>
            </div>
            <h3 style="padding:10px 0; font-size:20px;">Vacinas Disponiveis Hoje</h3>
            <div class="med-item taken">
                <span class="material-icons-round status-icon">vaccines</span>
                <div class="med-details"><h3>Gripe (Influenza)</h3><p>Disponivel no posto central</p></div>
            </div>
            <div class="med-item taken">
                <span class="material-icons-round status-icon">vaccines</span>
                <div class="med-details"><h3>Covid-19 Bivalente</h3><p>Para maiores de 60 anos</p></div>
            </div>
            <div class="med-item taken">
                <span class="material-icons-round status-icon">vaccines</span>
                <div class="med-details"><h3>Febre Amarela</h3><p>Dose unica ou reforco</p></div>
            </div>
            <hr style="margin:24px 0; border:0; border-top:1px solid #eee;">
            <h3 style="padding:10px 0; font-size:20px; display:flex; align-items:center; gap:8px;">
                <span class="material-icons-round" style="color:#005ce6;">health_and_safety</span>
                Minha Carteirinha (GOV.BR)
            </h3>
            <div id="gov-br-sync-area" style="text-align:center; padding:20px; background:#f0f8ff; border-radius:12px; border:1px solid #b3d4ff; margin-bottom:20px;">
                <p style="font-size:16px; color:#333; margin-bottom:16px;">
                    Sincronize sua carteira de vacinacao atraves do <strong>Meu SUS Digital (gov.br)</strong>.
                </p>
                <button class="btn-large-action" style="background-color:#005ce6; color:#fff;" onclick="syncGovBr()">
                    <span class="material-icons-round">login</span> Entrar com gov.br
                </button>
            </div>
            <div id="gov-br-results" class="hidden"></div>
        </div>
        <div style="padding:0 40px 30px;">
            <button class="btn-large-action" style="max-width:400px; background-color:#00BCD4;" onclick="readVaccines()">
                <span class="material-icons-round">volume_up</span> Ouvir Informacoes
            </button>
        </div>
    </div>

    <!-- FINISH TREATMENT MODAL -->
    <div id="finish-modal" class="modal-overlay hidden">
        <div class="modal-card">
            <span class="material-icons-round" style="font-size:56px; color:var(--warning); display:block; margin-bottom:16px;">warning_amber</span>
            <h2 style="font-size:22px; font-weight:800; margin-bottom:8px;">Encerrar Tratamento</h2>
            <p id="finish-modal-text" style="font-size:16px; color:var(--text-light); margin-bottom:28px; line-height:1.5;">
                Voce tem certeza que deseja encerrar o uso de <strong id="finish-med-name">-</strong>?<br>
                Os alarmes serao desativados e ele sera marcado como <em>Concluido</em>.
            </p>
            <div style="display:flex; gap:12px;">
                <button class="modal-btn modal-btn-cancel" onclick="closeFinishModal()">
                    <span class="material-icons-round">close</span> Nao, continuar
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmFinishTreatment()">
                    <span class="material-icons-round">check</span> Sim, encerrar
                </button>
            </div>
        </div>
    </div>

    <!-- EMERGENCY OVERLAY -->
    <div id="emergency-overlay" class="emergency-overlay hidden">
        <div class="emergency-content">
            <span class="material-icons-round huge-icon emergency-spin">emergency</span>
            <h1>LIGANDO PARA<br>AMBULANCIA...</h1>
            <p>192</p>
            <button class="btn-cancel" onclick="cancelEmergency()">
                <span class="material-icons-round">cancel</span> CANCELAR (5s)
            </button>
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div id="settings-modal" class="modal-overlay hidden">
        <div class="modal-card settings-modal-card">
            <div class="settings-modal-header">
                <div class="settings-title-group">
                    <span class="material-icons-round settings-head-icon">settings_accessibility</span>
                    <h2>Ajustes e Acessibilidade</h2>
                </div>
                <button class="btn-close-modal" onclick="closeSettingsModal()">
                    <span class="material-icons-round">close</span>
                </button>
            </div>
            <div class="settings-section">
                <div class="settings-row">
                    <div class="settings-row-text">
                        <h3>Leitura ao Passar o Mouse</h3>
                        <p>Le automaticamente qualquer texto da tela em voz alta ao passar o cursor.</p>
                    </div>
                    <button id="modal-tts-toggle" class="btn-settings-toggle active" onclick="toggleHoverTTS()">
                        <span class="material-icons-round" id="modal-tts-icon">volume_up</span>
                        <span id="modal-tts-status">Ativada</span>
                    </button>
                </div>
            </div>
            <div class="settings-section">
                <div class="settings-row-text" style="margin-bottom:12px;">
                    <h3>Velocidade da Voz</h3>
                    <p>Ajuste o ritmo da fala:</p>
                </div>
                <div class="speed-selector-group">
                    <button class="btn-speed" id="speed-slow" onclick="setVoiceSpeed(0.75)">
                        <span class="material-icons-round">directions_walk</span> Lenta
                    </button>
                    <button class="btn-speed active" id="speed-normal" onclick="setVoiceSpeed(0.95)">
                        <span class="material-icons-round">record_voice_over</span> Normal
                    </button>
                    <button class="btn-speed" id="speed-fast" onclick="setVoiceSpeed(1.2)">
                        <span class="material-icons-round">directions_run</span> Rapida
                    </button>
                </div>
            </div>
            <div class="settings-section">
                <button class="btn-large-action" style="padding:14px; width:100%;" onclick="testVoiceSpeech()">
                    <span class="material-icons-round">play_circle_filled</span> Testar Voz do Sistema
                </button>
            </div>
            <div class="settings-footer">
                <button class="btn-logout" onclick="logoutUser()">
                    <span class="material-icons-round">logout</span> Sair da Conta
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="closeSettingsModal()" style="max-width:140px; margin-left:auto; background:var(--primary);">
                    <span class="material-icons-round">check</span> Pronto
                </button>
            </div>
        </div>
    </div>

    <!-- MANUAL MED MODAL -->
    <div id="manual-med-modal" class="modal-overlay hidden">
        <div class="modal-card">
            <h2 style="font-size:22px; font-weight:800; margin-bottom:16px;">Cadastrar Remedio</h2>
            <div class="form-group">
                <label for="manual-med-name">Nome do Remedio</label>
                <input type="text" id="manual-med-name" placeholder="Ex: Paracetamol">
            </div>
            <div class="form-group">
                <label for="manual-med-freq">Frequencia (Horas)</label>
                <input type="number" id="manual-med-freq" value="8" min="1" max="48">
            </div>
            <div class="form-group">
                <label for="manual-med-last">Horario da Ultima Dose</label>
                <input type="time" id="manual-med-last">
            </div>
            <div style="display:flex; gap:12px; margin-top:24px;">
                <button class="modal-btn modal-btn-cancel" onclick="closeManualMedModal()">
                    <span class="material-icons-round">close</span> Cancelar
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="saveManualMed()">
                    <span class="material-icons-round">check</span> Salvar
                </button>
            </div>
        </div>
    </div>

</div><!-- #app -->

<audio id="alarm-sound" src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" preload="auto"></audio>
<audio id="success-sound" src="https://assets.mixkit.co/active_storage/sfx/1435/1435-preview.mp3" preload="auto"></audio>

<script src="app.js"></script>
</body>
</html>
