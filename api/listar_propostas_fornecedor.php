<?php
require_once 'api_base.php';
require_once 'helpers.php';
exigirAutenticacao('fornecedor');

$fornecedor_id = $_SESSION['usuario_id'];
$hasDataEnvio = tableHasColumn($pdo, 'propostas', 'data_envio');
$hasDataCriacao = tableHasColumn($pdo, 'propostas', 'data_criacao');
$proposalDateField = $hasDataEnvio ? 'pr.data_envio' : ($hasDataCriacao ? 'pr.data_criacao' : 'NULL');
$proposalOrderField = $hasDataEnvio ? 'pr.data_envio' : ($hasDataCriacao ? 'pr.data_criacao' : 'pr.id');

try {
    $stmt = $pdo->prepare("
        SELECT pr.*, {$proposalDateField} AS data_envio, p.produto_nome, p.descricao, u.nome as loja_nome
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        JOIN usuarios u ON p.loja_id = u.id
        WHERE pr.fornecedor_id = ?
        ORDER BY {$proposalOrderField} DESC
    ");
    $stmt->execute([$fornecedor_id]);
    $propostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    registrarAcaoBackend('Listagem de propostas do fornecedor');
    echo json_encode($propostas);

} catch (PDOException $e) {
    registrarAcaoBackend('Erro ao listar propostas: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>