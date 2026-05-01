<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'loja') {
	header('Location: ../login/index.php');
	exit();
}

$usuarioId = $_SESSION['usuario_id'];
$nomeLoja = $_SESSION['usuario_nome'];
$usuario = null;

try {
	$stmt = $pdo->prepare("SELECT id, nome, email, documento, endereco, data_criacao FROM usuarios WHERE id = ? LIMIT 1");
	$stmt->execute([$usuarioId]);
	$usuario = $stmt->fetch();
} catch (PDOException $e) {
	$usuario = null;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Perfil - Lyumios Supply</title>
	<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="styles/global.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="styles/dashboard.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="styles/modal.css?v=<?php echo time(); ?>">
	<link rel="stylesheet" href="styles/responsive.css?v=<?php echo time(); ?>">
	<style>
		.perfil-card { max-width: 900px; margin: 20px auto 0; }
		.perfil-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
		.perfil-item { background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: 10px; padding: 14px; }
		.perfil-label { display: block; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 6px; }
		.perfil-value { font-weight: 600; word-break: break-word; }
		@media (max-width: 768px) { .perfil-grid { grid-template-columns: 1fr; } }
	</style>
</head>
<body>
	<div class="app-layout">
		<aside class="sidebar" id="sidebar">
			<div class="logo">Lyumios<span>.</span></div>
			<nav class="menu">
				<a href="index.php"><i class="icon">📊</i> Dashboard</a>
				<a href="fornecedores.php"><i class="icon">🏢</i> Fornecedores</a>
				<a href="chat.php"><i class="icon">💬</i> Mensagens</a>
				<a href="perfil.php" class="active"><i class="icon">⚙️</i> Perfil</a>
			</nav>
			<div class="sidebar-footer">
				<a href="../api/logout.php" class="btn-logout"><i class="icon">🚪</i> Sair</a>
			</div>
		</aside>

		<div class="main-wrapper">
			<header class="topbar">
				<div class="topbar-left">
					<div class="hamburger" id="hamburger"><span></span><span></span><span></span></div>
					<h2>Perfil da Loja</h2>
				</div>
				<div class="user-info">
					<img src="https://ui-avatars.com/api/?name=<?php echo urlencode($nomeLoja); ?>&background=0D8ABC&color=fff" alt="Avatar">
					<span><?php echo htmlspecialchars($nomeLoja); ?></span>
				</div>
			</header>

			<main class="content">
				<div class="header-actions">
					<h3>Dados da Conta</h3>
				</div>

				<section class="glass-panel perfil-card">
					<?php if ($usuario): ?>
						<div class="perfil-grid">
							<div class="perfil-item">
								<span class="perfil-label">ID</span>
								<div class="perfil-value">#<?php echo (int) $usuario['id']; ?></div>
							</div>
							<div class="perfil-item">
								<span class="perfil-label">Nome</span>
								<div class="perfil-value"><?php echo htmlspecialchars($usuario['nome']); ?></div>
							</div>
							<div class="perfil-item">
								<span class="perfil-label">E-mail</span>
								<div class="perfil-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
							</div>
							<div class="perfil-item">
								<span class="perfil-label">Documento</span>
								<div class="perfil-value"><?php echo htmlspecialchars($usuario['documento'] ?? 'Nao informado'); ?></div>
							</div>
							<div class="perfil-item">
								<span class="perfil-label">Endereco</span>
								<div class="perfil-value"><?php echo htmlspecialchars($usuario['endereco'] ?? 'Nao informado'); ?></div>
							</div>
							<div class="perfil-item">
								<span class="perfil-label">Criado em</span>
								<div class="perfil-value"><?php echo !empty($usuario['data_criacao']) ? date('d/m/Y H:i', strtotime($usuario['data_criacao'])) : '-'; ?></div>
							</div>
						</div>
					<?php else: ?>
						<p>Nao foi possivel carregar os dados do perfil.</p>
					<?php endif; ?>
				</section>
			</main>
		</div>
	</div>
	<script src="js/dashboard.js"></script>
</body>
</html>
