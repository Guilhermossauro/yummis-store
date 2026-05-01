<?php
session_start();
require_once '../config/db.php';
$systemConfig = require_once '../config/system.php';
require_once '../api/helpers.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'fornecedor') {
    header('Location: ../login/index.php');
    exit();
}

$fornecedor_id = $_SESSION['usuario_id'];
$nome_fornecedor = $_SESSION['usuario_nome'];

// 1. KPIs do Fornecedor
try {
    // Total de propostas enviadas
    $stmtTotalPropostas = $pdo->prepare("SELECT COUNT(*) FROM propostas WHERE fornecedor_id = ?");
    $stmtTotalPropostas->execute([$fornecedor_id]);
    $kpi_total_propostas = $stmtTotalPropostas->fetchColumn();

    // Taxa de aprovação (ganhas / total)
    $stmtGanhas = $pdo->prepare("SELECT COUNT(*) FROM propostas WHERE fornecedor_id = ? AND status = 'ganha'");
    $stmtGanhas->execute([$fornecedor_id]);
    $kpi_ganhas = $stmtGanhas->fetchColumn();
    $kpi_taxa_aprovacao = $kpi_total_propostas > 0 ? round(($kpi_ganhas / $kpi_total_propostas) * 100, 1) : 0;

    // Valor total negociado (soma dos valores das propostas ganhas)
    $stmtValor = $pdo->prepare("SELECT SUM(valor) FROM propostas WHERE fornecedor_id = ? AND status = 'ganha'");
    $stmtValor->execute([$fornecedor_id]);
    $kpi_valor_negociado = $stmtValor->fetchColumn() ?? 0;

} catch (PDOException $e) {
    die("Erro ao carregar KPIs.");
}

// 2. Feed de Cotações Abertas (pedidos abertos de todas as lojas)
try {
    $stmtPedidosAbertos = $pdo->query("SELECT p.*, u.nome as loja_nome FROM pedidos p JOIN usuarios u ON p.loja_id = u.id WHERE p.status = 'aberto' ORDER BY p.data_criacao DESC LIMIT 20");
    $pedidos_abertos = $stmtPedidosAbertos->fetchAll();
} catch (PDOException $e) {
    $pedidos_abertos = [];
}

