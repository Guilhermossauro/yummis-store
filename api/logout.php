<?php
require_once 'api_base.php';
registrarAcaoBackend('Logout de usuário ID ' . ($_SESSION['usuario_id'] ?? 'desconhecido'));
// Destrói todas as sessões ativas
session_unset();
session_destroy();
// Manda de volta para o login
header("Location: ../login/index.php");
exit();
?>