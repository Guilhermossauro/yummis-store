<?php
require_once '../config/db.php';

$mostrarCadastroInicial = false;
try {
    $stmtUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios");
    $mostrarCadastroInicial = ((int) $stmtUsuarios->fetchColumn() === 0);
} catch (PDOException $e) {
    $mostrarCadastroInicial = false;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Lyumios Supply - Acesso Restrito</title>
    <!-- Adicionando a tag de tempo para quebrar o cache automaticamente nos seus testes locais -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <div class="login-container">
        <h1>Lyumios Supply</h1>
        <div class="subtitle">Gestão de Pedidos e Fornecedores</div>

        <div class="error-msg"></div>
        
        <form action="../api/login_process.php" method="POST">
            <?php if ($mostrarCadastroInicial): ?>
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" id="nome" name="nome" placeholder="Seu nome completo" required>
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="email">E-mail de Acesso</label>
                <input type="email" id="email" name="email" placeholder="nome@empresa.com.br" required>
            </div>
            <div class="form-group">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit">Entrar no Sistema</button>
        </form>
    </div>

    <script src="script.js"></script>
</body>
</html>