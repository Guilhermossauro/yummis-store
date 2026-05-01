<?php
require_once 'api_base.php';
exigirAutenticacao('fornecedor');

try {
    $stmt = $pdo->query("
        SELECT p.*, u.nome as loja_nome, u.email as loja_email
        FROM pedidos p
        JOIN usuarios u ON p.loja_id = u.id
        WHERE p.status = 'aberto'
        ORDER BY p.data_criacao DESC
        LIMIT 50
    ");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    registrarAcaoBackend('Listagem de pedidos abertos para fornecedores');
    echo json_encode($pedidos);

} catch (PDOException $e) {
    registrarAcaoBackend('Erro ao listar pedidos abertos: ' . $e->getMessage());
    echo json_encode(['error' => 'Erro interno do servidor']);
}
?>