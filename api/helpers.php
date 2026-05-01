<?php
// api/helpers.php

// Função para calcular o tamanho total da pasta de uploads
function getDirectorySize($path) {
    $bytestotal = 0;
    $path = realpath($path);
    if($path!==false && $path!='' && file_exists($path)){
        foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)) as $object){
            $bytestotal += $object->getSize();
        }
    }
    return $bytestotal;
}

function processUploadAndConvertToWebp($file, $destinationFolder, $maxSizeMB) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'Upload inválido.'];
    }

    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSizeBytes) {
        return ['error' => "O arquivo {$file['name']} excede o limite de {$maxSizeMB}MB."];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!in_array($mime, $allowedImageTypes, true)) {
        return ['error' => "Apenas imagens sao permitidas: {$file['name']}"];
    }

    if (@getimagesize($file['tmp_name']) === false) {
        return ['error' => 'Arquivo enviado nao e uma imagem valida.'];
    }

    if (!is_dir($destinationFolder) && !mkdir($destinationFolder, 0777, true)) {
        return ['error' => 'Falha ao preparar diretorio de upload.'];
    }

    $safeName = 'img_' . bin2hex(random_bytes(8));
    $finalPath = rtrim($destinationFolder, '/\\') . DIRECTORY_SEPARATOR . $safeName . '.webp';
    $image = null;

    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($file['tmp_name']);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'image/gif':
            $image = @imagecreatefromgif($file['tmp_name']);
            if ($image) {
                imagepalettetotruecolor($image);
            }
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($file['tmp_name']);
            break;
    }

    if (!$image) {
        return ['error' => 'Falha ao processar imagem.'];
    }

    $saved = imagewebp($image, $finalPath, 85);
    imagedestroy($image);

    if (!$saved) {
        return ['error' => 'Falha ao salvar imagem convertida.'];
    }

    return ['success' => true, 'path' => $finalPath];
}

function processProposalAttachment($file, $destinationFolder, $maxSizeMB) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'Upload de anexo invalido.'];
    }

    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    if (($file['size'] ?? 0) > $maxSizeBytes) {
        return ['error' => "O arquivo {$file['name']} excede o limite de {$maxSizeMB}MB."];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if ($mime === 'application/pdf') {
        if (!is_dir($destinationFolder) && !mkdir($destinationFolder, 0777, true)) {
            return ['error' => 'Falha ao preparar diretorio de upload.'];
        }

        $safeName = 'doc_' . bin2hex(random_bytes(8)) . '.pdf';
        $finalPath = rtrim($destinationFolder, '/\\') . DIRECTORY_SEPARATOR . $safeName;
        if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
            return ['error' => 'Falha ao salvar anexo PDF.'];
        }
        return ['success' => true, 'path' => $finalPath];
    }

    return processUploadAndConvertToWebp($file, $destinationFolder, $maxSizeMB);
}

function tableExists(PDO $pdo, string $tableName) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tableName]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}

function tableHasColumn(PDO $pdo, string $tableName, string $columnName) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$tableName` LIKE ?");
        $stmt->execute([$columnName]);
        return (bool) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return false;
    }
}