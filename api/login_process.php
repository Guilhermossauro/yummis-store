<?php
require_once 'api_base.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['password'] ?? '';
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING) ?? '';

    if (empty($email) || empty($senha)) {
        header('Location: ../login/index.php?error=empty_fields');
        exit();
    }

    try {
        // Verificar se existem usuários no banco
        $stmtVerificar = $pdo->query("SELECT COUNT(*) FROM usuarios");
        $totalUsuarios = $stmtVerificar->fetchColumn();

        // Se não há usuários, criar o primeiro como owner/admin
        if ($totalUsuarios == 0) {
            // Primeiro usuário - será o owner
            if (empty($nome)) {
                $nome = "Proprietário do Sistema";
            }

            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
            $tipo_usuario = 'loja'; // Pode ser loja (owner) ou fornecedor

            $stmtCriar = $pdo->prepare("
                INSERT INTO usuarios (nome, email, senha, tipo_usuario, data_criacao)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmtCriar->execute([$nome, $email, $senhaHash, $tipo_usuario]);

            $usuario_id = $pdo->lastInsertId();

            // Criar registro de ação para criação do primeiro usuário
            registrarAcaoBackend('Primeiro usuário criado automaticamente: ' . $email . ' como owner');

            // Autenticar automaticamente
            session_regenerate_id(true);
            $_SESSION['usuario_id']   = $usuario_id;
            $_SESSION['usuario_nome'] = $nome;
            $_SESSION['usuario_tipo'] = $tipo_usuario;

            header('Location: ../dashboard-loja/index.php');
            exit();
        }

        // Se houver usuários, proceder com login normal
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
                registrarAcaoBackend('Login bem-sucedido para usuário ID ' . $usuario['id']);
                header('Location: ../dashboard-loja/index.php');
            } else {
                registrarAcaoBackend('Login bem-sucedido para usuário ID ' . $usuario['id']);
                header('Location: ../fornecedor/index.php');
            }
            exit();
        } else {
            // Login falhou
            registrarAcaoBackend('Falha de login para email: ' . $email);
            header('Location: ../login/index.php?error=invalid_credentials');
            exit();
        }

    } catch (PDOException $e) {
        // Logar erro e mostrar mensagem genérica
        registrarAcaoBackend('Erro no processo de login: ' . $e->getMessage());
        error_log($e->getMessage());
        header('Location: ../login/index.php?error=system_error');
        exit();
    }
}