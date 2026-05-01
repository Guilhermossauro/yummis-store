<?php
// config/system.php
return [
    // Limite máximo de armazenamento do sistema (Em Megabytes)
    'storage_limit_mb' => 5000, // Exemplo: 5000MB = 5GB
    
    // Limite por arquivo enviado (Em Megabytes)
    'max_file_size_mb' => 30,
    
    // Diretório de uploads
    'upload_path' => __DIR__ . '/../uploads/',
];