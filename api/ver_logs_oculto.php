<?php
require_once 'api_base.php';
registrarAcaoBackend('Acesso à página de logs ocultos');

// ==========================================
// 🔒 BARREIRA DE SEGURANÇA MÁXIMA
// ==========================================
// Mude esta senha para algo extremamente seguro!
$SENHA_MESTRE = 'Lyumios@Admin2026'; 

// Lógica de Logout
if (isset($_GET['sair'])) {
    unset($_SESSION['god_mode_autorizado']);
    header('Location: ver_logs_oculto.php');
    exit();
}

// Lógica de Login
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['senha_mestre']) && $_POST['senha_mestre'] === $SENHA_MESTRE) {
        $_SESSION['god_mode_autorizado'] = true;
        header('Location: ver_logs_oculto.php');
        exit();
    } else {
        $erro = 'Acesso Negado. Tentativa registrada.';
        registrarAcaoBackend('Tentativa de invasão no painel de logs ocultos com senha errada');
    }
}

// Se não estiver logado, exibe a tela de bloqueio e PARA a execução do script
if (!isset($_SESSION['god_mode_autorizado']) || $_SESSION['god_mode_autorizado'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <title>Acesso Restrito</title>
        <style>
            body { background-color: #050505; color: #0f0; font-family: 'Courier New', Courier, monospace; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
            .login-box { background: #111; padding: 40px; border: 1px solid #333; border-radius: 8px; text-align: center; box-shadow: 0 0 20px rgba(0,255,0,0.1); }
            h2 { margin-top: 0; color: #fff; }
            input[type="password"] { width: 100%; padding: 10px; margin: 20px 0; background: #000; border: 1px solid #333; color: #0f0; outline: none; font-family: inherit; box-sizing: border-box; }
            input[type="password"]:focus { border-color: #0f0; }
            button { background: #0f0; color: #000; border: none; padding: 10px 20px; font-weight: bold; cursor: pointer; width: 100%; font-family: inherit; }
            button:hover { background: #fff; }
            .erro { color: #ef4444; margin-bottom: 15px; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>SISTEMA DE AUDITORIA</h2>
            <?php if($erro): ?><div class="erro">⚠️ <?php echo $erro; ?></div><?php endif; ?>
            <form method="POST">
                <input type="password" name="senha_mestre" placeholder="Insira a credencial mestre..." required autofocus>
                <button type="submit">DESBLOQUEAR ACESSO</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit(); // Para o script aqui. O invasor não vê as linhas de baixo.
}

// ==========================================
// 📄 CÓDIGO DO VISUALIZADOR DE LOGS (Só roda se passou da barreira)
// ==========================================

$dir_logs = __DIR__ . '/logs/';
$arquivos_log = [];

if (is_dir($dir_logs)) {
    $arquivos = scandir($dir_logs);
    foreach ($arquivos as $arq) {
        if (strpos($arq, '.txt') !== false) {
            $arquivos_log[] = $arq;
        }
    }
    rsort($arquivos_log);
}

$conteudo_log = "";
$arquivo_selecionado = $_GET['arquivo'] ?? ($arquivos_log[0] ?? null);

if ($arquivo_selecionado && in_array($arquivo_selecionado, $arquivos_log)) {
    $caminho_completo = $dir_logs . $arquivo_selecionado;
    if (file_exists($caminho_completo)) {
        $conteudo_log = htmlspecialchars(file_get_contents($caminho_completo));
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Oculto</title>
    <style>
        body { margin: 0; background-color: #050505; color: #0f0; font-family: 'Courier New', Courier, monospace; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar de Arquivos */
        .sidebar { width: 250px; background: #0a0a0a; border-right: 1px solid #333; display: flex; flex-direction: column; }
        .sidebar-title { padding: 20px; font-weight: bold; border-bottom: 1px solid #333; color: #fff; background: #111; display: flex; justify-content: space-between; align-items: center;}
        .btn-sair { background: #ef4444; color: white; text-decoration: none; padding: 5px 10px; font-size: 0.8rem; border-radius: 4px; }
        .log-list { flex: 1; overflow-y: auto; padding: 10px; list-style: none; margin: 0; }
        .log-list li { margin-bottom: 5px; }
        .log-list a { color: #888; text-decoration: none; display: block; padding: 10px; border-radius: 4px; font-size: 0.9rem; }
        .log-list a:hover { background: #222; color: #0f0; }
        .log-list a.active { background: #0f0; color: #000; font-weight: bold; }

        /* Terminal Principal */
        .terminal { flex: 1; display: flex; flex-direction: column; }
        .terminal-header { padding: 15px 20px; background: #111; border-bottom: 1px solid #333; display: flex; justify-content: space-between; align-items: center; color: #fff; }
        .terminal-content { flex: 1; padding: 20px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; font-size: 0.9rem; line-height: 1.5; }
        
        /* Destaque para palavras chave */
        .keyword-ip { color: #0ea5e9; }
        .keyword-user { color: #f59e0b; }
        .keyword-error { color: #ef4444; font-weight: bold; }
        .keyword-success { color: #22c55e; font-weight: bold; }
        .log-empty { color: #555; padding: 10px; }
        .btn-terminal-refresh { background: #333; color: white; border: none; padding: 5px 15px; cursor: pointer; border-radius: 4px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-title">
            <span>DIRETÓRIO</span>
            <a href="?sair=1" class="btn-sair">Sair</a>
        </div>
        <ul class="log-list">
            <?php if (empty($arquivos_log)): ?>
                <li class="log-empty">Nenhum log gerado ainda.</li>
            <?php else: ?>
                <?php foreach ($arquivos_log as $arq): ?>
                    <li>
                        <a href="?arquivo=<?php echo $arq; ?>" class="<?php echo ($arq === $arquivo_selecionado) ? 'active' : ''; ?>">
                            📄 <?php echo str_replace('.txt', '', $arq); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="terminal">
        <div class="terminal-header">
            <span>Visualizando: <strong><?php echo $arquivo_selecionado ?? 'Nenhum'; ?></strong></span>
            <button onclick="window.location.reload()" class="btn-terminal-refresh">Atualizar Terminal</button>
        </div>
        <div class="terminal-content" id="logContent"><?php echo $conteudo_log ?: 'Selecione um arquivo de log para visualizar...'; ?></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contentDiv = document.getElementById('logContent');
            let text = contentDiv.innerHTML;
            
            text = text.replace(/\[IP: (.*?)\]/g, '[IP: <span class="keyword-ip">$1</span>]');
            text = text.replace(/\[User_ID:(.*?)\]/g, '[<span class="keyword-user">User_ID:$1</span>]');
            text = text.replace(/(ERRO|FALHA|ERROR|FAILED|NEGADO|INVASÃO)/gi, '<span class="keyword-error">$1</span>');
            text = text.replace(/(SUCESSO|BEM SUCEDIDO|CRIOU)/gi, '<span class="keyword-success">$1</span>');

            contentDiv.innerHTML = text;
            contentDiv.scrollTop = contentDiv.scrollHeight;
        });
    </script>
</body>
</html>