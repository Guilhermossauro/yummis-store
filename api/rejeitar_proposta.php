<?php
require_once 'api_base.php';

exigirAutenticacao();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$proposta_id = $_POST['proposta_id'] ?? null;

if (!$proposta_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID da proposta é obrigatório']);
    exit;
}

try {
    // Verificar se a proposta existe e pertence à loja
    $stmt = $pdo->prepare("
        SELECT pr.*, p.loja_id
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        WHERE pr.id = ?
    ");
    $stmt->execute([$proposta_id]);
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

    // Verificar se a proposta ainda pode ser rejeitada
    if ($proposta['status'] !== 'pendente') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Esta proposta já foi processada']);
        exit;
    }

    // Rejeitar a proposta
    $stmtRejeitar = $pdo->prepare("UPDATE propostas SET status = 'rejeitada' WHERE id = ?");
    $stmtRejeitar->execute([$proposta_id]);

    // Registrar ação no log
    registrarAcaoBackend('rejeitar_proposta', "Proposta ID $proposta_id rejeitada");

    echo json_encode([
        'success' => true,
        'message' => 'Proposta rejeitada com sucesso!'
    ]);

} catch (Exception $e) {
    error_log("Erro ao rejeitar proposta: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
?>