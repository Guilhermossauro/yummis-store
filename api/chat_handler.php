<?php
require_once 'api_base.php';
header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['error' => 'Não autorizado']);
    exit();
}

$meu_id = $_SESSION['usuario_id'];
$acao = $_GET['acao'] ?? '';

// --- 1. CARREGAR MENSAGENS (COM CITAÇÃO/RESPOSTA) ---
if ($acao === 'carregar') {
    $destinatario_id = filter_input(INPUT_GET, 'destinatario_id', FILTER_VALIDATE_INT);
    if (!$destinatario_id) { echo json_encode([]); exit(); }
    registrarAcaoBackend('Carregar chat com destinatário ID ' . $destinatario_id);

    $sql = "SELECT m1.id, m1.remetente_id, m1.mensagem, m1.data_envio, m1.lida, 
                   m2.mensagem as msg_respondida
            FROM mensagens m1
            LEFT JOIN mensagens m2 ON m1.resposta_a = m2.id
            WHERE (m1.remetente_id = ? AND m1.destinatario_id = ?) 
               OR (m1.remetente_id = ? AND m1.destinatario_id = ?) 
            ORDER BY m1.data_envio ASC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$meu_id, $destinatario_id, $destinatario_id, $meu_id]);
    
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit();
}

// --- 2. ENVIAR MENSAGEM ---
if ($acao === 'enviar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $destinatario_id = filter_input(INPUT_POST, 'destinatario_id', FILTER_VALIDATE_INT);
    $mensagem = trim($_POST['mensagem']);
    $resposta_a = filter_input(INPUT_POST, 'resposta_a', FILTER_VALIDATE_INT);
    $loja_id = ($_SESSION['usuario_tipo'] === 'loja') ? $meu_id : 0; 

    if ($destinatario_id && !empty($mensagem)) {
        registrarAcaoBackend('Enviar mensagem para destinatário ID ' . $destinatario_id);
        $sql = "INSERT INTO mensagens (loja_id, remetente_id, destinatario_id, mensagem, resposta_a) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$loja_id, $meu_id, $destinatario_id, $mensagem, $resposta_a])) {
            echo json_encode(['success' => true]); exit();
        }
    }
    echo json_encode(['error' => 'Falha']); exit();
}

// --- 3. MARCAR COMO LIDAS ---
if ($acao === 'marcar_lidas') {
    $remetente_id = filter_input(INPUT_GET, 'remetente_id', FILTER_VALIDATE_INT);
    if($remetente_id) {
        registrarAcaoBackend('Marcar mensagens como lidas do remetente ID ' . $remetente_id);
        $sql = "UPDATE mensagens SET lida = 1 WHERE remetente_id = ? AND destinatario_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$remetente_id, $meu_id]);
    }
    echo json_encode(['success' => true]); exit();
}

// --- 4. EDITAR MENSAGEM ---
if ($acao === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $novo_texto = trim($_POST['novo_texto']);
    if($id && !empty($novo_texto)) {
        registrarAcaoBackend('Editar mensagem ID ' . $id);
        // Garante que só edita se o remetente for ele mesmo
        $sql = "UPDATE mensagens SET mensagem = ? WHERE id = ? AND remetente_id = ?";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$novo_texto, $id, $meu_id])) {
            echo json_encode(['success' => true]); exit();
        }
    }
    echo json_encode(['error' => 'Falha']); exit();
}

// --- 5. DELETAR MENSAGEM ---
if ($acao === 'deletar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    if($id) {
        registrarAcaoBackend('Deletar mensagem ID ' . $id);
        // Garante que só deleta a própria mensagem
        $sql = "DELETE FROM mensagens WHERE id = ? AND remetente_id = ?";
        $stmt = $pdo->prepare($sql);
        if($stmt->execute([$id, $meu_id])) {
            echo json_encode(['success' => true]); exit();
        }
    }
    echo json_encode(['error' => 'Falha']); exit();
}

echo json_encode(['error' => 'Invalido']);