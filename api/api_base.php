<?php
// api/api_base.php
// Centraliza inicialização de sessão, conexão PDO e auditoria de eventos.

session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/logger.php';

function exigirAutenticacao($tipo_usuario = null) {
    if (!isset($_SESSION['usuario_id'])) {
        registrarLog('Tentativa de acesso não autorizado na API', null);
        http_response_code(401);
        die('Acesso negado.');
    }

    if ($tipo_usuario && $_SESSION['usuario_tipo'] !== $tipo_usuario) {
        registrarLog("Acesso negado: tipo de usuário inválido ({$_SESSION['usuario_tipo']})", $_SESSION['usuario_id']);
        http_response_code(403);
        die('Acesso negado.');
    }
}

function registrarAcaoBackend($acao) {
    $usuario_id = $_SESSION['usuario_id'] ?? null;
    registrarLog($acao, $usuario_id);
}
