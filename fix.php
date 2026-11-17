<?php
$files = ["index.html", "app.js", "styles.css", "api/test.php", "api/analyze.php", "api/list_models.php"];
$replacements = [
    "Diǭrio" => "Diário",
    "Olǭ" => "Olá",
    "RemǸdio" => "Remédio",
    "Jǭ" => "Já",
    "EmergǦncia" => "Emergência",
    "Saǧde" => "Saúde",
    "Atenǜo" => "Atenção",
    "concludo" => "concluído",
    "" => "á", // We need to be careful with replace all
];
foreach($files as $file) {
    if(!file_exists(__DIR__ . "/" . $file)) continue;
    $content = file_get_contents(__DIR__ . "/" . $file);
    // actually, let us just utf8_encode or decode if it is a double encoding problem!
    $decoded = mb_convert_encoding($content, "Windows-1252", "UTF-8");
    if(strpos($decoded, "Diário") !== false || strpos($decoded, "Olá") !== false || strpos($decoded, "Já") !== false) {
        file_put_contents(__DIR__ . "/" . $file, $decoded);
        echo "Fixed $file\n";
    } else {
        echo "Could not fix $file by converting. Trying utf8_decode.\n";
        $decoded = utf8_decode($content);
        if(strpos($decoded, "Diário") !== false || strpos($decoded, "Olá") !== false || strpos($decoded, "Já") !== false) {
            file_put_contents(__DIR__ . "/" . $file, $decoded);
            echo "Fixed $file using utf8_decode\n";
        }
    }
}
echo "Done";
?>