// 3. Minhas Propostas Recentes
try {
    $stmtMinhasPropostas = $pdo->prepare("
        SELECT pr.*, p.produto_nome, p.descricao, u.nome as loja_nome
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        JOIN usuarios u ON p.loja_id = u.id
        WHERE pr.fornecedor_id = ?
        ORDER BY pr.data_envio DESC LIMIT 10
    ");
    $stmtMinhasPropostas->execute([$fornecedor_id]);
    $minhas_propostas = $stmtMinhasPropostas->fetchAll();
} catch (PDOException $e) {
    $minhas_propostas = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lyumios Supply - Portal do Fornecedor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="../dashboard-loja/styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../dashboard-loja/styles/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../dashboard-loja/styles/modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../dashboard-loja/styles/responsive.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-layout">

        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">Lyumios<span>.</span></div>

            <nav class="nav-menu">
                <a href="#dashboard" class="nav-item active" data-section="dashboard">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="#cotacoes" class="nav-item" data-section="cotacoes">
                    <span class="icon">📋</span> Cotações Abertas
                </a>
                <a href="#minhas-propostas" class="nav-item" data-section="minhas-propostas">
                    <span class="icon">📤</span> Minhas Propostas
                </a>
                <a href="#perfil" class="nav-item" data-section="perfil">
                    <span class="icon">👤</span> Meu Perfil
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">F</div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($nome_fornecedor); ?></div>
                        <div class="user-role">Fornecedor</div>
                    </div>
                </div>
                <a href="../api/logout.php" class="logout-btn">
                    <span class="icon">🚪</span> Sair
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">

            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <button class="menu-toggle" id="menuToggle">
                        <span class="hamburger"></span>
                    </button>
                    <h1 class="page-title">Portal do Parceiro</h1>
                </div>
                <div class="header-right">
                    <div class="notifications">
                        <button class="notification-btn">
                            <span class="icon">🔔</span>
                            <span class="badge">0</span>
                        </button>
                    </div>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">

                <!-- KPIs Cards -->
                <div class="kpi-grid">
                    <div class="kpi-card">
                        <div class="kpi-icon">📤</div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $kpi_total_propostas; ?></div>
                            <div class="kpi-label">Propostas Enviadas</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon">✅</div>
                        <div class="kpi-content">
                            <div class="kpi-value"><?php echo $kpi_taxa_aprovacao; ?>%</div>
                            <div class="kpi-label">Taxa de Aprovação</div>
                        </div>
                    </div>
                    <div class="kpi-card">
                        <div class="kpi-icon">💰</div>
                        <div class="kpi-content">
                            <div class="kpi-value">R$ <?php echo number_format($kpi_valor_negociado, 2, ',', '.'); ?></div>
                            <div class="kpi-label">Valor Negociado</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="recent-activity">
                    <h3>Atividade Recente</h3>
                    <div class="activity-list">
                        <?php if (empty($minhas_propostas)): ?>
                            <div class="empty-state">
                                <div class="empty-icon">📝</div>
                                <div class="empty-text">Nenhuma proposta enviada ainda</div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($minhas_propostas as $proposta): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">📤</div>
                                    <div class="activity-content">
                                        <div class="activity-title">Proposta enviada para "<?php echo htmlspecialchars($proposta['produto_nome']); ?>"</div>
                                        <div class="activity-meta">Loja: <?php echo htmlspecialchars($proposta['loja_nome']); ?> • Status: <?php echo htmlspecialchars($proposta['status']); ?></div>
                                    </div>
                                    <div class="activity-time"><?php echo date('d/m H:i', strtotime($proposta['data_envio'])); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </section>

            <!-- Cotações Abertas Section -->
            <section id="cotacoes" class="content-section">

                <div class="section-header">
                    <h2>Cotações Abertas</h2>
                    <div class="section-actions">
                        <button class="btn-primary" onclick="refreshCotacoes()">Atualizar</button>
                    </div>
                </div>

                <div class="cotacoes-grid">
                    <?php if (empty($pedidos_abertos)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📋</div>
                            <div class="empty-text">Nenhuma cotação aberta no momento</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pedidos_abertos as $pedido): ?>
                            <div class="cotacao-card" data-pedido-id="<?php echo $pedido['id']; ?>">
                                <div class="cotacao-header">
                                    <h3><?php echo htmlspecialchars($pedido['produto_nome']); ?></h3>
                                    <div class="cotacao-meta">
                                        <span class="loja"><?php echo htmlspecialchars($pedido['loja_nome']); ?></span>
                                        <span class="data"><?php echo date('d/m/Y', strtotime($pedido['data_criacao'])); ?></span>
                                    </div>
                                </div>
                                <div class="cotacao-content">
                                    <p><?php echo htmlspecialchars(substr($pedido['descricao'], 0, 150)); ?>...</p>
                                    <?php if ($pedido['imagem_url']): ?>
                                        <div class="cotacao-images">
                                            <?php
                                            $imagens = json_decode($pedido['imagem_url'], true);
                                            if (is_array($imagens) && count($imagens) > 0):
                                            ?>
                                                <img src="../<?php echo htmlspecialchars($imagens[0]); ?>" alt="Imagem do produto" class="cotacao-thumb">
                                                <?php if (count($imagens) > 1): ?>
                                                    <span class="image-count">+<?php echo count($imagens) - 1; ?></span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="cotacao-actions">
                                    <button class="btn-secondary" onclick="verDetalhes(<?php echo $pedido['id']; ?>)">Ver Detalhes</button>
                                    <button class="btn-primary" onclick="enviarProposta(<?php echo $pedido['id']; ?>)">Enviar Proposta</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </section>

            <!-- Minhas Propostas Section -->
            <section id="minhas-propostas" class="content-section">

                <div class="section-header">
                    <h2>Minhas Propostas</h2>
                </div>

                <div class="propostas-list">
                    <?php if (empty($minhas_propostas)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📤</div>
                            <div class="empty-text">Você ainda não enviou nenhuma proposta</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($minhas_propostas as $proposta): ?>
                            <div class="proposta-card">
                                <div class="proposta-header">
                                    <h3><?php echo htmlspecialchars($proposta['produto_nome']); ?></h3>
                                    <div class="proposta-status status-<?php echo $proposta['status']; ?>">
                                        <?php echo htmlspecialchars(ucfirst($proposta['status'])); ?>
                                    </div>
                                </div>
                                <div class="proposta-content">
                                    <div class="proposta-meta">
                                        <span>Loja: <?php echo htmlspecialchars($proposta['loja_nome']); ?></span>
                                        <span>Valor: R$ <?php echo number_format($proposta['valor'], 2, ',', '.'); ?></span>
                                        <span>Prazo: <?php echo $proposta['prazo']; ?> dias</span>
                                    </div>
                                    <p><?php echo htmlspecialchars($proposta['observacoes']); ?></p>
                                </div>
                                <div class="proposta-actions">
                                    <button class="btn-secondary" onclick="editarProposta(<?php echo $proposta['id']; ?>)">Editar</button>
                                    <button class="btn-danger" onclick="deletarProposta(<?php echo $proposta['id']; ?>)">Excluir</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </section>

            <!-- Perfil Section -->
            <section id="perfil" class="content-section">
                <h2>Meu Perfil</h2>
                <p>Funcionalidade em desenvolvimento...</p>
            </section>

        </main>
    </div>

    <!-- Modal para Enviar Proposta -->
    <div id="propostaModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enviar Proposta</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form id="propostaForm" enctype="multipart/form-data">
                <input type="hidden" name="pedido_id" id="pedido_id">
                <div class="form-group">
                    <label for="valor">Valor (R$)</label>
                    <input type="number" step="0.01" name="valor" id="valor" required>
                </div>
                <div class="form-group">
                    <label for="prazo">Prazo de Entrega (dias)</label>
                    <input type="number" name="prazo" id="prazo" required>
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea name="observacoes" id="observacoes" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="anexos">Anexos (PDF, imagens)</label>
                    <input type="file" name="anexos[]" id="anexos" multiple accept=".pdf,.jpg,.jpeg,.png">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Enviar Proposta</button>
                </div>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script src="../dashboard-loja/js/modal.js"></script>
</body>
</html>