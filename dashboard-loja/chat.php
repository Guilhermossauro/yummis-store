<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') {
    header('Location: ../login/index.php');
    exit();
}

$loja_id = $_SESSION['usuario_id'];
$nome_loja = $_SESSION['usuario_nome'];

try {
    // Busca fornecedores e conta as mensagens NÃO LIDAS de cada um para esta loja
    $sql = "SELECT u.id, u.nome, u.email, u.foto_perfil,
            (SELECT COUNT(*) FROM mensagens m WHERE m.remetente_id = u.id AND m.destinatario_id = ? AND m.lida = 0) as nao_lidas
            FROM usuarios u
            WHERE u.tipo_usuario = 'fornecedor' AND u.loja_id = ?
            ORDER BY u.nome ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$loja_id, $loja_id]);
    $fornecedores = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar contatos do chat.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - Lyumios Supply</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/chat.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/responsive.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">Lyumios<span>.</span></div>
            <nav class="menu">
                <a href="index.php"><i class="icon">📊</i> Dashboard</a>
                <a href="fornecedores.php"><i class="icon">🏢</i> Fornecedores</a>
                <a href="chat.php" class="active"><i class="icon">💬</i> Mensagens</a>
                <a href="perfil.php"><i class="icon">⚙️</i> Perfil</a>
            </nav>
            <div class="sidebar-footer">
                <a href="../api/logout.php" class="btn-logout"><i class="icon">🚪</i> Sair</a>
            </div>
        </aside>

        <div class="main-wrapper">
            <header class="topbar">
                <div class="topbar-left">
                    <div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
                    <h2>Central de Comunicação</h2>
                </div>
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nome_loja); ?>&background=0D8ABC&color=fff" alt="Avatar">
                    <span><?php echo htmlspecialchars($nome_loja); ?></span>
                </div>
            </header>

            <main class="content">
                <div class="chat-container glass-panel">
                    
                    <!-- BARRA LATERAL: CONTATOS -->
                    <div class="chat-sidebar">
                        <div class="chat-search">
                            <input type="text" id="searchContacts" placeholder="Buscar fornecedor...">
                        </div>
                        <div class="contacts-list">
                            <?php foreach ($fornecedores as $forn): 
                                $avatar = $forn['foto_perfil'] ? '../'.$forn['foto_perfil'] : 'https://ui-avatars.com/api/?name='.urlencode($forn['nome']).'&background=1e293b&color=fff';
                            ?>
                                <div class="contact-item" data-id="<?php echo $forn['id']; ?>" data-nome="<?php echo htmlspecialchars($forn['nome']); ?>" data-avatar="<?php echo $avatar; ?>">
                                    <img src="<?php echo $avatar; ?>" alt="Foto" class="contact-avatar">
                                    <div class="contact-info">
                                        <span class="contact-name"><?php echo htmlspecialchars($forn['nome']); ?></span>
                                        <span class="contact-status">Clique para conversar</span>
                                    </div>
                                    <!-- BADGE DE MENSAGENS NÃO LIDAS -->
                                    <?php if($forn['nao_lidas'] > 0): ?>
                                        <div class="unread-badge" id="badge-<?php echo $forn['id']; ?>"><?php echo $forn['nao_lidas']; ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ÁREA PRINCIPAL: CONVERSA -->
                    <div class="chat-main">
                        <div id="emptyChat" class="empty-chat">
                            <i>💬</i>
                            <h3>Suas Mensagens</h3>
                            <p>Selecione um fornecedor ao lado para iniciar a conversa.</p>
                        </div>

                        <div id="activeChat" style="display: none; height: 100%; flex-direction: column; position: relative;">
                            <div class="chat-header">
                                <img src="" alt="Foto" id="chatHeaderAvatar" class="contact-avatar">
                                <div>
                                    <strong style="color: white; display: block;" id="chatHeaderName">Nome do Fornecedor</strong>
                                    <span style="font-size: 0.8rem; color: var(--success);">Fornecedor Ativo</span>
                                </div>
                            </div>
                            
                            <div class="chat-messages" id="chatMessages">
                                <!-- Mensagens via JS -->
                            </div>

                            <!-- PREVIEW DE RESPOSTA -->
                            <div id="replyPreview" class="reply-preview" style="display: none;">
                                <div class="reply-content">
                                    <strong>Respondendo a:</strong>
                                    <span id="replyTextPreview">...</span>
                                </div>
                                <button id="closeReply" class="btn-close-reply">&times;</button>
                            </div>

                            <div class="chat-input-area">
                                <input type="hidden" id="destinatarioId">
                                <input type="hidden" id="respostaId"> <!-- Guarda o ID da msg respondida -->
                                <textarea id="messageInput" placeholder="Escreva sua mensagem... (Botão Direito na mensagem para opções)"></textarea>
                                <button id="btnSendMessage" class="btn-send">➤</button>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- MENU DE CONTEXTO (Botão Direito) -->
    <div id="chatContextMenu" class="context-menu" style="display: none;">
        <button id="menuReply" class="menu-item">↩️ Responder</button>
        <button id="menuEdit" class="menu-item mine-only">✏️ Editar</button>
        <button id="menuDelete" class="menu-item mine-only text-danger">🗑️ Deletar</button>
    </div>

    <script>const MEU_ID = <?php echo $loja_id; ?>;</script>
    <script src="js/dashboard.js"></script>
    <script src="js/chat.js"></script>
</body>
</html>