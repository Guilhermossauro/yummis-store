<?php
session_start();
require_once '../config/db.php';
require_once 'helpers.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') die("Acesso negado.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loja_id = $_SESSION['usuario_id'];
    $id_fornecedor = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    
    $nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
    $documento = trim(filter_input(INPUT_POST, 'documento', FILTER_SANITIZE_STRING));
    $endereco = trim(filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_STRING));
    $nova_senha = $_POST['nova_senha'] ?? '';

    $perms = [
        "enviar_proposta" => isset($_POST['perm_enviar']) ? true : false,
        "ver_concorrencia" => isset($_POST['perm_ver_outros']) ? true : false
    ];
    $permissoes_json = json_encode($perms);

    // 1. Busca foto atual para apagar se enviar uma nova
    $stmtBusca = $pdo->prepare("SELECT foto_perfil FROM usuarios WHERE id = ? AND loja_id = ?");
    $stmtBusca->execute([$id_fornecedor, $loja_id]);
    $fornecedorAtual = $stmtBusca->fetch();
    $foto_caminho = $fornecedorAtual['foto_perfil'];

    // 2. Processa nova foto se enviada
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        $dir_perfis = '../uploads/perfis/';
        if (!is_dir($dir_perfis)) mkdir($dir_perfis, 0777, true);
        
        $uploadResult = processUploadAndConvertToWebp($_FILES['foto_perfil'], $dir_perfis, 5);
        if (isset($uploadResult['success'])) {
            // Apaga a velha
            if ($foto_caminho && file_exists('../' . $foto_caminho)) {
                unlink('../' . $foto_caminho);
            }
            $foto_caminho = str_replace('../', '', $uploadResult['path']);
        }
    }

    try {
        if (!empty($nova_senha)) {
            $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET nome=?, email=?, documento=?, endereco=?, permissoes=?, senha=?, foto_perfil=? WHERE id=? AND loja_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $documento, $endereco, $permissoes_json, $hash, $foto_caminho, $id_fornecedor, $loja_id]);
        } else {
            $sql = "UPDATE usuarios SET nome=?, email=?, documento=?, endereco=?, permissoes=?, foto_perfil=? WHERE id=? AND loja_id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $email, $documento, $endereco, $permissoes_json, $foto_caminho, $id_fornecedor, $loja_id]);
        }
        header('Location: ../dashboard-loja/fornecedores.php?status=sucesso_edit');
    } catch (PDOException $e) {
        header('Location: ../dashboard-loja/fornecedores.php?status=erro_db');
    }
}