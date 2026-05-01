<?php
session_start();
require_once '../config/db.php';
$systemConfig = require_once '../config/system.php';
require_once 'helpers.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') {
    die("Acesso negado.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loja_id = $_SESSION['usuario_id'];
    $produto = trim(filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_STRING));
    $descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING));

    if (empty($produto)) {
        header('Location: ../dashboard-loja/index.php?status=erro_produto');
        exit();
    }

    $caminhos_imagens = [];
    $diretorio_destino = '../uploads/pedidos/';
    
    if (!is_dir($diretorio_destino)) { mkdir($diretorio_destino, 0777, true); }

    if (isset($_FILES['imagens_produto']) && !empty($_FILES['imagens_produto']['name'][0])) {
        $total_imagens = count($_FILES['imagens_produto']['name']);

        for ($i = 0; $i < $total_imagens; $i++) {
            if ($_FILES['imagens_produto']['error'][$i] === UPLOAD_ERR_OK) {
                
                $fileArray = [
                    'name' => $_FILES['imagens_produto']['name'][$i],
                    'type' => $_FILES['imagens_produto']['type'][$i],
                    'tmp_name' => $_FILES['imagens_produto']['tmp_name'][$i],
                    'error' => $_FILES['imagens_produto']['error'][$i],
                    'size' => $_FILES['imagens_produto']['size'][$i]
                ];

                $uploadResult = processUploadAndConvertToWebp($fileArray, $diretorio_destino, $systemConfig['max_file_size_mb']);
                
                if (isset($uploadResult['success'])) {
                    // Limpa o '../' para salvar bonitinho no banco
                    $caminhos_imagens[] = str_replace('../', '', $uploadResult['path']);
                }
            }
        }
    }

    $imagens_json = !empty($caminhos_imagens) ? json_encode($caminhos_imagens) : null;

    try {
        $sql = "INSERT INTO pedidos (loja_id, produto_nome, descricao, imagem_url, status) VALUES (?, ?, ?, ?, 'aberto')";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$loja_id, $produto, $descricao, $imagens_json]);

        header('Location: ../dashboard-loja/index.php?status=sucesso');
        exit();
    } catch (PDOException $e) {
        header('Location: ../dashboard-loja/index.php?status=erro_db');
        exit();
    }
}