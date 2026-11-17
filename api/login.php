<?php
session_start();
require '../conexao.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $email = $data['email'] ?? '';
    $senha_digitada = $data['senha'] ?? '';

    $stmt = $pdo->prepare("SELECT id, nome, email, senha FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        echo json_encode(['sucesso' => false, 'erro' => 'Usuario nao encontrado']);
        exit;
    }

    if (password_verify($senha_digitada, $usuario['senha'])) {
        session_regenerate_id(true);
        $_SESSION['id_usuario']    = $usuario['id'];
        $_SESSION['email_usuario'] = $usuario['email'];
        $_SESSION['nome_usuario']  = $usuario['nome']; // guarda nome na sessao
        echo json_encode(['sucesso' => true, 'usuario' => [
            'id'    => $usuario['id'],
            'nome'  => $usuario['nome'],
            'email' => $usuario['email']
        ]]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Senha incorreta']);
    }
}
?>
