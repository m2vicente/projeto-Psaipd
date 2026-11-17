<?php
$content = file_get_contents('app.js');
$content = str_replace('nǜone', 'none', $content);
$content = str_replace('nǜova', 'nova', $content);
$content = str_replace('Nǜo', 'Não', $content);
$content = str_replace('nǜo', 'não', $content);
$content = str_replace('Usuǭrio', 'Usuário', $content);
$content = str_replace('usuǭrio', 'usuário', $content);
$content = str_replace('jǭ', 'já', $content);
$content = str_replace('Olǭǭ', 'Olá', $content);
file_put_contents('app.js', $content);
echo "Fixed typos in app.js\n";
?>
