<?php
session_start();
require_once '../config/db.php';

// Bloqueia se não for lojista
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') {
    die("Acesso negado.");
}

// Pega o ID do pedido via URL de forma segura
$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$loja_id = $_SESSION['usuario_id'];

if ($pedido_id) {
    try {
        // Deleta APENAS se o pedido pertencer a loja logada (Segurança)
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ? AND loja_id = ?");
        $stmt->execute([$pedido_id, $loja_id]);

        // Redireciona com mensagem de sucesso
        header('Location: ../dashboard-loja/index.php?status=sucesso_delete');
        exit();

    } catch (PDOException $e) {
        error_log("Erro ao deletar: " . $e->getMessage());
        header('Location: ../dashboard-loja/index.php?status=erro_db');
        exit();
    }
} else {
    // Se não tiver ID válido na URL, volta para o dashboard
    header('Location: ../dashboard-loja/index.php');
    exit();
}
?>