<?php
require_once 'api_base.php';
exigirAutenticacao('loja');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $produto = trim(filter_input(INPUT_POST, 'produto', FILTER_SANITIZE_STRING));
    $descricao = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_STRING));
    $loja_id = $_SESSION['usuario_id'];

    if (!$id || empty($produto)) {
        header('Location: ../dashboard-loja/index.php?status=erro_produto');
        exit();
    }

    // --- 1. APAGAR AS IMAGENS ANTIGAS FÍSICAS DO SERVIDOR ---
    try {
        $stmtBusca = $pdo->prepare("SELECT imagem_url FROM pedidos WHERE id = ? AND loja_id = ?");
        $stmtBusca->execute([$id, $loja_id]);
        $pedidoAtual = $stmtBusca->fetch();

        if ($pedidoAtual && $pedidoAtual['imagem_url']) {
            $imagensAntigas = json_decode($pedidoAtual['imagem_url'], true);
            if (is_array($imagensAntigas)) {
                foreach ($imagensAntigas as $img) {
                    $caminhoArquivo = '../' . $img;
                    if (file_exists($caminhoArquivo)) {
                        unlink($caminhoArquivo); // Deleta o arquivo antigo
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erro ao buscar imagens antigas: " . $e->getMessage());
    }

    // --- 2. UPLOAD DAS NOVAS IMAGENS E DAQUELAS QUE FORAM MANTIDAS ---
    $caminhos_imagens = [];

    if (isset($_FILES['imagens_produto']) && !empty($_FILES['imagens_produto']['name'][0])) {
        $total_imagens = count($_FILES['imagens_produto']['name']);
        $diretorio_destino = '../uploads/pedidos/';

        if (!is_dir($diretorio_destino)) {
            mkdir($diretorio_destino, 0777, true);
        }

        for ($i = 0; $i < $total_imagens; $i++) {
            if ($_FILES['imagens_produto']['error'][$i] === UPLOAD_ERR_OK) {
                $foto_tmp = $_FILES['imagens_produto']['tmp_name'][$i];
                $nome_original = $_FILES['imagens_produto']['name'][$i];
                $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));

                $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($extensao, $extensoes_permitidas)) {
                    $nome_novo = uniqid('pedido_edit_') . '_' . $i . '.' . $extensao;
                    $caminho_completo = $diretorio_destino . $nome_novo;

                    if (move_uploaded_file($foto_tmp, $caminho_completo)) {
                        $caminhos_imagens[] = 'uploads/pedidos/' . $nome_novo;
                    }
                }
            }
        }
    }

    $imagens_json = !empty($caminhos_imagens) ? json_encode($caminhos_imagens) : null;

    // --- 3. ATUALIZAR O BANCO DE DADOS ---
    try {
        $sql = "UPDATE pedidos SET produto_nome = ?, descricao = ?, imagem_url = ? WHERE id = ? AND loja_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$produto, $descricao, $imagens_json, $id, $loja_id]);
        registrarAcaoBackend('Editar pedido ID ' . $id);
        header('Location: ../dashboard-loja/index.php?status=sucesso_edit');
        exit();

    } catch (PDOException $e) {
        registrarAcaoBackend('Falha ao editar pedido ID ' . $id . ': ' . $e->getMessage());
        error_log("Erro ao editar pedido: " . $e->getMessage());
        header('Location: ../dashboard-loja/index.php?status=erro_db');
        exit();
    }
}