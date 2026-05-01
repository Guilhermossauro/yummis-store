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
    $stmt = $pdo->prepare("SELECT id, nome, email, foto_perfil, documento, endereco, permissoes, data_criacao FROM usuarios WHERE tipo_usuario = 'fornecedor' AND loja_id = ? ORDER BY nome ASC");
    $stmt->execute([$loja_id]);
    $fornecedores = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao carregar fornecedores.");
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores - Lyumios Supply</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/dashboard.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/modal.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/fornecedores.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="styles/responsive.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="app-layout">
        
        <aside class="sidebar" id="sidebar">
            <div class="logo">Lyumios<span>.</span></div>
            <nav class="menu">
                <a href="index.php"><i class="icon">📊</i> Dashboard</a>
                <a href="fornecedores.php" class="active"><i class="icon">🏢</i> Fornecedores</a>
                <a href="chat.php"><i class="icon">💬</i> Mensagens</a>
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
                    <h2>Gestão de Fornecedores</h2>
                </div>
                <div class="user-info">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nome_loja); ?>&background=0D8ABC&color=fff" alt="Avatar">
                    <span><?php echo htmlspecialchars($nome_loja); ?></span>
                </div>
            </header>

            <main class="content">
                <div class="header-actions">
                    <h3>Sua Rede de Abastecimento</h3>
                    <button id="btnNovoFornecedor" class="btn-primary">+ Novo Fornecedor</button>
                </div>

                <div class="table-container glass-panel">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Empresa / Contato</th>
                                    <th>CNPJ / Tax ID</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($fornecedores) > 0): ?>
                                    <?php foreach ($fornecedores as $forn): 
                                        $jsonForn = htmlspecialchars(json_encode($forn), ENT_QUOTES, 'UTF-8');
                                        $avatar = $forn['foto_perfil'] ? '../'.$forn['foto_perfil'] : 'https://ui-avatars.com/api/?name='.urlencode($forn['nome']).'&background=1e293b&color=fff';
                                    ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 15px;">
                                                <img src="<?php echo $avatar; ?>" alt="Logo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color);">
                                                <div>
                                                    <strong style="display: block; color: white;"><?php echo htmlspecialchars($forn['nome']); ?></strong>
                                                    <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($forn['email']); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($forn['documento'] ?? 'Não informado'); ?></td>
                                        <td>
                                            <div class="table-actions">
                                                <button class="btn-icon edit" onclick="abrirModalEditarFornecedor(<?php echo $jsonForn; ?>)" title="Editar Perfil">✏️</button>
                                                <button class="btn-icon delete" onclick="if(confirm('Tem certeza que deseja remover este fornecedor? Ele perderá o acesso ao sistema.')) window.location.href='../api/deletar_fornecedor.php?id=<?php echo $forn['id']; ?>'" title="Remover">🗑️</button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" style="text-align: center; padding: 30px; color: var(--text-muted);">Nenhum fornecedor cadastrado ainda.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Modal Novo Fornecedor -->
    <div id="modalNovoFornecedor" class="modal">
        <div class="modal-content glass-panel">
            <div class="modal-header">
                <h3>Cadastrar Fornecedor</h3>
                <span class="close-modal">&times;</span>
            </div>
            <!-- Autocomplete off para matar sugestões indesejadas do navegador -->
            <form action="../api/criar_fornecedor.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                <p class="form-help">Apenas Nome, E-mail e Senha são obrigatórios.</p>
                
                <div class="form-group">
                    <label>Logo da Empresa (Opcional)</label>
                    <div id="dropzoneFornNovo" class="dropzone-container">
                        <span class="dropzone-icon">+</span>
                        <p class="dropzone-text">Arraste a logo da empresa aqui ou clique</p>
                        <input type="file" id="foto_perfil_novo" name="foto_perfil" accept="image/*" style="display: none;">
                    </div>
                    <div id="previewFornNovo" class="previews-grid single-profile"></div>
                </div>

                <div class="form-group">
                    <label>Nome da Empresa / Fantasia *</label>
                    <input type="text" name="nome" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>E-mail de Acesso *</label>
                    <input type="email" name="email" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Senha Provisória *</label>
                    <input type="password" name="senha" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label>CNPJ / Tax ID (Opcional)</label>
                    <input type="text" name="documento" autocomplete="off">
                </div>
                <button type="submit" class="btn-primary w-full">Criar Acesso</button>
            </form>
        </div>
    </div>

    <!-- Modal Editar Fornecedor -->
    <div id="modalEditarFornecedor" class="modal">
        <div class="modal-content glass-panel modal-lg">
            <div class="modal-header">
                <h3>Editar Perfil do Fornecedor</h3>
                <span class="close-modal" onclick="fecharModal('modalEditarFornecedor')">&times;</span>
            </div>
            
            <div class="modal-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'tab-basico')">Básico & Acesso</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-fiscal')">Dados & Endereço</button>
                <button class="tab-btn" onclick="openTab(event, 'tab-permissoes')">Permissões</button>
            </div>

            <form action="../api/editar_fornecedor.php" method="POST" enctype="multipart/form-data" autocomplete="off">
                <input type="hidden" id="edit_id" name="id">
                
                <div id="tab-basico" class="tab-content active">
                    
                    <div class="form-group">
                        <label>Alterar Logo da Empresa (Opcional)</label>
                        <div id="dropzoneFornEdit" class="dropzone-container">
                            <span class="dropzone-icon">+</span>
                            <p class="dropzone-text">Arraste a nova logo aqui ou clique</p>
                            <input type="file" id="foto_perfil_edit" name="foto_perfil" accept="image/*" style="display: none;">
                        </div>
                        <div id="previewFornEdit" class="previews-grid single-profile"></div>
                    </div>

                    <div class="form-group"><label>Nome da Empresa</label><input type="text" id="edit_nome" name="nome" required autocomplete="off"></div>
                    <div class="form-group"><label>E-mail (Login)</label><input type="email" id="edit_email" name="email" required autocomplete="off"></div>
                    <div class="form-group">
                        <label>Redefinir Senha</label>
                        <input type="password" name="nova_senha" placeholder="Deixe em branco para manter a atual" autocomplete="new-password">
                    </div>
                </div>

                <div id="tab-fiscal" class="tab-content">
                    <div class="form-group"><label>CNPJ / Tax ID</label><input type="text" id="edit_documento" name="documento" autocomplete="off"></div>
                    <div class="form-group"><label>Endereço Completo</label><textarea id="edit_endereco" name="endereco" rows="3" autocomplete="off"></textarea></div>
                </div>

                <div id="tab-permissoes" class="tab-content">
                    <div class="permission-row">
                        <div>
                            <strong>Pode enviar propostas?</strong>
                            <p>Permite que este fornecedor responda às suas cotações.</p>
                        </div>
                        <label class="switch"><input type="checkbox" id="perm_enviar" name="perm_enviar" value="1"><span class="slider"></span></label>
                    </div>
                    <div class="permission-row">
                        <div>
                            <strong>Ver concorrência?</strong>
                            <p>Ele poderá ver o valor das propostas de outros fornecedores.</p>
                        </div>
                        <label class="switch"><input type="checkbox" id="perm_ver_outros" name="perm_ver_outros" value="1"><span class="slider"></span></label>
                    </div>
                </div>

                <div class="modal-footer" style="margin-top: 20px;">
                    <button type="submit" class="btn-primary w-full">Salvar Configurações</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script src="js/fornecedores.js"></script>
</body>
</html>