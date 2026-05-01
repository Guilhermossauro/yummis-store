<?php
// api/logger.php

function registrarLog($acao, $usuario_id = null) {
    // Define a pasta de logs (um nível acima da pasta api)
    $dir_logs = __DIR__ . '/../logs/';
    
    // Se a pasta não existir, cria com permissão total
    if (!is_dir($dir_logs)) {
        mkdir($dir_logs, 0777, true);
        // Cria um index.php vazio para evitar que curiosos listem a pasta no navegador
        file_put_contents($dir_logs . 'index.php', '<?php // Silence is golden.');
    }

    // Pega a data atual para nomear o arquivo
    $data_atual = date('Y-m-d');
    $nome_arquivo = $dir_logs . "log_{$data_atual}.txt";

    // Pega a hora, IP e define quem está fazendo a ação
    $hora = date('H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP_Desconhecido';
    $user = $usuario_id ? "User_ID:{$usuario_id}" : "Visitante/Sistema";

    // Monta a linha do log
    $linha_log = "[{$data_atual} {$hora}] [IP: {$ip}] [{$user}] => {$acao}" . PHP_EOL;

    // Salva no arquivo (FILE_APPEND garante que ele adicione no final sem apagar o que já tem)
    file_put_contents($nome_arquivo, $linha_log, FILE_APPEND);
}
?>