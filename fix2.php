<?php
$f = "C:/xampp3/htdocs/Tcc/app.js";
$c = file_get_contents($f);
$c = str_replace(["no", "No", "Atenǜo", "disponveis", "configuraes", "udio", "concludo", "remdio", "frequncia", "horrio", "ltima", "No informado"], 
                 ["não", "Não", "Atenção", "disponíveis", "configurações", "áudio", "concluído", "remédio", "frequência", "horário", "última", "Não informado"], $c);
file_put_contents($f, $c);
?>
