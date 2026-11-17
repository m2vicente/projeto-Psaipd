<?php
session_start();
require '../conexao.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data  = json_decode(file_get_contents('php://input'), true);
    $nome  = trim($data['nome']  ?? '');
    $email = trim($data['email'] ?? '');
    $senha = $data['senha'] ?? '';

    if (!$nome || !$email || !$senha) {
        echo json_encode(['sucesso' => false, 'erro' => 'Preencha todos os campos']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['sucesso' => false, 'erro' => 'E-mail ja cadastrado']);
        exit;
    }

    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
    $sql = "INSERT INTO usuarios (nome, email, senha) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);

    if ($stmt->execute([$nome, $email, $senhaHash])) {
        $id = $pdo->lastInsertId();
        session_regenerate_id(true);
        $_SESSION['id_usuario']    = $id;
        $_SESSION['email_usuario'] = $email;
        $_SESSION['nome_usuario']  = $nome;
        echo json_encode(['sucesso' => true, 'usuario' => [
            'id'    => $id,
            'nome'  => $nome,
            'email' => $email
        ]]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Erro ao cadastrar']);
    }
}
?>
