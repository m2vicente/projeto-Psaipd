<?php
$index = file_get_contents('c:\xampp3\htdocs\Tcc\index.html');
$index = preg_replace('/<div id="login-view"[^>]*>\s*<\/div>/s', '', $index);
file_put_contents('c:\xampp3\htdocs\Tcc\index.html', $index);
echo "Removed login-view from index.html\n";
?>
