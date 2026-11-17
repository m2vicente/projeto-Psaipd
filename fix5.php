<?php
$content = file_get_contents('c:\xampp3\htdocs\Tcc\app.js');

$checkAuth_pattern = '/function checkAuth\(\) \{.*?return false;\s+\}\s+\}/s';
$new_checkAuth = <<<EOT
function checkAuth() {
    if (localStorage.getItem('acessiUser')) {
        localStorage.removeItem('acessiUser');
    }

    var user = sessionStorage.getItem('acessiUser');
    if (user) {
        var loginView = document.getElementById('login-view');
        if (loginView) loginView.style.display = 'none';
        var data = JSON.parse(user);
        var name = data.name.charAt(0).toUpperCase() + data.name.slice(1);
        var el = document.getElementById('greeting');
        if (el) el.textContent = 'Olá, ' + name + '!';
        return true;
    } else {
        window.location.href = 'login.php';
        return false;
    }
}
EOT;

$content = preg_replace($checkAuth_pattern, $new_checkAuth, $content);
file_put_contents('c:\xampp3\htdocs\Tcc\app.js', $content);
echo "Updated app.js for redirect to login.php\n";

$index = file_get_contents('c:\xampp3\htdocs\Tcc\index.html');
$index = preg_replace('/<div class="login-card">.*?<\/form>\s*<\/div>/s', '', $index);
file_put_contents('c:\xampp3\htdocs\Tcc\index.html', $index);
echo "Removed login card from index.html\n";

?>
