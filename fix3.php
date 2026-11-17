<?php
$content = file_get_contents('app.js');

$create_account_pattern = '/function createAccount\(\) \{.*?\n\}/s';
$new_create_account = <<<EOT
function createAccount() {
    var email = document.getElementById('user-email').value.trim();
    var pass = document.getElementById('user-password').value;
    if (!email || !pass) { 
        alert('Preencha e-mail e senha!'); 
        speakText('Preencha e-mail e senha.');
        return; 
    }
    var name = email.split('@')[0];

    fetch('api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ nome: name, email: email, senha: pass })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.sucesso) {
            sessionStorage.setItem('acessiUser', JSON.stringify({ name: data.usuario.nome, email: data.usuario.email }));
            speakText('Conta criada com sucesso. Bem vindo, ' + data.usuario.nome);
            checkAuth();
            AlarmSystem.start();
        } else {
            alert(data.erro);
            speakText(data.erro);
        }
    })
    .catch(function(err) {
        console.error(err);
        alert('Erro ao criar conta.');
        speakText('Erro ao criar conta.');
    });
}
EOT;
$content = preg_replace($create_account_pattern, $new_create_account, $content);

$login_pattern = '/var form = document\.getElementById\(\'login-form\'\);\s+if \(form\) \{\s+form\.addEventListener\(\'submit\', function \(e\) \{.*?\}\);\s+\}/s';
$new_login = <<<EOT
var form = document.getElementById('login-form');
    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var email = document.getElementById('user-email').value.trim();
            var pass = document.getElementById('user-password').value;
            if (!email || !pass) { 
                alert('Preencha e-mail e senha!'); 
                speakText('Preencha e-mail e senha.');
                return; 
            }

            fetch('api/login.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, senha: pass })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.sucesso) {
                    sessionStorage.setItem('acessiUser', JSON.stringify({ name: data.usuario.nome, email: data.usuario.email }));
                    speakText('Bem vindo, ' + data.usuario.nome);
                    checkAuth();
                    AlarmSystem.start();
                } else {
                    alert(data.erro);
                    speakText(data.erro);
                }
            })
            .catch(function(err) {
                console.error(err);
                alert('Erro ao fazer login.');
                speakText('Erro ao fazer login.');
            });
        });
    }
EOT;
$content = preg_replace($login_pattern, $new_login, $content);

file_put_contents('app.js', $content);
echo "Updated app.js successfully\n";
?>
