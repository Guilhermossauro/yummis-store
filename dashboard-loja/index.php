<?php
session_start();
require_once '../config/db.php';
$systemConfig = require_once '../config/system.php';
require_once '../api/helpers.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') {
    header('Location: ../login/index.php');
    exit();
}

$loja_id = $_SESSION['usuario_id'];
$nome_loja = $_SESSION['usuario_nome'];

// 1. Cálculos de Armazenamento
$usedBytes = 0;

try {
    // A. Soma o peso físico dos arquivos enviados pela Loja (Pedidos)
    $stmtLoja = $pdo->prepare("SELECT imagem_url FROM pedidos WHERE loja_id = ? AND imagem_url IS NOT NULL");
    $stmtLoja->execute([$loja_id]);
    foreach ($stmtLoja->fetchAll(PDO::FETCH_COLUMN) as $json) {
        $arquivos = json_decode($json, true);
        if (is_array($arquivos)) {
            foreach ($arquivos as $arq) {
                if (file_exists('../' . $arq)) {
                    $usedBytes += filesize('../' . $arq);
                }
            }
        }
    }

    // B. Soma os arquivos enviados pelos Fornecedores (Propostas)
    // Utilizamos um bloco Try/Catch interno caso a tabela/coluna de arquivos do fornecedor ainda não exista
    try {
        // Assume que a tabela propostas terá uma coluna (ex: arquivo_url) salvando os JSONs dos anexos
        $stmtForn = $pdo->prepare("SELECT p.arquivo_url FROM propostas p JOIN pedidos ped ON p.pedido_id = ped.id WHERE ped.loja_id = ? AND p.arquivo_url IS NOT NULL");
        $stmtForn->execute([$loja_id]);
        foreach ($stmtForn->fetchAll(PDO::FETCH_COLUMN) as $json) {
            $arquivos = json_decode($json, true);
            if (is_array($arquivos)) {
                foreach ($arquivos as $arq) {
                    if (file_exists('../' . $arq)) {
                        $usedBytes += filesize('../' . $arq);
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Ignora silenciosamente se o sistema de fornecedores ainda não estiver estruturado no banco
    }

} catch (PDOException $e) {
    die("Erro ao calcular armazenamento.");
}

// Converte os Bytes somados para MB e GB para exibição
$usedMB = round($usedBytes / (1024 * 1024), 2);
$maxMB = $systemConfig['storage_limit_mb'];
$storagePercentage = ($maxMB > 0) ? min(100, round(($usedMB / $maxMB) * 100, 1)) : 0;
$usedGB = round($usedMB / 1024, 2);
$maxGB = round($maxMB / 1024, 2);
// 2. Buscas no Banco
try {
    $stmt = $pdo->prepare("SELECT * FROM pedidos WHERE loja_id = ? ORDER BY data_criacao DESC LIMIT 10");
    $stmt->execute([$loja_id]);
    $pedidos = $stmt->fetchAll();

    $stmtTotal = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE loja_id = $loja_id");
    $kpi_total = $stmtTotal->fetchColumn();

    $stmtAbertos = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE loja_id = $loja_id AND status = 'aberto'");
    $kpi_abertos = $stmtAbertos->fetchColumn();

    $stmtPropostas = $pdo->prepare("SELECT COUNT(p.id) FROM propostas p JOIN pedidos ped ON p.pedido_id = ped.id WHERE ped.loja_id = ?");
    $stmtPropostas->execute([$loja_id]);
    $kpi_propostas = $stmtPropostas->fetchColumn();

    // Buscar propostas recebidas recentes
    $stmtPropostasRecebidas = $pdo->prepare("
        SELECT pr.*, p.produto_nome, f.nome as fornecedor_nome, f.email as fornecedor_email
        FROM propostas pr
        JOIN pedidos p ON pr.pedido_id = p.id
        JOIN usuarios f ON pr.fornecedor_id = f.id
        WHERE p.loja_id = ?
        ORDER BY pr.data_envio DESC LIMIT 10
    ");
    $stmtPropostasRecebidas->execute([$loja_id]);
    $propostas_recebidas = $stmtPropostasRecebidas->fetchAll();

} catch (PDOException $e) { die("Erro ao carregar dados."); }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lyumios Supply - Dashboard</title>
    <!-- Fonte e Chart.js -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <link rel="stylesheet" href="styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/responsive.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-layout">
        
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">Lyumios<span>.</span></div>
            <nav class="menu">
                <a href="index.php" class="active"><i class="icon">📊</i> Dashboard</a>
                <a href="fornecedores.php"><i class="icon">🏢</i> Fornecedores</a>
                <a href="#propostas" onclick="showSection('propostas')"><i class="icon">📋</i> Propostas</a>
                <a href="chat.php"><i class="icon">💬</i> Mensagens</a>
                <a href="perfil.php"><i class="icon">⚙️</i> Perfil</a>
            </nav>
            
            <!-- Box de Storage no Menu -->
            <div class="storage-box">
                <div class="storage-info">
                    <span>Espaço em Nuvem</span>
                    <span><?php echo $storagePercentage; ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?php echo $storagePercentage; ?>%;"></div>
                </div>
                <small><?php echo $usedGB; ?> GB de <?php echo $maxGB; ?> GB usados</small>
            </div>

            <div class="sidebar-footer">
                <a href="../api/logout.php" class="btn-logout"><i class="icon">🚪</i> Sair</a>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Topbar -->
            <header class="topbar">
                <div class="topbar-left">
                    <div class="hamburger" id="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <h2>Visão Analítica</h2>
                </div>
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nome_loja); ?>&background=0D8ABC&color=fff" alt="Avatar">
                    <span><?php echo htmlspecialchars($nome_loja); ?></span>
                </div>
            </header>

            <main class="content">
                <div class="header-actions">
                    <h3>Visão Geral</h3>
                    <button id="btnNovoPedido" class="btn-primary">+ Criar Cotação</button>
                </div>

                <!-- DASHBOARD GRID -->
                <div class="dashboard-grid">
                    
                    <!-- Cards Rápidos (Topo) -->
                    <div class="kpi-group">
                        <div class="kpi-card glass-panel">
                            <div class="kpi-icon blue">📦</div>
                            <div>
                                <h4>Total de Pedidos</h4>
                                <div class="number"><?php echo $kpi_total; ?></div>
                            </div>
                        </div>
                        <div class="kpi-card glass-panel">
                            <div class="kpi-icon orange">⏳</div>
                            <div>
                                <h4>Em Aberto</h4>
                                <div class="number text-orange"><?php echo $kpi_abertos; ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Analytics (Esquerda) -->
                    <div class="chart-container glass-panel">
                        <h4>Fluxo de Pedidos (Mensal)</h4>
                        <div class="chart-wrapper">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>

                    <!-- Detalhes de Armazenamento (Direita) -->
                    <div class="storage-circular glass-panel">
                        <h4>Storage Details</h4>
                        <div class="circle-wrap">
                            <div class="circle">
                                <div class="mask full" style="transform: rotate(<?php echo ($storagePercentage * 1.8); ?>deg);">
                                    <div class="fill" style="transform: rotate(<?php echo ($storagePercentage * 1.8); ?>deg);"></div>
                                </div>
                                <div class="mask half">
                                    <div class="fill" style="transform: rotate(<?php echo ($storagePercentage * 1.8); ?>deg);"></div>
                                </div>
                                <div class="inside-circle">
                                    <div class="perc"><?php echo $storagePercentage; ?>%</div>
                                    <div class="gb"><?php echo $usedGB; ?> GB</div>
                                </div>
                            </div>
                        </div>
                        <p class="storage-desc">Limite máximo: <?php echo $maxGB; ?> GB</p>
                    </div>

                    <!-- Tabela de Arquivos Recentes (Embaixo) -->
                    <div class="table-container glass-panel">
                        <h4>Arquivos Recentes</h4>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th>Status</th>
                                        <th>Data</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pedidos as $pedido): 
                                        $jsonPedido = htmlspecialchars(json_encode($pedido), ENT_QUOTES, 'UTF-8');
                                        $imgArray = json_decode($pedido['imagem_url'], true);
                                        $thumb = (!empty($imgArray)) ? '../'.$imgArray[0] : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="product-cell">
                                                <?php if($thumb): ?>
                                                    <img src="<?php echo $thumb; ?>" alt="Thumb" class="product-thumb">
                                                <?php else: ?>
                                                    <div class="product-placeholder"></div>
                                                <?php endif; ?>
                                                <strong class="product-name"><?php echo htmlspecialchars($pedido['produto_nome']); ?></strong>
                                            </div>
                                        </td>
                                        <td><span class="badge <?php echo $pedido['status']; ?>"><?php echo ucfirst($pedido['status']); ?></span></td>
                                        <td class="text-muted"><?php echo date('d/m/Y', strtotime($pedido['data_criacao'])); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-icon view" onclick="abrirModalVer(<?php echo $jsonPedido; ?>)">👁️</button>
                                                <button class="btn-icon edit" onclick="abrirModalEditar(<?php echo $jsonPedido; ?>)">✏️</button>
                                                <button class="btn-icon delete" onclick="if(confirm('Deletar permanentemente esta cotação?')) window.location.href='../api/deletar_pedido.php?id=<?php echo $pedido['id']; ?>'">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Propostas Recebidas -->
                    <div class="propostas-section glass-panel">
                        <div class="section-header">
                            <h4>Propostas Recebidas</h4>
                            <a href="#propostas" onclick="showSection('propostas')" class="btn-link">Ver Todas</a>
                        </div>
                        <div class="propostas-list">
                            <?php if (empty($propostas_recebidas)): ?>
                                <div class="empty-state">
                                    <div class="empty-icon">📋</div>
                                    <div class="empty-text">Nenhuma proposta recebida ainda</div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($propostas_recebidas as $proposta): ?>
                                    <div class="proposta-item">
                                        <div class="proposta-info">
                                            <div class="proposta-title"><?php echo htmlspecialchars($proposta['produto_nome']); ?></div>
                                            <div class="proposta-meta">
                                                <span>Fornecedor: <?php echo htmlspecialchars($proposta['fornecedor_nome']); ?></span>
                                                <span>Valor: R$ <?php echo number_format($proposta['valor'], 2, ',', '.'); ?></span>
                                                <span>Prazo: <?php echo $proposta['prazo']; ?> dias</span>
                                            </div>
                                        </div>
                                        <div class="proposta-actions">
                                            <button class="btn-sm primary" onclick="compararPropostas(<?php echo $proposta['pedido_id']; ?>)">Comparar</button>
                                            <button class="btn-sm success" onclick="aprovarProposta(<?php echo $proposta['id']; ?>)">Aprovar</button>
                                            <button class="btn-sm danger" onclick="rejeitarProposta(<?php echo $proposta['id']; ?>)">Rejeitar</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </main>

            <!-- Seção de Propostas (Oculto inicialmente) -->
            <section id="propostas" class="content-section" style="display: none;">
                <div class="section-header">
                    <h2>Propostas Recebidas</h2>
                    <button onclick="showSection('dashboard')" class="btn-secondary">Voltar ao Dashboard</button>
                </div>

                <div class="propostas-grid">
                    <?php
                    // Buscar todas as propostas agrupadas por pedido
                    $stmtPedidosComPropostas = $pdo->prepare("
                        SELECT DISTINCT p.id, p.produto_nome, p.descricao, p.status as pedido_status,
                               COUNT(pr.id) as num_propostas
                        FROM pedidos p
                        LEFT JOIN propostas pr ON p.id = pr.pedido_id
                        WHERE p.loja_id = ?
                        GROUP BY p.id
                        HAVING num_propostas > 0
                        ORDER BY p.data_criacao DESC
                    ");
                    $stmtPedidosComPropostas->execute([$loja_id]);
                    $pedidos_com_propostas = $stmtPedidosComPropostas->fetchAll();
                    ?>

                    <?php foreach ($pedidos_com_propostas as $pedido): ?>
                        <div class="pedido-propostas-card">
                            <div class="pedido-header">
                                <h3><?php echo htmlspecialchars($pedido['produto_nome']); ?></h3>
                                <div class="pedido-meta">
                                    <span><?php echo $pedido['num_propostas']; ?> propostas recebidas</span>
                                    <span class="status <?php echo $pedido['pedido_status']; ?>"><?php echo ucfirst($pedido['pedido_status']); ?></span>
                                </div>
                            </div>

                            <div class="propostas-comparison">
                                <?php
                                $stmtPropostasPedido = $pdo->prepare("
                                    SELECT pr.*, f.nome as fornecedor_nome, f.email as fornecedor_email
                                    FROM propostas pr
                                    JOIN usuarios f ON pr.fornecedor_id = f.id
                                    WHERE pr.pedido_id = ?
                                    ORDER BY pr.valor ASC
                                ");
                                $stmtPropostasPedido->execute([$pedido['id']]);
                                $propostas_pedido = $stmtPropostasPedido->fetchAll();
                                ?>

                                <?php foreach ($propostas_pedido as $index => $prop): ?>
                                    <div class="proposta-comparison-item <?php echo $index === 0 ? 'best-price' : ''; ?>">
                                        <div class="comparison-header">
                                            <div class="fornecedor-info">
                                                <div class="fornecedor-avatar"><?php echo substr($prop['fornecedor_nome'], 0, 1); ?></div>
                                                <div>
                                                    <div class="fornecedor-name"><?php echo htmlspecialchars($prop['fornecedor_nome']); ?></div>
                                                    <div class="fornecedor-email"><?php echo htmlspecialchars($prop['fornecedor_email']); ?></div>
                                                </div>
                                            </div>
                                            <?php if ($index === 0): ?>
                                                <div class="best-badge">Melhor Preço</div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="comparison-details">
                                            <div class="detail-item">
                                                <span class="label">Valor:</span>
                                                <span class="value">R$ <?php echo number_format($prop['valor'], 2, ',', '.'); ?></span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Prazo:</span>
                                                <span class="value"><?php echo $prop['prazo']; ?> dias</span>
                                            </div>
                                            <div class="detail-item">
                                                <span class="label">Status:</span>
                                                <span class="status-badge <?php echo $prop['status']; ?>"><?php echo ucfirst($prop['status']); ?></span>
                                            </div>
                                        </div>

                                        <div class="comparison-observations">
                                            <p><?php echo htmlspecialchars($prop['observacoes']); ?></p>
                                        </div>

                                        <?php if ($prop['arquivo_url']): ?>
                                            <div class="comparison-attachments">
                                                <span class="attachments-label">Anexos:</span>
                                                <?php
                                                $anexos = json_decode($prop['arquivo_url'], true);
                                                if (is_array($anexos)):
                                                ?>
                                                    <?php foreach ($anexos as $anexo): ?>
                                                        <a href="../<?php echo htmlspecialchars($anexo); ?>" target="_blank" class="attachment-link">📎 Anexo</a>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="comparison-actions">
                                            <button class="btn-sm success" onclick="aprovarProposta(<?php echo $prop['id']; ?>, <?php echo $pedido['id']; ?>)">Aprovar</button>
                                            <button class="btn-sm danger" onclick="rejeitarProposta(<?php echo $prop['id']; ?>)">Rejeitar</button>
                                            <button class="btn-sm secondary" onclick="contatarFornecedor(<?php echo $prop['fornecedor_id']; ?>)">Contatar</button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <?php if (count($propostas_pedido) > 1): ?>
                                    <div class="comparison-summary">
                                        <div class="savings-calc">
                                            <?php
                                            $menor_valor = $propostas_pedido[0]['valor'];
                                            $maior_valor = end($propostas_pedido)['valor'];
                                            $economia = $maior_valor - $menor_valor;
                                            $percentual = $maior_valor > 0 ? round(($economia / $maior_valor) * 100, 1) : 0;
                                            ?>
                                            <div class="savings-amount">Economia: R$ <?php echo number_format($economia, 2, ',', '.'); ?> (<?php echo $percentual; ?>%)</div>
                                            <div class="savings-text">Comparado com a proposta mais cara</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

        </div>
    </div>

    <!-- ================= MODAIS ================= -->

    <!-- Modal Nova Cotação -->
    <div id="modalPedido" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3>Criar Nova Cotação</h3>
                <span class="close-modal">&times;</span>
            </div>
            <form action="../api/criar_pedido.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="produto">Produto</label>
                    <input type="text" id="produto" name="produto" required>
                </div>
                <div class="form-group">
                    <label for="descricao">Detalhes Adicionais</label>
                    <textarea id="descricao" name="descricao" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Fotos de Referência (Arraste para reordenar)</label>
                    <div id="dropzone" class="dropzone-container">
                        <span class="dropzone-icon">+</span>
                        <p class="dropzone-text">Arraste as imagens aqui ou clique para selecionar</p>
                        <input type="file" id="imagem_produto" name="imagens_produto[]" accept="image/*,video/*" multiple style="display: none;">
                    </div>
                    <div id="previewContainer" class="previews-grid"></div>
                </div>
                <button type="submit" class="btn-primary w-full">Publicar no Feed</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3>Editar Cotação</h3>
                <span class="close-modal" onclick="fecharModal('modalEditar')">&times;</span>
            </div>
            <form action="../api/editar_pedido.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_produto">Produto</label>
                    <input type="text" id="edit_produto" name="produto" required>
                </div>
                <div class="form-group">
                    <label for="edit_descricao">Detalhes Adicionais</label>
                    <textarea id="edit_descricao" name="descricao" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Atualizar Fotos (Arraste para reordenar)</label>
                    <div id="dropzoneEdit" class="dropzone-container">
                        <span class="dropzone-icon">+</span>
                        <p class="dropzone-text">Arraste novas imagens ou altere as existentes</p>
                        <input type="file" id="imagem_produto_edit" name="imagens_produto[]" accept="image/*,video/*" multiple style="display: none;">
                    </div>
                    <div id="previewContainerEdit" class="previews-grid"></div>
                </div>
                <button type="submit" class="btn-primary w-full">Salvar Alterações</button>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalhes -->
    <div id="modalVer" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3>Detalhes da Cotação</h3>
                <span class="close-modal" onclick="fecharModal('modalVer')">&times;</span>
            </div>
            <div class="view-details-body">
                <div id="ver_imagem_container"></div>
                <h4 id="ver_produto" class="view-title"></h4>
                <p class="view-status-row">
                    <strong>Status Atual:</strong> 
                    <span id="ver_status" class="badge"></span>
                </p>
                <div class="specifications-box">
                    <strong>Especificações descritas:</strong>
                    <p id="ver_descricao" class="view-description-text"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/dashboard.js"></script>
    <script src="js/modal.js"></script>
</body>
</html>