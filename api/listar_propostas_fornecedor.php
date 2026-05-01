<?php
require_once 'api_base.php';
exigirAutenticacao('fornecedor');

$fornecedor_id = $_SESSION['usuario_id'];

try {
    $stmt = $pdo->prepare("
        SELECT pr.*, p.produto_nome, p.descricao, u.nome as loja_nome
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        JOIN usuarios u ON p.loja_id = u.id
        WHERE pr.fornecedor_id = ?
        ORDER BY pr.data_envio DESC
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