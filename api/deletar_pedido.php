<?php
require_once 'api_base.php';
exigirAutenticacao('loja');


// Pega o ID do pedido via URL de forma segura
$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$loja_id = $_SESSION['usuario_id'];

if ($pedido_id) {
    try {
        // Deleta APENAS se o pedido pertencer a loja logada (Segurança)
        $stmt = $pdo->prepare("DELETE FROM pedidos WHERE id = ? AND loja_id = ?");
        $stmt->execute([$pedido_id, $loja_id]);
        registrarAcaoBackend('Deletar pedido ID ' . $pedido_id);

        // Redireciona com mensagem de sucesso
        header('Location: ../dashboard-loja/index.php?status=sucesso_delete');
        exit();

    } catch (PDOException $e) {
        registrarAcaoBackend('Falha ao deletar pedido ID ' . $pedido_id . ': ' . $e->getMessage());
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