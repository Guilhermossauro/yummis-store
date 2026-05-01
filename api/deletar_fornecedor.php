<?php
require_once 'api_base.php';
exigirAutenticacao('loja');

$loja_id = $_SESSION['usuario_id'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id) {
    try {
        // Deleta APENAS se o fornecedor pertencer à loja logada
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND loja_id = ? AND tipo_usuario = 'fornecedor'");
        $stmt->execute([$id, $loja_id]);
        header('Location: ../dashboard-loja/fornecedores.php?status=sucesso_delete');
    } catch (PDOException $e) {
        header('Location: ../dashboard-loja/fornecedores.php?status=erro_db');
    }
}