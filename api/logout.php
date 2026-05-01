<?php
session_start();
// Destrói todas as sessões ativas
session_unset();
session_destroy();
// Manda de volta para o login
header("Location: ../login/index.php");
exit();
?>