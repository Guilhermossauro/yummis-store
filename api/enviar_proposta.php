<?php
require_once 'api_base.php';
require_once 'helpers.php';
exigirAutenticacao('fornecedor');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fornecedor_id = $_SESSION['usuario_id'];
    $pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
    $valor = filter_input(INPUT_POST, 'valor', FILTER_VALIDATE_FLOAT);
    $prazo = filter_input(INPUT_POST, 'prazo', FILTER_VALIDATE_INT);
    $observacoes = trim(filter_input(INPUT_POST, 'observacoes', FILTER_SANITIZE_STRING));

    if (!$pedido_id || !$valor || !$prazo) {
        registrarAcaoBackend('Tentativa de envio de proposta com dados inválidos');
        echo json_encode(['error' => 'Dados inválidos']);
        exit();
    }

    // Verificar se o pedido existe e está aberto
    try {
        $stmtPedido = $pdo->prepare("SELECT id, status FROM pedidos WHERE id = ? AND status = 'aberto'");
        $stmtPedido->execute([$pedido_id]);
        $pedido = $stmtPedido->fetch();

        if (!$pedido) {
            registrarAcaoBackend('Tentativa de proposta para pedido inexistente ou fechado: ' . $pedido_id);
            echo json_encode(['error' => 'Pedido não encontrado ou não está aberto']);
            exit();
        }

        // Verificar se já enviou proposta para este pedido
        $stmtVerificar = $pdo->prepare("SELECT id FROM propostas WHERE pedido_id = ? AND fornecedor_id = ?");
        $stmtVerificar->execute([$pedido_id, $fornecedor_id]);
        if ($stmtVerificar->fetch()) {
            registrarAcaoBackend('Tentativa de envio de segunda proposta para pedido: ' . $pedido_id);
            echo json_encode(['error' => 'Você já enviou uma proposta para este pedido']);
            exit();
        }

        // Processar anexos
        $anexos_paths = [];
        if (isset($_FILES['anexos']) && !empty($_FILES['anexos']['name'][0])) {
            $dir_anexos = '../uploads/propostas/';
            if (!is_dir($dir_anexos)) mkdir($dir_anexos, 0777, true);

            $total_anexos = count($_FILES['anexos']['name']);
            for ($i = 0; $i < $total_anexos; $i++) {
                if ($_FILES['anexos']['error'][$i] === UPLOAD_ERR_OK) {
                    $fileArray = [
                        'name' => $_FILES['anexos']['name'][$i],
                        'type' => $_FILES['anexos']['type'][$i],
                        'tmp_name' => $_FILES['anexos']['tmp_name'][$i],
                        'error' => $_FILES['anexos']['error'][$i],
                        'size' => $_FILES['anexos']['size'][$i]
                    ];

                    $uploadResult = processProposalAttachment($fileArray, $dir_anexos, 10); // 10MB max
                    if (isset($uploadResult['success'])) {
                        $anexos_paths[] = str_replace('../', '', $uploadResult['path']);
                    } elseif (isset($uploadResult['error'])) {
                        registrarAcaoBackend('Falha em anexo da proposta: ' . $uploadResult['error']);
                    }
                }
            }
        }

        $anexos_json = !empty($anexos_paths) ? json_encode($anexos_paths) : null;

        // Inserir proposta com compatibilidade de schema
        $columns = ['pedido_id', 'fornecedor_id', 'valor'];
        $placeholders = ['?', '?', '?'];
        $params = [$pedido_id, $fornecedor_id, $valor];

        if (tableHasColumn($pdo, 'propostas', 'prazo')) {
            $columns[] = 'prazo';
            $placeholders[] = '?';
            $params[] = $prazo;
        }

        if (tableHasColumn($pdo, 'propostas', 'observacoes')) {
            $observacoesComPrazo = trim("Prazo: {$prazo} dias\n" . ($observacoes ?? ''));
            $columns[] = 'observacoes';
            $placeholders[] = '?';
            $params[] = $observacoesComPrazo;
        }

        if (tableHasColumn($pdo, 'propostas', 'arquivo_url')) {
            $columns[] = 'arquivo_url';
            $placeholders[] = '?';
            $params[] = $anexos_json;
        }

        if (tableHasColumn($pdo, 'propostas', 'status')) {
            $columns[] = 'status';
            $placeholders[] = '?';
            $params[] = 'enviada';
        }

        if (tableHasColumn($pdo, 'propostas', 'data_envio')) {
            $columns[] = 'data_envio';
            $placeholders[] = 'NOW()';
        }

        $sql = 'INSERT INTO propostas (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        registrarAcaoBackend('Proposta enviada para pedido ID ' . $pedido_id . ' com valor R$ ' . number_format($valor, 2));
        echo json_encode(['success' => true]);

    } catch (PDOException $e) {
        registrarAcaoBackend('Erro ao enviar proposta: ' . $e->getMessage());
        echo json_encode(['error' => 'Erro interno do servidor']);
    }
} else {
    echo json_encode(['error' => 'Método não permitido']);
}
?>