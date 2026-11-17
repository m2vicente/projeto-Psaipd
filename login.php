<?php
session_start();
if (isset($_SESSION['id_usuario'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AcessiIdoso - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            background-color: var(--bg-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-wrapper {
            width: 100%;
            max-width: 440px;
            padding: 20px;
        }

        .login-logo-area {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-logo-area img {
            width: 80px;
            height: 80px;
            border-radius: 18px;
            object-fit: cover;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            margin-bottom: 14px;
        }

        .login-logo-area h1 {
            font-size: 26px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .login-logo-area p {
            font-size: 14px;
            color: var(--text-light);
            font-weight: 600;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 8px 40px rgba(0,0,0,0.12);
        }

        .login-tabs {
            display: flex;
            gap: 0;
            margin-bottom: 28px;
            background: var(--bg-color);
            border-radius: 10px;
            padding: 4px;
        }

        .login-tab {
            flex: 1;
            padding: 10px;
            border: none;
            background: transparent;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.2s;
        }

        .login-tab.active {
            background: var(--card-bg);
            color: var(--primary);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        #login-error {
            display: none;
            background: var(--danger-light);
            color: var(--danger);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: center;
        }

        #login-error.show { display: block; }

        .btn-submit {
            width: 100%;
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 8px;
            transition: filter 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover { filter: brightness(0.9); }
        .btn-submit:active { transform: scale(0.98); }

        .btn-submit.loading {
            opacity: 0.7;
            pointer-events: none;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-logo-area">
        <img src="assets/logo_tcc.jpg" alt="Logo AcessiIdoso" onerror="this.style.display='none'">
        <h1>AcessiIdoso</h1>
        <p>Seu companheiro diário de saúde</p>
    </div>

    <div class="login-card">
        <!-- Tabs -->
        <div class="login-tabs">
            <button class="login-tab active" id="tab-login" onclick="showTab('login')">
                <span class="material-icons-round" style="font-size:16px;vertical-align:middle;">login</span>
                Entrar
            </button>
            <button class="login-tab" id="tab-register" onclick="showTab('register')">
                <span class="material-icons-round" style="font-size:16px;vertical-align:middle;">person_add</span>
                Criar Conta
            </button>
        </div>

        <!-- Error message -->
        <div id="login-error"></div>

        <!-- LOGIN FORM -->
        <form id="login-form" autocomplete="on">
            <div class="form-group">
                <label for="user-email">E-mail</label>
                <input type="email" id="user-email" placeholder="seu@email.com"
                    aria-label="Digite seu e-mail" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="user-password">Senha</label>
                <input type="password" id="user-password" placeholder="••••••••"
                    aria-label="Digite sua senha" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-submit" id="btn-login-submit">
                <span class="material-icons-round">login</span>
                Entrar
            </button>
        </form>

        <!-- REGISTER FORM -->
        <form id="register-form" style="display:none;" autocomplete="on">
            <div class="form-group">
                <label for="reg-name">Nome</label>
                <input type="text" id="reg-name" placeholder="Seu nome"
                    aria-label="Digite seu nome" required autocomplete="name">
            </div>
            <div class="form-group">
                <label for="reg-email">E-mail</label>
                <input type="email" id="reg-email" placeholder="seu@email.com"
                    aria-label="Digite seu e-mail" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="reg-password">Senha</label>
                <input type="password" id="reg-password" placeholder="Mínimo 6 caracteres"
                    aria-label="Digite uma senha" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn-submit" id="btn-register-submit">
                <span class="material-icons-round">person_add</span>
                Criar Conta
            </button>
        </form>
    </div>
</div>

<script>
// ---- Tab switching ----
function showTab(tab) {
    var isLogin = tab === 'login';
    document.getElementById('tab-login').classList.toggle('active', isLogin);
    document.getElementById('tab-register').classList.toggle('active', !isLogin);
    document.getElementById('login-form').style.display = isLogin ? 'block' : 'none';
    document.getElementById('register-form').style.display = isLogin ? 'none' : 'block';
    showError('');
}

function showError(msg) {
    var el = document.getElementById('login-error');
    if (msg) {
        el.textContent = msg;
        el.classList.add('show');
    } else {
        el.classList.remove('show');
        el.textContent = '';
    }
}

function setLoading(btnId, loading) {
    var btn = document.getElementById(btnId);
    if (!btn) return;
    btn.classList.toggle('loading', loading);
    btn.disabled = loading;
}

// ---- Login ----
document.getElementById('login-form').addEventListener('submit', function(e) {
    e.preventDefault();
    showError('');

    var email = document.getElementById('user-email').value.trim();
    var pass  = document.getElementById('user-password').value;

    if (!email || !pass) {
        showError('Preencha e-mail e senha!');
        return;
    }

    setLoading('btn-login-submit', true);

    fetch('api/login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email: email, senha: pass })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        setLoading('btn-login-submit', false);
        if (data.sucesso) {
            sessionStorage.setItem('acessiUser', JSON.stringify({
                name: data.usuario.nome,
                email: data.usuario.email
            }));
            window.location.replace('index.php');
        } else {
            showError(data.erro || 'E-mail ou senha incorretos.');
        }
    })
    .catch(function(err) {
        setLoading('btn-login-submit', false);
        console.error(err);
        showError('Erro de conexão. Verifique se o servidor está rodando.');
    });
});

// ---- Register ----
document.getElementById('register-form').addEventListener('submit', function(e) {
    e.preventDefault();
    showError('');

    var name  = document.getElementById('reg-name').value.trim();
    var email = document.getElementById('reg-email').value.trim();
    var pass  = document.getElementById('reg-password').value;

    if (!name || !email || !pass) {
        showError('Preencha todos os campos!');
        return;
    }
    if (pass.length < 6) {
        showError('A senha deve ter pelo menos 6 caracteres.');
        return;
    }

    setLoading('btn-register-submit', true);

    fetch('api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: name, email: email, senha: pass })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        setLoading('btn-register-submit', false);
        if (data.sucesso) {
            sessionStorage.setItem('acessiUser', JSON.stringify({
                name: data.usuario.nome,
                email: data.usuario.email
            }));
            window.location.replace('index.php');
        } else {
            showError(data.erro || 'Erro ao criar conta.');
        }
    })
    .catch(function(err) {
        setLoading('btn-register-submit', false);
        console.error(err);
        showError('Erro de conexão. Verifique se o servidor está rodando.');
    });
});
</script>
</body>
</html>
