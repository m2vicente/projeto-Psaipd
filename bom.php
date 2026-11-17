<?php
$files = ["index.html", "app.js", "styles.css", "api/test.php", "api/analyze.php", "api/list_models.php"];
foreach($files as $f) {
    $path = "C:/xampp3/htdocs/Tcc/" . $f;
    if (file_exists($path)) {
        $c = file_get_contents($path);
        if (substr($c, 0, 3) === "\xef\xbb\xbf") {
            file_put_contents($path, substr($c, 3));
            echo "Removed BOM from $f\n";
        }
    }
}
?>
