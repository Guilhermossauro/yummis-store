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

// Função para converter imagem para WebP e respeitar o limite de 30MB
function processUploadAndConvertToWebp($file, $destinationFolder, $maxSizeMB) {
    $maxSizeBytes = $maxSizeMB * 1024 * 1024;
    
    if ($file['size'] > $maxSizeBytes) {
        return ['error' => "O arquivo {$file['name']} excede o limite de {$maxSizeMB}MB."];
    }

    $mime = mime_content_type($file['tmp_name']);
    $allowedImageTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedVideoTypes = ['video/mp4', 'video/webm', 'video/ogg'];

    if (!in_array($mime, $allowedImageTypes) && !in_array($mime, $allowedVideoTypes)) {
        return ['error' => "Tipo de arquivo não permitido: {$file['name']}"];
    }

    $fileNameRaw = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeName = preg_replace('/[^A-Za-z0-9\-]/', '', $fileNameRaw) . '_' . time();

    // Se for VÍDEO, apenas move (PHP puro não converte vídeo sem FFmpeg)
    if (in_array($mime, $allowedVideoTypes)) {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $finalPath = $destinationFolder . $safeName . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $finalPath);
        return ['success' => true, 'path' => $finalPath];
    }

    // Se for IMAGEM, converte para WebP
    $finalPath = $destinationFolder . $safeName . '.webp';
    $image = null;

    switch ($mime) {
        case 'image/jpeg': $image = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png': 
            $image = imagecreatefrompng($file['tmp_name']); 
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
            break;
        case 'image/gif': 
            $image = imagecreatefromgif($file['tmp_name']); 
            imagepalettetotruecolor($image);
            break;
        case 'image/webp': $image = imagecreatefromwebp($file['tmp_name']); break;
    }

    if ($image) {
        imagewebp($image, $finalPath, 85); // 85 é uma ótima qualidade de compressão
        imagedestroy($image);
        return ['success' => true, 'path' => $finalPath];
    }

    return ['error' => "Falha ao processar imagem."];
}