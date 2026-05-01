<?php
session_start();
require_once '../config/db.php';
require_once 'helpers.php'; // Usa o ajudante de conversão WebP

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') die("Acesso negado.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loja_id = $_SESSION['usuario_id'];
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $senha = $_POST['senha'];
    $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING));

    if (empty($nome) || empty($email) || empty($senha)) {
        header('Location: ../dashboard-loja/fornecedores.php?status=erro_campos');
        exit();
    }

    // Processamento da Foto de Perfil
    $foto_caminho = null;
    $dir_perfis = '../uploads/perfis/';
    if (!is_dir($dir_perfis)) mkdir($dir_perfis, 0777, true);

    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = processUploadAndConvertToWebp($_FILES['foto_perfil'], $dir_perfis, 5); // 5MB max para avatar
        if (isset($uploadResult['success'])) {
            $foto_caminho = str_replace('../', '', $uploadResult['path']); // Limpa o '../'
        }
    }

    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $permissoesPadrao = json_encode(["enviar_proposta" => true, "ver_concorrencia" => false]);

    try {
        $sql = "INSERT INTO usuarios (loja_id, nome, email, senha, foto_perfil, documento, tipo_usuario, permissoes) VALUES (?, ?, ?, ?, ?, ?, 'fornecedor', ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loja_id, $nome, $email, $hash, $foto_caminho, $documento, $permissoesPadrao]);
        
        header('Location: ../dashboard-loja/fornecedores.php?status=sucesso');
    } catch (PDOException $e) {
        header('Location: ../dashboard-loja/fornecedores.php?status=erro_db');
    }
}