<?php
require_once 'api_base.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$proposta_id = $_POST['proposta_id'] ?? null;
$pedido_id = $_POST['pedido_id'] ?? null;

if (!$proposta_id || !$pedido_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da proposta e pedido são obrigatórios']);
    exit;
}

try {
    // Verificar se a proposta existe e pertence à loja
    $stmt = $pdo->prepare("
        SELECT pr.*, p.loja_id
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        WHERE pr.id = ? AND p.id = ?
    ");
    $stmt->execute([$proposta_id, $pedido_id]);
    $proposta = $stmt->fetch();

    if (!$proposta) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Proposta não encontrada']);
        exit;
    }

    if ($proposta['loja_id'] != $_SESSION['usuario_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit;
    }

    // Verificar se o pedido ainda está aberto
    if ($proposta['status'] !== 'pendente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Esta proposta já foi processada']);
        exit;
    }

    // Iniciar transação
    $pdo->beginTransaction();

    // Aprovar a proposta selecionada
    $stmtAprovar = $pdo->prepare("UPDATE propostas SET status = 'aprovada' WHERE id = ?");
    $stmtAprovar->execute([$proposta_id]);

    // Rejeitar automaticamente todas as outras propostas do mesmo pedido
    $stmtRejeitar = $pdo->prepare("UPDATE propostas SET status = 'rejeitada' WHERE pedido_id = ? AND id != ?");
    $stmtRejeitar->execute([$pedido_id, $proposta_id]);

    // Fechar o pedido
    $stmtFecharPedido = $pdo->prepare("UPDATE pedidos SET status = 'fechado' WHERE id = ?");
    $stmtFecharPedido->execute([$pedido_id]);

    // Registrar ação no log
    registrarAcaoBackend('aprovar_proposta', "Proposta ID $proposta_id aprovada para pedido ID $pedido_id");

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Proposta aprovada com sucesso! Todas as outras propostas foram rejeitadas automaticamente.'
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao aprovar proposta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>