<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'] ?? '';

    if (empty($email) || empty($senha)) {
        header('Location: ../login/index.php?error=empty_fields');
        exit();
    }

    try {
        // PREPARED STATEMENT: Proteção total contra SQL Injection
        $stmt = $pdo->prepare("SELECT id, nome, senha, tipo_usuario FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $usuario = $stmt->fetch();

        // Verifica se o usuário existe e se a senha (hash) bate
        if ($usuario && password_verify($senha, $usuario['senha'])) {
            
            // Segurança: Regenera o ID da sessão para evitar Session Fixation
            session_regenerate_id(true);

            $_SESSION['usuario_id']   = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_tipo'] = $usuario['tipo_usuario']; // 'loja' ou 'fornecedor'

            // Redireciona conforme o nível de acesso
            if ($usuario['tipo_usuario'] === 'loja') {
                header('Location: ../dashboard-loja/index.php');
            } else {
                header('Location: ../fornecedor/index.php');
            }
            exit();
        } else {
            // Login falhou
            header('Location: ../login/index.php?error=invalid_credentials');
            exit();
        }

    } catch (PDOException $e) {
        // Logar erro e mostrar mensagem genérica
        error_log($e->getMessage());
        header('Location: ../login/index.php?error=system_error');
        exit();
    }
}